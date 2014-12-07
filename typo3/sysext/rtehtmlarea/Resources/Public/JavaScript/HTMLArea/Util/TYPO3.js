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
 * HTMLArea.util.TYPO3: Utility functions for dealing with tabs and inline elements in TYPO3 forms
 ***************************************************/
define('TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/TYPO3',
	['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent'],
	function (UserAgent) {

	return {

		/**
		 * Simplify the array of nested levels. Create an indexed array with the correct names of the elements.
		 *
		 * @param	object		nested: The array with the nested levels
		 * @return	object		The simplified array
		 * @author	Oliver Hader <oh@inpublica.de>
		 */
		simplifyNested: function(nested) {
			var i, type, level, elementId, max, simplifiedNested=[],
				elementIdSuffix = {
					tab: '-DIV',
					inline: '_fields',
					flex: '-content'
				};
			if (nested && nested.length) {
				if (nested[0][0]=='inline') {
					nested = inline.findContinuedNestedLevel(nested, nested[0][1]);
				}
				for (i = 0, max=nested.length; i < max; i++) {
					type = nested[i][0];
					level = nested[i][1];
					elementId = level + elementIdSuffix[type];
					if (document.getElementById(elementId)) {
						simplifiedNested.push(elementId);
					}
				}
			}
			return simplifiedNested;
		},

		/**
		 * Access an inline relational element or tab menu and make it "accessible".
		 * If a parent or ancestor object has the style "display: none", offsetWidth & offsetHeight are '0'.
		 *
		 * @params array parentElements: array of parent elements id's; note that this input array will be modified
		 * @params object callbackFunc: A function to be called, when the embedded objects are "accessible".
		 * @params array args: array of arguments
		 * @return object An object returned by the callbackFunc.
		 * @author Oliver Hader <oh@inpublica.de>
		 */
		accessParentElements: function (parentElements, callbackFunc, args) {
			var result = {};
			if (parentElements.length) {
				var currentElement = parentElements.pop();
				currentElement = document.getElementById(currentElement);
				var actionRequired = (currentElement.style.display === 'none');
				if (actionRequired) {
					var visibility = currentElement.style.visibility;
					var position = currentElement.style.position;
					var top = currentElement.style.top;
					var display = currentElement.style.display;
					var className = currentElement.parentNode.className;
					currentElement.style.visibility = 'hidden';
					currentElement.style.position = 'absolute';
					currentElement.style.top = '-10000px';
					currentElement.style.display = '';
					currentElement.parentNode.className = '';
				}
				result = this.accessParentElements(parentElements, callbackFunc, args);
				if (actionRequired) {
					currentElement.style.visibility = visibility;
					currentElement.style.position = position;
					currentElement.style.top = top;
					currentElement.style.display = display;
					currentElement.parentNode.className = className;
				}
			} else {
				result = eval(callbackFunc);
			}
			return result;
		},

		/**
		 * Check if all elements in input array are currently displayed
		 *
		 * @param array elements: array of element id's
		 * @return boolean true if all elements are displayed
		 */
		allElementsAreDisplayed: function(elements) {
			var allDisplayed = true;
			for (var i = elements.length; --i >= 0;) {
				allDisplayed = document.getElementById(elements[i]).style.display !== 'none';
				if (!allDisplayed) {
					break;
				}
			}
			return allDisplayed;
		},

		/**
		 * Get current size of window
		 *
		 * @return object width and height of window
		 */
		getWindowSize: function () {
			if (UserAgent.isIE) {
				var body = document.body, html = document.documentElement;
				var size = {
					width: document.body.clientWidth,
					height: Math.max( body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight)
				};
			} else {
				var size = {
					width: window.innerWidth,
					height: window.innerHeight
				};
			}
			// Subtract the docheader height from the calculated window height
			var docHeader = document.getElementById('typo3-docheader');
			if (docHeader) {
				size.height -= docHeader.offsetHeight;
			}
			return size;
		}
	}
});
