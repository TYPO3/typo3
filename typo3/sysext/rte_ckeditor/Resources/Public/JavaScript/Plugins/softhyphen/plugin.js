/**
 * soft hyphen character for CKEditor
 */
CKEDITOR.plugins.add("softhyphen", {
  lang: "da,de,en,fr,he,hr,hu,it,nl,ru", // lang: "ar,ca,da,de,el,en,es,eu,fa,fi,fr,he,hr,hu,it,ja,nl,no,pl,pt,pt-br,ru,sk,sv,tr,zh-cn"
  icons: 'softhyphen',
  hidpi: true,
  init: function (editor) {

    // Default Config
    var defaultConfig = {
      enableShortcut: true
    };
    var config = CKEDITOR.tools.extend(defaultConfig, editor.config.softhyphen || {}, true);

    // create command "insertSoftHyphen" which inserts the invisible html tag `&shy;`
    editor.addCommand('insertSoftHyphen', {
      exec: function (editor) {
        editor.insertHtml('&shy;', 'text');
      }
    });

    if (config.enableShortcut) {
      // enable shortcut ctrl+dash to insert a soft hyphen
      editor.setKeystroke(CKEDITOR.CTRL + 189 /* char 189 = dash */, 'insertSoftHyphen');
    }

    // add additional button to insert a soft hyphen via CKEditor toolbar
    editor.ui.addButton && editor.ui.addButton('softHyphen', {
      label: editor.lang.softhyphen.InsertButton,
      command: 'insertSoftHyphen',
      toolbar: 'insertcharacters',
      icon: 'softhyphen'
    });
  }
});
