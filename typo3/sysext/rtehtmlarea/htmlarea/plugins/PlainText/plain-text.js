/***************************************************************
*  Copyright notice
*
*  (c) 2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Paste as Plain Text Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.PlainText = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		this.buttonsConfiguration = this.editorConfiguration.buttons;
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '1.3',
			developer	: 'Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Stanislas Rolland',
			sponsor		: 'Otto van Bruggen',
			sponsorUrl	: 'http://www.webspinnerij.nl',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the buttons
		 */
		Ext.iterate(this.buttonList, function (buttonId, buttonConf) {
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + 'Tooltip'),
				iconCls		: 'htmlarea-action-' + buttonConf[1],
				action		: 'onButtonPress',
				dialog		: buttonConf[2]
			};
			if (buttonId == 'PasteToggle' && this.buttonsConfiguration && this.buttonsConfiguration[buttonConf[0]] && this.buttonsConfiguration[buttonConf[0]].hidden) {
				buttonConfiguration.hide = true;
				buttonConfiguration.hideInContextMenu = true;
				buttonConfiguration.hotKey = null;
				this.buttonsConfiguration[buttonConf[0]].hotKey = null;
			}
			this.registerButton(buttonConfiguration);
		}, this);
		return true;
	},
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList: {
		PasteToggle: 	['pastetoggle', 'paste-toggle', false],
		PasteBehaviour:	['pastebehaviour', 'paste-behaviour', true]
	},
	/*
	 * Cleaner configurations
	 */
	cleanerConfig: {
	 	pasteStructure: {
	 	 	keepTags: /^(a|p|h[0-6]|pre|address|article|aside|blockquote|div|footer|header|nav|section|hr|br|table|thead|tbody|tfoot|caption|tr|th|td|ul|ol|dl|li|dt|dd)$/i,
	 	 	removeAttributes: /^(id|on*|style|class|className|lang|align|valign|bgcolor|color|border|face|.*:.*)$/i
	 	},
		pasteFormat: {
			keepTags: /^(a|p|h[0-6]|pre|address|article|aside|blockquote|div|footer|header|nav|section|hr|br|table|thead|tbody|tfoot|caption|tr|th|td|ul|ol|dl|li|dt|dd|b|bdo|big|cite|code|del|dfn|em|i|ins|kbd|label|q|samp|small|strike|strong|sub|sup|tt|u|var)$/i,
			removeAttributes:  /^(id|on*|style|class|className|lang|align|valign|bgcolor|color|border|face|.*:.*)$/i
	 	}
	},
	/*
	 * This function gets called when the plugin is generated
	 */
	onGenerate: function () {
			// Create cleaners
		if (this.buttonsConfiguration && this.buttonsConfiguration['pastebehaviour']) {
			this.pasteBehaviourConfiguration = this.buttonsConfiguration['pastebehaviour'];
		}
		this.cleaners = {};
		Ext.iterate(this.cleanerConfig, function (behaviour) {
			if (this.pasteBehaviourConfiguration && this.pasteBehaviourConfiguration[behaviour]) {
				if (this.pasteBehaviourConfiguration[behaviour].keepTags) {
					this.cleanerConfig[behaviour].keepTags = new RegExp( '^(' + this.pasteBehaviourConfiguration[behaviour].keepTags.split(',').join('|') + ')$', 'i');
				}
				if (this.pasteBehaviourConfiguration[behaviour].removeAttributes) {
					this.cleanerConfig[behaviour].removeAttributes = new RegExp( '^(' + this.pasteBehaviourConfiguration[behaviour].removeAttributes.split(',').join('|') + ')$', 'i');
				}
			}
			this.cleaners[behaviour] = new HTMLArea.DOM.Walker(this.cleanerConfig[behaviour]);
		}, this);
			// Initial behaviour
		this.currentBehaviour = 'plainText';
			// May be set in TYPO3 User Settings
		if (this.buttonsConfiguration && this.buttonsConfiguration['pastebehaviour'] && this.buttonsConfiguration['pastebehaviour']['current']) {
			this.currentBehaviour = this.buttonsConfiguration['pastebehaviour']['current'];
		}
			// Set the toggle ON, if configured
		if (this.buttonsConfiguration && this.buttonsConfiguration['pastetoggle'] && this.buttonsConfiguration['pastetoggle'].setActiveOnRteOpen) {
			this.toggleButton('PasteToggle');
		}
			// Start monitoring paste events
		this.editor.iframe.mon(Ext.get(Ext.isIE ? this.editor.document.body : this.editor.document.documentElement), 'paste', this.onPaste, this);
	},
	/*
	 * This function toggles the state of a button
	 *
	 * @param	string		buttonId: id of button to be toggled
	 *
	 * @return	void
	 */
	toggleButton: function (buttonId) {
			// Set new state
		var button = this.getButton(buttonId);
		button.setInactive(!button.inactive);
	},
	/*
	 * This function gets called when a button was pressed.
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
		switch (buttonId) {
			case 'PasteBehaviour':
					// Open dialogue window
				this.openDialogue(
					buttonId,
					'PasteBehaviourTooltip',
					this.getWindowDimensions(
						{
							width: 260,
							height:260
						},
						buttonId
					)
				);
				break;
			case 'PasteToggle':
				this.toggleButton(buttonId);
				this.editor.focus();
				break;
			}
		return false;
	},
	/*
	 * Open the dialogue window
	 *
	 * @param	string		buttonId: the button id
	 * @param	string		title: the window title
	 * @param	object		dimensions: the opening dimensions of the window
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
					defaultType: 'radio',
					title: this.getHelpTip('behaviour', title),
					labelWidth: 170,
					defaults: {
						labelSeparator: '',
						name: buttonId
					},
					items: [{
							itemId: 'plainText',
							fieldLabel: this.getHelpTip('plainText', 'plainText'),
							checked: (this.currentBehaviour === 'plainText')
						},{
							itemId: 'pasteStructure',
							fieldLabel: this.getHelpTip('pasteStructure', 'pasteStructure'),
							checked: (this.currentBehaviour === 'pasteStructure')
						},{
							itemId: 'pasteFormat',
							fieldLabel: this.getHelpTip('pasteFormat', 'pasteFormat'),
							checked: (this.currentBehaviour === 'pasteFormat')
						}
					]
				}
			],
			buttons: [
				this.buildButtonConfig('OK', this.onOK)
			]
		});
		this.show();
	},
	/*
	 * Handler invoked when the OK button of the Clean Paste Behaviour window is pressed
	 */
	onOK: function () {
		var fields = [
			'plainText',
			'pasteStructure',
			'pasteFormat'
		];
		Ext.each(fields, function (field) {
			if (this.dialog.find('itemId', field)[0].getValue()) {
				this.currentBehaviour = field;
				return false;
			}
		}, this);
		this.close();
		return false;
	},
	/*
	 * Handler for paste event
	 *
	 * @param	object		event: the paste event
	 *
	 * @return	boolean		false, if the event was handled, true otherwise
	 */
	onPaste: function (event) {
		if (!this.getButton('PasteToggle').inactive) {
			switch (this.currentBehaviour) {
				case 'plainText':
					// Only IE before IE9 and Chrome will allow access to the clipboard content by default, in plain text only however
					if (HTMLArea.isIEBeforeIE9 || Ext.isChrome) {
						var clipboardText = this.grabClipboardText(event);
						if (clipboardText) {
							this.editor.getSelection().insertHtml(clipboardText);
						}
						return !this.clipboardText;
					}
				case 'pasteStructure':
				case 'pasteFormat':
					if (Ext.isIE) {
							// Show the pasting pad
						this.openPastingPad(
							'PasteToggle',
							this.currentBehaviour,
							this.getWindowDimensions(
								{
									width:  550,
									height: 550
								},
								'PasteToggle'
							)
						);
						if (HTMLArea.isIEBeforeIE9) {
							event.browserEvent.returnValue = false;
						} else {
							event.stopEvent();
						}
						return false;
					} else {
							// Redirect the paste operation to a hidden section
						this.redirectPaste();
							// Process the content of the hidden section after the paste operation is completed
							// WebKit seems to be pondering a very long time over what is happenning here...
						this.processPastedContent.defer(Ext.isWebKit ? 500 : 50, this);
					}
					break;
				default:
					break;
			}
		}
		return true;
	},
	/*
	 * Grab the text content directly from the clipboard
	 * If successful, stop the paste event
	 *
	 * @param	object		event: the paste event
	 *
	 * @return	string		clipboard content, in plain text, if access was granted
	 */
	grabClipboardText: function (event) {
		var clipboardText = '';
			// Grab the text content
		if (window.clipboardData || event.browserEvent.clipboardData || event.browserEvent.dataTransfer) {
			clipboardText = (window.clipboardData || event.browserEvent.clipboardData || event.browserEvent.dataTransfer).getData('text');
		}
		if (clipboardText) {
				// Stop the event
			event.stopEvent();
		} else {
				// If the user denied access to the clipboard, let the browser paste without intervention
			TYPO3.Dialog.InformationDialog({
				title: this.localize('Paste-as-Plain-Text'),
				msg: this.localize('Access-to-clipboard-denied')
			});
		}
		return clipboardText;
	},
	/*
	 * Redirect the paste operation towards a hidden section
	 *
	 * @return	void
	 */
	redirectPaste: function () {
			// Save the current selection
		this.bookmark = this.editor.getBookMark().get(this.editor.getSelection().createRange());
			// Create and append hidden section
		var hiddenSection = this.editor.document.createElement('div');
		HTMLArea.DOM.addClass(hiddenSection, 'htmlarea-paste-hidden-section');
		hiddenSection.setAttribute('style', 'position: absolute; left: -10000px; top: ' + this.editor.document.body.scrollTop + 'px; overflow: hidden;');
		hiddenSection = this.editor.document.body.appendChild(hiddenSection);
		if (Ext.isWebKit) {
			hiddenSection.innerHTML = '&nbsp;';
		}
			// Move the selection to the hidden section and let the browser paste into the hidden section
		this.editor.getSelection().selectNodeContents(hiddenSection);
	},
	/*
	 * Process the pasted content that was redirected towards a hidden section
	 * and insert it at the original selection
	 *
	 * @return	void
	 */
	processPastedContent: function () {
		this.editor.focus();
			// Get the hidden section
		var divs = this.editor.document.getElementsByClassName('htmlarea-paste-hidden-section');
		var hiddenSection = divs[0];
			// Delete any other hidden sections
		for (var i = divs.length; --i >= 1;) {
			HTMLArea.DOM.removeFromParent(divs[i]);
		}
		var content = '';
		switch (this.currentBehaviour) {
			case 'plainText':
					// Get plain text content
				content = hiddenSection.textContent;
				break;
			case 'pasteStructure':
			case 'pasteFormat':
					// Get clean content
				content = this.cleaners[this.currentBehaviour].render(hiddenSection, false);
				break;
		}
			// Remove the hidden section from the document
		HTMLArea.DOM.removeFromParent(hiddenSection);
			// Restore the selection
		this.editor.getSelection().selectRange(this.editor.getBookMark().moveTo(this.bookmark));
			// Insert the cleaned content
		if (content) {
			this.editor.getSelection().execCommand('insertHTML', false, content);
		}
	},
	/*
	 * Open the pasting pad window (for IE)
	 *
	 * @param	string		buttonId: the button id
	 * @param	string		title: the window title
	 * @param	object		dimensions: the opening dimensions of the window
	 *
	 * @return	void
	 */
	openPastingPad: function (buttonId, title, dimensions) {
		this.dialog = new Ext.Window({
			title: this.getHelpTip(title, title),
			cls: 'htmlarea-window',
			bodyCssClass: 'pasting-pad',
			border: false,
			width: dimensions.width,
			height: 'auto',
			iconCls: this.getButton(buttonId).iconCls,
			listeners: {
				afterrender: {
						// The document will not be immediately ready
					fn: function (event) { this.onPastingPadAfterRender.defer(100, this, [event]); },
					scope: this
				},
				close: {
					fn: this.onClose,
					scope: this
				}
			},
			items: [{
					xtype: 'tbtext',
					text: this.getHelpTip('pasteInPastingPad', 'pasteInPastingPad'),
					style: {
						marginBottom: '5px'
					}
				},{
						// The iframe
					xtype: 'box',
					itemId: 'pasting-pad-iframe',
					autoEl: {
						name: 'contentframe',
						tag: 'iframe',
						cls: 'contentframe',
						src: Ext.isGecko ? 'javascript:void(0);' : HTMLArea.editorUrl + 'popups/blank.html'
					}
				}
			],
			buttons: [
				this.buildButtonConfig('OK', this.onPastingPadOK),
				this.buildButtonConfig('Cancel', this.onCancel)
			]
		});
		this.show();
	},
	/*
	 * Handler invoked after the pasting pad iframe has been rendered
	 */
	onPastingPadAfterRender: function () {
		var iframe = this.dialog.getComponent('pasting-pad-iframe').getEl().dom;
		this.pastingPadDocument = iframe.contentWindow ? iframe.contentWindow.document : iframe.contentDocument;
		this.pastingPadBody = this.pastingPadDocument.body;
		this.pastingPadBody.contentEditable = true;
		// Start monitoring paste events
		this.dialog.mon(Ext.get(this.pastingPadBody), 'paste', this.onPastingPadPaste, this);
		// Try to keep focus on the pasting pad
		this.dialog.mon(Ext.get(this.editor.document.documentElement), 'mouseover', function (event) { this.focusOnPastingPad(); }, this);
		this.dialog.mon(Ext.get(this.editor.document.body), 'focus', function (event) { this.focusOnPastingPad(); }, this);
		this.dialog.mon(Ext.get(this.pastingPadDocument.documentElement), 'mouseover', function (event) { this.focusOnPastingPad(); }, this);
		this.focusOnPastingPad();
	},
	/*
	 * Bring focus and selection on the pasting pad
	 */
	focusOnPastingPad: function () {
		this.pastingPadBody.focus();
		this.pastingPadDocument.getSelection().selectAllChildren(this.pastingPadBody);
		this.pastingPadDocument.getSelection().collapseToEnd();
	},
	/*
	 * Handler invoked when content is pasted into the pasting pad
	 */
	onPastingPadPaste: function (event) {
			// Let the paste operation complete before cleaning
		this.cleanPastingPadContents.defer(50, this);
	},
	/*
	 * Clean the contents of the pasting pad
	 */
	cleanPastingPadContents: function () {
		var content = '';
		switch (this.currentBehaviour) {
			case 'plainText':
				// Get plain text content
				content = this.pastingPadBody.textContent;
				break;
			case 'pasteStructure':
			case 'pasteFormat':
				// Get clean content
				content = this.cleaners[this.currentBehaviour].render(this.pastingPadBody, false);
				break;
		}
		this.pastingPadBody.innerHTML = content;
		this.pastingPadBody.focus();
	},
	/*
	 * Handler invoked when the OK button of the Pasting Pad window is pressed
	 */
	onPastingPadOK: function () {
	 	 	// Restore the selection
		this.restoreSelection();
			// Insert the cleaned pasting pad content
		this.editor.getSelection().insertHtml(this.pastingPadBody.innerHTML);
		this.close();
		return false;
	},
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
		if (mode === 'wysiwyg' && this.editor.isEditable()) {
			switch (button.itemId) {
				case 'PasteToggle':
					button.setTooltip({
							title: this.localize((button.inactive ? 'enable' : 'disable') + this.currentBehaviour)
					});
					break;
			}
		}
	}
});
