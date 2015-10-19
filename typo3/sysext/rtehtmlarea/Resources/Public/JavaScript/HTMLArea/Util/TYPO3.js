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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/TYPO3
 * HTMLArea.util.TYPO3: Utility functions for dealing with tabs and inline elements in TYPO3 forms
 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/TYPO3
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM'],
	function (UserAgent, Dom) {

	return {

		/**
		 * Simplify the array of nested levels. Create an indexed array with the correct names of the elements.
		 *
		 * @param	object		nested: The array with the nested levels
		 * @return	object		The simplified array
		 */
		simplifyNested: function(nested) {
			var i, type, level, elementId, max, simplifiedNested=[],
				elementIdSuffix = {
					tab: '-DIV',
					inline: '_div',
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
		 */
		accessParentElements: function (parentElements, callbackFunc, args) {
			var result = {};
			if (parentElements.length) {
				var currentElementId = parentElements.pop();
				var currentElement = document.getElementById(currentElementId);
				var actionRequired = (currentElementId.indexOf('-DIV') !== -1 && !Dom.hasClass(currentElement, 'active'))
					|| (currentElementId.indexOf('_div') !== -1 && !Dom.hasClass(currentElement, 'panel-visible'))
					|| (currentElement.style.display === 'none');
				if (actionRequired) {
					var visibility = currentElement.style.visibility;
					var position = currentElement.style.position;
					var top = currentElement.style.top;
					var display = currentElement.style.display;
					var className = currentElement.className;
					currentElement.style.visibility = 'hidden';
					currentElement.style.position = 'absolute';
					currentElement.style.top = '-10000px';
					currentElement.style.display = '';
					if (currentElementId.indexOf('-DIV') !== -1) {
						Dom.addClass(currentElement, 'active');
					} else if (currentElementId.indexOf('_div') !== -1) {
						Dom.addClass(currentElement, 'panel-visible');
					}
					currentElement.className = '';
				}
				result = this.accessParentElements(parentElements, callbackFunc, args);
				if (actionRequired) {
					currentElement.style.visibility = visibility;
					currentElement.style.position = position;
					currentElement.style.top = top;
					currentElement.style.display = display;
					currentElement.className = className;
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
				var element = document.getElementById(elements[i]);
				if (element) {
					if (element.style.display) {
						allDisplayed = element.style.display !== 'none';
					}
					if (elements[i].indexOf('-DIV') !== -1) {
						allDisplayed = allDisplayed && Dom.hasClass(element, 'active');
					} else if (elements[i].indexOf('_div') !== -1) {
						allDisplayed = allDisplayed && Dom.hasClass(element, 'panel-visible');
					}
				}
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
