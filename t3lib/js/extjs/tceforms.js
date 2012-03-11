/***************************************************************
 * extJS for TCEforms
 *
 * Copyright notice
 *
 * (c) 2009-2011 Steffen Kamper <info@sk-typo3.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

Ext.ns('TYPO3');

	// class to manipulate TCEFORMS
TYPO3.TCEFORMS = {

	init: function() {
		Ext.QuickTips.init();

		this.update();
	},

	update: function() {
		this.convertDateFieldsToDatePicker();
		this.convertTextareasResizable();
	},

	convertDateFieldsToDatePicker: function() {
		var dateFields = Ext.select("input[id^=tceforms-date]"), minDate, maxDate, lowerMatch, upperMatch;
		dateFields.each(function(element) {
			var index = element.dom.id.match(/tceforms-datefield-/) ? 0 : 1;
			var format = TYPO3.settings.datePickerUSmode ? TYPO3.settings.dateFormatUS : TYPO3.settings.dateFormat;
			var datepicker = element.next('span'), menu;

			// check for daterange
			var lowerMatch = element.dom.className.match(/lower-(\d+)\b/);
			minDate = Ext.isArray(lowerMatch) ? new Date(lowerMatch[1] * 1000) : null;
			var upperMatch = element.dom.className.match(/upper-(\d+)\b/);
			maxDate = Ext.isArray(upperMatch) ? new Date(upperMatch[1] * 1000) : null;

			if (index === 0) {
				menu = new Ext.menu.DateMenu({
					id: 'p' + element.dom.id,
					format: format[index],
					value: Date.parseDate(element.dom.value, format[index]),
					minDate: minDate,
					maxDate: maxDate,
					handler: function(picker, date){
						var relElement = Ext.getDom(picker.ownerCt.id.substring(1));
						relElement.value = date.format(format[index]);
						if (Ext.isFunction(relElement.onchange)) {
							relElement.onchange.call(relElement);
						}
					},
					listeners: {
						beforeshow: function(obj) {
							var relElement = Ext.getDom(obj.picker.ownerCt.id.substring(1));
							if (relElement.value) {
								obj.picker.setValue(Date.parseDate(relElement.value, format[index]));
							}
						}
					}
				});
			} else {
				menu = new Ext.ux.menu.DateTimeMenu({
					id: 'p' + element.dom.id,
					format: format[index],
					value: Date.parseDate(element.dom.value, format[index]),
					minDate: minDate,
					maxDate: maxDate,
					listeners: {
						beforeshow: function(obj) {
							var relElement = Ext.getDom(obj.picker.ownerCt.id.substring(1));
							if (relElement.value) {
								obj.picker.setValue(Date.parseDate(relElement.value, format[index]));
							}
						},
						select: function(picker) {
							var relElement = Ext.getDom(picker.ownerCt.id.substring(1));
							relElement.value = picker.getValue().format(format[index]);
							if (Ext.isFunction(relElement.onchange)) {
								relElement.onchange.call(relElement);
							}
						}
					}
				});
			}

			datepicker.removeAllListeners();
			datepicker.on('click', function(){
				menu.show(datepicker);
			});
		});
	},

	convertTextareasResizable: function() {
		var textAreas = Ext.select("textarea[id^=tceforms-textarea-]");
		textAreas.each(function(element) {
			if (TYPO3.settings.textareaFlexible) {
				var elasticTextarea = new Ext.ux.elasticTextArea().applyTo(element.dom.id, {
					minHeight: 50,
					maxHeight: TYPO3.settings.textareaMaxHeight
				});
			}
			if (TYPO3.settings.textareaResize) {
				element.addClass('resizable');
				var dwrapped = new Ext.Resizable(element.dom.id, {
					minWidth:  300,
					minHeight: 50,
					dynamic:   true
				});
			}
		});
	}

}
Ext.onReady(TYPO3.TCEFORMS.init, TYPO3.TCEFORMS);

	// Fix for slider TCA control in IE9
Ext.override(Ext.dd.DragTracker, {
	onMouseMove:function (e, target) {
		var isIE9 = Ext.isIE && (/msie 9/.test(navigator.userAgent.toLowerCase())) && document.documentMode != 6;
		if (this.active && Ext.isIE && !isIE9 && !e.browserEvent.button) {
			e.preventDefault();
			this.onMouseUp(e);
			return;
		}
		e.preventDefault();
		var xy = e.getXY(), s = this.startXY;
		this.lastXY = xy;
		if (!this.active) {
			if (Math.abs(s[0] - xy[0]) > this.tolerance || Math.abs(s[1] - xy[1]) > this.tolerance) {
				this.triggerStart(e);
			} else {
				return;
			}
		}
		this.fireEvent('mousemove', this, e);
		this.onDrag(e);
		this.fireEvent('drag', this, e);
	}
});