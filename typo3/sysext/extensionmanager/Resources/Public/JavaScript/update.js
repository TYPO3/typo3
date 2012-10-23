// IIFE for faster access to jQuery and save $ use
(function ($) {

	$(document).ready(function() {
		$('.splash-receivedata a').each(function() {
			$(this).data('href', $(this).attr('href'));
			$(this).attr('href', '#');
			$(this).click(function() {
					// force update on click
				updateFromTer($(this).data('href'), 1);
			});
			updateFromTer($(this).data('href'), 0);
		});
	});

	function updateFromTer(url, forceUpdate) {
		if (forceUpdate == 1) {
			url = url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager%5BforceUpdateCheck%5D=1';
		}
		$('.splash-receivedata').addClass('is-shown');
		$('.typo3-extensionmanager-headerRowRight .splash-receivedata').addClass('is-hidden');

		$('#terTable_wrapper').addClass('is-loading');

		$.ajax({
			url: url,
			dataType: 'json',
			success: function(data) {

				// Hide loader
				$('.splash-receivedata').removeClass('is-shown');

				// Something went wrong, show message
				if (data.errorMessage.length) {
					TYPO3.Flashmessage.display(TYPO3.Severity.warning, TYPO3.l10n.localize('extensionList.updateFromTerFlashMessage.title'), data.errorMessage, 10);
				}

				// Message with latest updates
				$('.typo3-extensionmanager-headerRowRight .splash-receivedata .text').html(
					data.message
				);

				// Show content
				$('#terTable_wrapper').removeClass('is-loading');

				// Header: Show message
				$('.typo3-extensionmanager-headerRowRight .splash-receivedata').removeClass('is-hidden');

				if (data.updated) {
					$.ajax({
						url: window.location.href + '&tx_extensionmanager_tools_extensionmanagerextensionmanager%5Bformat%5D=json',
						dataType: 'json',
						success: function(data) {
							$('#terTableWrapper').html(
								data
							);
							transformPaginatorToAjax();
						}
					});
				}
			}
		});
	}

	function transformPaginatorToAjax() {
		$('.f3-widget-paginator a').each(function() {
			$(this).data('href', $(this).attr('href'));
			$(this).attr('href', '#');
			$(this).click(function() {
				$('#terTableWrapper').mask();
				$.ajax({
					url: $(this).data('href'),
					dataType: 'json',
					success: function(data) {
						$('#terTableWrapper').html(
							data
						);
						$('#terTableWrapper').unmask();
						transformPaginatorToAjax();
					}
				});
			});
		});
	}

}(jQuery));
