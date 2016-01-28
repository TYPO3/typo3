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
 * Module: TYPO3/CMS/Lang/Lang
 * Transforms the TYPO3.lang object into a flat object
 *
 * `TYPO3.lang.foo[0].target = 'blah'` becomes `TYPO3.lang['foo'] = 'blah'`
 */
define(['jquery'], function($) {
	/**
	 *
	 * @type {{}}
	 * @exports TYPO3/CMS/Lang/Lang
	 */
	var Lang = {};

	/**
	 *
	 */
	Lang.convertToOneDimension = function() {
		var originalLangObject = $.extend(true, {}, TYPO3.lang);
		TYPO3.lang = [];
		$.each(originalLangObject, function(index, value) {
			if (typeof value === "object") {
				TYPO3.lang[index] = value[0].target || value[0].source;
			} else {
				TYPO3.lang[index] = value;
			}
		});

		delete originalLangObject;
	};

	Lang.convertToOneDimension();
	return TYPO3.lang;
});
