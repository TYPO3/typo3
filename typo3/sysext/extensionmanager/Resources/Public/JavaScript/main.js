jQuery(document).ready(function() {
	var datatable = jQuery('#typo3-extension-list').dataTable({
		"sPaginationType":"full_numbers",
		"bJQueryUI":true,
		"bLengthChange": false,
		'iDisplayLength': 50,
		"bStateSave": true
	});

	var getVars = getUrlVars();

	if(datatable.length && getVars['search']) {
		datatable.fnFilter(getVars['search']);
	}

	jQuery("#typo3-extension-configuration-forms ul").tabs("div.category");
	jQuery(".validate").validator();

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
});

function getUrlVars() {
	var vars = [], hash;
	var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
	for(var i = 0; i < hashes.length; i++) {
		hash = hashes[i].split('=');
		vars.push(hash[0]);
		vars[hash[0]] = hash[1];
	}
	return vars;
}
