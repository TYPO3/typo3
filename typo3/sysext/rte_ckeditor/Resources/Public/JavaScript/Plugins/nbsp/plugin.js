/**
 * non-breaking space character for CKEditor
 * plugin based on softhyphen from sysext/rte_ckeditor
 */
CKEDITOR.plugins.add("nbsp", {
	lang: "de,en", // lang: "ar,ca,da,de,el,en,es,eu,fa,fi,fr,he,hr,hu,it,ja,nl,no,pl,pt,pt-br,ru,sk,sv,tr,zh-cn"
	icons: 'nbsp',
	hidpi: true,
	init: function (editor) {

		// Default Config
		var defaultConfig = {
			enableShortcut: true
		};
		var config = CKEDITOR.tools.extend(defaultConfig, editor.config.nbsp || {}, true);

		// create command "insertNonBreakingspace" which inserts the html-encoded character `&nbsp;`
		editor.addCommand('insertNonBreakingspace', {
			exec: function (editor) {
				editor.insertHtml('&nbsp;', 'text');
			}
		});

		if (config.enableShortcut) {
			// enable shortcut alt+space to insert a non-breaking space
			editor.setKeystroke(CKEDITOR.ALT + 32 /* char 32 = space */, 'insertNonBreakingspace');
		}

		// add additional button to insert a non-breaking space via CKEditor toolbar
		editor.ui.addButton && editor.ui.addButton('nbsp', {
			label: editor.lang.nbsp.InsertButton,
			command: 'insertNonBreakingspace',
			toolbar: 'insertcharacters',
			icon: 'nbsp'
		});
	},
	afterInit: function (editor) {
		let dataProcessor = editor.dataProcessor,
			htmlFilter = dataProcessor && dataProcessor.htmlFilter;

		if (htmlFilter) {
			htmlFilter.addRules({
				text: function (text) {
					// replace invisible Unicode character with HTML entity within source
					return text.replace(/&nbsp;/g, '\u00A0').replace(/\u00A0/g, '&nbsp;');
				}
			}, {
				applyToAll: true,
				excludeNestedEditable: false
			});
		}
	}
});
