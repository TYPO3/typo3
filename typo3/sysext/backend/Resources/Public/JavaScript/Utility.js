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
 * Utility helper
 */
define(['jquery'], function($) {

	/**
	 * The main Utility object
	 */
	var Utility = {
	};

	/**
	 * Checks if a given number is really a number
	 *
	 * Taken from:
	 * http://dl.dropbox.com/u/35146/js/tests/isNumber.html
	 *
	 * @param {string} number
	 * @returns {boolean|*}
	 */
	Utility.isNumber = function(number) {
		return !isNaN(parseFloat(number)) && isFinite(number);
	};

	/**
	 * Gets a parameter from a given url
	 *
	 * @param {string} url
	 * @param {string} parameter
	 * @returns {string}
	 */
	Utility.getParameterFromUrl = function(url, parameter) {
		var parts = url.split('?'),
			value = '';

		if (parts.length >= 2) {
			var queryString = parts.join('?');

			var prefix = encodeURIComponent(parameter) + '=';
			var parameters = queryString.split(/[&;]/g);
			for (var i = parameters.length; i-- > 0;) {
				if (parameters[i].lastIndexOf(prefix, 0) !== -1) {
					value = parameters[i].split('=')[1];
					break;
				}
			}
		}

		return value;
	};

	TYPO3.Utility = Utility;
	return Utility;
});
