/***************************************************************
*  Copyright notice
*
*  (c) 2002-2004 interactivetools.com, inc.
*  (c) 2003-2004 dynarch.com
*  (c) 2004-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 */

/***************************************************
 *  IE-SPECIFIC FUNCTIONS
 ***************************************************/
HTMLArea.Editor.prototype.isEditable = function() {
	return this._doc.body.contentEditable;
};

/***************************************************
 *  SELECTIONS AND RANGES
 ***************************************************/
/*
 * Get the current selection object
 */
HTMLArea.Editor.prototype._getSelection = function() {
	return this._doc.selection;
};

/*
 * Create a range for the current selection
 */
HTMLArea.Editor.prototype._createRange = function(sel) {
	if (typeof(sel) == "undefined") {
		var sel = this._getSelection();
	}
	if (sel.type.toLowerCase() == "none") {
		this.focus();
	}
	return sel.createRange();
};

/*
 * Select a node AND the contents inside the node
 */
HTMLArea.Editor.prototype.selectNode = function(node) {
	this.focus();
	this.forceRedraw();
	var range = this._doc.body.createTextRange();
	range.moveToElementText(node);
	range.select();
};

/*
 * Select ONLY the contents inside the given node
 */
HTMLArea.Editor.prototype.selectNodeContents = function(node, endPoint) {
	this.focus();
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
HTMLArea.Editor.prototype.rangeIntersectsNode = function(range, node) {
	this.focus();
	var nodeRange = this._doc.body.createTextRange();
	nodeRange.moveToElementText(node);
	return (range.compareEndPoints("EndToStart", nodeRange) == -1 && range.compareEndPoints("StartToEnd", nodeRange) == 1) ||
		(range.compareEndPoints("EndToStart", nodeRange) == 1 && range.compareEndPoints("StartToEnd", nodeRange) == -1);
};

/*
 * Retrieve the HTML contents of selected block
 */
HTMLArea.Editor.prototype.getSelectedHTML = function() {
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
HTMLArea.Editor.prototype.getSelectedHTMLContents = function() {
	var sel = this._getSelection();
	var range = this._createRange(sel);
	return range.htmlText;
};

/*
 * Get the deepest node that contains both endpoints of the current selection.
 */
HTMLArea.Editor.prototype.getParentElement = function(selection, range) {
	if (!selection) {
		var selection = this._getSelection();
	}
	if (typeof(range) === "undefined") {
		var range = this._createRange(selection);
	}
	switch (selection.type.toLowerCase()) {
		case "text":
		case "none":
			var el = range.parentElement();
			if (el.nodeName.toLowerCase() == 'form') {
				return this._doc.body;
			}
			if (el.nodeName.toLowerCase() == "li" && range.htmlText.replace(/\s/g,"") == el.parentNode.outerHTML.replace(/\s/g,"")) {
				return el.parentNode;
			}
			return el;
		case "control": return range.item(0);
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
HTMLArea.Editor.prototype._activeElement = function(sel) {
	if (sel == null) {
		return null;
	}
	if (this._selectionEmpty(sel)) {
		return null;
	}
	if (sel.type.toLowerCase() == "control") {
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
HTMLArea.Editor.prototype._selectionEmpty = function(selection) {
	if (!selection || selection.type.toLowerCase() === "none") return true;
	if (selection.type.toLowerCase() === "text") {
		return !this._createRange(selection).text;
	}
	return !this._createRange(selection).htmlText;
};

/*
 * Get a bookmark
 */
HTMLArea.Editor.prototype.getBookmark = function (range) {
	return range.getBookmark();
};

/*
 * Move the range to the bookmark
 */
HTMLArea.Editor.prototype.moveToBookmark = function (bookmark) {
	var range = this._createRange();
	range.moveToBookmark(bookmark);
	return range;
};

/*
 * Select range
 */
HTMLArea.Editor.prototype.selectRange = function (range) {
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
HTMLArea.Editor.prototype.insertNodeAtSelection = function(toBeInserted) {
	this.insertHTML(toBeInserted.outerHTML);
};

/*
 * Insert HTML source code at the current position.
 * Delete the current selection, if any.
 */
HTMLArea.Editor.prototype.insertHTML = function(html) {
	var sel = this._getSelection();
	if (sel.type.toLowerCase() == "control") {
		sel.clear();
		sel = this._getSelection();
	}
	var range = this._createRange(sel);
	range.pasteHTML(html);
};

/*
 * Wrap the range with an inline element
 *
 * @param	string	element: the node that will wrap the range
 * @param	object	selection: the selection object
 * @param	object	range: the range to be wrapped
 *
 * @return	void
 */
HTMLArea.Editor.prototype.wrapWithInlineElement = function(element, selection, range) {
	var nodeName = element.nodeName;
	var parent = this.getParentElement(selection, range);
	var bookmark = this.getBookmark(range);
	if (selection.type !== "Control") {
		var rangeStart = range.duplicate();
		rangeStart.collapse(true);
		var parentStart = rangeStart.parentElement();
		var rangeEnd = range.duplicate();
		rangeEnd.collapse(true);
		var newRange = this._createRange();
		
		var parentEnd = rangeEnd.parentElement();
		var upperParentStart = parentStart;
		if (parentStart !== parent) {
			while (upperParentStart.parentNode !== parent) {
				upperParentStart = upperParentStart.parentNode;
			}
		}
		
		element.innerHTML = range.htmlText;
			// IE eats spaces on the start boundary
		if (range.htmlText.charAt(0) === "\x20") {
			element.innerHTML = "&nbsp;" + element.innerHTML;
		}
		var elementClone = element.cloneNode(true);
		range.pasteHTML(element.outerHTML);
			// IE inserts the element as the last child of the start container
		if (parentStart !== parent
				&& parentStart.lastChild
				&& parentStart.lastChild.nodeType === 1
				&& parentStart.lastChild.nodeName.toLowerCase() === nodeName) {
			parent.insertBefore(elementClone, upperParentStart.nextSibling);
			parentStart.removeChild(parentStart.lastChild);
				// Sometimes an empty previous sibling was created
			if (elementClone.previousSibling
					&& elementClone.previousSibling.nodeType === 1
					&& !elementClone.previousSibling.innerText) {
				parent.removeChild(elementClone.previousSibling);
			}
				// The bookmark will not work anymore
			newRange.moveToElementText(elementClone);
			newRange.collapse(false);
			newRange.select();
		} else {
				// Working around IE boookmark bug
			if (parentStart != parentEnd) {
				var newRange = this._createRange();
				if (newRange.moveToBookmark(bookmark)) {
					newRange.collapse(false);
					newRange.select();
				}
			} else {
				range.collapse(false);
			}
		}
			// normalize() is not available in IE5.5
		try {
			parent.normalize();
		} catch(e) { }
	} else {
		element = parent.parentNode.insertBefore(element, parent);
		element.appendChild(parent);
		this.moveToBookmark(bookmark);
	}
};

/***************************************************
 *  EVENT HANDLERS
 ***************************************************/

/*
 * Handle the backspace event in IE browsers
 */
HTMLArea.Editor.prototype._checkBackspace = function() {
	var selection = this._getSelection();
	var range = this._createRange(selection);
	if (selection.type == "Control"){ // Deleting or backspacing on a control selection : delete the element
		var el = this.getParentElement();
		var p = el.parentNode;
		p.removeChild(el);
		return true;
	} else if (this._selectionEmpty(selection)) { // Check if deleting an empty block with a table as next sibling
		var el = this.getParentElement();
		if (!el.innerHTML && HTMLArea.isBlockElement(el) && el.nextSibling && /^table$/i.test(el.nextSibling.nodeName)) {
			var previous = el.previousSibling;
			if (!previous) {
				this.selectNodeContents(el.nextSibling.rows[0].cells[0], true);
			} else if (/^table$/i.test(previous.nodeName)) {
				this.selectNodeContents(previous.rows[previous.rows.length-1].cells[previous.rows[previous.rows.length-1].cells.length-1], false);
			} else {
				range.moveStart("character", -1);
				range.collapse(true);
				range.select();
			}
			el.parentNode.removeChild(el);
			return true;
		}
	} else { // Backspacing into a link
		var r2 = range.duplicate();
		r2.moveStart("character", -1);
		var a = r2.parentElement();
		if (a != range.parentElement() && /^a$/i.test(a.nodeName)) {
			r2.collapse(true);
			r2.moveEnd("character", 1);
			r2.pasteHTML('');
			r2.select();
			return true;
		}
		return false;
	}
};
