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
 * Default Link Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM'],
	function (Plugin, UserAgent, Util, Dom) {

	var DefaultLink = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(DefaultLink, Plugin);
	Util.apply(DefaultLink.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {
			this.baseURL = this.editorConfiguration.baseURL;
			this.pageTSConfiguration = this.editorConfiguration.buttons.link;
			this.showTarget = !(this.pageTSConfiguration && this.pageTSConfiguration.targetSelector && this.pageTSConfiguration.targetSelector.disabled);

			/**
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

		/**
		 * The list of buttons added by this plugin
		 */
		buttonList: [
			['CreateLink', 'a,img', false, true, 'link-edit'],
			['UnLink', 'a', false, false, 'unlink']
		],

		/**
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

		/**
		 * This function gets called when the editor is generated
		 */
		onGenerate: function () {
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
		onButtonPress: function (editor, id, target) {
				// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			this.link = this.editor.getSelection().getFirstAncestorOfType('a');
			switch (buttonId) {
				case 'UnLink':
					this.unLink();
					break;
				case 'CreateLink':
					if (!this.link) {
						if (this.editor.getSelection().isEmpty()) {
							TYPO3.Dialog.InformationDialog({
								title: this.getButton(buttonId).tooltip,
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
							href:	this.link.getAttribute('href'),
							title:	this.link.title,
							target:	this.link.target
						};
					}
						// Open dialogue window
					this.openDialogue(
						buttonId,
						this.getButton(buttonId).tooltip,
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
			this.dialog = new Ext.Window({
				title: this.localize(title) || title,
				cls: 'htmlarea-window',
				border: false,
				width: dimensions.width,
				height: 'auto',
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
							}, Util.apply({
								xtype: 'combo',
								fieldLabel: this.localize('Target:'),
								itemId: 'target',
								helpTitle: this.localize('link_target_tooltip'),
								store: new Ext.data.ArrayStore({
									autoDestroy:  true,
									fields: [ { name: 'text'}, { name: 'value'}],
									data: [
										[this.localize('target_none'), ''],
										[this.localize('target_blank'), '_blank'],
										[this.localize('target_self'), '_self'],
										[this.localize('target_top'), '_top'],
										[this.localize('target_other'), '_other']
									]
								}),
								listeners: {
									select: {
										fn: this.onTargetSelect
									}
								},
								hidden: !this.showTarget
								}, this.configDefaults['combo'])
							,{
								itemId: 'frame',
								name: 'frame',
								fieldLabel: this.localize('frame'),
								helpTitle: this.localize('frame_help'),
								hideLabel: true,
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
			var targetCombo = dialog.find('itemId', 'target')[0];
			if (!targetCombo.hidden && this.parameters.target) {
				var frameField = dialog.find('itemId', 'frame')[0];
				var index = targetCombo.getStore().find('value', this.parameters.target);
				if (index == -1) {
						// The target is a specific frame name
					targetCombo.setValue('_other');
					frameField.setValue(this.parameters.target);
					frameField.show();
					frameField.label.show();
				} else {
					targetCombo.setValue(this.parameters.target);
				}
			}
		},
		/*
		 * Handler invoked when a target is selected
		 */
		onTargetSelect: function (combo, record) {
			var frameField = combo.ownerCt.getComponent('frame');
			if (record.get('value') == '_other') {
				frameField.show();
				frameField.label.show();
				frameField.focus();
			} else if (!frameField.hidden) {
				frameField.hide();
				frameField.label.hide();
			}
		},
		/*
		 * Handler invoked when the OK button is clicked
		 */
		onOK: function () {
			var hrefField = this.dialog.find('itemId', 'href')[0];
			var href = hrefField.getValue().trim();
			if (href && href != 'http://') {
				var title = this.dialog.find('itemId', 'title')[0].getValue();
				var target = this.dialog.find('itemId', 'target')[0].getValue();
				if (target == '_other') {
					target = this.dialog.find('itemId', 'frame')[0].getValue().trim();
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
				this.restoreSelection();
				this.editor.getSelection().execCommand('CreateLink', false, href);
				a = this.editor.getSelection().getParentElement();
				if (!/^a$/i.test(a.nodeName)) {
					var range = this.editor.getSelection().createRange();
					if (range.startContainer.nodeType !== Dom.TEXT_NODE) {
						a = range.startContainer.childNodes[range.startOffset];
					} else {
						a = range.startContainer.nextSibling;
					}
					this.editor.getSelection().selectNode(a);
				}
				var el = this.editor.getSelection().getFirstAncestorOfType('a');
				if (el != null) {
					a = el;
				}
			} else {
				a.href = href;
			}
			if (a && /^a$/i.test(a.nodeName)) {
				a.title = title;
				a.target = target;
				if (UserAgent.isOpera) {
					this.editor.getSelection().selectNodeContents(a, false);
				} else {
					this.editor.getSelection().selectNodeContents(a);
				}
			}
		},
		/*
		 * Unlink the selection
		 */
		unLink: function () {
			this.restoreSelection();
			if (this.link) {
				this.editor.getSelection().selectNode(this.link);
			}
			this.editor.getSelection().execCommand('Unlink', false, '');
		},
		/*
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			button.setInactive(true);
			if (mode === 'wysiwyg' && this.editor.isEditable()) {
				switch (button.itemId) {
					case 'CreateLink':
						button.setDisabled(selectionEmpty && !button.isInContext(mode, selectionEmpty, ancestors));
						if (!button.disabled) {
							var node = this.editor.getSelection().getParentElement();
							var el = this.editor.getSelection().getFirstAncestorOfType('a');
							if (el != null) {
								node = el;
							}
							if (node != null && /^a$/i.test(node.nodeName)) {
								button.setTooltip(this.localize('Modify link'));
								button.setInactive(false);
							} else {
								button.setTooltip(this.localize('Insert link'));
							}
						}
						break;
					case 'UnLink':
						var link = false;
							// Let's see if a link was double-clicked in Firefox
						if (UserAgent.isGecko && !selectionEmpty) {
							var range = this.editor.getSelection().createRange();
							if (range.startContainer.nodeType === Dom.ELEMENT_NODE && range.startContainer == range.endContainer && (range.endOffset - range.startOffset == 1)) {
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

	return DefaultLink;

});
