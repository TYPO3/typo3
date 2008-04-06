/***************************************************************
*  Copyright notice
*
*  (c) 2002-2004, interactivetools.com, inc.
*  (c) 2003-2004 dynarch.com
*  (c) 2004-2007 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * TYPO3 CVS ID: $Id$
 */

/***************************************************
 *  GECKO-SPECIFIC FUNCTIONS
 ***************************************************/

/***************************************************
 *  MOZILLA/FIREFOX EDIT MODE INITILIZATION
 ***************************************************/

HTMLArea.prototype._initEditMode = function () {
		// We can't set designMode when we are in a hidden TYPO3 tab
		// Then we will set it when the tab comes in the front.
	var isNested = false;
	var allDisplayed = true;

	if (this.nested.sorted && this.nested.sorted.length) {
		isNested = true;
		allDisplayed = HTMLArea.allElementsAreDisplayed(this.nested.sorted);
	}

	if (!HTMLArea.is_wamcom) {
		try {
			if (!isNested || allDisplayed) {
				this._iframe.style.display = "block";
				this._doc.designMode = "on";
			}
		} catch(e) { }
	} else {
		try { 
			this._doc.designMode = "on"; 
		} catch(e) {
			if (!isNested || allDisplayed) {
				this._doc.open();
				this._doc.close();
				this._initIframeTimer = window.setTimeout("HTMLArea.initIframe(" + this._editorNumber + ");", 500);
				return false;
			}
		}
	}
		// When the TYPO3 TCA feature div2tab is used, the editor iframe may become hidden with style.display = "none"
		// This breaks the editor in Mozilla/Firefox browsers: the designMode attribute needs to be resetted after the style.display of the containing div is resetted to "block"
		// Here we rely on TYPO3 naming conventions for the div id and class name
	if (this.nested.sorted && this.nested.sorted.length) {
		var nestedObj, listenerFunction;
		for (var i=0, length=this.nested.sorted.length; i < length; i++) {
			nestedObj = document.getElementById(this.nested.sorted[i]);
			listenerFunction = HTMLArea.NestedListener(this, nestedObj, false);
			HTMLArea._addEvent(nestedObj, 'DOMAttrModified', listenerFunction);
		}
	}

	return true;
};

/***************************************************
 *  SELECTIONS AND RANGES
 ***************************************************/

/*
 * Get the current selection object
 */
HTMLArea.prototype._getSelection = function() {
	if (HTMLArea.is_safari) return window.getSelection();
	return this._iframe.contentWindow.getSelection();
};

/*
 * Create a range for the current selection
 */
HTMLArea.prototype._createRange = function(sel) {
	if (HTMLArea.is_safari) {
		var range = this._doc.createRange();
		if (typeof(sel) == "undefined") return range;
		switch (sel.type) {
			case "Range": 
				range.setStart(sel.baseNode,sel.baseOffset);
				range.setEnd(sel.extentNode,sel.extentOffset);
				break;
			case "Caret":
				range.setStart(sel.baseNode,sel.baseOffset);
				range.setEnd(sel.baseNode,sel.baseOffset);
				break;
			case "None":
				range.setStart(this._doc.body,0);
				range.setEnd(this._doc.body,0);
		}
		return range;
	}
	if (typeof(sel) == "undefined") return this._doc.createRange();
	try {
		return sel.getRangeAt(0);
	} catch(e) {
		return this._doc.createRange();
 	}
};

/*
 * Select a node AND the contents inside the node
 */
HTMLArea.prototype.selectNode = function(node,pos) {
	this.focusEditor();
	var sel = this._getSelection();
	var range = this._doc.createRange();
	if (node.nodeType == 1 && node.tagName.toLowerCase() == "body") range.selectNodeContents(node);
		else range.selectNode(node);
	if ((typeof(pos) != "undefined")) range.collapse(pos);
	if (HTMLArea.is_safari) {
		sel.empty();
		sel.setBaseAndExtent(range.startContainer,range.startOffset,range.endContainer,range.endOffset);
	} else {
		sel.removeAllRanges();
		sel.addRange(range);
	}
};

/*
 * Select ONLY the contents inside the given node
 */
HTMLArea.prototype.selectNodeContents = function(node,pos) {
	this.focusEditor();
	var sel = this._getSelection();
	var range = this._doc.createRange();
	range.selectNodeContents(node);
	if ((typeof(pos) != "undefined")) range.collapse(pos);
	if (HTMLArea.is_safari) {
		sel.empty();
		sel.setBaseAndExtent(range.startContainer,range.startOffset,range.endContainer,range.endOffset);
	} else {
		sel.removeAllRanges();
		sel.addRange(range);
	}
};

