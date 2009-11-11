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
 * StatusBar Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
StatusBar = HTMLArea.Plugin.extend({

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
		 * Making function refernce to status bar handler
		 */
		this.statusBarHandlerFunctRef = this.makeFunctionReference("statusBarHandler");

		return true;
	 },

	/*
	 * Create the status bar
	 */
	onGenerate : function () {
		var statusBar = document.createElement("div");
		this.statusBar = statusBar;
		statusBar.className = "statusBar";
		var statusBarTree = document.createElement("span");
		this.statusBarTree = statusBarTree;
		statusBarTree.className = "statusBarTree";
		statusBar.appendChild(statusBarTree);
		statusBarTree.appendChild(document.createTextNode(HTMLArea.I18N.msg["Path"] + ": "));
		this.editor._htmlArea.appendChild(this.statusBar);
		this.setSelection(null);
		this.noUpdate = false;
	},

	/*
	 * Adapt status bar to current editor mode
	 *
	 * @param	string	mode: the mode to which the editor got switched to
	 */
	onMode : function (mode) {
		switch (mode) {
			case "wysiwyg":
				this.statusBar.innerHTML = "";
				this.statusBar.appendChild(this.statusBarTree);
				break;
			case "textmode":
			default:
				var statusBarTextMode = document.createElement("span");
				statusBarTextMode.className = "statusBarTextMode";
				statusBarTextMode.appendChild(document.createTextNode(HTMLArea.I18N.msg["TEXT_MODE"]));
				this.statusBar.innerHTML = "";
				this.statusBar.appendChild(statusBarTextMode);
				break;
		}
	},

	/*
	 * Replace the contents of the staus bar with a text string
	 *
	 * @param	string	text: the text string to be inserted in the status bar
	 */
	setText : function(text) {
		this.statusBarTree.innerHTML = text;
	},

	/*
	 * Clear the status bar
	 */
	clear : function() {
			// Unhook events handlers
		if (this.statusBarTree) {
			if (this.statusBarTree.hasChildNodes()) {
				for (var element = this.statusBarTree.firstChild; element; element = element.nextSibling) {
					if (element.nodeName.toLowerCase() == "a") {
						HTMLArea._removeEvents(element, ["click", "contextmenu", "mousedown"], this.statusBarHandlerFunctRef);
						element.ancestor = null;
						element.editor = null;
					}
				}
			}
			this.statusBarTree.innerHTML = "";
		}
		this.setSelection(null);
	},

	/*
	 * Cleanup the status bar when the editor closes
	 */
	onClose : function() {
		 this.clear();
		 this.statusBarHandlerFunctRef = null;
		 this.statusBar = null;
		 this.statusBarTree =  null;
	 },

	/*
	 * Get the status bar selection
	 */
	getSelection : function() {
		return this.selected;
	},

	/*
	 * Set the status bar selection
	 *
	 * @param	object	element: set the status bar selection to the given element
	 */
	setSelection : function(element) {
		this.selected = element ? element : null;
	},

	/*
	 * Update the status bar
	 */
	onUpdateToolbar : function() {
		if (this.getEditorMode() == "wysiwyg" && !this.noUpdate) {
			var text,
				language,
				languageObject = this.editor.getPluginInstance("Language"),
				classes = new Array(),
				classText,
				ancestors = this.editor.getAllAncestors();
			this.clear();
			this.statusBarTree.appendChild(document.createTextNode(HTMLArea.I18N.msg["Path"] + ": "));
			for (var i = ancestors.length; --i >= 0;) {
				var ancestor = ancestors[i];
				if (!ancestor) {
					continue;
				}
				var element = document.createElement("a");
				element.href = "#";
				element.ancestor = ancestor;
				element.editor = this.editor;
				HTMLArea._addEvent(element, (HTMLArea.is_ie ? "click" : "mousedown"), this.statusBarHandlerFunctRef);
				if (!HTMLArea.is_opera) {
					HTMLArea._addEvent(element, "contextmenu", this.statusBarHandlerFunctRef);
				}
				element.title = ancestor.style.cssText;
				text = ancestor.nodeName.toLowerCase();
				if (ancestor.id) {
					text += "#" + ancestor.id;
				}
				if (languageObject && languageObject.getLanguageAttribute) {
					language = languageObject.getLanguageAttribute(ancestor);
					if (language != "none") {
						text += "[" + language + "]";
					}
				}
				if (ancestor.className) {
					classText = "";
					classes = ancestor.className.trim().split(" ");
					for (var j = 0, n = classes.length; j < n; ++j) {
						if (!HTMLArea.reservedClassNames.test(classes[j])) {
							classText += "." + classes[j];
						}
					}
					text += classText;
				}
				element.appendChild(document.createTextNode(text));
				this.statusBarTree.appendChild(element);
				if (i) {
					this.statusBarTree.appendChild(document.createTextNode(String.fromCharCode(0xbb)));
				}
			}
		}
		this.noUpdate = false;
	},

	/*
	 * Handle statusbar element events
	 */
	statusBarHandler : function (ev) {
		if (!ev) {
			var ev = window.event;
		}
		var target = (ev.target) ? ev.target : ev.srcElement;
		var editor = target.editor;
		target.blur();
		if (HTMLArea.is_gecko) {
			editor.selectNodeContents(target.ancestor);
		} else {
			var nodeName = target.ancestor.nodeName.toLowerCase();
			if (nodeName == "table" || nodeName == "img") {
				var range = editor._doc.body.createControlRange();
				range.addElement(target.ancestor);
				range.select();
			} else {
				editor.selectNode(target.ancestor);
			}
		}
		this.setSelection(target.ancestor);
		this.noUpdate = true;
		editor.updateToolbar();
		switch (ev.type) {
			case "mousedown" :
				if (HTMLArea.is_ie) {
					return true;
				}
			case "click" :
				HTMLArea._stopEvent(ev);
				return false;
			case "contextmenu" :
				return editor.getPluginInstance("ContextMenu") ? editor.getPluginInstance("ContextMenu").popupMenu(ev, target.ancestor) : false;
		}
	}
});
