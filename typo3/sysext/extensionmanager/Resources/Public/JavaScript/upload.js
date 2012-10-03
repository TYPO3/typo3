jQuery(document).ready(function() {

		// Show upload form
	jQuery('.uploadExtension').not('.transformed').on('click', function(event) {
		event.preventDefault();
		jQuery(this).addClass('transformed').hide();
		jQuery('.uploadForm').show();
	});

		// Handle form submit response
	jQuery('#typo3-extensionmanager-upload-target').on('load', function(event) {
		var html = jQuery.trim(jQuery(this).contents().find('body').html());
		if (html[0] === '{') {
			var data = jQuery.parseJSON(html);
			if (data.error) {
				TYPO3.Flashmessage.display(TYPO3.Severity.error, 'Extension Upload failed', data.error, 5);
			} else if (data.success) {
				TYPO3.Flashmessage.display(TYPO3.Severity.information, 'Extension Upload', data.extension + ' uploaded!', 15);
				location.reload();
			}
		}
	});

});