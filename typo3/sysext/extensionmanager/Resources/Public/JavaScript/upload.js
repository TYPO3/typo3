
jQuery(document).ready(function() {
	jQuery('.uploadExtension').not('.transformed').each(function(){
		jQuery(this).data('href', jQuery(this).attr('href'));
		jQuery(this).attr('href', 'javascript:void(0);');
		jQuery(this).addClass('transformed');
		jQuery(this).click(function() {
			jQuery('.uploadForm').show();
			jQuery(this).hide();
			jQuery.ajax({
				url: jQuery(this).data('href'),
				dataType: 'html',
				success: function(data) {
					jQuery('.uploadForm').html(
						data
					);
					handleUploadForm();
				}
			});
		});
	});
});

function handleUploadForm() {
	jQuery('#typo3-extensionmanager-upload-target').on('load', function() {
		var ret = frames['typo3-extensionmanager-upload-target'].document.getElementsByTagName("body")[0].innerHTML;
		var data = eval("("+ret+")");
		if (data.success) {
			TYPO3.Flashmessage.display(TYPO3.Severity.information, 'Extension Upload', data.extension + ' uploaded!', 15);
			location.reload();
		}
	})
}
