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

/**
 * Module: TYPO3/CMS/Linkvalidator/Linkvalidator
 */
define(['jquery'], function($) {
	'use strict';

	/**
	 *
	 * @type {{}}
	 * @exports TYPO3/CMS/Linkvalidator/Linkvalidator
	 */
	var Linkvalidator = {};

	/**
	 *
	 * @param {String} prefix
	 */
	Linkvalidator.toggleActionButton = function(prefix) {
		var buttonDisable = true;
		$('.' + prefix).each(function() {
			if ($(this).prop('checked')) {
				buttonDisable = false;
			}
		});

		if (prefix === 'check') {
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

	$(Linkvalidator.initializeEvents);

	return Linkvalidator;
});
