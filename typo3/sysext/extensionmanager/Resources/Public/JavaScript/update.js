// IIFE for faster access to jQuery and save $ use
(function ($) {

	$(document).ready(function() {
		$('.updateFromTer a').each(function() {
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
		$('.updateFromTer .spinner').show();
		$('#terTableWrapper').mask();
		$.ajax({
			url: url,
			dataType: 'json',
			success: function(data) {
				$('.updateFromTer .spinner').hide();

				if (data.errorMessage.length) {
					TYPO3.Flashmessage.display(TYPO3.Severity.warning, TYPO3.l10n.localize('extensionList.updateFromTerFlashMessage.title'), data.errorMessage, 10);
				}
				$('.updateFromTer .text').html(
					data.message
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
				$('#terTableWrapper').unmask();
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
