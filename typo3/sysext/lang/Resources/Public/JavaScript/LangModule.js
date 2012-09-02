TYPO3.jQuery(document).ready(function($) {

	/**
	 * Add click event handler to submit language selection form when choosing
	 * a new or remove an existing language
	 */
	$('.languageSelection .c-checkbox, .languageSelection .c-labelCell').on('click', function(event) {
		$('form[name="languageSelectionForm"]').submit();
	});

});