document.observe('t3editor:save', function(event) {
	var params = Object.extend({
		ajaxID: "tx_t3editor::saveCode",
		t3editor_savetype: "tx_tstemplateinfo"
	}, event.memo.parameters);

	new Ajax.Request(
		T3editor.URL_typo3 + "ajax.php", {
			parameters: params,
			onComplete: function(ajaxrequest) {
				var wasSuccessful = ajaxrequest.status == 200
				&& ajaxrequest.headerJSON.result == true
				event.memo.t3editor.saveFunctionComplete(wasSuccessful);
			}
		}
		);
});