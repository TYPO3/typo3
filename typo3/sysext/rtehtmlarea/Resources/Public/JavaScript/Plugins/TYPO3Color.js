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
 * TYPO3 Color Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Color',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ColorPalette',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, UserAgent, Dom, Color, ColorPalette, Util) {

	var TYPO3Color = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(TYPO3Color, Plugin);
	Util.apply(TYPO3Color.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {
			this.buttonsConfiguration = this.editorConfiguration.buttons;
			this.colorsConfiguration = this.editorConfiguration.colors;
			this.disableColorPicker = this.editorConfiguration.disableColorPicker;
				// Coloring will use the style attribute
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
			/*
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '4.3',
				developer	: 'Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca/',
				copyrightOwner	: 'Stanislas Rolland',
				sponsor		: 'SJBR',
				sponsorUrl	: 'http://www.sjbr.ca/',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);
			/*
			 * Registering the buttons
			 */
			var buttonList = this.buttonList, buttonId;
			for (var i = 0; i < buttonList.length; ++i) {
				var button = buttonList[i];
				buttonId = button[0];
				var buttonConfiguration = {
					id		: buttonId,
					tooltip		: this.localize(buttonId),
					iconCls		: 'htmlarea-action-' + button[2],
					action		: 'onButtonPress',
					hotKey		: (this.buttonsConfiguration[button[1]] ? this.buttonsConfiguration[button[1]].hotKey : null),
					dialog		: true
				};
				this.registerButton(buttonConfiguration);
			}
			return true;
		 },
		/*
		 * The list of buttons added by this plugin
		 */
		buttonList: [
			['ForeColor', 'textcolor', 'color-foreground'],
			['HiliteColor', 'bgcolor', 'color-background']
		],
		/*
		 * Conversion object: button name to corresponding style property name
		 */
		styleProperty: {
			ForeColor	: 'color',
			HiliteColor	: 'backgroundColor'
		},
		colors: [
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
		/*
		 * This function gets called when the button was pressed.
		 *
		 * @param	object		editor: the editor instance
		 * @param	string		id: the button id or the key
		 * @param	object		target: the target element of the contextmenu event, when invoked from the context menu
		 *
		 * @return	boolean		false if action is completed
		 */
		onButtonPress: function (editor, id, target) {
				// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			var element = this.editor.getSelection().getParentElement();
			this.openDialogue(
				buttonId + '_title',
				{
					element: element,
					buttonId: buttonId
				},
				this.getWindowDimensions(
					{
						width: 350,
						height: 350
					},
					buttonId
				),
				this.buildItemsConfig(element, buttonId),
				this.setColor
			);
		},
		/*
		 * Build the window items config
		 */
		buildItemsConfig: function (element, buttonId) {
			var itemsConfig = [];
			var paletteItems = [];
				// Standard colors palette (boxed)
			if (!this.disableColorPicker) {
				paletteItems.push({
					xtype: 'container',
					items: {
						xtype: 'colorpalette',
						itemId: 'color-palette',
						colors: this.colors,
						cls: 'color-palette',
						value: (element && element.style[this.styleProperty[buttonId]]) ? Color.colorToHex(element.style[this.styleProperty[buttonId]]).substr(1, 6) : '',
						allowReselect: true,
						listeners: {
							select: {
								fn: this.onSelect,
								scope: this
							}
						}
					}
				});
			}
				// Custom colors palette (boxed)
			if (this.colorsConfiguration) {
				paletteItems.push({
					xtype: 'container',
					items: {
						xtype: 'colorpalette',
						itemId: 'custom-colors',
						cls: 'htmlarea-custom-colors',
						colors: this.colorsConfiguration,
						value: (element && element.style[this.styleProperty[buttonId]]) ? Color.colorToHex(element.style[this.styleProperty[buttonId]]).substr(1, 6) : '',
						tpl: new Ext.XTemplate(
							'<tpl for="."><a href="#" class="color-{1}" hidefocus="on"><em><span style="background:#{1}" unselectable="on">&#160;</span></em><span unselectable="on">{0}<span></a></tpl>'
						),
						allowReselect: true,
						listeners: {
							select: {
								fn: this.onSelect,
								scope: this
							}
						}
					}
				});
			}
			itemsConfig.push({
				xtype: 'container',
				layout: 'hbox',
				items: paletteItems
			});
			itemsConfig.push({
				xtype: 'displayfield',
				itemId: 'show-color',
				cls: 'show-color',
				width: 60,
				height: 22,
				helpTitle: this.localize(buttonId)
			});
			itemsConfig.push({
				itemId: 'color',
				cls: 'color',
				width: 60,
				minValue: 0,
				value: (element && element.style[this.styleProperty[buttonId]]) ? Color.colorToHex(element.style[this.styleProperty[buttonId]]).substr(1, 6) : '',
				enableKeyEvents: true,
				fieldLabel: this.localize(buttonId),
				helpTitle: this.localize(buttonId),
				listeners: {
					change: {
						fn: this.onChange,
						scope: this
					},
					afterrender: {
						fn: this.onAfterRender,
						scope: this
					}
				}
			});
			return {
				xtype: 'fieldset',
				title: this.localize('color_title'),
				defaultType: 'textfield',
				labelWidth: 175,
				defaults: {
					helpIcon: false
				},
				items: itemsConfig
			};
		},
		/*
		 * On select handler: set the value of the color field, display the new color and update the other palette
		 */
		onSelect: function (palette, color) {
			this.dialog.find('itemId', 'color')[0].setValue(color);
			this.showColor(color);
			if (palette.getItemId() == 'color-palette') {
				var customPalette = this.dialog.find('itemId', 'custom-colors')[0];
				if (customPalette) {
					customPalette.deSelect();
				}
			} else {
				var standardPalette = this.dialog.find('itemId', 'color-palette')[0];
				if (standardPalette) {
					standardPalette.deSelect();
				}
			}
		},
		/*
		 * Display the selected color
		 */
		showColor: function (color) {
			if (color) {
				var newColor = color;
				if (newColor.indexOf('#') == 0) {
					newColor = newColor.substr(1);
				}
				this.dialog.find('itemId', 'show-color')[0].el.setStyle('backgroundColor', Color.colorToHex(parseInt(newColor, 16)));
			}
		},
		/*
		 * On change handler: display the new color and select it in the palettes, if it exists
		 */
		onChange: function (field, value) {
			if (value) {
				var color = value.toUpperCase();
				this.showColor(color);
				var standardPalette = this.dialog.find('itemId', 'color-palette')[0];
				if (standardPalette) {
					standardPalette.select(color);
				}
				var customPalette = this.dialog.find('itemId', 'custom-colors')[0];
				if (customPalette) {
					customPalette.select(color);
				}
			}
		},
		/*
		 * On after render handler: display the color
		 */
		onAfterRender: function (field) {
			var value = field.getValue();
			if (typeof value === 'string' && value.length > 0) {
				this.showColor(value);
			}
		},
		/*
		 * Open the dialogue window
		 *
		 * @param	string		title: the window title
		 * @param	object		arguments: some arguments for the handler
		 * @param	integer		dimensions: the opening width of the window
		 * @param	object		tabItems: the configuration of the tabbed panel
		 * @param	function	handler: handler when the OK button if clicked
		 *
		 * @return	void
		 */
		openDialogue: function (title, arguments, dimensions, items, handler) {
			if (this.dialog) {
				this.dialog.close();
			}
			this.dialog = new Ext.Window({
				title: this.localize(title),
				arguments: arguments,
				cls: 'htmlarea-window',
				border: false,
				width: dimensions.width,
				height: dimensions.height,
				autoScroll: true,
				iconCls: this.getButton(arguments.buttonId).iconCls,
				listeners: {
					close: {
						fn: this.onClose,
						scope: this
					}
				},
				items: {
					xtype: 'container',
					layout: 'form',
					style: {
						width: '95%'
					},
					defaults: {
						labelWidth: 150
					},
					items: items
				},
				buttons: [
					this.buildButtonConfig('OK', handler),
					this.buildButtonConfig('Cancel', this.onCancel)
				]
			});
			this.show();
		},
		/*
		 * Set the color and close the dialogue
		 */
		setColor: function(button, event) {
			this.restoreSelection();
			var buttonId = this.dialog.arguments.buttonId;
			var color = this.dialog.find('itemId', 'color')[0].getValue();
			if (color) {
				if (color.indexOf('#') == 0) {
					color = color.substr(1);
				}
				color = Color.colorToHex(parseInt(color, 16));
			}
			var 	element,
				fullNodeSelected = false;
			var range = this.editor.getSelection().createRange();
			var parent = this.editor.getSelection().getParentElement();
			var selectionEmpty = this.editor.getSelection().isEmpty();
			var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
			if (!selectionEmpty) {
				var fullySelectedNode = this.editor.getSelection().getFullySelectedNode();
				if (fullySelectedNode) {
					fullNodeSelected = true;
					parent = fullySelectedNode;
				}
			}
			if (selectionEmpty || fullNodeSelected) {
				element = parent;
					// Set the color in the style attribute
				element.style[this.styleProperty[buttonId]] = color;
					// Remove the span tag if it has no more attribute
				if (/^span$/i.test(element.nodeName) && !Dom.hasAllowedAttributes(element, this.allowedAttributes)) {
					this.editor.getDomNode().removeMarkup(element);
				}
			} else if (statusBarSelection) {
				var element = statusBarSelection;
					// Set the color in the style attribute
				element.style[this.styleProperty[buttonId]] = color;
					// Remove the span tag if it has no more attribute
				if (/^span$/i.test(element.nodeName) && !Dom.hasAllowedAttributes(element, this.allowedAttributes)) {
					this.editor.getDomNode().removeMarkup(element);
				}
			} else if (color && this.editor.getSelection().endPointsInSameBlock()) {
				var element = this.editor.document.createElement('span');
					// Set the color in the style attribute
				element.style[this.styleProperty[buttonId]] = color;
				this.editor.getDomNode().wrapWithInlineElement(element, range);
			}
			this.close();
			event.stopEvent();
		},

		/**
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors, endPointsInSameBlock) {
			if (mode === 'wysiwyg' && this.editor.isEditable()) {
				var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null,
					parentElement = statusBarSelection ? statusBarSelection : this.editor.getSelection().getParentElement(),
					disabled = !endPointsInSameBlock || (selectionEmpty && /^body$/i.test(parentElement.nodeName));
				button.setInactive(!parentElement.style[this.styleProperty[button.itemId]]);
				button.setDisabled(disabled);
			}
		}
	});

	return TYPO3Color;

});
