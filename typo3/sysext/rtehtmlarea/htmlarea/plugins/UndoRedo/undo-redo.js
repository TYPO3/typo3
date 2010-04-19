/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Undo Redo Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
UndoRedo = HTMLArea.Plugin.extend({
	
	constructor : function (editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function (editor) {
		
		this.pageTSconfiguration = this.editorConfiguration.buttons.undo;
		this.customUndo = true;
		this.undoQueue = new Array();
		this.undoPosition = -1;
			// Maximum size of the undo queue
		this.undoSteps = 25;
			// The time interval at which undo samples are taken: 1/2 sec.
		this.undoTimeout = 500;
		this.undoTimer;
		
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.0",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.sjbr.ca",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: "SJBR",
			sponsorUrl	: "http://www.sjbr.ca",
			license		: "GPL"
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
				action		: "onButtonPress",
				context		: button[1],
				hotKey		: ((this.editorConfiguration.buttons[buttonId.toLowerCase()] && this.editorConfiguration.buttons[buttonId.toLowerCase()].hotKey) ? this.editorConfiguration.buttons[buttonId.toLowerCase()].hotKey : button[2])
			};
			this.registerButton(buttonConfiguration);
		}
		
		return true;
	},
	
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList : [
		["Undo", null, "z"],
		["Redo", null, "y"]
	],
	
	/*
	 * This function gets called when the editor is generated
	 */
	onGenerate : function () {
			// Start undo snapshots
		if (this.customUndo) {
			var takeSnapshotFunctRef = this.makeFunctionReference("takeSnapshot");
			this.undoTimer = window.setInterval(takeSnapshotFunctRef, this.undoTimeout);
		}
	},
	
	/*
	 * This function gets called when the editor is closing
	 */
	onClose : function () {
			// Clear snapshot interval
		window.clearInterval(this.undoTimer);
			// Release undo/redo snapshots
		this.undoQueue = null;
	},
	
	/*
	 * Take a snapshot of the current contents for undo
	 */
	takeSnapshot : function () {
		var currentTime = (new Date()).getTime();
		var newSnapshot = false;
		if (this.undoPosition >= this.undoSteps) {
				// Remove the first element
			this.undoQueue.shift();
			--this.undoPosition;
		}
			// New undo slot should be used if this is first takeSnapshot call or if undoTimeout is elapsed
		if (this.undoPosition < 0 || this.undoQueue[this.undoPosition].time < currentTime - this.undoTimeout) {
			++this.undoPosition;
			newSnapshot = true;
		}
			// Get the html text
		var text = this.getPluginInstance("EditorMode").getInnerHTML();
		
		if (newSnapshot) {
				// If previous slot contains the same text, a new one should not be used
			if (this.undoPosition == 0  || this.undoQueue[this.undoPosition - 1].text != text) {
				this.undoQueue[this.undoPosition] = this.buildSnapshot();
				this.undoQueue[this.undoPosition].time = currentTime;
				this.undoQueue.length = this.undoPosition + 1;
				this.updateButtonsState();
			} else {
				--this.undoPosition;
			}
		} else {
			if (this.undoQueue[this.undoPosition].text != text){
				var snapshot = this.buildSnapshot();
				this.undoQueue[this.undoPosition].text = snapshot.text;
				this.undoQueue[this.undoPosition].bookmark = snapshot.bookmark;
				this.undoQueue[this.undoPosition].bookmarkedText = snapshot.bookmarkedText;
				this.undoQueue.length = this.undoPosition + 1;
			}
		}
	},

	/*
	 * Build the snapshot entry
	 *
	 * @return	object	a snapshot entry with three components:
	 *				- text (the content of the RTE without any bookmark),
	 *				- bookmark (the bookmark),
	 *				- bookmarkedText (the content of the RTE including the bookmark)
	 */
	buildSnapshot : function () {
		var bookmark = null, bookmarkedText = null;
			// Insert a bookmark
		if (this.editor.getMode() == "wysiwyg" && this.editor.isEditable()) {
			var selection = this.editor._getSelection();
			if ((HTMLArea.is_gecko && !HTMLArea.is_opera9) || (HTMLArea.is_ie && selection.type.toLowerCase() != "control")) {
					// Catch error in FF when the selection contains no usable range
				try {
						// Work around IE8 bug: can't create a range correctly if the selection is empty and the focus is not on the editor window
						// But we cannot grab focus from an opened window just for the sake of taking this bookmark
					if (!HTMLArea.is_ie || !this.editor.hasOpenedWindow() || selection.type.toLowerCase() != "none") {
						bookmark = this.editor.getBookmark(this.editor._createRange(selection));
					}
				} catch (e) {
					bookmark = null;
				}
			}
				// Get the bookmarked html text and remove the bookmark
			if (bookmark) {
				bookmarkedText = this.getPluginInstance("EditorMode").getInnerHTML();
				var range = this.editor.moveToBookmark(bookmark);
					// Restore Firefox selection
				if (HTMLArea.is_gecko && !HTMLArea.is_opera && !HTMLArea.is_safari) {
					this.editor.emptySelection(selection);
					this.editor.addRangeToSelection(selection, range);
				}
			}
		}
		return {
			text		: this.getPluginInstance("EditorMode").getInnerHTML(),
			bookmark	: bookmark,
			bookmarkedText	: bookmarkedText
		};
	},
	
	/*
	 * Execute the undo request
	 */
	undo : function () {
		if (this.undoPosition > 0) {
				// Make sure we would not loose any changes
			this.takeSnapshot();
			this.setContent(--this.undoPosition);
			this.updateButtonsState();
		}
	},
	
	/*
	 * Execute the redo request
	 */
	redo : function () {
		if (this.undoPosition < this.undoQueue.length - 1) {
				// Make sure we would not loose any changes
			this.takeSnapshot();
				// Previous call could make undo queue shorter
			if (this.undoPosition < this.undoQueue.length - 1) {
				this.setContent(++this.undoPosition);
				this.updateButtonsState();
			}
		}
	},
	
	/*
	 * Set content using undo queue position
	 */
	setContent : function (undoPosition) {
		var bookmark = this.undoQueue[undoPosition].bookmark;
		if (bookmark) {
			this.getPluginInstance("EditorMode").setHTML(this.undoQueue[undoPosition].bookmarkedText);
			this.editor.focusEditor();
			this.editor.selectRange(this.editor.moveToBookmark(bookmark));
			this.editor.scrollToCaret();
		} else {
			this.getPluginInstance("EditorMode").setHTML(this.undoQueue[undoPosition].text);
		}
	},
	
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar : function () {
		this.updateButtonsState();
	},
	
	/*
	 * Update the state of the undo/redo buttons
	 */
	updateButtonsState : function () {
		if (this.editor.getMode() == "wysiwyg" && this.editor.isEditable()) {
			if (this.customUndo) {
				if (this.isButtonInToolbar("Undo")) {
					this.editor._toolbarObjects.Undo.state("enabled", this.undoPosition > 0);
				}
				if (this.isButtonInToolbar("Redo")) {
					this.editor._toolbarObjects.Redo.state("enabled", this.undoPosition < this.undoQueue.length-1);
				}
			} else {
				try {
					if (this.isButtonInToolbar("Undo")) {
						this.editor._toolbarObjects.Undo.state("enabled", this.editor._doc.queryCommandEnabled("Undo"));
					}
					if (this.isButtonInToolbar("Redo")) {
						this.editor._toolbarObjects.Redo.state("enabled", this.editor._doc.queryCommandEnabled("Redo"));
					}
				} catch (e) {
					if (this.isButtonInToolbar("Undo")) {
						this.editor._toolbarObjects.Undo.state("enabled", false);
					}
					if (this.isButtonInToolbar("Redo")) {
						this.editor._toolbarObjects.Redo.state("enabled", false);
					}
				}
			}
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
	onButtonPress : function (editor, id) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		if (this.isButtonInToolbar(buttonId) && !this.editor._toolbarObjects[buttonId].disabled) {
			if (this.customUndo) {
				this[buttonId.toLowerCase()]();
			} else {
				this.editor._doc.execCommand(buttonId, false, null);
			}
		}
		return false;
	}
});
