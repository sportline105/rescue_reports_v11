/**
 * CKEditor 4 Plugin: textSnippets
 *
 * Fügt ein Dropdown-Combo in die Toolbar ein, über das Textbausteine
 * per AJAX aus der Datenbank geladen und in den Editor eingefügt werden.
 *
 * Snippets werden beim Plugin-Init asynchron per AJAX geladen
 * (TYPO3 Backend AJAX Route: rescue_reports_snippets).
 *
 * Warum CSS-Injection und onOpen-Override?
 *   CKEditor 4 setzt .cke_combo_text im Skin auf width: 60px (fester Wert).
 *   rte.css landet nur im Content-Iframe – nicht im Toolbar-Bereich.
 *   Die Panel-Breite leitet CKEditor aus dem offsetWidth des Combo-Buttons ab
 *   (showBlock in floatpanel.js). Deshalb muss die Button-Breite VOR dem ersten
 *   Öffnen korrekt sein und die Panel-Breite wird zusätzlich per onOpen erzwungen.
 */
CKEDITOR.plugins.add('textSnippets', {
    requires: 'richcombo',

    init: function (editor) {
        var config = editor.config.textSnippets || {};
        var groupTitle = config.groupTitle || 'Textbausteine';
        var pluginPath = CKEDITOR.plugins.get('textSnippets').path;
        var loadedItems = [];

        // AJAX-URL aus TYPO3-Backend-Settings lesen
        var ajaxUrl = (typeof TYPO3 !== 'undefined' &&
                       TYPO3.settings &&
                       TYPO3.settings.ajaxUrls &&
                       TYPO3.settings.ajaxUrls['rescue_reports_snippets'])
            ? TYPO3.settings.ajaxUrls['rescue_reports_snippets']
            : null;

        // Snippets sofort beim Plugin-Init laden (vor dem ersten Klick des Nutzers)
        var snippetsPromise = ajaxUrl
            ? fetch(ajaxUrl)
                .then(function (r) { return r.json(); })
                .then(function (items) { loadedItems = items; return items; })
                .catch(function () { loadedItems = []; return []; })
            : Promise.resolve([]);

        // Fix 1: Toolbar-Button-Breite per CSS-Injection
        // .cke_combo_text ist das sichtbare Textelement im Combo-Button.
        // Skin-Standard: width: 60px – zu schmal für "Textbausteine".
        // Style wird direkt ins TYPO3-Backend-Dokument injiziert, da contentsCss
        // nur den Content-Iframe betrifft.
        editor.on('instanceReady', function () {
            if (document.querySelector('style[data-plugin="textSnippets"]')) {
                return; // Nur einmal pro Seite injizieren (mehrere Editor-Instanzen)
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
                // panel.css wird in den Panel-Iframe geladen (Items-Rendering).
                css: [
                    CKEDITOR.skin.getPath('editor'),
                    CKEDITOR.getUrl(pluginPath + '../../../Css/panel.css')
                ],
                multiSelect: false
            },

            init: function () {
                var combo = this;
                // Asynchron befüllen – das Panel wird erst beim ersten onOpen gerendert,
                // daher reicht es, wenn die Items vor dem ersten Klick bereitstehen.
                snippetsPromise.then(function (items) {
                    if (items.length === 0) {
                        combo.add('_empty', '(keine Textbausteine vorhanden)', '(keine Textbausteine vorhanden)');
                    } else {
                        for (var i = 0; i < items.length; i++) {
                            combo.add(items[i].id, items[i].title, items[i].title);
                        }
                    }
                });
            },

            // Fix 2: Panel-Breite via onOpen erzwingen
            onOpen: function () {
                var panel = this._.panel;
                setTimeout(function () {
                    if (panel && panel._.element) {
                        var el = panel._.element;
                        var currentWidth = parseInt(el.getStyle('width'), 10) || 0;
                        if (currentWidth < 260) {
                            el.setStyle('width', '260px');
                        }
                    }
                }, 0);
            },

            onClick: function (value) {
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
