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
 * AND-Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Color
 * Color utilities
 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Color
 */
define([], function () {

	return {

		/**
		 * Returns a rgb-style color from a number
		 */
		colorToRgb: function(v) {
			if (typeof(v) != 'number') {
				return v;
			}
			var r = v & 0xFF;
			var g = (v >> 8) & 0xFF;
			var b = (v >> 16) & 0xFF;
			return 'rgb(' + r + ',' + g + ',' + b + ')';
		},

		/**
		 * Returns hexadecimal color representation from a number or a rgb-style color.
		 */
		colorToHex: function(v) {
			if (typeof v === 'undefined' || v == null) {
				return '';
			}
			function hex(d) {
				return (d < 16) ? ('0' + d.toString(16)) : d.toString(16);
			};
			if (typeof(v) == 'number') {
				var b = v & 0xFF;
				var g = (v >> 8) & 0xFF;
				var r = (v >> 16) & 0xFF;
				return '#' + hex(r) + hex(g) + hex(b);
			}
			if (v.substr(0, 3) === 'rgb') {
				var re = /rgb\s*\(\s*([0-9]+)\s*,\s*([0-9]+)\s*,\s*([0-9]+)\s*\)/;
				if (v.match(re)) {
					var r = parseInt(RegExp.$1);
					var g = parseInt(RegExp.$2);
					var b = parseInt(RegExp.$3);
					return ('#' + hex(r) + hex(g) + hex(b)).toUpperCase();
				}
				return '';
			}
			if (v.substr(0, 1) === '#') {
				return v;
			}
			return '';
		},

		/**
		 * Select interceptor to ensure that the color exists in the palette before trying to select
		 */
		checkIfColorInPalette: function (color) {
				// Do not continue if the new color is not in the palette
			if (this.el && !this.el.child('a.color-' + color)) {
					// Remove any previous selection
				this.deSelect();
				return false;
			}
		}
	}
});
