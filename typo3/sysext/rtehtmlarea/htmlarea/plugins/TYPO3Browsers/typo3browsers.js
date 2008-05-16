/***************************************************************
*  Copyright notice
*
*  (c) 2005-2008 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * TYPO3 Image & Link Browsers Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 CVS ID: $Id$
 */

TYPO3Browsers = function(editor,args) {
	this.editor = editor;
	var cfg = this.editor.config;
	cfg.btnList.InsertImage[1] = this.editor.imgURL("ed_image.gif", "TYPO3Browsers");
	cfg.btnList.CreateLink[1] = this.editor.imgURL("ed_link.gif", "TYPO3Browsers");
};

TYPO3Browsers.I18N = TYPO3Browsers_langArray;

TYPO3Browsers._pluginInfo = {
	name		: "TYPO3Browsers",
	version		: "1.7",
	developer	: "Stanislas Rolland",
	developer_url 	: "http://www.fructifor.ca/",
	c_owner		: "Stanislas Rolland",
	sponsor		: "Fructifor Inc.",
	sponsor_url 	: "http://www.fructifor.ca/",
	license		: "GPL"
};

/*
 *  Insert Image TYPO3 RTE function.
 */
HTMLArea.prototype.renderPopup_image = function() {
	var editorNumber = this._editorNumber,
		backreturn,
		addParams = "?" + RTEarea[editorNumber]["RTEtsConfigParams"],
		image = this.getParentElement();
		
	this._selectedImage = null;
	if (image && image.tagName.toLowerCase() == "img") {
		addParams = "?act=image" + RTEarea[editorNumber]["RTEtsConfigParams"];
		this._selectedImage = image;
	}
	
	this._popupDialog(RTEarea[0]["pathImageModule"] + addParams + "&editorNo=" + editorNumber + "&sys_language_content=" + RTEarea[editorNumber]["sys_language_content"], null, backreturn, 550, 350, null, "yes");
	return false;
};

/*
 * Insert the Image.
 * This function is called from the typo3-image-popup.
 */
HTMLArea.prototype.renderPopup_insertImage = function(image) {
	this.focusEditor();
	this.insertHTML(image);
	this._selectedImage = null;
	Dialog._modal.close();
	this.updateToolbar();
};

/*
 *  CreateLink: Typo3-RTE function, use this instead of the original.
 */
HTMLArea.prototype.renderPopup_link = function() {
	var editorNumber = this._editorNumber,
		addUrlParams = "?" + RTEarea[editorNumber]["RTEtsConfigParams"],
		backreturn,
		sel = this.getParentElement();

		// Download the definition of special anchor classes if not yet done
	if(RTEarea[editorNumber]["classesAnchorUrl"] && !this.classesAnchorSetup) {
		var classesAnchorData = HTMLArea._getScript(0, false, RTEarea[editorNumber]["classesAnchorUrl"]);
		var editor = this;
		if(classesAnchorData) eval(classesAnchorData);
		editor = null;
	}

	var el = HTMLArea.getElementObject(sel,"a");
	if (el != null && el.tagName && el.tagName.toLowerCase() == "a") sel = el;
	if (sel != null && sel.tagName && sel.tagName.toLowerCase() == "a") {
		addUrlParams = "?curUrl[href]=" + encodeURIComponent(sel.getAttribute("href"));
		addUrlParams += "&curUrl[typo3ContentLanguage]=" + RTEarea[editorNumber]["typo3ContentLanguage"];
		addUrlParams += "&curUrl[typo3ContentCharset]=" + RTEarea[editorNumber]["typo3ContentCharset"];
		if (sel.target) addUrlParams += "&curUrl[target]=" + encodeURIComponent(sel.target);
		if (sel.className) addUrlParams += "&curUrl[class]=" + encodeURIComponent(sel.className);
		if (sel.title) addUrlParams += "&curUrl[title]=" + encodeURIComponent(sel.title);
		addUrlParams += RTEarea[editorNumber]["RTEtsConfigParams"];
	} else if (this.hasSelectedText()) {
		var text = this.getSelectedHTML();
		if (text && text != null) {
			var offset = text.toLowerCase().indexOf("<a");
			if (offset!=-1) {
				var ATagContent = text.substring(offset+2);
				offset = ATagContent.toUpperCase().indexOf(">");
				ATagContent = ATagContent.substring(0,offset);
				addUrlParams = "?curUrl[all]=" + encodeURIComponent(ATagContent);
				addUrlParams += RTEarea[editorNumber]["RTEtsConfigParams"];
			}
		}
	}
	this._popupDialog(RTEarea[0]["pathLinkModule"] + addUrlParams + "&editorNo=" + editorNumber + "&typo3ContentLanguage=" + RTEarea[editorNumber]["typo3ContentLanguage"] + "&typo3ContentCharset=" + encodeURIComponent(RTEarea[editorNumber]["typo3ContentCharset"]), null, backreturn, 550, 350, null, "yes");
	return false;
};

/*
 * Add a link to the selection.
 * This function is called from the TYPO3 link popup.
 */
