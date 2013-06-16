/***************************************************************
 *
 *  Various JavaScript functions for the Install Tool
 *
 *  Copyright notice
 *
 *  (c) 2009-2010 Marcus Krause, Helmut Hummel, Lars Houmark
 *  (c) 2013 Wouter Wolters <typo3@wouterwolters.nl>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 backend provided by
 *  Kasper Skaarhoj <kasper@typo3.com> together with TYPO3
 *
 *  Released under GNU/GPL (see license file in /typo3/)
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  This copyright notice MUST APPEAR in all copies of this script
 *
 ***************************************************************/

/**
 * Small javascript helpers of the install tool based on jquery
 *
 * @author Wouter Wolters <typo3@wouterwolters.nl>
 */
$(document).ready(function() {
	// Used in database compare section to select/deselect checkboxes
	$('.checkall').on('click', function() {
		$(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);
	});

	// Toggle open/close
	$('.toggleButton').on('click', function() {
		$(this).closest('.toggleGroup').find('.toggleData').toggle();
	});

	// Simple password strength indicator
	$('.t3-install-form-password-strength').on('keyup', function() {
		var value = $(this).val();
		var strongRegex = new RegExp('^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$', 'g');
		var mediumRegex = new RegExp('^(?=.{7,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$', 'g');
		var enoughRegex = new RegExp('(?=.{6,}).*', 'g');

		if (value.length == 0) {
			$(this).attr('style', 'background-color:#FBB19B; border:1px solid #DC4C42');
		} else if (false == enoughRegex.test(value)) {
			$(this).attr('style', 'background-color:#FBB19B; border:1px solid #DC4C42');
		} else if (strongRegex.test(value)) {
			$(this).attr('style', 'background-color:#CDEACA; border:1px solid #58B548');
		} else if (mediumRegex.test(value)) {
			$(this).attr('style', 'background-color:#FBFFB3; border:1px solid #C4B70D');
		} else {
			$(this).attr('style', 'background-color:#FBFFB3; border:1px solid #C4B70D');
		}
	});

});