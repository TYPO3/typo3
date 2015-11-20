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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Resizable
 * Make resizable
 */
define(['jquery',
	'jquery-ui/resizable'],
	function ($, Resizable) {

	/**
	 *
	 * @type {{makeResizable: Function, destroy: Function}}
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Resizable
	 */
	var Resizable = {

		/**
		 * Make an element resizable
		 *
		 * @param object element: the target element
		 * @param object
		 */
		makeResizable: function (element, config) {
			if (typeof config !== 'undefined') {
				return $(element).resizable(config);
			} else {
				return $(element).resizable();
			}
		},

		/**
		 * Removes the resizable feature from the element
		 *
		 * @param object element: the target element
		 * @return object the element
		 */
		destroy: function (element) {
			return $(element).resizable('destroy');
		}
	};

	return Resizable;

});