/*
 * Retrieve the HTML contents of selected block
 */
HTMLArea.prototype.getSelectedHTML = function() {
	var sel = this._getSelection();
	var range = this._createRange(sel);
	var cloneContents = "";
	try {cloneContents = range.cloneContents();} catch(e) { }
	return (cloneContents ? HTMLArea.getHTML(cloneContents,false,this) : "");
};

/*
 * Retrieve simply HTML contents of the selected block, IE ignoring control ranges
 */
HTMLArea.prototype.getSelectedHTMLContents = function() {
	return this.getSelectedHTML();
};

/*
 * Get the deepest node that contains both endpoints of the current selection.
 */
HTMLArea.prototype.getParentElement = function(sel,range) {
	if(!sel) var sel = this._getSelection();
	if (typeof(range) == "undefined") var range = this._createRange(sel);
	try {
		var p = range.commonAncestorContainer;
		if(!range.collapsed && range.startContainer == range.endContainer &&
		    range.startOffset - range.endOffset <= 1 && range.startContainer.hasChildNodes())
			p = range.startContainer.childNodes[range.startOffset];
		while (p.nodeType == 3) {p = p.parentNode;}
		return p;
	} catch (e) {
		return this._doc.body;
	}
};

/*
 * Get the selected element, if any.  That is, the element that you have last selected in the "path"
 * at the bottom of the editor, or a "control" (eg image)
 *
 * @returns null | element
 * Borrowed from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
 */
HTMLArea.prototype._activeElement = function(sel) {
	if(sel == null) return null;
	if(this._selectionEmpty(sel)) return null;
		// Check if the selection is not collapsed (something is selected) and if the anchor (start of selection) is an element.
	if(!sel.isCollapsed && sel.anchorNode.nodeType == 1) return sel.anchorNode;
		else return null;
};

/*
 * Determine if the current selection is empty or not.
 */
HTMLArea.prototype._selectionEmpty = function(sel) {
	if (!sel) return true;
	if (typeof(sel.isCollapsed) != 'undefined') {
		if (HTMLArea.is_opera) this._createRange(sel).collapsed;
			else sel.isCollapsed;
	} else {
		return true;
	}
};

/***************************************************
 *  DOM TREE MANIPULATION
 ***************************************************/

 /*
 * Insert a node at the current position.
 * Delete the current selection, if any.
 * Split the text node, if needed.
 */
HTMLArea.prototype.insertNodeAtSelection = function(toBeInserted) {
	this.focusEditor();
	var sel = this._getSelection(),
		range = this._createRange(sel),
		node = range.startContainer,
		pos = range.startOffset,
		selnode = toBeInserted;
	if (HTMLArea.is_safari) sel.empty();
		else sel.removeAllRanges();
	range.deleteContents();
	switch (node.nodeType) {
	    case 3: // Node.TEXT_NODE: we have to split it at the caret position.
		if(toBeInserted.nodeType == 3) {
			node.insertData(pos,toBeInserted.data);
			range = this._createRange();
			range.setEnd(node, pos + toBeInserted.length);
			range.setStart(node, pos + toBeInserted.length);
			if (HTMLArea.is_safari) sel.setBaseAndExtent(range.startContainer, range.startOffset, range.endContainer, range.endOffset);
				else sel.addRange(range);
		} else {
			node = node.splitText(pos);
			if (toBeInserted.nodeType == 11) selnode = selnode.lastChild;
			node = node.parentNode.insertBefore(toBeInserted, node);
			this.selectNode(selnode, false);
			this.updateToolbar();
		}
		break;
	    case 1:
		if (toBeInserted.nodeType == 11) selnode = selnode.lastChild;
		node = node.insertBefore(toBeInserted, node.childNodes[pos]);
		this.selectNode(selnode, false);
		this.updateToolbar();
		break;
	}
};

/* 
 * Insert HTML source code at the current position.
 * Delete the current selection, if any.
 */
HTMLArea.prototype.insertHTML = function(html) {
	this.focusEditor();
	var fragment = this._doc.createDocumentFragment();
	var div = this._doc.createElement("div");
	div.innerHTML = html;
	while (div.firstChild) {fragment.appendChild(div.firstChild);}
	this.insertNodeAtSelection(fragment);
};

/***************************************************
 *  EVENTS HANDLERS
 ***************************************************/

/*
 * TYPO3 hidden tab and inline event listener (gets event calls)
 */
HTMLArea.NestedListener = function (editor,nestedObj,noOpenCloseAction) {
	return (function(ev) {
		if(!ev) var ev = window.event;
		HTMLArea.NestedHandler(ev,editor,nestedObj,noOpenCloseAction);
	});
};

