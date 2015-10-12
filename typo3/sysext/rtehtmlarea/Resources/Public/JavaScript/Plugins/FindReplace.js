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
 * Find and Replace Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, Util) {

	var FindReplace = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(FindReplace, Plugin);
	Util.apply(FindReplace.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {

			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '2.2',
				developer	: 'Cau Guanabara & Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca',
				copyrightOwner	: 'Cau Guanabara & Stanislas Rolland',
				sponsor		: 'Independent production & SJBR',
				sponsorUrl	: 'http://www.sjbr.ca',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);

			/**
			 * Registering the button
			 */
			var buttonId = 'FindReplace';
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize('Find and Replace'),
				iconCls		: 'htmlarea-action-find-replace',
				action		: 'onButtonPress',
				dialog		: true
			};
			this.registerButton(buttonConfiguration);

			// Compile regular expression to clean up marks
			this.marksCleaningRE = /(<span\s+[^>]*id="?htmlarea-frmark[^>]*"?>)([^<>]*)(<\/span>)/gi;
			return true;
		},

		/**
		 * This function gets called when the 'Find & Replace' button is pressed.
		 *
		 * @param	object		editor: the editor instance
		 * @param	string		id: the button id or the key
		 *
		 * @return	boolean		false if action is completed
		 */
		onButtonPress: function (editor, id, target) {
				// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
				// Initialize search variables
			this.buffer = null;
			this.initVariables();
				// Disable the toolbar undo/redo buttons and snapshots while this window is opened
			var plugin = this.getPluginInstance('UndoRedo');
			if (plugin) {
				plugin.stop();
				var undo = this.getButton('Undo');
				if (undo) {
					undo.setDisabled(true);
				}
				var redo = this.getButton('Redo');
				if (redo) {
					redo.setDisabled(true);
				}
			}
				// Open dialogue window
			this.openDialogue(
				buttonId,
				'Find and Replace',
				this.getWindowDimensions(
					{
						width: 410,
						height:360
					},
					buttonId
				)
			);
			return false;
		},

		/**
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
				title: this.localize(title),
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
				items: [{
						xtype: 'fieldset',
						defaultType: 'textfield',
						labelWidth: 100,
						defaults: {
							labelSeparator: '',
							width: 250,
							listeners: {
								change: {
									fn: this.clearDoc,
									scope: this
								}
							}
						},
						listeners: {
							render: {
								fn: this.initPattern,
								scope: this
							}
						},
						items: [{
								itemId: 'pattern',
								fieldLabel: this.localize('Search for:')
							},{
								itemId: 'replacement',
								fieldLabel: this.localize('Replace with:')
							}
						]
					},{
						xtype: 'fieldset',
						defaultType: 'checkbox',
						title: this.localize('Options'),
						labelWidth: 150,
						items: [{
								itemId: 'words',
								fieldLabel: this.localize('Whole words only'),
								listeners: {
									check: {
										fn: this.clearDoc,
										scope: this
									}
								}
							},{
								itemId: 'matchCase',
								fieldLabel: this.localize('Case sensitive search'),
								listeners: {
									check: {
										fn: this.clearDoc,
										scope: this
									}
								}
							},{
								itemId: 'replaceAll',
								fieldLabel: this.localize('Substitute all occurrences'),
								listeners: {
									check: {
										fn: this.requestReplacement,
										scope: this
									}
								}
							}
						]
					},{
						xtype: 'fieldset',
						defaultType: 'button',
						title: this.localize('Actions'),
						defaults: {
							minWidth: 150,
							disabled: true,
							style: {
								marginBottom: '5px'
							}
						},
						items: [{
								text: this.localize('Clear'),
								itemId: 'clear',
								listeners: {
									click: {
										fn: this.clearMarks,
										scope: this
									}
								}
							},{
								text: this.localize('Highlight'),
								itemId: 'hiliteall',
								listeners: {
									click: {
										fn: this.hiliteAll,
										scope: this
									}
								}
							},{
								text: this.localize('Undo'),
								itemId: 'undo',
								listeners: {
									click: {
										fn: this.resetContents,
										scope: this
									}
								}
							}
						]
					}
				],
				buttons: [
					this.buildButtonConfig('Next', this.onNext),
					this.buildButtonConfig('Done', this.onCancel)
				]
			});
			this.show();
		},
		/*
		 * Handler invoked to initialize the pattern to search
		 *
		 * @param	object		fieldset: the fieldset component
		 *
		 * @return	void
		 */
		initPattern: function (fieldset) {
			var selection = this.editor.getSelection().getHtml();
			if (/\S/.test(selection)) {
				selection = selection.replace(/<[^>]*>/g, '');
				selection = selection.replace(/&nbsp;/g, '');
			}
			if (/\S/.test(selection)) {
				fieldset.getComponent('pattern').setValue(selection);
				fieldset.getComponent('replacement').focus();
			} else {
				fieldset.getComponent('pattern').focus();
			}
		},
		/*
		 * Handler invoked when the replace all checkbox is checked
		 */
		requestReplacement: function () {
			if (!this.dialog.find('itemId', 'replacement')[0].getValue() && this.dialog.find('itemId', 'replaceAll')[0].getValue()) {
				TYPO3.Dialog.InformationDialog({
					title: this.getButton('FindReplace').tooltip.title,
					msg: this.localize('Inform a replacement word'),
					fn: function () { this.dialog.find('itemId', 'replacement')[0].focus(); },
					scope: this
				});
			}
			this.clearDoc();
		},
		/*
		 * Handler invoked when the 'Next' button is pressed
		 */
		onNext: function () {
			if (!this.dialog.find('itemId', 'pattern')[0].getValue()) {
				TYPO3.Dialog.InformationDialog({
					title: this.getButton('FindReplace').tooltip.title,
					msg: this.localize('Enter the text you want to find'),
					fn: function () { this.dialog.find('itemId', 'pattern')[0].focus(); },
					scope: this
				});
				return false;
			}
			var fields = [
				'pattern',
				'replacement',
				'words',
				'matchCase',
				'replaceAll'
			];
			var params = {}, field;
			for (var i = fields.length; --i >= 0;) {
				field = fields[i];
				params[field] = this.dialog.find('itemId', field)[0].getValue();
			}
			this.search(params);
			return false;
		},
		/*
		 * Search the pattern and insert span tags
		 *
		 * @param	object		params: the parameters of the search corresponding to the values of fields:
		 *					pattern
		 *					replacement
		 *					words
		 *					matchCase
		 *					replaceAll
		 *
		 * @return	void
		 */
		search: function (params) {
			var html = this.editor.getInnerHTML();
			if (this.buffer == null) {
				this.buffer = html;
			}
			if (this.matches == 0) {
				var pattern = new RegExp(params.words ? '(?!<[^>]*)(\\b' + params.pattern + '\\b)(?![^<]*>)' : '(?!<[^>]*)(' + params.pattern + ')(?![^<]*>)', 'g' + (params.matchCase? '' : 'i'));
				this.editor.setHTML(html.replace(pattern, '<span id="htmlarea-frmark">' + "$1" + '</span>'));
				var spanElements = this.editor.document.body.getElementsByTagName('span');
				for (var i = 0, n = spanElements.length; i < n; i++) {
					var mark = spanElements[i];
					if (/^htmlarea-frmark/.test(mark.id)) {
						this.spans.push(mark);
					}
				}
			}
			this.spanWalker(params.pattern, params.replacement, params.replaceAll);
		},
		/*
		 * Walk the span tags
		 *
		 * @param	string		pattern: the pattern being searched for
		 * @param	string		replacement: the replacement string
		 * @param	bolean		replaceAll: true if all occurrences should be replaced
		 *
		 * @return	void
		 */
		spanWalker: function (pattern, replacement, replaceAll) {
			this.clearMarks();
			if (this.spans.length) {
				for (var i = 0, n = this.spans.length; i < n; i++) {
					var mark = this.spans[i];
					if (i >= this.matches && !/[0-9]$/.test(mark.id)) {
						this.matches++;
						this.disableActions('clear', false);
						mark.id = 'htmlarea-frmark_' + this.matches;
						mark.style.color = 'white';
						mark.style.backgroundColor = 'highlight';
						mark.style.fontWeight = 'bold';
						mark.scrollIntoView(false);
						var self = this;
						function replace(button) {
							if (button == 'yes') {
								mark.firstChild.replaceData(0, mark.firstChild.data.length, replacement);
								self.replaces++;
								self.disableActions('undo', false);
							}
							self.endWalk(pattern, i);
						}
						if (replaceAll) {
							replace('yes');
						} else {
							TYPO3.Dialog.QuestionDialog({
								title: this.getButton('FindReplace').tooltip.title,
								msg: this.localize('Substitute this occurrence?'),
								fn: replace,
								scope: this
							});
							break;
						}
					}
				}
			} else {
				this.endWalk(pattern, 0);
			}
		},
		/*
		 * End the replacement walk
		 *
		 * @param	string		pattern: the pattern being searched for
		 * @param	integer		index: the index reached in the walk
		 *
		 * @return 	void
		 */
		endWalk: function (pattern, index) {
			if (index >= this.spans.length - 1 || !this.spans.length) {
				var message = this.localize('Done') + ':<br /><br />';
				if (this.matches > 0) {
					if (this.matches == 1) {
						message += this.matches + ' ' + this.localize('found item');
					} else {
						message += this.matches + ' ' + this.localize('found items');
					}
					if (this.replaces > 0) {
						if (this.replaces == 1) {
							message += ',<br />' + this.replaces + ' ' + this.localize('replaced item');
						} else {
							message += ',<br />' + this.replaces + ' ' + this.localize('replaced items');
						}
					}
					this.hiliteAll();
				} else {
					message += '"' + pattern + '" ' + this.localize('not found');
					this.disableActions('hiliteall,clear', true);
				}
				TYPO3.Dialog.InformationDialog({
					title: this.getButton('FindReplace').tooltip.title,
					msg: message + '.',
					minWidth: 300
				});
			}
		},
		/*
		 * Remove all marks
		 */
		clearDoc: function () {
			this.editor.setHTML(this.editor.getInnerHTML().replace(this.marksCleaningRE, "$2"));
			this.initVariables();
			this.disableActions('hiliteall,clear', true);
		},
		/*
		 * De-highlight all marks
		 */
		clearMarks: function () {
			var spanElements = this.editor.document.body.getElementsByTagName('span');
			for (var i = spanElements.length; --i >= 0;) {
				var mark = spanElements[i];
				if (/^htmlarea-frmark/.test(mark.id)) {
					mark.style.backgroundColor = '';
					mark.style.color = '';
					mark.style.fontWeight = '';
				}
			}
			this.disableActions('hiliteall', false);
			this.disableActions('clear', true);
		},
		/*
		 * Highlight all marks
		 */
		hiliteAll: function () {
			var spanElements = this.editor.document.body.getElementsByTagName('span');
			for (var i = spanElements.length; --i >= 0;) {
				var mark = spanElements[i];
				if (/^htmlarea-frmark/.test(mark.id)) {
					mark.style.backgroundColor = 'highlight';
					mark.style.color = 'white';
					mark.style.fontWeight = 'bold';
				}
			}
			this.disableActions('clear', false);
			this.disableActions('hiliteall', true);
		},
		/*
		 * Undo the replace operation
		 */
		resetContents: function () {
			if (this.buffer != null) {
				var transp = this.editor.getInnerHTML();
				this.editor.setHTML(this.buffer);
				this.buffer = transp;
				this.disableActions('clear', true);
			}
		},
		/**
		 * Disable action buttons
		 *
		 * @param string actions: comma-separated list of buttonIds to set disabled/enabled
		 * @param boolean disabled: true to set disabled
		 */
		disableActions: function (actions, disabled) {
			var buttonIds = actions.split(/[,; ]+/), action;
			for (var i = buttonIds.length; --i >= 0;) {
				action = buttonIds[i];
				this.dialog.find('itemId', action)[0].setDisabled(disabled);
			}
		},
		/*
		 * Initialize find & replace variables
		 */
		initVariables: function () {
			this.matches = 0;
			this.replaces = 0;
			this.spans = new Array();
		},

		/**
		 * Clear the document before leaving on 'Done' button
		 */
		onCancel: function () {
			this.clearDoc();
			var plugin = this.getPluginInstance('UndoRedo');
			if (plugin) {
				plugin.start();
			}
			FindReplace.super.prototype.onCancel.call(this);
		},

		/**
		 * Clear the document before leaving on window close handle
		 */
		onClose: function () {
			this.clearDoc();
			var plugin = this.getPluginInstance('UndoRedo');
			if (plugin) {
				plugin.start();
			}
			FindReplace.super.prototype.onClose.call(this);
		}
	});

	return FindReplace;

});
