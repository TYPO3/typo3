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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ColorPalette
 * Intercept Ext.ColorPalette.prototype.select
 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ColorPalette
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Color'],
	function (Color) {

	Ext.ColorPalette.prototype.select = Ext.ColorPalette.prototype.select.createInterceptor(Color.checkIfColorInPalette);
	/**
	 * Add deSelect method to Ext.ColorPalette
	 */
	Ext.override(Ext.ColorPalette, {
		deSelect: function () {
			if (this.el && this.value){
				this.el.child('a.color-' + this.value).removeClass('x-color-palette-sel');
				this.value = null;
			}
		}
	});
});