/*
 * TYPO3 hidden tab and inline event handler (performs actions on event calls)
 */
HTMLArea.NestedHandler = function(ev,editor,nestedObj,noOpenCloseAction) {
	window.setTimeout(function() {
		var target = (ev.target) ? ev.target : ev.srcElement;
		if(target == nestedObj && editor._editMode == "wysiwyg" && ev.attrName=='style' && (target.style.display == '' || target.style.display == 'block')) {
				// Check if all affected nested elements are displayed (style.display!='none'):
			if (HTMLArea.allElementsAreDisplayed(editor.nested.sorted)) {
				window.setTimeout(function() {
					try { 
						editor._doc.designMode = "on";
						if (editor.config.sizeIncludesToolbar && editor._initialToolbarOffsetHeight != editor._toolbar.offsetHeight) {
							editor.sizeIframe(-2);
						}
					} catch(e) {
							// If an event of a parent tab ("nested tabs") is triggered, the following lines should not be
							// processed, because this causes some trouble on all event handlers...
						if (!noOpenCloseAction) {
							editor._doc.open();
							editor._doc.close();
						}
						editor.initIframe();
					}
				}, 50);
			}
			HTMLArea._stopEvent(ev);
		}
	}, 50);
};

/*
 * Handle statusbar element events
 */
HTMLArea.statusBarHandler = function (ev) {
	if(!ev) var ev = window.event;
	var target = (ev.target) ? ev.target : ev.srcElement;
	var editor = target.editor;
	target.blur();
	editor.selectNode(target.el);
	editor.updateToolbar(true);
	switch (ev.type) {
		case "click" :
		case "mousedown" :
			HTMLArea._stopEvent(ev);
			return false;
		case "contextmenu" :
			return editor.plugins["ContextMenu"] ? editor.plugins["ContextMenu"].instance.popupMenu(ev,target.el) : false;
	}
};

/*
 * Paste exception handler
 */
HTMLArea.prototype._mozillaPasteException = function(cmdID, UI, param) {
		// Mozilla lauches an exception, but can paste anyway on ctrl-V
		// UI is false on keyboard shortcut, and undefined on button click
	if(typeof(UI) != "undefined") {
		this._doc.execCommand(cmdID, UI, param);
		if (cmdID == "Paste" && this.config.killWordOnPaste) HTMLArea._wordClean(this._doc.body);
	} else if (this.config.enableMozillaExtension) {
		if (confirm(HTMLArea.I18N.msg["Allow-Clipboard-Helper-Extension"])) {
			if (InstallTrigger.enabled()) {
				HTMLArea._mozillaXpi = new Object();
				HTMLArea._mozillaXpi["AllowClipboard Helper"] = _editor_mozAllowClipboard_url;
				InstallTrigger.install(HTMLArea._mozillaXpi,HTMLArea._mozillaInstallCallback);
			} else {
				alert(HTMLArea.I18N.msg["Mozilla-Org-Install-Not-Enabled"]);
				HTMLArea._appendToLog("WARNING [HTMLArea::execCommand]: Mozilla install was not enabled.");
				return; 
			}
		}
	} else if (confirm(HTMLArea.I18N.msg["Moz-Clipboard"])) {
		window.open("http://mozilla.org/editor/midasdemo/securityprefs.html");
	}
}

HTMLArea._mozillaInstallCallback = function(url,returnCode) {
	if (returnCode == 0) {
		if (HTMLArea._mozillaXpi["TYPO3 htmlArea RTE Preferences"]) alert(HTMLArea.I18N.msg["Moz-Extension-Success"]);
			else alert(HTMLArea.I18N.msg["Allow-Clipboard-Helper-Extension-Success"]);
		return; 
	} else {
		alert(HTMLArea.I18N.msg["Moz-Extension-Failure"]);
		HTMLArea._appendToLog("WARNING [HTMLArea::execCommand]: Mozilla install return code was: " + returnCode + ".");
		return; 
	}
};

/*
 * Backspace event handler
 */
