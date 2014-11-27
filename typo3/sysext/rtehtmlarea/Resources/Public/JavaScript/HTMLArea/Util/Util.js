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
/***************************************************
 *  UTILITY FUNCTIONS
 ***************************************************/
HTMLArea.util = function() {

	var Util = {

		/**
		 * Perform HTML decoding of some given string
		 * Borrowed in part from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
		 */
		htmlDecode: function (str) {
			str = str.replace(/&lt;/g, '<').replace(/&gt;/g, '>');
			str = str.replace(/&nbsp;/g, '\xA0'); // Decimal 160, non-breaking-space
			str = str.replace(/&quot;/g, '\x22');
			str = str.replace(/&#39;/g, "'");
			str = str.replace(/&amp;/g, '&');
			return str;
		},

		/**
		 * Perform HTML encoding of some given string
		 */
		htmlEncode: function (str) {
			if (typeof str !== 'string') {
				str = str.toString();
			}
			str = str.replace(/&/g, '&amp;');
			str = str.replace(/</g, '&lt;').replace(/>/g, '&gt;');
			str = str.replace(/\xA0/g, '&nbsp;'); // Decimal 160, non-breaking-space
			str = str.replace(/\x22/g, '&quot;'); // \x22 means '"'
			return str;
		},

		/**
		 * Copies all the properties of config to obj.
		 * @param Object obj The receiver of the properties
		 * @param Object config The source of the properties
		 * @param Object defaults A different object that will also be applied for default values
		 * @return Object obj
		 */
		apply: function (object, config, defaults){
		    if (defaults){
			Util.apply(object, defaults);
		    }
		    if (object && typeof object === 'object' && config && typeof config === 'object'){
			for (var property in config) {
			    object[property] = config[property];
			}
		    }
		    return object;
		},

		/**
		 * Copies all the properties of config to object if they don't already exist.
		 *
		 * @param Object object The receiver of the properties
		 * @param Object config The source of the properties
		 * @return Object object
		 */
		applyIf: function (object, config) {
		    if (object && typeof object === 'object' && config && typeof config === 'object') {
			for (var property in config){
			    if (typeof object[property] === 'undefined') {
				object[property] = config[property];
			    }
			}
		    }
		    return object;
		}
	};

	return Util;

}();
