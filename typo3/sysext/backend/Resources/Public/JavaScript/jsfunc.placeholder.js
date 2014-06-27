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