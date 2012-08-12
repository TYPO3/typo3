jQuery(document).ready(function() {
	configurationFieldSupport();
	jQuery(".validate").validator();
});

function configurationFieldSupport() {
	jQuery('.offset').each(function() {
		jQuery(this).hide();
		val = jQuery(this).attr('value');
		valArr = val.split(',');

		jQuery(this).wrap('<div class="offsetSelector"></div>');
		jQuery(this).parent().append('x: <input value="' + jQuery.trim(valArr[0]) + '" class="tempOffset1 tempOffset">');
		jQuery(this).parent().append('<span>, </span>');
		jQuery(this).parent().append('y: <input value="' + jQuery.trim(valArr[1]) + '" class="tempOffset2 tempOffset">');

		jQuery(this).siblings('.tempOffset').keyup(function() {
			jQuery(this).siblings('.offset').attr(
				'value',
				jQuery(this).parent().children('.tempOffset1').attr('value') + ',' + jQuery(this).parent().children('.tempOffset2').attr('value'));
		})
	});

	jQuery('.wrap').each(function() {
		jQuery(this).hide();
		val = jQuery(this).attr('value');
		valArr = val.split('|');

		jQuery(this).wrap('<div class="wrapSelector"></div>');
		jQuery(this).parent().append('<input value="' + jQuery.trim(valArr[0]) + '" class="tempWrap1 tempWrap">');
		jQuery(this).parent().append('<span>|</span>');
		jQuery(this).parent().append('<input value="' + jQuery.trim(valArr[1]) + '" class="tempWrap2 tempWrap">');

		jQuery(this).siblings('.tempWrap').keyup(function() {
			jQuery(this).siblings('.wrap').attr(
				'value',
				jQuery(this).parent().children('.tempWrap1').attr('value') + '|' + jQuery(this).parent().children('.tempWrap2').attr('value'));
		})
	});
}