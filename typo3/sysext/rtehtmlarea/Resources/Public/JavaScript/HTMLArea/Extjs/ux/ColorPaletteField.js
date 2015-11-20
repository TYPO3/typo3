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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ColorPalette/ColorPaletteField
 * Color palette trigger field
 * Based on http://www.extjs.com/forum/showthread.php?t=89312
 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ColorPalette/ColorPaletteField
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ux/ColorMenu'],
	function (ColorMenu) {
		Ext.ux.form.ColorPaletteField = Ext.extend(Ext.form.TriggerField, {
			triggerClass: 'x-form-color-trigger',
			defaultColors: [
				'000000', '222222', '444444', '666666', '999999', 'BBBBBB', 'DDDDDD', 'FFFFFF',
				'660000', '663300', '996633', '003300', '003399', '000066', '330066', '660066',
				'990000', '993300', 'CC9900', '006600', '0033FF', '000099', '660099', '990066',
				'CC0000', 'CC3300', 'FFCC00', '009900', '0066FF', '0000CC', '663399', 'CC0099',
				'FF0000', 'FF3300', 'FFFF00', '00CC00', '0099FF', '0000FF', '9900CC', 'FF0099',
				'CC3333', 'FF6600', 'FFFF33', '00FF00', '00CCFF', '3366FF', '9933FF', 'FF00FF',
				'FF6666', 'FF6633', 'FFFF66', '66FF66', '00FFFF', '3399FF', '9966FF', 'FF66FF',
				'FF9999', 'FF9966', 'FFFF99', '99FF99', '99FFFF', '66CCFF', '9999FF', 'FF99FF',
				'FFCCCC', 'FFCC99', 'FFFFCC', 'CCFFCC', 'CCFFFF', '99CCFF', 'CCCCFF', 'FFCCFF'
			],
				// Whether or not the field background, text, or triggerbackgroud are set to the selected color
			colorizeFieldBackgroud: true,
			colorizeFieldText: true,
			colorizeTrigger: false,
			editable: true,
			initComponent: function () {
				Ext.ux.form.ColorPaletteField.superclass.initComponent.call(this);
				if (!this.colors) {
					this.colors = this.defaultColors;
				}
				this.addEvents(
					'select'
				);
			},
				// private
			validateBlur: function () {
				return !this.menu || !this.menu.isVisible();
			},
			setValue: function (color) {
				if (color) {
					if (this.colorizeFieldBackgroud) {
						this.el.applyStyles('background: #' + color  + ';');
					}
					if (this.colorizeFieldText) {
						this.el.applyStyles('color: #' + this.rgbToHex(this.invert(this.hexToRgb(color)))  + ';');
					}
					if (this.colorizeTrigger) {
						this.trigger.applyStyles('background-color: #' + color  + ';');
					}
				}
				return Ext.ux.form.ColorPaletteField.superclass.setValue.call(this, color);
			},
				// private
			onDestroy: function () {
				Ext.destroy(this.menu);
				Ext.ux.form.ColorPaletteField.superclass.onDestroy.call(this);
			},
				// private
			onTriggerClick: function () {
				if (this.disabled) {
					return;
				}
				if (this.menu == null) {
					this.menu = new Ext.ux.menu.HTMLAreaColorMenu({
						cls: 'htmlarea-color-menu',
						hideOnClick: false,
						colors: this.colors,
						colorsConfiguration: this.colorsConfiguration,
						value: this.getValue()
					});
				}
				this.onFocus();
				this.menu.show(this.el, "tl-bl?");
				this.menuEvents('on');
			},
				//private
			menuEvents: function (method) {
				this.menu[method]('select', this.onSelect, this);
				this.menu[method]('hide', this.onMenuHide, this);
				this.menu[method]('show', this.onFocus, this);
			},
			onSelect: function (m, d) {
				this.setValue(d);
				this.fireEvent('select', this, d);
				this.menu.hide();
			},
			onMenuHide: function () {
				this.focus(false, 60);
				this.menuEvents('un');
			},
			invert: function ( r, g, b ) {
				if( r instanceof Array ) { return this.invert.call( this, r[0], r[1], r[2] ); }
				return [255-r,255-g,255-b];
			},
			hexToRgb: function ( hex ) {
				return [ this.hexToDec( hex.substr(0, 2) ), this.hexToDec( hex.substr(2, 2) ), this.hexToDec( hex.substr(4, 2) ) ];
			},
			hexToDec: function( hex ) {
				var s = hex.split('');
				return ( ( this.getHCharPos( s[0] ) * 16 ) + this.getHCharPos( s[1] ) );
			},
			getHCharPos: function( c ) {
				var HCHARS = '0123456789ABCDEF';
				return HCHARS.indexOf( c.toUpperCase() );
			},
			rgbToHex: function( r, g, b ) {
				if( r instanceof Array ) { return this.rgbToHex.call( this, r[0], r[1], r[2] ); }
				return this.decToHex( r ) + this.decToHex( g ) + this.decToHex( b );
			},
			decToHex: function( n ) {
				var HCHARS = '0123456789ABCDEF';
				n = parseInt(n, 10);
				n = ( !isNaN( n )) ? n : 0;
				n = (n > 255 || n < 0) ? 0 : n;
				return HCHARS.charAt( ( n - n % 16 ) / 16 ) + HCHARS.charAt( n % 16 );
			}
		});
		Ext.reg('colorpalettefield', Ext.ux.form.ColorPaletteField);
});
