/***************************************************************
*  Copyright notice
*
*  (c) 2011 Tobias Liebig <tobias.liebig@typo3.org>
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

Ext.onReady(function() {
	// Only use placeholder JavaScript fallback in Internet Explorer
	if (!Ext.isIE) {
		return;
	}

	// TODO rewrite in ExtJS
	$$('[placeholder]').each(function(el) {
		if (el.getAttribute('placeholder') != "") {
			el.observe('TYPO3:focus', function() {
				var input = Ext.get(this);
				if (this.getValue() == this.getAttribute('placeholder')) {
					this.setValue('');
					input.removeClass('placeholder');
				}
			});
			el.observe('focus', function() { el.fire('TYPO3:focus'); });
			el.observe('TYPO3:blur', function() {
				var input = Ext.get(this);
				if (input.getValue() == '' || this.getValue() == this.getAttribute('placeholder')) {
					this.setValue(this.getAttribute('placeholder'));
					input.addClass('placeholder');
				}
			});
			el.observe('blur', function() { el.fire('TYPO3:blur'); });
			el.fire('TYPO3:blur');
		}
	});
});