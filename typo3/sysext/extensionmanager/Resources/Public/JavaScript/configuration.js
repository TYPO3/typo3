// IIFE for faster access to $ and save $ use
(function ($) {

	$(document).ready(function() {
		configurationFieldSupport();
		$(".validate").validator();
	});

	function configurationFieldSupport() {
		$('.offset').each(function() {
			$(this).hide();
			val = $(this).attr('value');
			valArr = val.split(',');

			$(this).wrap('<div class="offsetSelector"></div>');
			$(this).parent().append('x: <input value="' + $.trim(valArr[0]) + '" class="tempOffset1 tempOffset">');
			$(this).parent().append('<span>, </span>');
			$(this).parent().append('y: <input value="' + $.trim(valArr[1]) + '" class="tempOffset2 tempOffset">');

			$(this).siblings('.tempOffset').keyup(function() {
				$(this).siblings('.offset').attr(
					'value',
					$(this).parent().children('.tempOffset1').attr('value') + ',' + $(this).parent().children('.tempOffset2').attr('value'));
			});
		});

		$('.wrap').each(function() {
			$(this).hide();
			val = $(this).attr('value');
			valArr = val.split('|');

			$(this).wrap('<div class="wrapSelector"></div>');
			$(this).parent().append('<input value="' + $.trim(valArr[0]) + '" class="tempWrap1 tempWrap">');
			$(this).parent().append('<span>|</span>');
			$(this).parent().append('<input value="' + $.trim(valArr[1]) + '" class="tempWrap2 tempWrap">');

			$(this).siblings('.tempWrap').keyup(function() {
				$(this).siblings('.wrap').attr(
					'value',
					$(this).parent().children('.tempWrap1').attr('value') + '|' + $(this).parent().children('.tempWrap2').attr('value'));
			});
		});
	}
}(jQuery));