HTMLArea.prototype.renderPopup_addLink = function(theLink,cur_target,cur_class,cur_title) {
	var a, sel = null, range = null, node = null, imageNode = null;
	this.focusEditor();
	node = this.getParentElement();
	var el = HTMLArea.getElementObject(node,"a");
	if (el != null && el.tagName && el.tagName.toLowerCase() == "a") node = el;
	if (node != null && node.tagName && node.tagName.toLowerCase() == "a") this.selectNode(node);
		// Clean images from existing anchors otherwise Mozilla may create nested anchors
	if (this.classesAnchorSetup) {
		sel = this._getSelection();
		range = this._createRange(sel);
		this.cleanAllLinks(node, range, true);
	}
	
	this._doc.execCommand("CreateLink", false, theLink);
	
	sel = this._getSelection();
	range = this._createRange(sel);
	node = this.getParentElement();
	var el = HTMLArea.getElementObject(node,"a");
	if (el != null && el.tagName && el.tagName.toLowerCase() == "a") node = el;
	if (node) {
		if (this.classesAnchorSetup && cur_class) {
			for (var i = this.classesAnchorSetup.length; --i >= 0;) {
				var anchorClass = this.classesAnchorSetup[i];
				if(anchorClass['name'] == cur_class && anchorClass["image"]) {
					imageNode = this._doc.createElement("img");
					imageNode.src = anchorClass["image"];
					imageNode.alt = anchorClass["altText"];
					break;
				}
			}
		}
			// We may have created multiple links in as many blocks
		this.setLinkAttributes(node, range, cur_target, cur_class, cur_title, imageNode);
	}
	
	Dialog._modal.close();
	this.updateToolbar();
};

/*
 * Set attributes of anchors intersecting a range in the given node
 */
HTMLArea.prototype.setLinkAttributes = function(node,range,cur_target,cur_class,cur_title,imageNode) {
	if (node.tagName && node.tagName.toLowerCase() == "a") {
		var nodeInRange = false;
		if (HTMLArea.is_gecko) {
			nodeInRange = this.rangeIntersectsNode(range, node);
		} else {
			if (this._getSelection().type.toLowerCase() == "control") {
					// we assume an image is selected
				nodeInRange = true;
			} else {
				var nodeRange = this._doc.body.createTextRange();
				nodeRange.moveToElementText(node);
				nodeInRange = range.inRange(nodeRange) || (range.compareEndPoints("StartToStart", nodeRange) == 0) || (range.compareEndPoints("EndToEnd", nodeRange) == 0);
			}
		}
		if (nodeInRange) {
			if (imageNode != null) node.insertBefore(imageNode.cloneNode(false), node.firstChild);
			if (cur_target.trim()) node.target = cur_target.trim();
				else node.removeAttribute("target");
			if (cur_class.trim()) {
				node.className = cur_class.trim();
			} else { 
				if (HTMLArea.is_gecko) node.removeAttribute('class');
					else node.removeAttribute('className');
			}
			if (cur_title.trim()) {
				node.title = cur_title.trim();
			} else {
				node.removeAttribute("title");
				node.removeAttribute("rtekeep");
			}
		}
	} else {
		for (var i = node.firstChild;i;i = i.nextSibling) {
			if (i.nodeType == 1 || i.nodeType == 11) this.setLinkAttributes(i, range, cur_target, cur_class, cur_title, imageNode);
		}
	}
};

/*
 * Clean up images in special anchor classes
 */
HTMLArea.prototype.cleanClassesAnchorImages = function(node) {
	var nodeArray = [], splitArray1 = [], splitArray2 = [];
	for (var childNode = node.firstChild; childNode; childNode = childNode.nextSibling) {
		if (childNode.tagName && childNode.tagName.toLowerCase() == "img") {
			splitArray1 = childNode.src.split("/");
			for (var i = this.classesAnchorSetup.length; --i >= 0;) {
				if (this.classesAnchorSetup[i]["image"]) {
					splitArray2 = this.classesAnchorSetup[i]["image"].split("/");
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
};

/*
 * Clean up all anchors intesecting with the range in the given node
 */
 HTMLArea.prototype.cleanAllLinks = function(node,range,keepLinks) {
	if (node.tagName && node.tagName.toLowerCase() == "a") {
		var intersection = false;
		if (HTMLArea.is_gecko) {
			intersection = this.rangeIntersectsNode(range, node);
		} else {
			if (this._getSelection().type.toLowerCase() == "control") {
					// we assume an image is selected
				intersection = true;
			} else {
				var nodeRange = this._doc.body.createTextRange();
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
};

/*
 * Unlink the selection.
 * This function is called from the TYPO3 link popup and from the context menu.
 */
HTMLArea.prototype.renderPopup_unLink = function() {
	this.focusEditor();
	var node = this.getParentElement();
	var el = HTMLArea.getElementObject(node,"a");
	if (el != null && el.tagName && el.tagName.toLowerCase() == "a") node = el;
	if (node != null && node.tagName && node.tagName.toLowerCase() == "a") this.selectNode(node);
	if (this.classesAnchorSetup) {
		var sel = this._getSelection();
		var range = this._createRange(sel);
		if (HTMLArea.is_gecko) {
			this.cleanAllLinks(node, range, false);
		} else {
			this.cleanAllLinks(node, range, true);
			this._doc.execCommand("Unlink", false, "");
		}
			
	} else {
		this._doc.execCommand("Unlink", false, "");
	}
	if(Dialog._modal) Dialog._modal.close();
};

/*
 * IE-Browsers strip URL's to relative URL's. But for the TYPO3 backend we need absolute URL's.
 * This function overloads the normal stripBaseURL-function (which generate relative URLs).
 */
HTMLArea.prototype.nonStripBaseURL = function(url) {
	return url;
};

TYPO3Browsers.prototype.onGenerate = function() {
	var editor = this.editor;
	editor._insertImage = editor.renderPopup_image;
	editor._createLink = editor.renderPopup_link;
	editor.stripBaseURL = editor.nonStripBaseURL;
};