HTMLArea.prototype._checkBackspace = function() {
	var self = this;
	self.focusEditor();
	var sel = self._getSelection();
	var range = self._createRange(sel);
	var SC = range.startContainer;
	var SO = range.startOffset;
	var EC = range.endContainer;
	var EO = range.endOffset;
	var newr = SC.nextSibling;
	while (SC.nodeType == 3 || /^a$/i.test(SC.tagName)) SC = SC.parentNode;
	if (!self.config.disableEnterParagraphs && /^td$/i.test(SC.parentNode.tagName) && SC.parentNode.firstChild == SC && SO == 0 && range.collapsed) return true;
	window.setTimeout(function() {
			// Remove br tag inserted by Mozilla
		if (!self.config.disableEnterParagraphs && (/^p$/i.test(SC.tagName) || !/\S/.test(SC.tagName)) && SO == 0) {
			if (SC.firstChild && /^br$/i.test(SC.firstChild.tagName)) {
				HTMLArea.removeFromParent(SC.firstChild);
				return true;
			}
		}
		if (!/\S/.test(SC.tagName)) {
			var p = document.createElement("p");
			while (SC.firstChild) p.appendChild(SC.firstChild);
			SC.parentNode.insertBefore(p, SC);
			HTMLArea.removeFromParent(SC);
			var r = range.cloneRange();
			r.setStartBefore(newr);
			r.setEndAfter(newr);
			r.extractContents();
			if(HTMLArea.is_safari) {
				sel.empty();
				sel.setBaseAndExtent(r.startContainer,r.startOffset,r.endContainer,r.endOffset);
			} else {
				sel.removeAllRanges();
				sel.addRange(r);
			}
			return true;
		}
	},10);
	return false;
};

/*
 * Enter event handler
 */
HTMLArea.prototype._checkInsertP = function(ev) {
	var editor = this;
	this.focusEditor();
	var i, left, right, rangeClone,
		sel	= this._getSelection(),
		range	= this._createRange(sel),
		p	= this.getAllAncestors(),
		block	= null,
		a	= null,
		doc	= this._doc;
	
	for (i = 0; i < p.length; ++i) {
		if (HTMLArea.isBlockElement(p[i]) && !/html|body|table|tbody|tr/i.test(p[i].tagName)) {
			block = p[i];
			break;
		}
	}
	if (!range.collapsed) range.deleteContents();
	if (HTMLArea.is_safari) sel.empty();
		else sel.removeAllRanges();
	if (!block || /^(td|div)$/i.test(block.tagName)) {
		if (!block) var block = doc.body;
		if (/\S/.test(HTMLArea.getInnerText(block))) {
			rangeClone = range.cloneRange();
			rangeClone.setStartBefore(block.firstChild);
			rangeClone.surroundContents(left = doc.createElement('p'));
			if (!/\S/.test(HTMLArea.getInnerText(left))) {
					// Remove any element created empty
				a = left.lastChild;
				if (a && !/\S/.test(HTMLArea.getInnerText(a))) HTMLArea.removeFromParent(a);
				left.appendChild(doc.createElement('br'));
			}
			left.normalize();
			range.setEndAfter(block.lastChild);
			range.surroundContents(right = doc.createElement('p'));
				// Remove any element created empty
			a = right.previousSibling;
			if (a && !/\S/.test(HTMLArea.getInnerText(a))) HTMLArea.removeFromParent(a);
			if (!/\S/.test(HTMLArea.getInnerText(right))) {
					// Remove any element created empty
				a = right.lastChild;
				if (a && !/\S/.test(HTMLArea.getInnerText(a))) HTMLArea.removeFromParent(a);
				right.appendChild(doc.createElement('br'));
			}
			right.normalize();
		} else {
			range = doc.createRange();
			var first = block.firstChild;
			block.removeChild(first);
			block.appendChild(right = doc.createElement('p'));
			right.appendChild(first);
		}
		range.selectNodeContents(right);
	} else {
		range.setEndAfter(block);
		var df = range.extractContents(), left_empty = false;
		if(!/\S/.test(HTMLArea.getInnerText(block))) {
			block.innerHTML = "<br />";
			left_empty = true;
		}
		p = df.firstChild;
		if (p) {
			if(!/\S/.test(HTMLArea.getInnerText(p))) {
 				if (/^h[1-6]$/i.test(p.tagName)) p = this.convertNode(p,"p");
				p.innerHTML = "<br />";
			}
			if(/^li$/i.test(p.tagName) && left_empty && !block.nextSibling) {
				left = block.parentNode;
				left.removeChild(block);
				range.setEndAfter(left);
				range.collapse(false);
				p = this.convertNode(p, /^li$/i.test(left.parentNode.tagName) ? "br" : "p");
			}
			range.insertNode(df);
				// Remove any anchor created empty
			var a = p.previousSibling.lastChild;
			if (a && /^a$/i.test(a.tagName) && !/\S/.test(a.innerHTML)) HTMLArea.removeFromParent(a);
			range.selectNodeContents(p);
		}
	}
	range.collapse(true);
	if (HTMLArea.is_safari) sel.setBaseAndExtent(r.startContainer,r.startOffset,r.endContainer,r.endOffset);
		else sel.addRange(range);
	this.scrollToCaret();
	return true;
};

