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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util
 * UTILITY FUNCTIONS
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent'],
	function (UserAgent) {

	/**
	 *
	 * @type {{htmlDecode: Function, htmlEncode: Function, emptyFunction: Function, apply: Function, applyIf: Function, inherit: Function, scrollBarWidth: null, getScrollBarWidth: Function, testCssPropertySupport: Function}}
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util
	 */
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
		 * Empty function
		 */
		emptyFunction: function () {},

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
		},

		/**
		 * Simple inheritance
		 * subClass inherits from superClass
		 *
		 * @param Object subClass The class that inherits
		 * @param Object superClass The source of the properties
		 * @return Object the subClass
		 */
		inherit: function (subClass, superClass) {
		    	var Construct = Util.emptyFunction;
		    	Construct.prototype = superClass.prototype;
		    	subClass.prototype = new Construct;
		    	subClass.prototype.constructor = subClass;
			subClass.super = superClass;
			return subClass;
		},

		/**
		 * Width of the browser scrollbar
		 */
		scrollBarWidth: null,

		/**
		 * Utility method for getting the width of the browser scrollbar. This can differ depending on
		 * operating system settings, such as the theme or font size.
		 *
		 * @return integer The width of the scrollbar.
		 */
		getScrollBarWidth: function (){
			if (Util.scrollBarWidth === null) {
				// Append a div, do calculation and then remove it
				var div = document.createElement('div');
				div.style.cssText = 'position:absolute!important;left:-10000px;top:-10000px;visibility:hidden;width:100px;height:50px;overflow:hidden;';
				div = document.body.appendChild(div);
				var innerDiv = document.createElement('div');
				innerDiv.style.height = '200px';
				innerDiv = div.appendChild(innerDiv);
				var w1 = innerDiv.offsetWidth;
				div.style.overflow = (UserAgent.isWebKit || UserAgent.isGecko) ? 'auto' : 'scroll';
				var w2 = innerDiv.offsetWidth;
				div.parentNode.removeChild(div);
				// Need to add 2 to ensure we leave enough space
				Util.scrollBarWidth = w1 - w2 + 2;
		    }
		    return Util.scrollBarWidth;
		},

		/**
		 * Test whether a css property is supported by the browser
		 *
		 * @param object element: the DOM element on which to test
		 * @param string property: the CSSS property to test
		 * @param value value: the value to test
		 * @return boolean true if the property is supported
		 */
		testCssPropertySupport: function (element, property, value) {
			var style = element.style;
			if (!(property in style)) {
				return false;
			}
			var before = style[property];
			try {
				style[property] = value;
			} catch (e) {}
			var after = style[property];
			style[property] = before;
			return before !== after;
		}
	};

	return Util;

});
