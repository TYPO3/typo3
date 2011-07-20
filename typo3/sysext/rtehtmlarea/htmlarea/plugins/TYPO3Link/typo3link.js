/***************************************************************
*  Copyright notice
*
*  (c) 2005-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * TYPO3Link plugin for htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
HTMLArea.TYPO3Link = HTMLArea.Plugin.extend({
	constructor: function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function(editor) {
		this.pageTSConfiguration = this.editorConfiguration.buttons.link;
		this.modulePath = this.pageTSConfiguration.pathLinkModule;
		this.classesAnchorUrl = this.pageTSConfiguration.classesAnchorUrl;
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '2.1',
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
	 * This function is invoked when the editor is being generated
	 */
	onGenerate: function () {
			// Download the definition of special anchor classes if not yet done
		if (this.classesAnchorUrl && (typeof(HTMLArea.classesAnchorSetup) === 'undefined')) {
			this.getJavascriptFile(this.classesAnchorUrl, function (options, success, response) {
				if (success) {
					try {
						if (typeof(HTMLArea.classesAnchorSetup) === 'undefined') {
							eval(response.responseText);
							this.appendToLog('ongenerate', 'Javascript file successfully evaluated: ' + this.classesAnchorUrl);
						}
					} catch(e) {
						this.appendToLog('ongenerate', 'Error evaluating contents of Javascript file: ' + this.classesAnchorUrl);
					}
				}
			});
		}
	},
	/*
	 * This function gets called when the button was pressed
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
			// Download the definition of special anchor classes if not yet done
		if (this.classesAnchorUrl && (typeof(HTMLArea.classesAnchorSetup) === 'undefined')) {
			this.getJavascriptFile(this.classesAnchorUrl, function (options, success, response) {
				if (success) {
					try {
						if (typeof(HTMLArea.classesAnchorSetup) === 'undefined') {
							eval(response.responseText);
							this.appendToLog('onButtonPress', 'Javascript file successfully evaluated: ' + this.classesAnchorUrl);
						}
						this.onButtonPress(editor, id, target);
					} catch(e) {
						this.appendToLog('onButtonPress', 'Error evaluating contents of Javascript file: ' + this.classesAnchorUrl);
					}
				}
			});
		} else {
			if (buttonId === "UnLink") {
				this.unLink(true);
				return false;
			}
			var additionalParameter;
			var node = this.editor.getParentElement();
			var el = HTMLArea.getElementObject(node, "a");
			if (el != null && /^a$/i.test(el.nodeName)) node = el;
			if (node != null && /^a$/i.test(node.nodeName)) {
				additionalParameter = "&curUrl[href]=" + encodeURIComponent(node.getAttribute("href"));
				if (node.target) additionalParameter += "&curUrl[target]=" + encodeURIComponent(node.target);
				if (node.className) additionalParameter += "&curUrl[class]=" + encodeURIComponent(node.className);
				if (node.title) additionalParameter += "&curUrl[title]=" + encodeURIComponent(node.title);
				if (this.pageTSConfiguration && this.pageTSConfiguration.additionalAttributes) {
					var additionalAttributes = this.pageTSConfiguration.additionalAttributes.split(",");
					for (var i = additionalAttributes.length; --i >= 0;) {
							// hasAttribute() not available in IE < 8
						if ((node.hasAttribute && node.hasAttribute(additionalAttributes[i])) || node.getAttribute(additionalAttributes[i]) != null) {
							additionalParameter += "&curUrl[" + additionalAttributes[i] + "]=" + encodeURIComponent(node.getAttribute(additionalAttributes[i]));
						}
					}
				}
			} else if (this.editor.hasSelectedText()) {
				var text = this.editor.getSelectedHTML();
				if (text && text != null) {
					var offset = text.toLowerCase().indexOf("<a");
					if (offset!=-1) {
						var ATagContent = text.substring(offset+2);
						offset = ATagContent.toUpperCase().indexOf(">");
						ATagContent = ATagContent.substring(0,offset);
						additionalParameter = "&curUrl[all]=" + encodeURIComponent(ATagContent);
					}
				}
			}
			this.openContainerWindow(
				buttonId,
				this.getButton(buttonId).tooltip.title,
				this.getWindowDimensions(
					{
						width:	550,
						height:	500
					},
					buttonId
				),
				this.makeUrlFromModulePath(this.modulePath, additionalParameter)
			);
		}
		return false;
	},
	/*
	 * Add a link to the selection.
	 * This function is called from the TYPO3 link popup.
	 *
	 * @param	string	theLink: the href attribute of the link to be created
	 * @param	string	cur_target: value for the target attribute
	 * @param	string	cur_class: value for the class attribute
	 * @param	string	cur_title: value for the title attribute
	 * @param	object	additionalValues: values for additional attributes (may be used by extension)
	 *
	 * @return void
	 */
	createLink : function(theLink,cur_target,cur_class,cur_title,additionalValues) {
		var selection, range, anchorClass, imageNode = null, addIconAfterLink;
		this.editor.focus();
		this.restoreSelection();
		var node = this.editor.getParentElement();
			// Looking at parent
		var el = HTMLArea.getElementObject(node, 'a');
		if (el != null && /^a$/i.test(el.nodeName)) {
			node = el;
		}
		if (HTMLArea.classesAnchorSetup && cur_class) {
			for (var i = HTMLArea.classesAnchorSetup.length; --i >= 0;) {
				anchorClass = HTMLArea.classesAnchorSetup[i];
				if (anchorClass.name == cur_class && anchorClass.image) {
					imageNode = this.editor.document.createElement('img');
					imageNode.src = anchorClass.image;
					imageNode.alt = anchorClass.altText;
					addIconAfterLink = anchorClass.addIconAfterLink;
					break;
				}
			}
		}
		if (!Ext.isIE && node != null && /^a$/i.test(node.nodeName)) {
				// Update existing link in non-IE
			this.editor.selectNode(node);
			selection = this.editor._getSelection();
			range = this.editor._createRange(selection);
				// Clean images
			if (HTMLArea.classesAnchorSetup) {
				this.cleanAllLinks(node, range, true);
			}
				// Update link href
			node.href = Ext.isGecko ? encodeURI(theLink) : theLink;
				// Update link attributes
			this.setLinkAttributes(node, range, cur_target, cur_class, cur_title, imageNode, addIconAfterLink, additionalValues);
		} else {
				// Create new link
				// Update links in IE
			selection = this.editor._getSelection();
			range = this.editor._createRange(selection);
			if (Ext.isIE) {
					// Clean images, keep links
				if (HTMLArea.classesAnchorSetup) {
					this.cleanAllLinks(node, range, true);
				}
			} else {
					// Clean existing anchors otherwise Mozilla may create nested anchors
					// Selection may be lost when cleaning links
				var bookmark = this.editor.getBookmark(range);
				this.cleanAllLinks(node, range);
				this.editor.selectRange(this.editor.moveToBookmark(bookmark));
			}
			if (Ext.isGecko) {
				this.editor.document.execCommand('CreateLink', false, encodeURI(theLink));
			} else {
				this.editor.document.execCommand('CreateLink', false, theLink);
			}
				// Get the created link
			selection = this.editor._getSelection();
			range = this.editor._createRange(selection);
			node = this.editor.getParentElement();
				// Looking at parent
			var el = HTMLArea.getElementObject(node, 'a');
			if (el != null && /^a$/i.test(el.nodeName)) {
				node = el;
			}
			if (node) {
					// Export trailing br that IE may include in the link
				if (Ext.isIE) {
					if (node.lastChild && /^br$/i.test(node.lastChild.nodeName)) {
						HTMLArea.removeFromParent(node.lastChild);
						node.parentNode.insertBefore(this.editor.document.createElement('br'), node.nextSibling);
					}
				}
					// We may have created multiple links in as many blocks
				this.setLinkAttributes(node, range, cur_target, cur_class, cur_title, imageNode, addIconAfterLink, additionalValues);
			}
		}
		this.close();
	},
	
	/*
	 * Unlink the selection.
	 * This function is called from the TYPO3 link popup and from unlink button pressed in toolbar or context menu.
	 *
	 * @param	string	buttonPressd: true if the unlink button was pressed
	 *
	 * @return void
	 */
	unLink: function (buttonPressed) {
		this.editor.focus();
			// If no dialogue window was opened, the selection should not be restored
		if (!buttonPressed) {
			this.restoreSelection();
		}
		var node = this.editor.getParentElement();
		var el = HTMLArea.getElementObject(node, "a");
		if (el != null && /^a$/i.test(el.nodeName)) node = el;
		if (node != null && /^a$/i.test(node.nodeName)) this.editor.selectNode(node);
		if (HTMLArea.classesAnchorSetup) {
			var selection = this.editor._getSelection();
			var range = this.editor._createRange(selection);
			if (!Ext.isIE) {
				this.cleanAllLinks(node, range, false);
			} else {
				this.cleanAllLinks(node, range, true);
				this.editor._doc.execCommand("Unlink", false, "");
			}
		} else {
			this.editor._doc.execCommand("Unlink", false, "");
		}
		if (this.dialog) {
			this.close();
		}
	},
	
	/*
	* Set attributes of anchors intersecting a range in the given node
	*
	* @param	object	node: a node that may interesect the range
	* @param	object	range: set attributes on all nodes intersecting this range
	* @param	string	cur_target: value for the target attribute
	* @param	string	cur_class: value for the class attribute
	* @param	string	cur_title: value for the title attribute
	* @param	object	imageNode: image to clone and append to the anchor
	* @param	boolean	addIconAfterLink: add icon after rather than before the link
	* @param	object	additionalValues: values for additional attributes (may be used by extension)
	*
	* @return	void
	*/
	setLinkAttributes : function(node, range, cur_target, cur_class, cur_title, imageNode, addIconAfterLink, additionalValues) {
		if (/^a$/i.test(node.nodeName)) {
			var nodeInRange = false;
			if (!Ext.isIE) {
				nodeInRange = this.editor.rangeIntersectsNode(range, node);
			} else {
				if (this.editor._getSelection().type.toLowerCase() == "control") {
						// we assume an image is selected
					nodeInRange = true;
				} else {
					var nodeRange = this.editor._doc.body.createTextRange();
					nodeRange.moveToElementText(node);
					nodeInRange = nodeRange.inRange(range) || range.inRange(nodeRange) || (range.compareEndPoints("StartToStart", nodeRange) == 0) || (range.compareEndPoints("EndToEnd", nodeRange) == 0);
				}
			}
			if (nodeInRange) {
				if (imageNode != null) {
					if (addIconAfterLink) {
						node.appendChild(imageNode.cloneNode(false));
					} else {
						node.insertBefore(imageNode.cloneNode(false), node.firstChild);
					}
				}
				if (Ext.isGecko) {
					node.href = decodeURI(node.href);
				}
				if (cur_target.trim()) node.target = cur_target.trim();
					else node.removeAttribute("target");
				if (cur_class.trim()) {
					node.className = cur_class.trim();
				} else { 
					if (!Ext.isOpera) {
						node.removeAttribute('class');
						if (Ext.isIE) {
							node.removeAttribute('className');
						}
					} else {
						node.className = '';
					}
				}
				if (cur_title.trim()) {
					node.title = cur_title.trim();
				} else {
					node.removeAttribute("title");
					node.removeAttribute("rtekeep");
				}
				if (this.pageTSConfiguration && this.pageTSConfiguration.additionalAttributes && typeof(additionalValues) == "object") {
					for (additionalAttribute in additionalValues) {
						if (additionalValues.hasOwnProperty(additionalAttribute)) {
							if (additionalValues[additionalAttribute].toString().trim()) {
								node.setAttribute(additionalAttribute, additionalValues[additionalAttribute]);
							} else {
								node.removeAttribute(additionalAttribute);
							}
						}
					}
				}
			}
		} else {
			for (var i = node.firstChild;i;i = i.nextSibling) {
				if (i.nodeType == 1 || i.nodeType == 11) {
					this.setLinkAttributes(i, range, cur_target, cur_class, cur_title, imageNode, addIconAfterLink, additionalValues);
				}
			}
		}
	},
	
	/*
	 * Clean up images in special anchor classes
	 */
	cleanClassesAnchorImages : function(node) {
		var nodeArray = [], splitArray1 = [], splitArray2 = [];
		for (var childNode = node.firstChild; childNode; childNode = childNode.nextSibling) {
			if (/^img$/i.test(childNode.nodeName)) {
				splitArray1 = childNode.src.split("/");
				for (var i = HTMLArea.classesAnchorSetup.length; --i >= 0;) {
					if (HTMLArea.classesAnchorSetup[i]["image"]) {
						splitArray2 = HTMLArea.classesAnchorSetup[i]["image"].split("/");
						if (splitArray1[splitArray1.length-1] == splitArray2[splitArray2.length-1]) {
							nodeArray.push(childNode);
							break;
						}
					}
				}
			}
		}
		for (i = nodeArray.length; --i >= 0;) {
			node.removeChild(nodeArray[i]);
		}
	},
	
	/*
	 * Clean up all anchors intesecting with the range in the given node
	 */
	cleanAllLinks : function(node, range, keepLinks) {
		if (/^a$/i.test(node.nodeName)) {
			var intersection = false;
			if (!Ext.isIE) {
				intersection = this.editor.rangeIntersectsNode(range, node);
			} else {
				if (this.editor._getSelection().type.toLowerCase() == "control") {
						// we assume an image is selected
					intersection = true;
				} else {
					var nodeRange = this.editor._doc.body.createTextRange();
					nodeRange.moveToElementText(node);
					intersection = range.inRange(nodeRange) || ((range.compareEndPoints("StartToStart", nodeRange) > 0) && (range.compareEndPoints("StartToEnd", nodeRange) < 0)) || ((range.compareEndPoints("EndToStart", nodeRange) > 0) && (range.compareEndPoints("EndToEnd", nodeRange) < 0));
				}
			}
			if (intersection) {
				this.cleanClassesAnchorImages(node);
				if (!keepLinks) {
					while(node.firstChild) node.parentNode.insertBefore(node.firstChild, node);
					node.parentNode.removeChild(node);
				}
			}
		} else {
			for (var i = node.firstChild;i;i = i.nextSibling) {
				if (i.nodeType == 1 || i.nodeType == 11) this.cleanAllLinks(i, range, keepLinks);
			}
		}
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
							button.setTooltip({ title: this.localize('Modify link') });
						} else {
							button.setTooltip({ title: this.localize('Insert link') });
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
