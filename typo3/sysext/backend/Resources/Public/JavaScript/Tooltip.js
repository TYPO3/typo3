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
 * API for tooltip windows powered by Twitter Bootstrap.
 */
define('TYPO3/CMS/Backend/Tooltip', ['jquery'], function($) {

	/**
	 * The main tooltip object
	 */
	var Tooltip = {
	};

	/**
	 * Initialize
	 */
	Tooltip.initialize = function(selector, options) {
		options = options || {};
		$(selector).tooltip(options);
	};

	/**
	 * Show tooltip on $element
	 *
	 * @param {object} $element
	 * @param {string} title
	 */
	Tooltip.show = function($element, title) {
		$element
			.attr('data-placement', 'auto')
			.attr('data-title', title)
			.tooltip('show');
	};

	/**
	 * Hide tooltip on $element
	 *
	 * @param $element
	 */
	Tooltip.hide = function($element) {
		$element.tooltip('hide');
	};

	Tooltip.initialize('[data-toggle="tooltip"]');
	TYPO3.Tooltip = Tooltip;
	return Tooltip;
});
