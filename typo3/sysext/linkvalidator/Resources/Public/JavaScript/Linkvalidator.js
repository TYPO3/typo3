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

define('TYPO3/CMS/Linkvalidator/Linkvalidator', ['jquery'], function($) {

	var Linkvalidator = {};

	Linkvalidator.toggleActionButton = function(prefix) {
		var buttonDisable = true;
		$('.' + prefix).each(function(index) {
			if ($(this).prop('checked')) {
				buttonDisable = false;
			}
		});

		if (prefix == 'check') {
			$('#updateLinkList').prop('disabled', buttonDisable);
		} else {
			$('#refreshLinkList').prop('disabled', buttonDisable);
		}
	};

	/**
	 * Registers listeners
	 */
	Linkvalidator.initializeEvents = function() {
		$('.refresh').on('click', function() {
			Linkvalidator.toggleActionButton('refresh');
		});

		$('.check').on('click', function() {
			Linkvalidator.toggleActionButton('check');
		});
	};

	// intialize and return the Linkvalidator object
	return function() {
		$(document).ready(function() {
			Linkvalidator.initializeEvents();
		});

		TYPO3.Linkvalidator = Linkvalidator;
		return Linkvalidator;
	}();
});
