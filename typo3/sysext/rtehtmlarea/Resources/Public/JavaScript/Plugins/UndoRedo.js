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
 * Undo Redo Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, UserAgent, Util) {

	var UndoRedo = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(UndoRedo, Plugin);
	Util.apply(UndoRedo.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {
			this.pageTSconfiguration = this.editorConfiguration.buttons.undo;
			this.customUndo = true;
			this.undoQueue = new Array();
			this.undoPosition = -1;
			// Maximum size of the undo queue
			this.undoSteps = 25;
			// The time interval at which undo samples are taken: 1/2 sec.
			this.undoTimeout = 500;

			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '2.2',
				developer	: 'Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca',
				copyrightOwner	: 'Stanislas Rolland',
				sponsor		: 'SJBR',
				sponsorUrl	: 'http://www.sjbr.ca',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);
			/**
			 * Registering the buttons
			 */
			var buttonList = this.buttonList, buttonId;
			for (var i = 0; i < buttonList.length; ++i) {
				var button = buttonList[i];
				buttonId = button[0];
				var buttonConfiguration = {
					id		: buttonId,
					tooltip		: this.localize(buttonId.toLowerCase()),
					iconCls		: 'htmlarea-action-' + button[3],
					action		: 'onButtonPress',
					hotKey		: ((this.editorConfiguration.buttons[buttonId.toLowerCase()] && this.editorConfiguration.buttons[buttonId.toLowerCase()].hotKey) ? this.editorConfiguration.buttons[buttonId.toLowerCase()].hotKey : button[2]),
					noAutoUpdate	: true
				};
				this.registerButton(buttonConfiguration);
			}
			return true;
		},

		/**
		 * The list of buttons added by this plugin
		 */
		buttonList: [
			['Undo', null, 'z', 'undo'],
			['Redo', null, 'y', 'redo']
		],

		/**
		 * This function gets called when the editor is generated
		 */
		onGenerate: function () {
			// Start undo snapshots
			this.start();
		},

		/**
		 * Start the undo/redo snapshot task
		 */
		start: function () {
			if (this.customUndo) {
				this.stop();
				var self = this;
				this.task = window.setInterval(function () {
					self.takeSnapshot();
				}, this.undoTimeout);
			}
		},

		/**
		 * Stop the undo/redo snapshot task
		 */
		stop: function () {
			if (this.customUndo && this.task) {
				window.clearInterval(this.task);
			}
		},

		/**
		 * Take a snapshot of the current contents for undo
		 */
		takeSnapshot: function () {
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
			var text = this.editor.getInnerHTML();

			if (newSnapshot) {
				// If previous slot contains the same text, a new one should not be used
				if (this.undoPosition == 0 || this.undoQueue[this.undoPosition - 1].text != text) {
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
		buildSnapshot: function () {
			var bookmark = null, bookmarkedText = null;
				// Insert a bookmark
			if (this.getEditorMode() === 'wysiwyg' && this.editor.isEditable()) {
				if (!(UserAgent.isOpera && navigator.userAgent.toLowerCase().indexOf('presto/2.1') !== -1)) {
						// Catch error in FF when the selection contains no usable range
					try {
						var range = this.editor.getSelection().createRange();
						bookmark = this.editor.getBookMark().get(range, true);
					} catch (e) {
						bookmark = null;
					}
				}
			}
			return {
				text: this.editor.getInnerHTML(),
				bookmark: bookmark,
				bookmarkedText: bookmarkedText
			};
		},
		/*
		 * Execute the undo request
		 */
		undo: function () {
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
		redo: function () {
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
		setContent: function (undoPosition) {
			var bookmark = this.undoQueue[undoPosition].bookmark;
			if (bookmark) {
				this.editor.setHTML(this.undoQueue[undoPosition].text);
				this.editor.getSelection().selectRange(this.editor.getBookMark().moveTo(bookmark));
				this.editor.scrollToCaret();
			} else {
				this.editor.setHTML(this.undoQueue[undoPosition].text);
			}
		},
		/*
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			if (mode == 'wysiwyg' && this.editor.isEditable()) {
				if (this.customUndo) {
					switch (button.itemId) {
						case 'Undo':
							button.setDisabled(this.undoPosition == 0);
							break;
						case 'Redo':
							button.setDisabled(this.undoPosition >= this.undoQueue.length-1);
							break;
					}
				} else {
					try {
						button.setDisabled(!this.editor.document.queryCommandEnabled(button.itemId));
					} catch (e) {
						button.setDisabled(true);
					}
				}
			} else {
				button.setDisabled(!button.textMode);
			}
		},
		/*
		 * Update the state of the undo/redo buttons
		 */
		updateButtonsState: function () {
			var mode = this.getEditorMode(),
				selectionEmpty = true,
				ancestors = null;
			if (mode === 'wysiwyg') {
				selectionEmpty = this.editor.getSelection().isEmpty();
				ancestors = this.editor.getSelection().getAllAncestors();
			}
			var button = this.getButton('Undo');
			if (button) {
				this.onUpdateToolbar(button, mode, selectionEmpty, ancestors)
			}
			var button = this.getButton('Redo');
			if (button) {
				this.onUpdateToolbar(button, mode, selectionEmpty, ancestors)
			}
		},

		/**
		 * This function gets called when the button was pressed.
		 *
		 * @param	object		editor: the editor instance
		 * @param	string		id: the button id or the key
		 *
		 * @return	boolean		false if action is completed
		 */
		onButtonPress: function (editor, id) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			if (this.getButton(buttonId) && !this.getButton(buttonId).disabled) {
				if (this.customUndo) {
					this[buttonId.toLowerCase()]();
				} else {
					this.editor.getSelection().execCommand(buttonId, false, null);
				}
			}
			return false;
		}
	});

	return UndoRedo;

});
