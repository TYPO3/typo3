/***************************************************************
*  Copyright notice
*
*  (c) 2002-2004, interactivetools.com, inc.
*  (c) 2003-2004 dynarch.com
*  (c) 2004-2008 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * TYPO3 SVN ID: $Id$
 */

/***************************************************
 *  IE-SPECIFIC FUNCTIONS
 ***************************************************/
HTMLArea.prototype.isEditable = function() {
	return this._doc.body.contentEditable;
};

/***************************************************
 *  SELECTIONS AND RANGES
 ***************************************************/
/*
 * Get the current selection object
 */
HTMLArea.prototype._getSelection = function() {
	return this._doc.selection;
};

/*
 * Create a range for the current selection
 */
HTMLArea.prototype._createRange = function(sel) {
	if (typeof(sel) != "undefined") return sel.createRange();
	return this._doc.selection.createRange();
};

/*
 * Select a node AND the contents inside the node
 */
HTMLArea.prototype.selectNode = function(node) {
	this.focusEditor();
	this.forceRedraw();
	var range = this._doc.body.createTextRange();
	range.moveToElementText(node);
	range.select();
};

/*
 * Select ONLY the contents inside the given node
 */
HTMLArea.prototype.selectNodeContents = function(node, endPoint) {
	this.focusEditor();
	this.forceRedraw();
	var range = this._doc.body.createTextRange();
	range.moveToElementText(node);
	if (typeof(endPoint) !== "undefined") {
		range.collapse(endPoint);
	}
	range.select();
};

/*
 * Determine whether the node intersects the range
 */
HTMLArea.prototype.rangeIntersectsNode = function(range, node) {
	var nodeRange = this._doc.body.createTextRange();
	nodeRange.moveToElementText(node);
	return (range.compareEndPoints("EndToStart", nodeRange) == -1 && range.compareEndPoints("StartToEnd", nodeRange) == 1) ||
		(range.compareEndPoints("EndToStart", nodeRange) == 1 && range.compareEndPoints("StartToEnd", nodeRange) == -1);
};

/*
 * Retrieve the HTML contents of selected block
 */
HTMLArea.prototype.getSelectedHTML = function() {
	var sel = this._getSelection();
	var range = this._createRange(sel);
	if (sel.type.toLowerCase() == "control") {
		var r1 = this._doc.body.createTextRange();
		r1.moveToElementText(range(0));
		return r1.htmlText;
	} else {
		return range.htmlText;
	}
};

/*
 * Retrieve simply HTML contents of the selected block, IE ignoring control ranges
 */
HTMLArea.prototype.getSelectedHTMLContents = function() {
	var sel = this._getSelection();
	var range = this._createRange(sel);
	return range.htmlText;
};

/*
 * Get the deepest node that contains both endpoints of the current selection.
 */
HTMLArea.prototype.getParentElement = function(sel) {
	if(!sel) var sel = this._getSelection();
	var range = this._createRange(sel);
	switch (sel.type) {
		case "Text":
		case "None":
			var el = range.parentElement();
			if(el.nodeName.toLowerCase() == "li" && range.htmlText.replace(/\s/g,"") == el.parentNode.outerHTML.replace(/\s/g,"")) return el.parentNode;
			return el;
		case "Control": return range.item(0);
		default: return this._doc.body;
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
	if(sel.type.toLowerCase() == "control") {
		return sel.createRange().item(0);
	} else {
			// If it's not a control, then we need to see if the selection is the _entire_ text of a parent node
			// (this happens when a node is clicked in the tree)
		var range = sel.createRange();
		var p_elm = this.getParentElement(sel);
		if(p_elm.outerHTML == range.htmlText) return p_elm;
		return null;
    	}
};

/*
 * Determine if the current selection is empty or not.
 */
HTMLArea.prototype._selectionEmpty = function(selection) {
	if (!selection || selection.type.toLowerCase() === "none") return true;
	if (selection.type.toLowerCase() === "text") {
		return !this._createRange(selection).text;
	}
	return !this._createRange(selection).htmlText;
};

/*
 * Get a bookmark
 */
HTMLArea.prototype.getBookmark = function (range) {
	return range.getBookmark();
};

/*
 * Move the range to the bookmark
 */
HTMLArea.prototype.moveToBookmark = function (bookmark) {
	var range = this._createRange();
	range.moveToBookmark(bookmark);
	return range;
};

/*
 * Select range
 */
HTMLArea.prototype.selectRange = function (range) {
	range.select();
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
	var sel = this._getSelection();
	var range = this._createRange(sel);
	range.pasteHTML(toBeInserted.outerHTML);
};

/*
 * Insert HTML source code at the current position.
 * Delete the current selection, if any.
 */
HTMLArea.prototype.insertHTML = function(html) {
	this.focusEditor();
	var sel = this._getSelection();
	if (sel.type.toLowerCase() == "control") {
		sel.clear();
		sel = this._getSelection();
	}
	var range = this._createRange(sel);
	range.pasteHTML(html);
};

/***************************************************
 *  EVENT HANDLERS
 ***************************************************/

/*
 * Handle statusbar element events
 */
HTMLArea.statusBarHandler = function (ev) {
	if(!ev) var ev = window.event;
	var target = (ev.target) ? ev.target : ev.srcElement;
	var editor = target.editor;
	target.blur();
	var tagname = target.el.tagName.toLowerCase();
	if(tagname == "table" || tagname == "img") {
		var range = editor._doc.body.createControlRange();
		range.addElement(target.el);
		range.select();
	} else {
		editor.selectNode(target.el);
	}
	editor._statusBarTree.selected = target.el;
	editor.updateToolbar(true);
	switch (ev.type) {
		case "click" :
			HTMLArea._stopEvent(ev);
			return false;
		case "contextmenu" :
			return editor.plugins["ContextMenu"] ? editor.plugins["ContextMenu"].instance.popupMenu(ev,target.el) : false;
	}
};

/*
 * Handle the backspace event in IE browsers
 */
HTMLArea.prototype._checkBackspace = function() {
	var sel = this._getSelection();
	var range = this._createRange(sel);
	if(sel.type == "Control"){
		var el = this.getParentElement();
		var p = el.parentNode;
		p.removeChild(el);
		return true;
	} else {
		var r2 = range.duplicate();
		r2.moveStart("character", -1);
		var a = r2.parentElement();
		if(a != range.parentElement() && /^a$/i.test(a.tagName)) {
			r2.collapse(true);
			r2.moveEnd("character", 1);
			r2.pasteHTML('');
			r2.select();
			return true;
		}
		return false;
	}
};
