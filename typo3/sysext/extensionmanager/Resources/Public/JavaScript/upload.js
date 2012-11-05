// IIFE for faster access to $ and save $ use
(function ($) {

	$(function() {

		// Show upload form
		$('.t3-icon-edit-upload').parent().not('.transformed').on('click', function(e) {
			e.preventDefault();
			$(this).addClass('transformed');
			$('.uploadForm').show();

			$.ajax({
				url: $(this).attr('href'),
				dataType: 'html',
				success: function (data) {
					$('.uploadForm').html(data);

					handleUploadForm();
				}
			});
		});

		function handleUploadForm() {

			// Handle form submit response
			$('#typo3-extensionmanager-upload-target').on('load', function(e) {
				var data,
				    html = $.trim($(this).contents().find('body').html());

				if (html[0] === '{') {
					data = $.parseJSON(html);

					if (data.error) {
						TYPO3.Flashmessage.display(TYPO3.Severity.error,
								'Extension Upload failed', data.error, 5);
					} else if (data.success) {
						TYPO3.Flashmessage.display(
							TYPO3.Severity.information,
							TYPO3.l10n.localize('extensionList.uploadFlashMessage.title'),
							TYPO3.l10n.localize('extensionList.uploadFlashMessage.message').replace(/\{0\}/g, data.extension),
							15
						);

						// Reload the page
						location.reload();
					}
				}
			});
		}
	});
}(jQuery));
