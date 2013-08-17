/**
 * Add event to width selector
 */
jQuery(document).ready(function($) {
	jQuery('#width').on('change', function() {
		var widthInPixels = jQuery('#width :selected').val();

		jQuery('#tx_viewpage_iframe').animate({
			'width': widthInPixels
		});
	});
});