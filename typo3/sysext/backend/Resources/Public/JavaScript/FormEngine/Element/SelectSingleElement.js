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
 * Module: TYPO3/CMS/Backend/FormEngine/Element/SelectSingleElement
 * Logic for SelectSingleElement
 */
define(['jquery'], function ($) {

	/**
	 *
	 * @type {{}}
	 * @exports TYPO3/CMS/Backend/FormEngine/Element/SelectSingleElement
	 */
	var SelectSingleElement = {};

	/**
	 * Initializes the SelectSingleEleemnt
	 *
	 * @param {String} selector
	 * @param {Object} options
	 */
	SelectSingleElement.initialize = function(selector, options) {

		var $selectElement = $(selector);
		var $groupIconContainer = $selectElement.prev('.input-group-icon');
		var options = options || {};

		$selectElement.on('change', function() {
			// Update prepended select icon
			$groupIconContainer.html($selectElement.find(':selected').data('icon'));
		});

		// Append optionally passed additional "change" event callback
		if (typeof options.onChange === 'function') {
			$selectElement.on('change', options.onChange);
		}

		// Append optionally passed additional "focus" event callback
		if (typeof options.onFocus === 'function') {
			$selectElement.on('focus', options.onFocus);
		}

		$selectElement.closest('.form-control-wrap').next('.t3js-forms-select-single-icons').on('click', function(e) {
			var $selectIcon = $(e.target).closest('[data-select-index]');

			$selectElement
				.prop('selectedIndex', $selectIcon.data('selectIndex'))
				.trigger('change');
			$selectIcon.trigger('blur');

			return false;
		});
	};

	return SelectSingleElement;
});
