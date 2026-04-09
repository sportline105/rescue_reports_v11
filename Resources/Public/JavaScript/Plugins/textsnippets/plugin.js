/**
 * CKEditor 4 Plugin: textSnippets
 *
 * Lädt Textbausteine per AJAX und fügt sie als Dropdown-Combo in die Toolbar ein.
 *
 * Timing-Strategie:
 *   CKEditor 4 ruft richcombo.init() LAZILY beim ersten open() auf.
 *   Da der Pre-Fetch schon beim Plugin-Load startet, ist loadedItems in aller
 *   Regel bereits gesetzt, wenn init() aufgerufen wird – und kann synchron
 *   übergeben werden (identisch zum Original-Ansatz mit config-Items).
 *   onOpen dient als Fallback für den unwahrscheinlichen Fall, dass der Request
 *   noch läuft.
 */
CKEDITOR.plugins.add('textSnippets', {
    requires: 'richcombo',

    init: function (editor) {
        var config = editor.config.textSnippets || {};
        var groupTitle = config.groupTitle || 'Textbausteine';
        var pluginPath = CKEDITOR.plugins.get('textSnippets').path;

        // null = wird noch geladen, Array = fertig (auch leer)
        var loadedItems = null;

        var ajaxUrl = (typeof TYPO3 !== 'undefined' &&
                       TYPO3.settings &&
                       TYPO3.settings.ajaxUrls &&
                       TYPO3.settings.ajaxUrls['rescue_reports_snippets'])
            ? TYPO3.settings.ajaxUrls['rescue_reports_snippets']
            : null;

        if (ajaxUrl) {
            // Pre-Fetch sofort starten – init() wird erst beim ersten Klick aufgerufen,
            // daher ist der Request meistens schon fertig.
            fetch(ajaxUrl)
                .then(function (r) { return r.json(); })
                .then(function (items) { loadedItems = items; })
                .catch(function () { loadedItems = []; });
        } else {
            loadedItems = [];
        }

        // Toolbar-Button-Breite (Skin setzt .cke_combo_text auf 60px fix)
        editor.on('instanceReady', function () {
            if (document.querySelector('style[data-plugin="textSnippets"]')) {
                return;
            }
            var style = document.createElement('style');
            style.setAttribute('data-plugin', 'textSnippets');
            style.textContent =
                '.cke_combo__textsnippets .cke_combo_text {' +
                '    width: auto !important; min-width: 150px !important;' +
                '}' +
                '.cke_combopanel { min-width: 260px !important; }';
            (document.head || document.getElementsByTagName('head')[0]).appendChild(style);
        });

        function fillCombo(combo, items) {
            if (!items || items.length === 0) {
                combo.add(
                    '_empty',
                    '<span style="color:#999;font-style:italic">(keine Textbausteine vorhanden)</span>',
                    '(keine Textbausteine vorhanden)'
                );
            } else {
                for (var i = 0; i < items.length; i++) {
                    combo.add(items[i].id, items[i].title, items[i].title);
                }
            }
        }

        editor.ui.add('TextSnippets', CKEDITOR.UI_RICHCOMBO, {
            label: groupTitle,
            title: groupTitle,
            toolbar: 'insert',

            panel: {
                css: [
                    CKEDITOR.skin.getPath('editor'),
                    CKEDITOR.getUrl(pluginPath + '../../../Css/panel.css')
                ],
                multiSelect: false
            },

            init: function () {
                // init() wird LAZY beim ersten open() aufgerufen – Pre-Fetch ist
                // zu diesem Zeitpunkt normalerweise bereits abgeschlossen.
                var combo = this;
                if (loadedItems !== null) {
                    fillCombo(combo, loadedItems);
                }
                // Falls noch nicht fertig: onOpen greift als Fallback
            },

            onOpen: function () {
                var combo = this;
                var panel = this._.panel;

                // Panel-Breite erzwingen
                setTimeout(function () {
                    if (panel && panel._.element) {
                        var el = panel._.element;
                        var w = parseInt(el.getStyle('width'), 10) || 0;
                        if (w < 260) {
                            el.setStyle('width', '260px');
                        }
                    }
                }, 0);

                // Fallback: init() hatte keine Items (Pre-Fetch war noch nicht fertig)
                var hasItems = combo._.items && Object.keys(combo._.items).length > 0;
                if (!hasItems) {
                    if (loadedItems !== null) {
                        // Inzwischen fertig
                        fillCombo(combo, loadedItems);
                    } else if (ajaxUrl) {
                        // Noch am Laden – jetzt synchron nachladen und Panel neu öffnen
                        combo.add(
                            '_loading',
                            '<span style="color:#999;font-style:italic">Wird geladen\u2026</span>',
                            'Wird geladen\u2026'
                        );
                        fetch(ajaxUrl)
                            .then(function (r) { return r.json(); })
                            .then(function (items) {
                                loadedItems = items;
                                // Panel schließen, Items zurücksetzen, neu öffnen
                                combo.close();
                                combo._.items = {};
                                fillCombo(combo, items);
                                combo.open();
                            })
                            .catch(function () {
                                loadedItems = [];
                                combo.close();
                                combo._.items = {};
                                fillCombo(combo, []);
                                combo.open();
                            });
                    }
                }
            },

            onClick: function (value) {
                if (!loadedItems) {
                    return;
                }
                var item = null;
                for (var i = 0; i < loadedItems.length; i++) {
                    if (loadedItems[i].id === value) {
                        item = loadedItems[i];
                        break;
                    }
                }
                if (!item) {
                    return;
                }
                editor.focus();
                editor.fire('saveSnapshot');
                editor.insertHtml(item.html);
                editor.fire('saveSnapshot');
            }
        });
    }
});
