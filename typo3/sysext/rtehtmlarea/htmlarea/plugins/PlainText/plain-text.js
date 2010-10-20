/***************************************************************
*  Copyright notice
*
*  (c) 2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 *
 * TYPO3 SVN ID: $Id: plain-text.js 8945 2010-10-04 03:00:03Z stan $
 */
HTMLArea.PlainText = HTMLArea.Plugin.extend({
	constructor: function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function(editor) {
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '1.0',
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
		var buttonId = 'PlainText';
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize(buttonId + 'Tooltip'),
			iconCls		: 'htmlarea-action-paste-as-plain-text',
			action		: 'onButtonPress'
		};
		this.registerButton(buttonConfiguration);
		return true;
	},
	/*
	 * This function gets called when the plugin is generated
	 */
	onGenerate: function () {
			// Initialize state of toggle
		this.togglePasteAsPlainText(false);
			// Start monitoring paste events
		this.editor.iframe.mon(Ext.get(Ext.isIE ? this.editor.document.body : this.editor.document.documentElement), 'paste', this.onPaste, this);
	},
	/*
	 * This function toggles the state of Paste as Plain text
	 *
	 * @param	boolean		state: if defined, the specified state to set
	 *
	 * @return	void
	 */
	togglePasteAsPlainText: function (state) {
			// Set new state
		this.pasteAsPlainTextActive = (typeof(state) != 'undefined') ? state : !this.pasteAsPlainTextActive;
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
		this.togglePasteAsPlainText();
		return false;
	},
	/*
	 * This function gets called when the toolbar is updated
	 *
	 * @return	void
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
		if (this.getEditorMode() === 'wysiwyg' && this.editor.isEditable()) {
			button.setInactive(!this.pasteAsPlainTextActive);
		}
	},
	/*
	 * Handler for paste event
	 */
	onPaste: function (event) {
		if (this.pasteAsPlainTextActive) {
			this.grabClipboardText(event);
			if (this.clipboardText) {
					// Direct access to the clipboard text was possible
				this.pasteAsPlainText();
			} else {
					// Get the text content from the hidden section
					// after the paste operation is completed
				this.getClipboardText.defer(10, this);
			}
			return !this.clipboardText;
		}
	},
	/*
	 * Grab the text content directly from the clipboard or
	 * redirect the paste operation towards a hidden section
	 *
	 * @param	object		event: the paste event
	 *
	 * @return	void
	 */
	grabClipboardText: function (event) {
		this.clipboardText = null;
			// Check if browser supports direct plaintext access (IE and WebKit)
		if (window.clipboardData || event.browserEvent.clipboardData || event.browserEvent.dataTransfer) {
				// Grab the text content
			this.clipboardText = (window.clipboardData || event.browserEvent.clipboardData || event.browserEvent.dataTransfer).getData('Text');
			if (this.clipboardText) {
					// Stop the event
				event.stopEvent();
			} else {
				TYPO3.Dialog.InformationDialog({
					title: this.localize('Paste-as-Plain-Text'),
					msg: this.localize('Access-to-clipboard-denied')
				});
			}
		} else {
				// When direct access was not possible
				// Save the current selection
			var selection = this.editor._getSelection();
			var range = this.editor._createRange(selection);
			this.bookmark = this.editor.getBookmark(range);
				// Create and append hidden section
			this.hiddenSection = this.editor.document.createElement('div');
			this.hiddenSection.id = this.editorId + 'htmlarea-paste-hidden-section';
			this.hiddenSection.style.position = 'absolute';
			this.hiddenSection.style.left = -10000;
			this.hiddenSection.style.top = this.editor.document.body.scrollTop;
			this.hiddenSection.style.overflow = 'hidden';
			this.hiddenSection = this.editor.document.body.appendChild(this.hiddenSection);
				// Move the selection to the hidden section and
				// let the browser paste into the hidden section
			this.editor.selectNodeContents(this.hiddenSection, true);
		}
	},
	/*
	 * If the paste operation was redirected towards a hidden section
	 * get the text content from the section
	 *
	 * @return	void
	 */
	getClipboardText: function () {
			// Get the text content
		this.clipboardText = this.hiddenSection.textContent;
			// Delete the hidden section
		HTMLArea.removeFromParent(this.hiddenSection);
			// Restore the selection
		this.editor.selectRange(this.editor.moveToBookmark(this.bookmark));
		this.pasteAsPlainText();
	},
	/*
	 * Paste as plain text
	 */
	pasteAsPlainText: function () {
	 	this.editor.insertHTML(this.clipboardText);
	}
});
