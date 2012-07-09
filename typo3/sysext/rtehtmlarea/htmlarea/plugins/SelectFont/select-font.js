/***************************************************************
*  Copyright notice
*
*  (c) 2008-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * SelectFont Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.SelectFont = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		this.buttonsConfiguration = this.editorConfiguration.buttons;
		this.disablePCexamples = this.editorConfiguration.disablePCexamples;
			// Font formating will use the style attribute
		if (this.getPluginInstance('TextStyle')) {
			this.getPluginInstance('TextStyle').addAllowedAttribute('style');
			this.allowedAttributes = this.getPluginInstance('TextStyle').allowedAttributes;
		}
		if (this.getPluginInstance('InlineElements')) {
			this.getPluginInstance('InlineElements').addAllowedAttribute('style');
			if (!this.allowedAllowedAttributes) {
				this.allowedAttributes = this.getPluginInstance('InlineElements').allowedAttributes;
			}
		}
		if (this.getPluginInstance('BlockElements')) {
			this.getPluginInstance('BlockElements').addAllowedAttribute('style');
		}
		if (!this.allowedAttributes) {
			this.allowedAttributes = new Array('id', 'title', 'lang', 'xml:lang', 'dir', 'class', 'style');
			if (HTMLArea.isIEBeforeIE9) {
				this.allowedAttributes.push('className');
			}
		}
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '2.2',
			developer	: 'Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Stanislas Rolland',
			sponsor		: 'SJBR',
			sponsorUrl	: 'http://www.sjbr.ca/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the dropdowns
		 */
		Ext.each(this.dropDownList, function (dropDown) {
			var buttonId = dropDown[0];
			if (this.isButtonInToolbar(buttonId)) {
				var dropDownConfiguration = {
					id: buttonId,
					tooltip: this.localize(buttonId.toLowerCase()),
					storeUrl: this.buttonsConfiguration[dropDown[2]].dataUrl,
					action: 'onChange',
					tpl: this.disablePCexamples ? '' : '<tpl for="."><div ext:qtip="{value}" style="' + dropDown[3] + '" class="x-combo-list-item">{text}</div></tpl>'
				};
				if (this.buttonsConfiguration[dropDown[2]]) {
					if (this.editorConfiguration.buttons[dropDown[2]].width) {
						dropDownConfiguration.width = parseInt(this.editorConfiguration.buttons[dropDown[2]].width, 10);
					}
					if (this.editorConfiguration.buttons[dropDown[2]].listWidth) {
						dropDownConfiguration.listWidth = parseInt(this.editorConfiguration.buttons[dropDown[2]].listWidth, 10);
					}
					if (this.editorConfiguration.buttons[dropDown[2]].maxHeight) {
						dropDownConfiguration.maxHeight = parseInt(this.editorConfiguration.buttons[dropDown[2]].maxHeight, 10);
					}
				}
				this.registerDropDown(dropDownConfiguration);
			}
			return true;
		}, this);
		return true;
	 },
	/*
	 * The list of buttons added by this plugin
	 */
	dropDownList: [
		['FontName', null, 'fontstyle', 'font-family:{value};text-align:left;font-size:11px;'],
		['FontSize', null, 'fontsize', 'text-align:left;font-size:{value};']
	],
	/*
	 * Conversion object: button name to corresponding style property name
	 */
	styleProperty: {
		FontName: 'fontFamily',
		FontSize: 'fontSize'
	},
	/*
	 * Conversion object: button name to corresponding css property name
	 */
	cssProperty: {
		FontName: 'font-family',
		FontSize: 'font-size'
	},
	/*
	 * This funcion is invoked by the editor when it is being generated
	 */
	onGenerate: function () {
			// Monitor the dropdowns stores being loaded
		Ext.each(this.dropDownList, function (dropDown) {
			var select = this.getButton(dropDown[0]);
			if (select) {
				select.mon(select.getStore(), 'load', function () {
					var selection = this.editor.getSelection(),
						selectionEmpty = selection.isEmpty(),
						ancestors = selection.getAllAncestors(),
						endPointsInSameBlock = selection.endPointsInSameBlock();
					this.onUpdateToolbar(select, this.getEditorMode(), selectionEmpty, ancestors, endPointsInSameBlock);
				}, this);
			}
		}, this);
	},
	/*
	 * This function gets called when some font style or font size was selected from the dropdown lists
	 */
	onChange: function (editor, combo, record, index) {
		var param = combo.getValue();
		var 	element,
			fullNodeSelected = false;
		var range = editor.getSelection().createRange();
		var parent = editor.getSelection().getParentElement();
		var selectionEmpty = editor.getSelection().isEmpty();
		var statusBarSelection = editor.statusBar ? editor.statusBar.getSelection() : null;
		if (!selectionEmpty) {
			var fullySelectedNode = editor.getSelection().getFullySelectedNode();
			if (fullySelectedNode) {
				fullNodeSelected = true;
				parent = fullySelectedNode;
			}
		}
		if (selectionEmpty || fullNodeSelected) {
			element = parent;
				// Set the style attribute
			this.setStyle(element, combo.itemId, param);
				// Remove the span tag if it has no more attribute
			if (/^span$/i.test(element.nodeName) && !HTMLArea.DOM.hasAllowedAttributes(element, this.allowedAttributes)) {
				editor.getDomNode().removeMarkup(element);
			}
		} else if (statusBarSelection) {
			element = statusBarSelection;
				// Set the style attribute
			this.setStyle(element, combo.itemId, param);
				// Remove the span tag if it has no more attribute
			if (/^span$/i.test(element.nodeName) && !HTMLArea.DOM.hasAllowedAttributes(element, this.allowedAttributes)) {
				editor.getDomNode().removeMarkup(element);
			}
		} else if (editor.getSelection().endPointsInSameBlock()) {
			element = editor.document.createElement('span');
				// Set the style attribute
			this.setStyle(element, combo.itemId, param);
				// Wrap the selection with span tag with the style attribute
			editor.getDomNode().wrapWithInlineElement(element, range);
			if (!HTMLArea.isIEBeforeIE9) {
				range.detach();
			}
		}
		return false;
	},
	/*
	 * This function sets the style attribute on the element
	 *
	 * @param	object	element: the element on which the style attribute is to be set
	 * @param	string	buttonId: the button being processed
	 * @param	string	value: the value to be assigned
	 *
	 * @return	void
	 */
	setStyle: function (element, buttonId, value) {
		element.style[this.styleProperty[buttonId]] = (value && value !== 'none') ? value : '';
			// In IE, we need to remove the empty attribute in order to unset it
		if (Ext.isIE && (!value || value == 'none')) {
			element.style.removeAttribute(this.styleProperty[buttonId], false);
		}
		if (Ext.isOpera) {
				// Opera 9.60 replaces single quotes with double quotes
			element.style.cssText = element.style.cssText.replace(/\"/g, "\'");
				// Opera 9.60 removes from the list of fonts any fonts that are not installed on the client system
				// If the fontFamily property becomes empty, it is broken and cannot be reset/unset
				// We remove it using cssText
			if (!/\S/.test(element.style[this.styleProperty[buttonId]])) {
				element.style.cssText = element.style.cssText.replace(/font-family: /gi, '');
			}
		}
	},
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar: function (select, mode, selectionEmpty, ancestors, endPointsInSameBlock) {
		var editor = this.editor;
		if (mode === 'wysiwyg' && editor.isEditable()) {
			var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
			var parentElement = statusBarSelection ? statusBarSelection : editor.getSelection().getParentElement();
			var value = parentElement.style[this.styleProperty[select.itemId]];
			if (!value) {
				if (!HTMLArea.isIEBeforeIE9) {
					if (editor.document.defaultView && editor.document.defaultView.getComputedStyle(parentElement, null)) {
						value = editor.document.defaultView.getComputedStyle(parentElement, null).getPropertyValue(this.cssProperty[select.itemId]);
					}
				} else {
					value = parentElement.currentStyle[this.styleProperty[select.itemId]];
				}
			}
			var store = select.getStore();
			var index = -1;
			if (value) {
				index = store.findBy(
					function (record, id) {
						return record.get('value').replace(/[\"\']/g, '') == value.replace(/, /g, ',').replace(/[\"\']/g, '');
					}
				);
			}
			if (index != -1) {
				select.setValue(store.getAt(index).get('value'));
			} else if (store.getCount()) {
				select.setValue('none');
			}
			select.setDisabled(!endPointsInSameBlock || (selectionEmpty && /^body$/i.test(parentElement.nodeName)));
		}
	}
});
