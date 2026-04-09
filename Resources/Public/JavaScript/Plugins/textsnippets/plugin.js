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
 *   Falls der Fetch noch läuft, wenn init() aufgerufen wird, wird comboRef
 *   gespeichert und der Fetch befüllt das Combo retroaktiv nach Abschluss.
 */
CKEDITOR.plugins.add('textSnippets', {
    requires: 'richcombo',

    init: function (editor) {
        var config = editor.config.textSnippets || {};
        var groupTitle = config.groupTitle || 'Textbausteine';
        var pluginPath = CKEDITOR.plugins.get('textSnippets').path;

        // null = wird noch geladen, Array = fertig (auch leer)
        var loadedItems = null;
        // Referenz auf das Combo, sobald init() aufgerufen wurde
        var comboRef = null;

        var ajaxUrl = (typeof TYPO3 !== 'undefined' &&
                       TYPO3.settings &&
                       TYPO3.settings.ajaxUrls &&
                       TYPO3.settings.ajaxUrls['rescue_reports_snippets'])
            ? TYPO3.settings.ajaxUrls['rescue_reports_snippets']
            : null;

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

        if (ajaxUrl) {
            // Pre-Fetch sofort starten – init() wird erst beim ersten Klick aufgerufen,
            // daher ist der Request meistens schon fertig.
            fetch(ajaxUrl)
                .then(function (r) { return r.json(); })
                .then(function (items) {
                    loadedItems = items;
                    // Retroaktiv befüllen, falls init() schon gelaufen ist,
                    // das Combo aber noch leer ist (Pre-Fetch war noch nicht fertig)
                    if (comboRef && !(comboRef._.items && Object.keys(comboRef._.items).length > 0)) {
                        fillCombo(comboRef, items);
                    }
                })
                .catch(function () {
                    loadedItems = [];
                    if (comboRef && !(comboRef._.items && Object.keys(comboRef._.items).length > 0)) {
                        fillCombo(comboRef, []);
                    }
                });
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
                comboRef = this;
                if (loadedItems !== null) {
                    fillCombo(this, loadedItems);
                }
                // Falls noch nicht fertig: Pre-Fetch befüllt retroaktiv nach Abschluss
            },

            onOpen: function () {
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
