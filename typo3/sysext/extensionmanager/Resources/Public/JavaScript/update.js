// IIFE for faster access to jQuery and save $ use
(function ($) {

	$(document).ready(function() {

		// Register "update from ter" action
		$('.update-from-ter').each(function() {

			// "this" is the form which updates the extension list from
			// TER on submit
			var updateURL = $(this).attr('action');
			$(this).attr('action', '#');

			$(this).submit(function() {
				// Force update on click.
				updateFromTer(updateURL, 1);

				// Prevent normal submit action.
				return false;
			});

			// This might give problems when there are more "update"-buttons,
			// each one would trigger a TER-update.
			updateFromTer(updateURL, 0);
		});
	});

	function updateFromTer(url, forceUpdate) {
		if (forceUpdate == 1) {
			url = url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager%5BforceUpdateCheck%5D=1';
		}

		// Hide triggers for TER update
		$('.update-from-ter').addClass('is-hidden');

		// Show loaders
		$('.splash-receivedata').addClass('is-shown');
		$('#terTable_wrapper').addClass('is-loading');

		$.ajax({
			url: url,
			dataType: 'json',
			cache: false,
			success: function(data) {

				// Something went wrong, show message
				if (data.errorMessage.length) {
					TYPO3.Flashmessage.display(TYPO3.Severity.warning, TYPO3.l10n.localize('extensionList.updateFromTerFlashMessage.title'), data.errorMessage, 10);
				}

				// Message with latest updates
				var $lastUpdate = $('.update-from-ter .time-since-last-update');
				$lastUpdate.text(data.timeSinceLastUpdate);
				$lastUpdate.attr(
					'title',
					TYPO3.l10n.localize('extensionList.updateFromTer.lastUpdate.timeOfLastUpdate') + data.lastUpdateTime
				);

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
			},
			error: function(jqXHR, textStatus, errorThrown) {
				// Create an error message with diagnosis info.
				var errorMessage = textStatus + '(' + errorThrown + '): ' + jqXHR.responseText;


				TYPO3.Flashmessage.display(
					TYPO3.Severity.warning,
					TYPO3.l10n.localize('extensionList.updateFromTerFlashMessage.title'),
					errorMessage,
					10
				);
			},
			complete: function() {

				// Hide loaders
				$('.splash-receivedata').removeClass('is-shown');
				$('#terTable_wrapper').removeClass('is-loading');

				// Show triggers for TER-update
				$('.update-from-ter').removeClass('is-hidden');
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
