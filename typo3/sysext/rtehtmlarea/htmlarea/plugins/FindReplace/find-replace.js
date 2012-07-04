/***************************************************************
*  Copyright notice
*
*  (c) 2004 Cau guanabara <caugb@ibest.com.br>
*  (c) 2005-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Find and Replace Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.FindReplace = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		/*
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
		/*
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
	/*
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
		var params = {};
		Ext.each(fields, function (field) {
			params[field] = this.dialog.find('itemId', field)[0].getValue();
		}, this);
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
			Ext.each(this.editor.document.body.getElementsByTagName('span'), function (mark) {
				if (/^htmlarea-frmark/.test(mark.id)) {
					this.spans.push(mark);
				}
			}, this);
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
			Ext.each(this.spans, function (mark, i) {
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
						return true;
					} else {
						TYPO3.Dialog.QuestionDialog({
							title: this.getButton('FindReplace').tooltip.title,
							msg: this.localize('Substitute this occurrence?'),
							fn: replace,
							scope: this
						});
						return false;
					}
				}
			}, this);
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
		Ext.each(this.editor.document.body.getElementsByTagName('span'), function (mark) {
			if (/^htmlarea-frmark/.test(mark.id)) {
				mark.style.backgroundColor = '';
				mark.style.color = '';
				mark.style.fontWeight = '';
			}
		}, this);
		this.disableActions('hiliteall', false);
		this.disableActions('clear', true);
	},
	/*
	 * Highlight all marks
	 */
	hiliteAll: function () {
		Ext.each(this.editor.document.body.getElementsByTagName('span'), function (mark) {
			if (/^htmlarea-frmark/.test(mark.id)) {
				mark.style.backgroundColor = 'highlight';
				mark.style.color = 'white';
				mark.style.fontWeight = 'bold';
			}
		}, this);
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
	/*
	 * Disable action buttons
	 *
	 * @param	array		actions: array of buttonIds to set disabled/enabled
	 * @param	boolean		disabled: true to set disabled
	 */
	disableActions: function (actions, disabled) {
		Ext.each(actions.split(/[,; ]+/), function (action) {
				this.dialog.find('itemId', action)[0].setDisabled(disabled);
		}, this);
	},
	/*
	 * Initialize find & replace variables
	 */
	initVariables: function () {
		this.matches = 0;
		this.replaces = 0;
		this.spans = new Array();
	},
	/*
	 * Clear the document before leaving on 'Done' button
	 */
	onCancel: function () {
		this.clearDoc();
		var plugin = this.getPluginInstance('UndoRedo');
		if (plugin) {
			plugin.start();
		}
		HTMLArea.FindReplace.superclass.onCancel.call(this);
	},
	/*
	 * Clear the document before leaving on window close handle
	 */
	onClose: function () {
		this.clearDoc();
		var plugin = this.getPluginInstance('UndoRedo');
		if (plugin) {
			plugin.start();
		}
		HTMLArea.FindReplace.superclass.onClose.call(this);
	}
});
