jQuery(document).ready(function() {
	jQuery('.updateFromTer a').each(function() {
		jQuery(this).data('href', jQuery(this).attr('href'));
		jQuery(this).attr('href', 'javascript:void(0);');
		jQuery(this).click(function() {
				// force update on click
			updateFromTer(jQuery(this).data('href'), 1);
		});
		updateFromTer(jQuery(this).data('href'), 0);
	});
});

function updateFromTer(url, forceUpdate) {
	var url = url;
	if (forceUpdate == 1) {
		url = url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager%5BforceUpdateCheck%5D=1'
	}
	jQuery('.updateFromTer .spinner').show();
	jQuery('#terTableWrapper').mask();
	jQuery.ajax({
		url: url,
		dataType: 'json',
		success: function(data) {
			jQuery('.updateFromTer .spinner').hide();

			if (data.errorMessage.length) {
				TYPO3.Flashmessage.display(TYPO3.Severity.warning, 'Update Extension List', data.errorMessage, 10);
			}
			jQuery('.updateFromTer .text').html(
				data.message
			);
			if (data.updated) {
				jQuery.ajax({
					url: window.location.href + '&tx_extensionmanager_tools_extensionmanagerextensionmanager%5Bformat%5D=json',
					dataType: 'json',
					success: function(data) {
						jQuery('#terTableWrapper').html(
							data
						);
						transformPaginatorToAjax();
					}
				});
			}
			jQuery('#terTableWrapper').unmask();
		}
	});
}

function transformPaginatorToAjax() {
	jQuery('.f3-widget-paginator a').each(function() {
		jQuery(this).data('href', jQuery(this).attr('href'));
		jQuery(this).attr('href', 'javascript:void(0);');
		jQuery(this).click(function() {
			jQuery('#terTableWrapper').mask();
			jQuery.ajax({
				url: jQuery(this).data('href'),
				dataType: 'json',
				success: function(data) {
					jQuery('#terTableWrapper').html(
						data
					);
					jQuery('#terTableWrapper').unmask();
					transformPaginatorToAjax();
				}
			});
		});
	});
}