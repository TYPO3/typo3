/***************************************************************
*  Copyright notice
*
*  (c) 2008-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Default Link Plugin for TYPO3 htmlArea RTE
 */
Ext.define('HTMLArea.DefaultLink', {
	extend: 'HTMLArea.Plugin',
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function(editor) {
		this.baseURL = this.editorConfiguration.baseURL;
		this.pageTSConfiguration = this.editorConfiguration.buttons.link;
		this.stripBaseUrl = this.pageTSConfiguration && this.pageTSConfiguration.stripBaseUrl && this.pageTSConfiguration.stripBaseUrl;
		this.showTarget = !(this.pageTSConfiguration && this.pageTSConfiguration.targetSelector && this.pageTSConfiguration.targetSelector.disabled);
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
		 * Registering the buttons
		 */
		var buttonList = this.buttonList, buttonId;
		for (var i = 0; i < buttonList.length; ++i) {
			var button = buttonList[i];
			buttonId = button[0];
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId.toLowerCase()),
				iconCls		: 'htmlarea-action-' + button[4],
				action		: 'onButtonPress',
				hotKey		: (this.pageTSConfiguration ? this.pageTSConfiguration.hotKey : null),
				context		: button[1],
				selection	: button[2],
				dialog		: button[3]
			};
			this.registerButton(buttonConfiguration);
		}
		return true;
	},
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList: [
		['CreateLink', 'a,img', false, true, 'link-edit'],
		['UnLink', 'a', false, false, 'unlink']
	],
	/*
	 * Sets of default configuration values for dialogue form fields
	 */
	configDefaults: {
		combobox: {
			cls: 'htmlarea-combo',
			displayField: 'text',
			listConfig: {
				cls: 'htmlarea-combo-list',
				getInnerTpl: function () {
					return '<div data-qtip="{value}" class="htmlarea-combo-list-item">{text}</div>';
				}
			},
			editable: true,
			forceSelection: true,
			helpIcon: true,
			queryMode: 'local',
			selectOnFocus: true,
			triggerAction: 'all',
			typeAhead: true,
			valueField: 'value',
			xtype: 'combobox'
		}
	},
	/*
	 * This function gets called when the editor is generated
	 */
	onGenerate: function () {
		if (Ext.isIE) {
			this.editor.iframe.htmlRenderer.stripBaseUrl = this.stripBaseUrl;
		}
	},
	/*
	 * This function gets called when the button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 * @param	object		target: the target element of the contextmenu event, when invoked from the context menu
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress: function(editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		this.editor.focus();
		this.link = this.editor.getParentElement();
		var el = HTMLArea.getElementObject(this.link, 'a');
		if (el && /^a$/i.test(el.nodeName)) {
			this.link = el;
		}
		if (!this.link || !/^a$/i.test(this.link.nodeName)) {
			this.link = null;
		}
		switch (buttonId) {
			case 'UnLink':
				this.unLink();
				break;
			case 'CreateLink':
				if (!this.link) {
					var selection = this.editor._getSelection();
					if (this.editor._selectionEmpty(selection)) {
						TYPO3.Dialog.InformationDialog({
							title: this.getButton(buttonId).tooltip.text,
							msg: this.localize('Select some text')
						});
						break;
					}
					this.parameters = {
						href:	'http://',
						title:	'',
						target:	''
					};
				} else {
					this.parameters = {
						href:	(Ext.isIE && this.stripBaseUrl) ? this.stripBaseURL(this.link.href) : this.link.getAttribute('href'),
						title:	this.link.title,
						target:	this.link.target
					};
				}
					// Open dialogue window
				this.openDialogue(
					buttonId,
					this.getButton(buttonId).tooltip.text,
					this.getWindowDimensions(
						{
							width: 470,
							height:150
						},
						buttonId
					)
				);
				break;
		}
		return false;
	},
	/*
	 * Open the dialogue window
	 *
	 * @param	string		buttonId: the button id
	 * @param	string		title: the window title
	 * @param	integer		dimensions: the opening width of the window
	 *
	 * @return	void
	 */
	openDialogue: function (buttonId, title, dimensions) {
			// Create target options global store
		var targetStore = Ext.data.StoreManager.lookup('HTMLArea' + '-store-' + this.name + '-target');
		if (!targetStore) {
			targetStore = Ext.create('Ext.data.ArrayStore', {
				model: 'HTMLArea.model.Default',
				storeId: 'HTMLArea' + '-store-' + this.name + '-target'
			});
			targetStore.loadData([
				{
					text: this.localize('target_none'),
					value: ''
				},{
					text: this.localize('target_blank'),
					value: '_blank'
				},{
					text: this.localize('target_self'),
					value: '_self'
				},{
					text: this.localize('target_top'),
					value: '_top'
				},{
					text: this.localize('target_other'),
					value: '_other'
				}
			]);
		}
		this.dialog = Ext.create('Ext.window.Window', {
			title: this.localize(title) || title,
			cls: 'htmlarea-window',
			border: false,
			width: dimensions.width,
			layout: 'anchor',
			resizable: true,
			iconCls: this.getButton(buttonId).iconCls,
			listeners: {
				afterrender: {
					fn: this.onAfterRender,
					scope: this
				},
				close: {
					fn: this.onClose,
					scope: this
				}
			},
			items: [{
					xtype: 'fieldset',
					defaultType: 'textfield',
					labelWidth: 100,
					defaults: {
						helpIcon: true,
						width: 250,
						labelSeparator: ''
					},
					items: [{
							itemId: 'href',
							name: 'href',
							fieldLabel: this.localize('URL:'),
							value: this.parameters.href,
							helpTitle: this.localize('link_href_tooltip')
						},{
							itemId: 'title',
							name: 'title',
							fieldLabel: this.localize('Title (tooltip):'),
							value: this.parameters.title,
							helpTitle: this.localize('link_title_tooltip')
						}, Ext.applyIf({
								fieldLabel: this.localize('Target:'),
								helpTitle: this.localize('link_target_tooltip'),
								hidden: !this.showTarget,
								itemId: 'target',
								listeners: {
									select: {
										fn: this.onTargetSelect
									}
								},
								store: targetStore
							},
							this.configDefaults['combobox']
						),{
							itemId: 'framename',
							name: 'framename',
							fieldLabel: this.localize('frame'),
							helpTitle: this.localize('frame_help'),
							hidden: true
						}
					]
				}
			],
			buttons: [
				this.buildButtonConfig('OK', this.onOK),
				this.buildButtonConfig('Cancel', this.onCancel)
			]
		});
		this.show();
	},
	/*
	 * Handler invoked after the dialogue window is rendered
	 * If the current target is not in the available options, show frame field
	 */
	onAfterRender: function (dialog) {
		var targetCombo = dialog.down('component[itemId=target]');
			// Somehow getStore method got lost...
		if (!Ext.isFunction(targetCombo.getStore)) {
			targetCombo.getStore = function () {
				return targetCombo.store;
			};
		}
		if (!targetCombo.isHidden() && this.parameters.target) {
			var frameField = dialog.down('component[itemId=framename]');
			var index = targetCombo.getStore().find('value', this.parameters.target);
			if (index == -1) {
					// The target is a specific frame name
				targetCombo.setValue('_other');
				frameField.setValue(this.parameters.target);
				frameField.show();
			} else {
				targetCombo.setValue(this.parameters.target);
			}
		}
	},
	/*
	 * Handler invoked when a target is selected
	 */
	onTargetSelect: function (combo, records) {
		var frameField = combo.ownerCt.getComponent('framename');
		if (records[0].get('value') == '_other') {
			frameField.show();
			frameField.focus();
		} else if (!frameField.isHidden()) {
			frameField.hide();
		}
	},
	/*
	 * Handler invoked when the OK button is clicked
	 */
	onOK: function () {
		var hrefField = this.dialog.down('component[itemId=href]');
		var href = hrefField.getValue().trim();
		if (href && href != 'http://') {
			var title = this.dialog.down('component[itemId=title]').getValue();
			var target = this.dialog.down('component[itemId=target]').getValue();
			if (target == '_other') {
				target = this.dialog.down('component[itemId=framename]').getValue().trim();
			}
			this.createLink(href, title, target);
			this.close();
		} else {
			TYPO3.Dialog.InformationDialog({
				title: this.localize('URL'),
				msg: this.localize('link_url_required'),
				fn: function () { hrefField.focus(); }
			});
		}
		return false;
	},
	/*
	 * Create the link
	 *
	 * @param	string		href: the value of href attribute
	 * @param	string		title: the value of title attribute
	 * @param	string		target: the value of target attribute
	 *
	 * @return	void
	 */
	createLink: function (href, title, target) {
		var a = this.link;
		if (!a) {
			this.editor.focus();
			this.restoreSelection();
			this.editor.document.execCommand('CreateLink', false, href);
			a = this.editor.getParentElement();
			if (!Ext.isIE && !/^a$/i.test(a.nodeName)) {
				var range = this.editor._createRange(this.editor._getSelection());
				if (range.startContainer.nodeType != 3) {
					a = range.startContainer.childNodes[range.startOffset];
				} else {
					a = range.startContainer.nextSibling;
				}
				this.editor.selectNode(a);
			}
			var el = HTMLArea.getElementObject(a, 'a');
			if (el != null && /^a$/i.test(el.nodeName)) {
				a = el;
			}
		} else {
			a.href = href;
		}
		if (a && /^a$/i.test(a.nodeName)) {
			a.title = title;
			a.target = target;
			if (Ext.isOpera) {
				this.editor.selectNodeContents(a, false);
			} else {
				this.editor.selectNodeContents(a);
			}
		}
	},
	/*
	 * Unlink the selection
	 */
	unLink: function () {
		this.editor.focus();
		this.restoreSelection();
		if (this.link) {
			this.editor.selectNode(this.link);
		}
		this.editor.document.execCommand('Unlink', false, '');
	},
	/*
	 * IE makes relative links absolute. This function reverts this conversion.
	 *
	 * @param	string		url: the url
	 *
	 * @return	string		the url stripped out of the baseurl
	 */
	stripBaseURL: function (url) {
		var baseurl = this.baseURL;
			// strip to last directory in case baseurl points to a file
		baseurl = baseurl.replace(/[^\/]+$/, '');
		var basere = new RegExp(baseurl);
		url = url.replace(basere, '');
			// strip host-part of URL which is added by MSIE to links relative to server root
		baseurl = baseurl.replace(/^(https?:\/\/[^\/]+)(.*)$/, "$1");
		basere = new RegExp(baseurl);
		return url.replace(basere, '');
	},
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
		if (mode === 'wysiwyg' && this.editor.isEditable()) {
			switch (button.itemId) {
				case 'CreateLink':
					button.setDisabled(selectionEmpty && !button.isInContext(mode, selectionEmpty, ancestors));
					if (!button.disabled) {
						var node = this.editor.getParentElement();
						var el = HTMLArea.getElementObject(node, 'a');
						if (el != null && /^a$/i.test(el.nodeName)) {
							node = el;
						}
						if (node != null && /^a$/i.test(node.nodeName)) {
							button.setTooltip({ text: this.localize('Modify link') });
						} else {
							button.setTooltip({ text: this.localize('Insert link') });
						}
					}
					break;
				case 'UnLink':
					var link = false;
						// Let's see if a link was double-clicked in Firefox
					if (Ext.isGecko && !selectionEmpty) {
						var range = this.editor._createRange(this.editor._getSelection());
						if (range.startContainer.nodeType == 1 && range.startContainer == range.endContainer && (range.endOffset - range.startOffset == 1)) {
							var node = range.startContainer.childNodes[range.startOffset];
							if (node && /^a$/i.test(node.nodeName) && node.textContent == range.toString()) {
								link = true;
							}
						}
					}
					button.setDisabled(!link && !button.isInContext(mode, selectionEmpty, ancestors));
					break;
			}
		}
	}
});
