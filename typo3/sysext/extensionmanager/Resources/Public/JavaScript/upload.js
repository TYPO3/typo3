// IIFE for faster access to $ and save $ use
(function ($) {

	$(document).ready(function() {
		$('.t3-icon-edit-upload').parent().not('.transformed').each(function () {
			$(this).data('href', $(this).attr('href'));
			$(this).attr('href', '#');
			$(this).addClass('transformed');
			$(this).click(function () {
				$('.uploadForm').show();
				$.ajax({
					url:$(this).data('href'),
					dataType:'html',
					success:function (data) {
						$('.uploadForm').html(data);
						handleUploadForm();
					}
				});
			});
		});
	});

	function handleUploadForm() {
		$('#typo3-extensionmanager-upload-target').on('load', function() {
			var ret = frames['typo3-extensionmanager-upload-target'].document.getElementsByTagName("body")[0].innerHTML;
			var data = eval("("+ret+")");
			if (data.success) {
				TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.l10n.localize('extensionList.uploadFlashMessage.title'), TYPO3.l10n.localize('extensionList.uploadFlashMessage.message').replace(/\{0\}/g, data.extension), 15);
				location.reload();
			}
		});
	}

}(jQuery));
