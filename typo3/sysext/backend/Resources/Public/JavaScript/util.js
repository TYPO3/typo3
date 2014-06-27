/**
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
Ext.namespace('TYPO3');

/**
 * @class TYPO3.Utility
 *
 * Generic utility methods that are not provided by ExtJs
 *
 * @namespace TYPO3
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.Utility = {
	/**
	 * Checks if a given number is really a number
	 *
	 * Taken from:
	 * http://dl.dropbox.com/u/35146/js/tests/isNumber.html
	 *
	 * @param {String} number
	 * @return {Boolean}
	 */
	isNumber: function(number) {
		return !isNaN(parseFloat(number)) && isFinite(number);
	},

	/**
	 * Gets a parameter from a given url
	 *
	 * @param {string} url
	 * @param {string} parameter
	 * @return {string}
	 */
	getParameterFromUrl: function(url, parameter) {
	  var parts = url.split('?'),
		  value = '';

	  if (parts.length >= 2) {
		  var urlBase = parts.shift();
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
	}
};
