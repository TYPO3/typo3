/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with DocumentHeader source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Initializes global handling of split buttons.
 */
define('TYPO3/CMS/Backend/SplitButtons', ['jquery'], function($) {
	"use strict";

	var SplitButtons = {
		preSubmitCallbacks: []
	};

	/**
	 * Initializes the save handling
	 */
	SplitButtons.initializeSaveHandling = function() {
		var elements = [
			'button[name^="_save"]',
			'a[data-name^="_save"]',
			'button[name="CMD"][value^="save"]',
			'a[data-name="CMD"][data-value^="save"]'
		].join(',');
		$(document).on('click', elements, function(e) {
			var $me = $(this),
				$form = $me.closest('form'),
				name = $me.data('name') || this.name,
				value = $me.data('value') || this.value,
				$elem = $('<input />').attr('type', 'hidden').attr('name', name).attr('value', value);

			// Run any preSubmit callbacks
			for (var i = 0; i < SplitButtons.preSubmitCallbacks.length; ++i) {
				SplitButtons.preSubmitCallbacks[i](e);
			}

			$form.append($elem);

			if (e.currentTarget.tagName === 'A' && !e.isDefaultPrevented()) {
				$form.submit();
				e.preventDefault();
			}
		});
	};

	/**
	 * Adds a callback being executed before submit
	 *
	 * @param callback
	 */
	SplitButtons.addPreSubmitCallback = function(callback) {
		if (typeof callback !== 'function') {
			throw 'callback must be a function.';
		}

		SplitButtons.preSubmitCallbacks.push(callback);
	};

	$(SplitButtons.initializeSaveHandling);

	return SplitButtons;
});
