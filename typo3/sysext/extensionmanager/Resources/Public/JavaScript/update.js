// IIFE for faster access to jQuery and save $ use
(function ($) {

	$(document).ready(function() {
		var i, receiveLinkCurrent,
		    receiveLink = $('.splash-receivedata a'),
		    receiveLinkLength = receiveLink.length;


		// Events
		$(document).on('click', '.splash-receivedata a', function () {

			// force update on click
			updateFromTer($(this).data('href'), 1);
		});

		for(i = 0; i <receiveLinkLength; i++) {

			// Faster access
			receiveLinkCurrent = $(receiveLink[i]);
			receiveLinkCurrent.data('href', receiveLinkCurrent.attr('href'));
			receiveLinkCurrent.attr('href', '#');
			updateFromTer(receiveLinkCurrent.data('href'), 0);
		}
	});


	function updateFromTer(url, forceUpdate) {
		if (forceUpdate === 1) {
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
		var i, paginatorLinkCurrent,
		    paginatorLinks = $('.f3-widget-paginator a');
		    paginatorLinksLength = paginatorLinks.length;

		$(document).on('click', '.f3-widget-paginator a', function() {
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

		for (i = 0; i < paginatorLinksLength; i++) {
			paginatorLinkCurrent = $(paginatorLinks[i]);

			paginatorLinkCurrent.data('href', paginatorLinkCurrent.attr('href'));
			paginatorLinkCurrent.attr('href', '#');
		}
	}

}(jQuery));
