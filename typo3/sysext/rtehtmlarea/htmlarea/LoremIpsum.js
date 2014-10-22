/*
 * Extending the TYPO3 Lorem Ipsum extension
 */
var lorem_ipsum = function (element, text) {
	if (/^textarea$/i.test(element.nodeName) && element.id && element.id.substr(0,7) === 'RTEarea') {
		var editor = RTEarea[element.id.substr(7, element.id.length)]['editor'];
		editor.getSelection().insertHtml(text);
		editor.updateToolbar();
	}
}
