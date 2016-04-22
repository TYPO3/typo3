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
 * Module: TYPO3/CMS/Backend/ColorPicker
 * contains all logic for the color picker used in FormEngine
 */
define(['jquery', 'TYPO3/CMS/Core/Contrib/jquery.minicolors'], function($) {

	/**
	 * @type {{selector: string}}
	 * @exports TYPO3/CMS/Backend/ColorPicker
	 */
	var ColorPicker = {
		selector: '.t3js-color-picker'
	};

	/**
	 * Initialize ColorPicker elements
	 */
	ColorPicker.initialize = function() {
		$(function () {
			$(ColorPicker.selector).minicolors({
				theme: 'bootstrap',
				format: 'hex',
				position: 'bottom left'
			});
		});
	};

	return ColorPicker;
});
