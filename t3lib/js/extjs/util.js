/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Stefan Galinski <stefan.galinski@gmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
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
