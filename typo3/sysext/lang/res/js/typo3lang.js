/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Dominique Feyer <dominique.feyer@reelpeek.net>
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

Ext.ns('TYPO3.l10n');

TYPO3.l10n = {

	localize: function(label, replace, plural) {
		if (typeof TYPO3.lang[label] === 'undefined') return false;

		var i = plural || 0,
				translationUnit = TYPO3.lang[label],
				label = null, regexp = null;

		// Get localized label
		if (Ext.isString(translationUnit)) {
			label = translationUnit;
		} else {
			label = translationUnit[i]['target'];
		}

		// Replace
		if (typeof replace !== 'undefined') {
			for (key in replace) {
				console.log('%' + key + '|%s');
				regexp = new RegExp('%' + key + '|%s');
				label = label.replace(regexp, replace[key]);
			}
		}

		return label;
	}

}