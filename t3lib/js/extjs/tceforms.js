/***************************************************************
 * extJS for TCEforms
 *
 * $Id$
 *
 * Copyright notice
 *
 * (c) 2009-2010 Steffen Kamper <info@sk-typo3.de>
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

		this.convertDateFieldsToDatePicker();
		this.convertTextareasResizable();
	},

	convertDateFieldsToDatePicker: function() {
		var dateFields = Ext.select("input[id^=tceforms-date]");
		dateFields.each(function(element) {
			var index = element.dom.id.match(/tceforms-datefield-/) ? 0 : 1;
			var format = TYPO3.settings.datePickerUSmode ? TYPO3.settings.dateFormatUS : TYPO3.settings.dateFormat;

			var datepicker = element.next('span');
			var oldValue = Date.parseDate(element.dom.value, format[index]);

			var menu = new Ext.menu.DateMenu({
				id:			'p' + element.dom.id,
				format:		format[index],
				value:		oldValue,
				handler: 	function(picker, date){
					var relElement = Ext.getDom(picker.ownerCt.id.substring(1));
					if (index === 1) {
							//datetimefield, preserve time information
						date.setHours(oldValue.getHours());
						date.setMinutes(oldValue.getMinutes());
					}
					relElement.value = date.format(format[index]);
					if (Ext.isFunction(relElement.onchange)) {
						relElement.onchange.call(relElement);
					}
				},
				listeners:	{
					beforeshow:	function(obj) {
						var relElement = Ext.getDom(obj.picker.ownerCt.id.substring(1));
						if (relElement.value) {
							obj.picker.setValue(Date.parseDate(relElement.value, format[index]));
						}
					}
				}
			});

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
					maxHeight: TYPO3.settings.textareaMaxHeight,
					dynamic:   true
				});
			}
		});
	}

}
Ext.onReady(TYPO3.TCEFORMS.init, TYPO3.TCEFORMS);