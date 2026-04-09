/**
 * CKEditor 4 Plugin: textSnippets
 *
 * Fügt ein Dropdown-Combo in die Toolbar ein, über das Textbausteine
 * per AJAX aus der Datenbank geladen und in den Editor eingefügt werden.
 *
 * Lade-Strategie:
 *   Items werden beim ersten onOpen geladen (nicht in init), weil CKEditor 4
 *   das Panel-Iframe lazy rendert. Items die in init() per Promise hinzugefügt
 *   werden, könnten zu spät kommen falls der Browser das Micro-Task-Timing
 *   anders behandelt. onOpen ist der sichere Zeitpunkt.
 */
CKEDITOR.plugins.add('textSnippets', {
    requires: 'richcombo',

    init: function (editor) {
        var config = editor.config.textSnippets || {};
        var groupTitle = config.groupTitle || 'Textbausteine';
        var pluginPath = CKEDITOR.plugins.get('textSnippets').path;

        // loadedItems: null = noch nicht geladen, Array = bereits geladen (auch leer)
        var loadedItems = null;
        var isLoading = false;

        // AJAX-URL aus TYPO3-Backend-Settings lesen
        var ajaxUrl = (typeof TYPO3 !== 'undefined' &&
                       TYPO3.settings &&
                       TYPO3.settings.ajaxUrls &&
                       TYPO3.settings.ajaxUrls['rescue_reports_snippets'])
            ? TYPO3.settings.ajaxUrls['rescue_reports_snippets']
            : null;

        // Sofortiger Pre-Fetch im Hintergrund (optional, verbessert Latenz)
        if (ajaxUrl) {
            isLoading = true;
            fetch(ajaxUrl)
                .then(function (r) { return r.json(); })
                .then(function (items) { loadedItems = items; isLoading = false; })
                .catch(function () { loadedItems = []; isLoading = false; });
        }

        // CSS-Injection für Toolbar-Button-Breite
        editor.on('instanceReady', function () {
            if (document.querySelector('style[data-plugin="textSnippets"]')) {
                return;
            }
            var style = document.createElement('style');
            style.setAttribute('data-plugin', 'textSnippets');
            style.textContent =
                '.cke_combo__textsnippets .cke_combo_text {' +
                '    width: auto !important;' +
                '    min-width: 150px !important;' +
                '}' +
                '.cke_combopanel {' +
                '    min-width: 260px !important;' +
                '}';
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

            // init() bleibt leer – Items werden in onOpen geladen
            init: function () {},

            onOpen: function () {
                var combo = this;
                var panel = this._.panel;

                // Panel-Breite erzwingen
                setTimeout(function () {
                    if (panel && panel._.element) {
                        var el = panel._.element;
                        var currentWidth = parseInt(el.getStyle('width'), 10) || 0;
                        if (currentWidth < 260) {
                            el.setStyle('width', '260px');
                        }
                    }
                }, 0);

                // Schon Items im Combo? Dann nichts tun (Cache)
                if (combo._.items && Object.keys(combo._.items).length > 0) {
                    return;
                }

                function populateCombo(items) {
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

                if (loadedItems !== null) {
                    // Bereits durch Pre-Fetch verfügbar
                    populateCombo(loadedItems);
                } else if (!ajaxUrl) {
                    // Keine AJAX-URL konfiguriert
                    loadedItems = [];
                    populateCombo([]);
                } else {
                    // Pre-Fetch noch nicht fertig oder fehlgeschlagen – jetzt laden
                    combo.add(
                        '_loading',
                        '<span style="color:#999;font-style:italic">Wird geladen...</span>',
                        'Wird geladen...'
                    );
                    fetch(ajaxUrl)
                        .then(function (r) { return r.json(); })
                        .then(function (items) {
                            loadedItems = items;
                            // Combo schließen und mit echten Items erneut öffnen
                            combo.close();
                            // Items zurücksetzen
                            combo._.items = {};
                            if (combo._.list && combo._.list._.items) {
                                combo._.list._.items = {};
                            }
                            populateCombo(items);
                            combo.open();
                        })
                        .catch(function () {
                            loadedItems = [];
                            combo.close();
                            combo._.items = {};
                            if (combo._.list && combo._.list._.items) {
                                combo._.list._.items = {};
                            }
                            populateCombo([]);
                            combo.open();
                        });
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
