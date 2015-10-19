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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/String
 * String utilities
 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/String
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent'],
	function (UserAgent) {

	// Create the ruler
	if (!document.getElementById('htmlarea-ruler')) {
		// Insert the css rule in the stylesheet
		var styleSheet = document.styleSheets[0];
		var selector = '#htmlarea-ruler';
		var style = 'visibility: hidden; white-space: nowrap;';
		var rule = selector + ' { ' + style + ' }';
		if (!UserAgent.isIEBeforeIE9) {
			try {
				styleSheet.insertRule(rule, styleSheet.cssRules.length);
			} catch (e) {}
		} else {
			styleSheet.addRule(selector, style);
		}
		//Insert the ruler on the document
		var ruler = document.createElement('span');
		ruler.setAttribute('id', 'htmlarea-ruler');
		document.body.appendChild(ruler);
	}

	/**
	 * Get the visual length of a string
	 */
	String.prototype.visualLength = function() {
		var ruler = document.getElementById('htmlarea-ruler');
		ruler.innerHTML = this;
		return ruler.offsetWidth;
	};

	/**
	 * Set an ellipsis on a string
	 */
	String.prototype.ellipsis = function(length) {
		var temp = this;
		var trimmed = this;
		if (temp.visualLength() > length) {
			trimmed += "...";
			while (trimmed.visualLength() > length) {
				temp = temp.substring(0, temp.length-1);
					trimmed = temp + "...";
			}
		}
		return trimmed;
	};
});
