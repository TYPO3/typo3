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
 * SelectFont Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, UserAgent, Dom, Util) {

	var SelectFont = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(SelectFont, Plugin);
	Util.apply(SelectFont.prototype, {

		/**
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
			}

			/**
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

			/**
			 * Registering the dropdowns
			 */
			var dropDown, buttonId;
			for (var i = this.dropDownList.length; --i >= 0;) {
				dropDown = this.dropDownList[i];
				buttonId = dropDown[0];
				if (this.isButtonInToolbar(buttonId) && this.buttonsConfiguration[dropDown[2]].dataUrl) {
					var dropDownConfiguration = {
						id: buttonId,
						tooltip: this.localize(buttonId.toLowerCase()),
						action: 'onChange'
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
			}
			return true;
		 },

		/**
		 * The list of buttons added by this plugin
		 */
		dropDownList: [
			['FontName', null, 'fontstyle', 'font-family:{value};text-align:left;font-size:11px;'],
			['FontSize', null, 'fontsize', 'text-align:left;font-size:{value};']
		],

		/**
		 * Conversion object: button name to corresponding style property name
		 */
		styleProperty: {
			FontName: 'fontFamily',
			FontSize: 'fontSize'
		},

		/**
		 * Conversion object: button name to corresponding css property name
		 */
		cssProperty: {
			FontName: 'font-family',
			FontSize: 'font-size'
		},

		/**
		 * This funcion is invoked by the editor when it is being generated
		 */
		onGenerate: function () {
			// Monitor the dropdowns stores being loaded
			for (var i = this.dropDownList.length; --i >= 0;) {
				var dropDown = this.dropDownList[i];
				var select = this.getButton(dropDown[0]);
				if (select) {
					this.editor.ajax.getJavascriptFile(this.buttonsConfiguration[dropDown[2]].dataUrl, function (settings, success, response) {
						if (success && response['responseJSON']) {
							for (var j = this.dropDownList.length; --j >= 0;) {
								var dropDown = this.dropDownList[j];
								if (settings['url'] === this.buttonsConfiguration[dropDown[2]].dataUrl) {
									var options = response['responseJSON']['options'];
									if (options) {
										var select = this.getButton(dropDown[0]);
										for (var k = 0, n = options.length; k < n; k++) {
											var title = options[k]['value'] === 'none' ? options[k]['text'] : options[k]['value'];
											var style = this.disablePCexamples ? '' : dropDown[3].replace(/\{value\}/g, options[k]['value']);
											select.addOption(options[k]['text'], options[k]['value'], title, style);
										}
										var selection = this.editor.getSelection(),
											selectionEmpty = selection.isEmpty(),
											ancestors = selection.getAllAncestors(),
											endPointsInSameBlock = selection.endPointsInSameBlock();
										this.onUpdateToolbar(select, this.getEditorMode(), selectionEmpty, ancestors, endPointsInSameBlock);
									}
									break;
								}
							}
						}
					}, this, 'json');
				}
			}
		},

		/**
		 * This function gets called when some font style or font size was selected from the dropdown lists
		 */
		onChange: function (editor, select) {
			var param = select.getValue();
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
				this.setStyle(element, select.itemId, param);
				// Remove the span tag if it has no more attribute
				if (/^span$/i.test(element.nodeName) && !Dom.hasAllowedAttributes(element, this.allowedAttributes)) {
					editor.getDomNode().removeMarkup(element);
				}
			} else if (statusBarSelection) {
				element = statusBarSelection;
				// Set the style attribute
				this.setStyle(element, select.itemId, param);
				// Remove the span tag if it has no more attribute
				if (/^span$/i.test(element.nodeName) && !Dom.hasAllowedAttributes(element, this.allowedAttributes)) {
					editor.getDomNode().removeMarkup(element);
				}
			} else if (editor.getSelection().endPointsInSameBlock()) {
				element = editor.document.createElement('span');
				// Set the style attribute
				this.setStyle(element, select.itemId, param);
				// Wrap the selection with span tag with the style attribute
				editor.getDomNode().wrapWithInlineElement(element, range);
				range.detach();
			}
			return false;
		},

		/**
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
			if (UserAgent.isIE && (!value || value == 'none')) {
				element.style.removeAttribute(this.styleProperty[buttonId], false);
			}
			if (UserAgent.isOpera) {
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

		/**
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (select, mode, selectionEmpty, ancestors, endPointsInSameBlock) {
			var editor = this.editor;
			if (mode === 'wysiwyg' && editor.isEditable()) {
				var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
				var parentElement = statusBarSelection ? statusBarSelection : editor.getSelection().getParentElement();
				var value = parentElement.style[this.styleProperty[select.itemId]];
				if (!value) {
					if (editor.document.defaultView && editor.document.defaultView.getComputedStyle(parentElement, null)) {
						value = editor.document.defaultView.getComputedStyle(parentElement, null).getPropertyValue(this.cssProperty[select.itemId]);
					}
				}
				var index = -1;
				if (value) {
					for (var i = 0, n = select.getCount(); i < n; i++) {
						if (select.getOptionValue(i).replace(/[\"\']/g, '') === value.replace(/, /g, ',').replace(/[\"\']/g, '')) {
							index = i;
							break;
						}
					}
				}
				if (index !== -1) {
					select.setValueByIndex(index);
				} else if (select.getCount()) {
					select.setValue('none');
				}
				select.setDisabled(!endPointsInSameBlock || (selectionEmpty && /^body$/i.test(parentElement.nodeName)));
			}
		}
	});

	return SelectFont;

});
