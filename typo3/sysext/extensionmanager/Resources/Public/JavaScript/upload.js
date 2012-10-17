
jQuery(document).ready(function() {
	jQuery('.t3-icon-edit-upload').parent().not('.transformed').each(function () {
		jQuery(this).data('href', jQuery(this).attr('href'));
		jQuery(this).attr('href', 'javascript:void(0);');
		jQuery(this).addClass('transformed');
		jQuery(this).click(function () {
			jQuery('.uploadForm').show();
			jQuery.ajax({
				url:jQuery(this).data('href'),
				dataType:'html',
				success:function (data) {
					jQuery('.uploadForm').html(data);
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
			TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.l10n.localize('extensionList.uploadFlashMessage.title'), TYPO3.l10n.localize('extensionList.uploadFlashMessage.message').replace(/\{0\}/g, data.extension), 15);
			location.reload();
		}
	})
}
