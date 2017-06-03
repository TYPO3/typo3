/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

require(['jquery', 'TYPO3/CMS/Install/CardLayout'], function($, CardLayout) {

	// Simple password strength indicator
	$(document).on('keyup', '.t3-install-form-password-strength', function() {
		var value = $(this).val();
		var strongRegex = new RegExp('^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$', 'g');
		var mediumRegex = new RegExp('^(?=.{8,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$', 'g');
		var enoughRegex = new RegExp('(?=.{8,}).*', 'g');

		if (value.length === 0) {
			$(this).attr('style', 'background-color:#FBB19B; border:1px solid #DC4C42');
		} else if (!enoughRegex.test(value)) {
			$(this).attr('style', 'background-color:#FBB19B; border:1px solid #DC4C42');
		} else if (strongRegex.test(value)) {
			$(this).attr('style', 'background-color:#CDEACA; border:1px solid #58B548');
		} else if (mediumRegex.test(value)) {
			$(this).attr('style', 'background-color:#FBFFB3; border:1px solid #C4B70D');
		} else {
			$(this).attr('style', 'background-color:#FBFFB3; border:1px solid #C4B70D');
		}
	});

	// Install step database settings
	$('#t3js-connect-database-driver').on('change', function() {
		var driver = $(this).val();
		$('.t3-install-driver-data').hide();
		$('.t3-install-driver-data input').attr('disabled', 'disabled');
		$('#' + driver + ' input').attr('disabled', false);
		$('#' + driver).show();
	}).trigger('change');

	CardLayout.initialize();

	// Each card head can have a t3js-require class and a data-require attribute
	// with the name of a requireJS module. Those are loaded here and initialize()
	// is executed if exists.
	$('.t3js-require').each(function() {
		var module = $(this).data('require');
		require([module], function(aModule) {
			if (typeof aModule.initialize !== 'undefined') {
				aModule.initialize();
			}
		});
	});
});
