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
*  This script is a modified version of a script published under the htmlArea License.
*  A copy of the htmlArea License may be found in the textfile HTMLAREA_LICENSE.txt.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Image Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.DefaultImage = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		this.baseURL = this.editorConfiguration.baseURL;
		this.pageTSConfiguration = this.editorConfiguration.buttons.image;
		if (this.pageTSConfiguration && this.pageTSConfiguration.properties && this.pageTSConfiguration.properties.removeItems) {
			this.removeItems = this.pageTSConfiguration.properties.removeItems.split(',');
			var layout = 0;
			var padding = 0;
			for (var i = 0, n = this.removeItems.length; i < n; ++i) {
				this.removeItems[i] = this.removeItems[i].replace(/(?:^\s+|\s+$)/g, '');
				if (/^(align|border|float)$/i.test(this.removeItems[i])) {
					++layout;
				}
				if (/^(paddingTop|paddingRight|paddingBottom|paddingLeft)$/i.test(this.removeItems[i])) {
					++padding;
				}
			}
			if (layout == 3) {
				this.removeItems.push('layout');
			}
			if (layout == 4) {
				this.removeItems.push('padding');
			}
			this.removeItems = new RegExp( '^(' + this.removeItems.join('|') + ')$', 'i');
		} else {
			this.removeItems = new RegExp( '^(none)$', 'i');
		}
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '2.3',
			developer	: 'Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Stanislas Rolland',
			sponsor		: 'SJBR',
			sponsorUrl	: 'http://www.sjbr.ca/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the button
		 */
		var buttonId = 'InsertImage';
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize('insertimage'),
			action		: 'onButtonPress',
			hotKey		: (this.pageTSConfiguration ? this.pageTSConfiguration.hotKey : null),
			dialog		: true,
			iconCls		: 'htmlarea-action-image-edit'
		};
		this.registerButton(buttonConfiguration);
		return true;
	 },
	/*
	 * Sets of default configuration values for dialogue form fields
	 */
	configDefaults: {
		combo: {
			editable: true,
			selectOnFocus: true,
			typeAhead: true,
			triggerAction: 'all',
			forceSelection: true,
			mode: 'local',
			valueField: 'value',
			displayField: 'text',
			helpIcon: true,
			tpl: '<tpl for="."><div ext:qtip="{value}" style="text-align:left;font-size:11px;" class="x-combo-list-item">{text}</div></tpl>'
		}
	},
	/*
	 * This function gets called when the button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress: function(editor, id) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		this.image = this.editor.getSelection().getParentElement();
		if (this.image && !/^img$/i.test(this.image.nodeName)) {
			this.image = null;
		}
		if (this.image) {
			this.parameters = {
				base: 		this.baseURL,
				url: 		this.image.getAttribute('src'),
				alt:		this.image.alt,
				border:		isNaN(parseInt(this.image.style.borderWidth)) ? '' : parseInt(this.image.style.borderWidth),
				align:		this.image.style.verticalAlign ? this.image.style.verticalAlign : '',
				paddingTop:	isNaN(parseInt(this.image.style.paddingTop)) ? '' : parseInt(this.image.style.paddingTop),
				paddingRight:	isNaN(parseInt(this.image.style.paddingRight)) ? '' : parseInt(this.image.style.paddingRight),
				paddingBottom:	isNaN(parseInt(this.image.style.paddingBottom)) ? '' : parseInt(this.image.style.paddingBottom),
				paddingLeft:	isNaN(parseInt(this.image.style.paddingLeft)) ? '' : parseInt(this.image.style.paddingLeft),
				cssFloat: 	HTMLArea.isIEBeforeIE9 ? this.image.style.styleFloat : this.image.style.cssFloat
			};
		} else {
			this.parameters = {
				base: 	this.baseURL,
				url: 	'',
				alt:	'',
				border:	'',
				align:	'',
				paddingTop:	'',
				paddingRight:	'',
				paddingBottom:	'',
				paddingLeft:	'',
				cssFloat: ''
			};
		}
			// Open dialogue window
		this.openDialogue(
			buttonId,
			this.getButton(buttonId).tooltip.title,
			this.getWindowDimensions(
				{
					width: 460,
					height:300
				},
				buttonId
			),
			this.buildTabItems()
		);
		return false;
	},
	/*
	 * Open the dialogue window
	 *
	 * @param	string		buttonId: the button id
	 * @param	string		title: the window title
	 * @param	integer		dimensions: the opening width of the window
	 * @param	object		tabItems: the configuration of the tabbed panel
	 *
	 * @return	void
	 */
	openDialogue: function (buttonId, title, dimensions, tabItems) {
		this.dialog = new Ext.Window({
			title: this.localize(title) || title,
			cls: 'htmlarea-window',
			border: false,
			width: dimensions.width,
			height: 'auto',
			iconCls: this.getButton(buttonId).iconCls,
			listeners: {
				close: {
					fn: this.onClose,
					scope: this
				}
			},
			items: {
				xtype: 'tabpanel',
				itemId: 'tabpanel',
				activeTab: 0,
				defaults: {
					xtype: 'container',
					layout: 'form',
					defaults: {
						labelWidth: 100
					}
				},
				listeners: {
					tabchange: {
						fn: this.syncHeight,
						scope: this
					}
				},
				items: tabItems
			},
			buttons: [
				this.buildButtonConfig('OK', this.onOK),
				this.buildButtonConfig('Cancel', this.onCancel)
			]
		});
		this.show();
	},
	/*
	 * Build the configuration of the the tab items
	 *
	 * @return	array	the configuration array of tab items
	 */
	buildTabItems: function () {
		var tabItems = [];
			// General tab
		tabItems.push({
			title: this.localize('General'),
			items: [{
					xtype: 'fieldset',
					defaultType: 'textfield',
					defaults: {
						helpIcon: true,
						width: 300,
						labelSeparator: ''
					},
					items: [{
							itemId: 'url',
							fieldLabel: this.localize('Image URL:'),
							value: this.parameters.url,
							helpTitle: this.localize('Enter the image URL here')
						},{
							itemId: 'alt',
							fieldLabel: this.localize('Alternate text:'),
							value: this.parameters.alt,
							helpTitle: this.localize('For browsers that dont support images')
						}
					]
				},{
					xtype: 'fieldset',
					title: this.localize('Image Preview'),
					items: [{
								// The preview iframe
							xtype: 'box',
							itemId: 'image-preview',
							autoEl: {
								name: 'ipreview',
								tag: 'iframe',
								cls: 'image-preview',
								src: this.parameters.url
							}
						},{
							xtype: 'button',
							minWidth: 150,
							text: this.localize('Preview'),
							itemId: 'preview',
							style: {
								marginTop: '5px',
								'float': 'right'
							},
							listeners: {
								click: {
									fn: this.onPreviewClick,
									scope: this
								}
							}
						}
					]
				}
			]
		});
			// Layout tab
		if (!this.removeItems.test('layout')) {
			tabItems.push({
				title: this.localize('Layout'),
				items: [{
						xtype: 'fieldset',
						defaultType: 'textfield',
						defaults: {
							helpIcon: true,
							width: 250,
							labelSeparator: ''
						},
						items: [
							Ext.apply({
								xtype: 'combo',
								fieldLabel: this.localize('Image alignment:'),
								itemId: 'align',
								value: this.parameters.align,
								helpTitle: this.localize('Positioning of this image'),
								store: new Ext.data.ArrayStore({
									autoDestroy:  true,
									fields: [ { name: 'text'}, { name: 'value'}],
									data: [
										[this.localize('Not set'), ''],
										[this.localize('Bottom'), 'bottom'],
										[this.localize('Middle'), 'middle'],
										[this.localize('Top'), 'top']
									]
								}),
								hidden: this.removeItems.test('align'),
								hideLabel: this.removeItems.test('align')
								}, this.configDefaults['combo'])
							,{
								itemId: 'border',
								fieldLabel: this.localize('Border thickness:'),
								width: 100,
								value: this.parameters.border,
								helpTitle: this.localize('Leave empty for no border'),
								hidden: this.removeItems.test('border'),
								hideLabel: this.removeItems.test('border')
							},
							Ext.apply({
								xtype: 'combo',
								fieldLabel: this.localize('Float:'),
								itemId: 'cssFloat',
								value: this.parameters.cssFloat,
								helpTitle: this.localize('Where the image should float'),
								store: new Ext.data.ArrayStore({
									autoDestroy:  true,
									fields: [ { name: 'text'}, { name: 'value'}],
									data: [
										[this.localize('Not set'), ''],
										[this.localize('Non-floating'), 'none'],
										[this.localize('Left'), 'left'],
										[this.localize('Right'), 'right']
									]
								}),
								hidden: this.removeItems.test('float'),
								hideLabel: this.removeItems.test('float')
								}, this.configDefaults['combo'])
						]
				}]
			});
		}
			// Padding tab
		if (!this.removeItems.test('padding')) {
			tabItems.push({
				title: this.localize('Spacing and padding'),
				items: [{
						xtype: 'fieldset',
						defaultType: 'textfield',
						defaults: {
							helpIcon: true,
							width: 100,
							labelSeparator: ''
						},
						items: [{
								itemId: 'paddingTop',
								fieldLabel: this.localize('Top:'),
								value: this.parameters.paddingTop,
								helpTitle: this.localize('Top padding'),
								hidden: this.removeItems.test('paddingTop'),
								hideLabel: this.removeItems.test('paddingTop')
							},{
								itemId: 'paddingRight',
								fieldLabel: this.localize('Right:'),
								value: this.parameters.paddingRight,
								helpTitle: this.localize('Right padding'),
								hidden: this.removeItems.test('paddingRight'),
								hideLabel: this.removeItems.test('paddingRight')
							},{
								itemId: 'paddingBottom',
								fieldLabel: this.localize('Bottom:'),
								value: this.parameters.paddingBottom,
								helpTitle: this.localize('Bottom padding'),
								hidden: this.removeItems.test('paddingBottom'),
								hideLabel: this.removeItems.test('paddingBottom')
							},{
								itemId: 'paddingLeft',
								fieldLabel: this.localize('Left:'),
								value: this.parameters.paddingLeft,
								helpTitle: this.localize('Left padding'),
								hidden: this.removeItems.test('paddingLeft'),
								hideLabel: this.removeItems.test('paddingLeft')
							}
						]
				}]
			});
		}
		return tabItems;
	},
	/*
	 * Handler invoked when the Preview button is clicked
	 */
	onPreviewClick: function () {
		var tabPanel = this.dialog.find('itemId', 'tabpanel')[0];
		var urlField = this.dialog.find('itemId', 'url')[0];
		var url = urlField.getValue().trim();
		if (url) {
			try {
				window.ipreview.location.replace(url);
			} catch (e) {
				TYPO3.Dialog.InformationDialog({
					title: this.localize('Image Preview'),
					msg: this.localize('image_url_invalid'),
					fn: function () { tabPanel.setActiveTab(0); urlField.focus(); }
				});
			}
		} else {
			TYPO3.Dialog.InformationDialog({
				title: this.localize('Image Preview'),
				msg: this.localize('image_url_first'),
				fn: function () { tabPanel.setActiveTab(0); urlField.focus(); }
			});
		}
		return false;
	},
	/*
	 * Handler invoked when the OK button is clicked
	 */
	onOK: function () {
		var urlField = this.dialog.find('itemId', 'url')[0];
		var url = urlField.getValue().trim();
		if (url) {
			var fieldNames = ['url', 'alt', 'align', 'border', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft', 'cssFloat'];
			Ext.each(fieldNames, function (fieldName) {
				var field = this.dialog.find('itemId', fieldName)[0];
				if (field && !field.hidden) {
					this.parameters[fieldName] = field.getValue();
				}
			}, this);
			this.insertImage();
			this.close();
		} else {
			var tabPanel = this.dialog.find('itemId', 'tabpanel')[0];
			TYPO3.Dialog.InformationDialog({
				title: this.localize('image_url'),
				msg: this.localize('image_url_required'),
				fn: function () { tabPanel.setActiveTab(0); urlField.focus(); }
			});
		}
		return false;
	},
	/*
	 * Insert the image
	 */
	insertImage: function() {
		this.restoreSelection();
		var image = this.image;
		if (!image) {
			var range = this.editor.getSelection().createRange();
			this.editor.getSelection().execCommand('InsertImage', false, this.parameters.url);
			if (Ext.isWebKit) {
				this.editor.getDomNode().cleanAppleStyleSpans(this.editor.document.body);
			}
			if (HTMLArea.isIEBeforeIE9) {
				image = range.parentElement();
				if (!/^img$/i.test(image.nodeName)) {
					image = image.previousSibling;
				}
				this.editor.getSelection().selectNode(image);
			} else {
				var range = this.editor.getSelection().createRange();
				image = range.startContainer;
				image = image.lastChild;
				while (image && !/^img$/i.test(image.nodeName)) {
					image = image.previousSibling;
				}
			}
		} else {
			image.src = this.parameters.url;
		}
		if (/^img$/i.test(image.nodeName)) {
			Ext.iterate(this.parameters, function (fieldName, value) {
				switch (fieldName) {
					case 'alt':
						image.alt = value;
						break;
					case 'border':
						if (parseInt(value)) {
							image.style.borderWidth = parseInt(value) + 'px';
							image.style.borderStyle = 'solid';
						} else {
							image.style.borderWidth = '';
							image.style.borderStyle = 'none';
						}
						break;
					case 'align':
						image.style.verticalAlign = value;
						break;
					case 'paddingTop':
					case 'paddingRight':
					case 'paddingBottom':
					case 'paddingLeft':
						if (parseInt(value)) {
							image.style[fieldName] = parseInt(value) + 'px';
						} else {
							image.style[fieldName] = '';
						}
						break;
					case 'cssFloat':
						if (HTMLArea.isIEBeforeIE9) {
							image.style.styleFloat = value;
						} else {
							image.style.cssFloat = value;
						}
						break;
				}
			});
		}
	},
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
		if (mode === 'wysiwyg' && this.editor.isEditable() && button.itemId === 'InsertImage' && !button.disabled) {
			var image = this.editor.getSelection().getParentElement();
			if (image && !/^img$/i.test(image.nodeName)) {
				image = null;
			}
			if (image) {
				button.setTooltip({ title: this.localize('Modify image') });
			} else {
				button.setTooltip({ title: this.localize('Insert image') });
			}
		}
	}
});