/*
 * Detect emails and urls as they are typed in Mozilla
 * Borrowed from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
 */
HTMLArea.prototype._detectURL = function(ev) {
	var editor = this;
	var s = this._getSelection();
	var autoWrap = function (textNode, tag) {
		var rightText = textNode.nextSibling;
		if (typeof(tag) == 'string') tag = editor._doc.createElement(tag);
		var a = textNode.parentNode.insertBefore(tag, rightText);
		HTMLArea.removeFromParent(textNode);
		a.appendChild(textNode);
		rightText.data += " ";
		s.collapse(rightText, rightText.data.length);
		HTMLArea._stopEvent(ev);

		editor._unLink = function() {
			var t = a.firstChild;
			a.removeChild(t);
			a.parentNode.insertBefore(t, a);
			HTMLArea.removeFromParent(a);
			t.parentNode.normalize();
			editor._unLink = null;
			editor._unlinkOnUndo = false;
		};

		editor._unlinkOnUndo = true;
		return a;
	};

	switch(ev.which) {
			// Space or Enter or >, see if the text just typed looks like a URL, or email address and link it accordingly
		case 13:	// Enter
			if(ev.shiftKey || editor.config.disableEnterParagraphs) break;
				//Space
		case 32:
			if(s && s.isCollapsed && s.anchorNode.nodeType == 3 && s.anchorNode.data.length > 3 && s.anchorNode.data.indexOf('.') >= 0) {
				var midStart = s.anchorNode.data.substring(0,s.anchorOffset).search(/[a-zA-Z0-9]+\S{3,}$/);
				if(midStart == -1) break;
				if(this._getFirstAncestor(s, 'a')) break; // already in an anchor
				var matchData = s.anchorNode.data.substring(0,s.anchorOffset).replace(/^.*?(\S*)$/, '$1');
				if (matchData.indexOf('@') != -1) {
					var m = matchData.match(HTMLArea.RE_email);
					if(m) {
						var leftText  = s.anchorNode;
						var rightText = leftText.splitText(s.anchorOffset);
						var midText   = leftText.splitText(midStart);
						var midEnd = midText.data.search(/[^a-zA-Z0-9\.@_\-]/);
						if (midEnd != -1) var endText = midText.splitText(midEnd);
						autoWrap(midText, 'a').href = 'mailto:' + m[0];
						break;
					}
				}
				var m = matchData.match(HTMLArea.RE_url);
				if(m) {
					var leftText  = s.anchorNode;
					var rightText = leftText.splitText(s.anchorOffset);
					var midText   = leftText.splitText(midStart);
					var midEnd = midText.data.search(/[^a-zA-Z0-9\._\-\/\&\?=:@]/);
					if (midEnd != -1) var endText = midText.splitText(midEnd);
					autoWrap(midText, 'a').href = (m[1] ? m[1] : 'http://') + m[2];
					break;
				}
			}
			break;
		default:
			if(ev.keyCode == 27 || (editor._unlinkOnUndo && ev.ctrlKey && ev.which == 122) ) {
				if(this._unLink) {
					this._unLink();
					HTMLArea._stopEvent(ev);
				}
				break;
			} else if(ev.which || ev.keyCode == 8 || ev.keyCode == 46) {
				this._unlinkOnUndo = false;
				if(s.anchorNode && s.anchorNode.nodeType == 3) {
						// See if we might be changing a link
					var a = this._getFirstAncestor(s, 'a');
					if(!a) break; // not an anchor
					if(!a._updateAnchTimeout) {
						if(s.anchorNode.data.match(HTMLArea.RE_email) && (a.href.match('mailto:' + s.anchorNode.data.trim()))) {
							var textNode = s.anchorNode;
							var fn = function() {
								a.href = 'mailto:' + textNode.data.trim();
								a._updateAnchTimeout = setTimeout(fn, 250);
							};
							a._updateAnchTimeout = setTimeout(fn, 250);
							break;
						}
						var m = s.anchorNode.data.match(HTMLArea.RE_url);
						if(m &&  a.href.match(s.anchorNode.data.trim())) {
							var textNode = s.anchorNode;
							var fn = function() {
								var m = textNode.data.match(HTMLArea.RE_url);
								a.href = (m[1] ? m[1] : 'http://') + m[2];
								a._updateAnchTimeout = setTimeout(fn, 250);
							}
							a._updateAnchTimeout = setTimeout(fn, 250);
						}
					}
				}
			}
			break;
	}
};
