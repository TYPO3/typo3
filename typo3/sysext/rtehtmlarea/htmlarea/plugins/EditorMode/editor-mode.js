/***************************************************************
*  Copyright notice
*
*  (c) 2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * EditorMode Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
EditorMode = HTMLArea.Plugin.extend({

	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},

	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function (editor) {

		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "0.1",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.sjbr.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: "SJBR",
			sponsorUrl	: "http://www.sjbr.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);

		/*
		 * Registering the buttons
		 */
		var buttonList = this.buttonList, buttonId;
		for (var i = 0, n = buttonList.length; i < n; ++i) {
			var button = buttonList[i];
			buttonId = button[0];
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + "-Tooltip"),
				action		: "onButtonPress",
				context		: button[1],
				textMode	: (buttonId == "TextMode")
			};
			this.registerButton(buttonConfiguration);
		}
		return true;
	 },

	/* The list of buttons added by this plugin */
	buttonList : [
		["TextMode", null]
	],

	/*
	 * This function gets called during the editor generation and initializes the editor mode
	 *
	 * @return	void
	 */
	init : function () {
		var doc = this.editor._doc;
			// Catch error if html content is invalid
		var documentIsWellFormed = true;
		try {
			doc.body.innerHTML = this.editor._textArea.value;
		} catch(e) {
			this.appendToLog("init", "The HTML document is not well-formed.");
			alert(this.localize("HTML-document-not-well-formed"));
			documentIsWellFormed = false;
		}
			// Set contents editable
		if (documentIsWellFormed) {
			if (HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera && !this.editor._initEditMode()) {
				return false;
			}
			if (HTMLArea.is_ie || HTMLArea.is_safari) {
				doc.body.contentEditable = true;
			}
			if (HTMLArea.is_opera || HTMLArea.is_safari) {
				doc.designMode = "on";
				this.setGeckoOptions();
			}
			this.editorMode = "wysiwyg";
			if (doc.body.contentEditable || doc.designMode == "on") {
				this.appendToLog("init", "Design mode successfully set.");
			}
		} else {
			this.editorMode = "textmode";
			this.setEditorMode("docnotwellformedmode");
			this.appendToLog("init", "Design mode could not be set.");
		}
		return true;
	},

	/*
	 * This function gets called when the editor is generated
	 *
	 * @return 	void
	 */
	onGenerate : function () {
			// Set some references
		this.textArea = this.editor._textArea;
		this.editorBody = this.editor._doc.body;
	},

	/*
	 * This function gets called when a button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress : function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		this.setEditorMode((this.getEditorMode() == buttonId.toLowerCase()) ? "wysiwyg" : buttonId.toLowerCase());
		return false;
	},

	/*
	 * Switch editor mode
	 *
	 * @param	string	"textmode" or "wysiwyg"; if no parameter was passed, toggle between modes.
	 * @return	void
	 */
	setEditorMode : function (mode) {
		var editor = this.editor;
		switch (mode) {
			case "textmode":
			case "docnotwellformedmode":
				this.textArea.value = this.getHTML();
				editor._iframe.style.display = "none";
				this.textArea.style.display = "block";
				this.editorMode = "textmode";
				break;
			case "wysiwyg":
				if (HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera) {
					editor._doc.designMode = "off";
				}
				try {
					this.editorBody.innerHTML = this.getHTML();
				} catch(e) {
					alert(HTMLArea.I18N.msg["HTML-document-not-well-formed"]);
					break;
				}
				this.textArea.style.display = "none";
				editor._iframe.style.display = "block";
				if (HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera) {
					editor._doc.designMode = "on";
				}
				this.editorMode = "wysiwyg";
				this.setGeckoOptions();
				break;
			case "htmlmode":
				this.editorMode = "htmlmode";
				break;
			default:
				return false;
		}
		if (mode !== "docnotwellformedmode") {
			editor.focusEditor();
		}
		for (var pluginId in editor.plugins) {
			if (editor.plugins.hasOwnProperty(pluginId)) {
				var pluginInstance = this.getPluginInstance(pluginId);
				if (typeof(pluginInstance.onMode) === "function") {
					pluginInstance.onMode(mode);
				}
			}
		}
	},

	/*
	 * Set gecko editing mode options (if we can... raises exception in Firefox 3)
	 *
	 * @return	void
	 */
	setGeckoOptions : function () {
		if (HTMLArea.is_gecko) {
			var doc = this.editor._doc;
			var config = this.editor.config;
			try {
				if (doc.queryCommandEnabled("insertbronreturn")) {
					doc.execCommand("insertbronreturn", false, config.disableEnterParagraphs);
				}
				if (doc.queryCommandEnabled("styleWithCSS")) {
					doc.execCommand("styleWithCSS", false, config.useCSS);
				} else if (!HTMLArea.is_opera && !HTMLArea.is_safari && doc.queryCommandEnabled("useCSS")) {
					doc.execCommand("useCSS", false, !config.useCSS);
				}
				if (!HTMLArea.is_opera && !HTMLArea.is_safari) {
					if (doc.queryCommandEnabled("enableObjectResizing")) {
						doc.execCommand("enableObjectResizing", false, !config.disableObjectResizing);
					}
					if (doc.queryCommandEnabled("enableInlineTableEditing")) {
						doc.execCommand("enableInlineTableEditing", false, (config.buttons.table && config.buttons.table.enableHandles) ? true : false);
					}
				}
			} catch(e) {}
		}
	},

	/*
	 * Get editor mode
	 *
	 * @return	string	the current editor mode
	 */
	getEditorMode : function() {
		return this.editorMode;
	},

	/*
	 * This function gets called when the toolbar is updated
	 *
	 * @return	void
	 */
	onUpdateToolbar : function () {
		var buttonList = this.buttonList, buttonId;
		for (var i = 0, n = buttonList.length; i < n; ++i) {
			buttonId = buttonList[i][0];
			if (this.isButtonInToolbar(buttonId)) {
				this.editor._toolbarObjects[buttonId].state("active", (this.getEditorMode() == buttonId.toLowerCase()));
			}
		}
	},

	/*
	 * Retrieve the HTML
	 * In the case of the wysiwyg mode, the html content is parsed
	 *
	 * @return	string	the textual html content from the current editing mode
	 */
	getHTML : function () {
		switch (this.editorMode) {
			case "wysiwyg":
				return HTMLArea.getHTML(this.editorBody, false, this.editor);
			case "textmode":
				return this.textArea.value;
		}
		return "";
	},

	/*
	 * Retrieve raw HTML
	 *
	 * @return	string	the textual html content from the current editing mode
	 */
	getInnerHTML : function () {
		switch (this.editorMode) {
			case "wysiwyg":
				return this.editorBody.innerHTML;
			case "textmode":
				return this.textArea.value;
		}
		return "";
	},

	/*
	 * Replace the HTML inside
	 *
	 * @param	string	html: the textual html
	 * @return	boolean	false
	 */
	setHTML : function (html) {
		switch (this.editorMode) {
			case "wysiwyg":
				this.editorBody.innerHTML = html;
				break;
			case "textmode":
				this.textArea.value = html;
				break;
		}
		return false;
	}
});
