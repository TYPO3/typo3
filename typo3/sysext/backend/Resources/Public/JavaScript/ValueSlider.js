/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Steffen Kamper <steffen@typo3.org>
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

Ext.ns('TYPO3.Components');

TYPO3.Components.TcaValueSlider = Ext.extend(Ext.slider.SingleSlider, {
	itemName: null,
	getField: null,
	changeCallback: null,
	valueItems: null,
	itemElement: null,
	elementType: null,

	initComponent: function() {
		var items, step, n;
		var step = this.increment || 1;
		if (step < 1) {
			this.type = 'float';
			this.increment = 1;
			this.floatValue = 1 / step;
			this.maxValue *= this.floatValue;
		}

		Ext.apply(this, {
			minValue: this.minValue || 0,
			maxValue: this.maxValue || 10000,
			keyIncrement: step,
			increment: step,
			type: this.type,
			plugins: new Ext.slider.Tip({
				getText: function(thumb) {
					return thumb.slider.renderValue(thumb.value);
				}
			}),
			listeners: {
				beforerender: function(slider) {
					var items = Ext.query(this.elementType);
					items.each(function(item) {
						var n = item.getAttribute('name');
						if (n == this.itemName) {
							this.itemElement = item;
						}
					}, this);

					if (this.elementType == 'select') {
						this.minValue = 0;
						this.maxValue = this.itemElement.options.length - 1;
						step = 1;
					}
				},
				changecomplete: function(slider, newValue, thumb) {
					if (slider.itemName) {
						if (slider.elementType == 'input') {
							slider.itemElement.value = slider.renderValue(thumb.value);
						}
						if (slider.elementType == 'select') {
							slider.itemElement.options[thumb.value].selected = '1';
						}
					}
					if (slider.getField) {
						eval(slider.getField);
					}
					if (slider.changeCallback) {
						eval(slider.changeCallback);
					}
				},
				scope: this
			}
		});
		TYPO3.Components.TcaValueSlider.superclass.initComponent.call(this);
	},

	/**
	* Render value for tooltip
	*
	* @param {string} value
	* @return string
	*/
	renderValue: function(value) {
		switch (this.type) {
			case 'array':
				return this.itemElement.options[value].text;
			break;
			case 'time':
				return this.renderValueFromTime(value);
			break;
			case 'float':
				return this.renderValueFromFloat(value);
			break;
			case 'int':
			default:
				return value;
		}
	},

	/**
	* Render value for tooltip as float
	*
	* @param {string} value
	* @return string
	*/
	renderValueFromFloat: function(value) {
		var v = value / this.floatValue;
		return v;
	},

	/**
	* Render value for tooltip as time
	*
	* @param {string} value
	* @return string
	*/
	renderValueFromTime: function(value) {
		var hours = Math.floor(value / 3600);
		var rest = value - (hours * 3600);
		var minutes = Math.round(rest / 60);
		minutes = minutes < 10 ? '0' + minutes : minutes;
		return hours + ':' + minutes;
	}

});

Ext.reg('TYPO3.Components.TcaValueSlider', TYPO3.Components.TcaValueSlider);