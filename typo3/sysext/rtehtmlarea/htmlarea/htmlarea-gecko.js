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
 * TYPO3 SVN ID: $Id$
 */

/***************************************************
 *  GECKO-SPECIFIC FUNCTIONS
 ***************************************************/
HTMLArea.Editor.prototype.isEditable = function() {
	return (this._doc.designMode === "on");
};
/***************************************************
 *  SELECTIONS AND RANGES
 ***************************************************/

/*
 * Get the current selection object
 */
HTMLArea.Editor.prototype._getSelection = function() {
	return this._iframe.contentWindow.getSelection();
};

/*
 * Empty the selection object
 */
HTMLArea.Editor.prototype.emptySelection = function(selection) {
	if (Ext.isWebKit) {
		if (Ext.isFunction(selection.removeAllRanges)) {
			selection.removeAllRanges();
		} else {
			selection.empty();
		}
	} else {
		selection.removeAllRanges();
	}
	if (Ext.isOpera) {
		this.focus();
	}
};

/*
 * Add a range to the selection
 */
HTMLArea.Editor.prototype.addRangeToSelection = function(selection, range) {
	if (Ext.isWebKit) {
		if (Ext.isFunction(selection.addRange)) {
			selection.addRange(range);
		} else {
			selection.setBaseAndExtent(range.startContainer, range.startOffset, range.endContainer, range.endOffset);
		}
	} else {
		selection.addRange(range);
	}
};

/*
 * Create a range for the current selection
 */
HTMLArea.Editor.prototype._createRange = function(sel) {
	if (Ext.isWebKit) {
		var range = this._doc.createRange();
		if (typeof(sel) == "undefined") {
			return range;
		} else if (sel.baseNode == null) {
			range.setStart(this._doc.body,0);
			range.setEnd(this._doc.body,0);
			return range;
		} else {
			range.setStart(sel.baseNode, sel.baseOffset);
			range.setEnd(sel.extentNode, sel.extentOffset);
			if (range.collapsed != sel.isCollapsed) {
				range.setStart(sel.extentNode, sel.extentOffset);
				range.setEnd(sel.baseNode, sel.baseOffset);
			}
			return range;
		}
	}
	if (Ext.isEmpty(sel)) {
		return this._doc.createRange();
	}
	try {
		return sel.getRangeAt(0);
	} catch(e) {
		return this._doc.createRange();
 	}
};

/*
 * Select a node AND the contents inside the node
 */
HTMLArea.Editor.prototype.selectNode = function(node, endPoint) {
	this.focus();
	var selection = this._getSelection();
	if (Ext.isWebKit && /^(img)$/i.test(node.nodeName)) {
		this._getSelection().setBaseAndExtent(node, 0, node, 1);
	} else {
		var range = this._doc.createRange();
		if (node.nodeType == 1 && node.nodeName.toLowerCase() == "body") {
			if (Ext.isWebKit) {
				range.setStart(node, 0);
				range.setEnd(node, node.childNodes.length);
			} else {
				range.selectNodeContents(node);
			}
		} else {
			range.selectNode(node);
		}
		if (typeof(endPoint) != "undefined") {
			range.collapse(endPoint);
		}
		this.emptySelection(selection);
		this.addRangeToSelection(selection, range);
	}
};
/*
 * Select ONLY the contents inside the given node
 */
HTMLArea.Editor.prototype.selectNodeContents = function(node, endPoint) {
	this.focus();
	var selection = this._getSelection();
	var range = this._doc.createRange();
	if (Ext.isWebKit) {
		range.setStart(node, 0);
		if (node.nodeType == 3 || node.nodeType == 8 || node.nodeType == 4) {
			range.setEnd(node, node.textContent.length);
		} else {
			range.setEnd(node, node.childNodes.length);
		}
	} else {
		range.selectNodeContents(node);
	}
	if (typeof(endPoint) !== "undefined") {
		range.collapse(endPoint);
	}
	this.emptySelection(selection);
	this.addRangeToSelection(selection, range);
};
HTMLArea.Editor.prototype.rangeIntersectsNode = function(range, node) {
	var nodeRange = this._doc.createRange();
	try {
		nodeRange.selectNode(node);
	} catch (e) {
		if (Ext.isWebKit) {
			nodeRange.setStart(node, 0);
			if (node.nodeType == 3 || node.nodeType == 8 || node.nodeType == 4) {
				nodeRange.setEnd(node, node.textContent.length);
			} else {
				nodeRange.setEnd(node, node.childNodes.length);
			}
		} else {
			nodeRange.selectNodeContents(node);
		}
	}
		// Note: sometimes WebKit inverts the end points
	return (range.compareBoundaryPoints(range.END_TO_START, nodeRange) == -1 && range.compareBoundaryPoints(range.START_TO_END, nodeRange) == 1) ||
		(range.compareBoundaryPoints(range.END_TO_START, nodeRange) == 1 && range.compareBoundaryPoints(range.START_TO_END, nodeRange) == -1);
};
/*
 * Get the selection type
 */
HTMLArea.Editor.prototype.getSelectionType = function(selection) {
		// By default set the type to "Text".
	var type = 'Text';
	if (!selection) {
		var selection = this._getSelection();
	}
			// Check if the actual selection is a Control
	if (selection && selection.rangeCount == 1) {
		var range = selection.getRangeAt(0);
		if (range.startContainer.nodeType == 1) {
			if (
					// Gecko
				(range.startContainer == range.endContainer && (range.endOffset - range.startOffset) == 1) ||
					// Opera and WebKit
				(range.endContainer.nodeType == 3 && range.endOffset == 0 && range.startContainer.childNodes[range.startOffset].nextSibling == range.endContainer)
			) {
				if (/^(img|hr|li|table|tr|td|embed|object|ol|ul|dl)$/i.test(range.startContainer.childNodes[range.startOffset].nodeName)) {
					type = 'Control';
				}
			}
		}
	}
	return type;
};
/*
 * Return the ranges of the selection
 */
HTMLArea.Editor.prototype.getSelectionRanges = function(selection) {
	if (!selection) {
		var selection = this._getSelection();
	}
	var ranges = [];
	for (var i = selection.rangeCount; --i >= 0;) {
		ranges.push(selection.getRangeAt(i));
	}
	return ranges;
};
/*
 * Add ranges to the selection
 */
HTMLArea.Editor.prototype.setSelectionRanges = function(ranges, selection) {
	if (!selection) {
		var selection = this._getSelection();
	}
	this.emptySelection(selection);
	for (var i = ranges.length; --i >= 0;) {
		this.addRangeToSelection(selection, ranges[i]);
	}
};
/*
 * Retrieves the selected element (if any), just in the case that a single element (object like and image or a table) is selected.
 */
HTMLArea.Editor.prototype.getSelectedElement = function(selection) {
	var selectedElement = null;
	if (!selection) {
		var selection = this._getSelection();
	}
	if (selection && selection.anchorNode && selection.anchorNode.nodeType == 1) {
		if (this.getSelectionType(selection) == "Control") {
			selectedElement = selection.anchorNode.childNodes[selection.anchorOffset];
				// For Safari, the anchor node for a control selection is the control itself
			if (!selectedElement) {
				selectedElement = selection.anchorNode;
			} else if (selectedElement.nodeType != 1) {
				return null;
			}
		}
	}
	return selectedElement;
};

/*
 * Retrieve the HTML contents of selected block
 */
HTMLArea.Editor.prototype.getSelectedHTML = function() {
	var range = this._createRange(this._getSelection());
	if (range.collapsed) return "";
	var cloneContents = range.cloneContents();
	if (!cloneContents) {
		cloneContents = this._doc.createDocumentFragment();
	}
	return HTMLArea.getHTML(cloneContents, false, this);
};

/*
 * Retrieve simply HTML contents of the selected block, IE ignoring control ranges
 */
HTMLArea.Editor.prototype.getSelectedHTMLContents = function() {
	return this.getSelectedHTML();
};

/*
 * Get the deepest node that contains both endpoints of the current selection.
 */
HTMLArea.Editor.prototype.getParentElement = function(selection, range) {
	if (!selection) {
		var selection = this._getSelection();
	}
	if (this.getSelectionType(selection) === "Control") {
		return this.getSelectedElement(selection);
	}
	if (typeof(range) === "undefined") {
		var range = this._createRange(selection);
	}
	var parentElement = range.commonAncestorContainer;
		// For some reason, Firefox 3 may report the Document as commonAncestorContainer
	if (parentElement.nodeType == 9) return this._doc.body;
	while (parentElement && parentElement.nodeType == 3) {
		parentElement = parentElement.parentNode;
	}
	return parentElement;
};

/*
 * Get the selected element, if any.  That is, the element that you have last selected in the "path"
 * at the bottom of the editor, or a "control" (eg image)
 *
 * @returns null | element
 * Borrowed from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
 */
HTMLArea.Editor.prototype._activeElement = function(selection) {
	if (this._selectionEmpty(selection)) {
		return null;
	}
		// Check if the anchor (start of selection) is an element.
	if (selection.anchorNode.nodeType == 1) {
		return selection.anchorNode;
	} else {
		return null;
	}
};

/*
 * Determine if the current selection is empty or not.
 */
HTMLArea.Editor.prototype._selectionEmpty = function(sel) {
	if (!sel) return true;
	return sel.isCollapsed;
};

/*
 * Get a bookmark
 * Adapted from FCKeditor
 * This is an "intrusive" way to create a bookmark. It includes <span> tags
 * in the range boundaries. The advantage of it is that it is possible to
 * handle DOM mutations when moving back to the bookmark.
 */
HTMLArea.Editor.prototype.getBookmark = function (range) {
		// Create the bookmark info (random IDs).
	var bookmark = {
		startId : (new Date()).valueOf() + Math.floor(Math.random()*1000) + 'S',
		endId   : (new Date()).valueOf() + Math.floor(Math.random()*1000) + 'E'
	};

	var startSpan;
	var endSpan;
	var rangeClone = range.cloneRange();

		// For collapsed ranges, add just the start marker.
	if (!range.collapsed ) {
		endSpan = this._doc.createElement("span");
		endSpan.style.display = "none";
		endSpan.id = bookmark.endId;
		endSpan.setAttribute("HTMLArea_bookmark", true);
		endSpan.innerHTML = "&nbsp;";
		rangeClone.collapse(false);
		rangeClone.insertNode(endSpan);
	}

	startSpan = this._doc.createElement("span");
	startSpan.style.display = "none";
	startSpan.id = bookmark.startId;
	startSpan.setAttribute("HTMLArea_bookmark", true);
	startSpan.innerHTML = "&nbsp;";
	var rangeClone = range.cloneRange();
	rangeClone.collapse(true);
	rangeClone.insertNode(startSpan);
	bookmark.startNode = startSpan;
	bookmark.endNode = endSpan;
		// Update the range position.
	if (endSpan) {
		range.setEndBefore(endSpan);
		range.setStartAfter(startSpan);
	} else {
		range.setEndAfter(startSpan);
		range.collapse(false);
	}
	return bookmark;
};

/*
 * Get the end point of the bookmark
 * Adapted from FCKeditor
 */
HTMLArea.Editor.prototype.getBookmarkNode = function(bookmark, endPoint) {
	if (endPoint) {
		return this._doc.getElementById(bookmark.startId);
	} else {
		return this._doc.getElementById(bookmark.endId);
	}
};

/*
 * Move the range to the bookmark
 * Adapted from FCKeditor
 */
HTMLArea.Editor.prototype.moveToBookmark = function (bookmark) {
	var startSpan  = this.getBookmarkNode(bookmark, true);
	var endSpan    = this.getBookmarkNode(bookmark, false);
	var parent;
	var range = this._createRange();
	if (startSpan) {
			// If the previous sibling is a text node, let the anchorNode have it as parent
		if (startSpan.previousSibling && startSpan.previousSibling.nodeType == 3) {
			range.setStart(startSpan.previousSibling, startSpan.previousSibling.data.length);
		} else {
			range.setStartBefore(startSpan);
		}
		HTMLArea.removeFromParent(startSpan);
	} else {
			// For some reason, the startSpan was removed or its id attribute was removed so that it cannot be retrieved
		range.setStart(this._doc.body, 0);
	}
		// If the bookmarked range was collapsed, the end span will not be available
	if (endSpan) {
			// If the next sibling is a text node, let the focusNode have it as parent
		if (endSpan.nextSibling && endSpan.nextSibling.nodeType == 3) {
			range.setEnd(endSpan.nextSibling, 0);
		} else {
			range.setEndBefore(endSpan);
		}
		HTMLArea.removeFromParent(endSpan);
	} else {
		range.collapse(true);
	}
	return range;
};

/*
 * Select range
 */
HTMLArea.Editor.prototype.selectRange = function (range) {
	var selection = this._getSelection();
	this.emptySelection(selection);
	this.addRangeToSelection(selection, range);
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
	this.focus();
	var range = this._createRange(this._getSelection());
	range.deleteContents();
	var toBeSelected = (toBeInserted.nodeType === 11) ? toBeInserted.lastChild : toBeInserted;
	range.insertNode(toBeInserted);
	this.selectNodeContents(toBeSelected, false);
};

/*
 * Insert HTML source code at the current position.
 * Delete the current selection, if any.
 */
HTMLArea.Editor.prototype.insertHTML = function(html) {
	this.focus();
	var fragment = this._doc.createDocumentFragment();
	var div = this._doc.createElement("div");
	div.innerHTML = html;
	while (div.firstChild) {
		fragment.appendChild(div.firstChild);
	}
	this.insertNodeAtSelection(fragment);
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
	element.appendChild(range.extractContents());
	range.insertNode(element);
	element.normalize();
		// Sometimes Firefox inserts empty elements just outside the boundaries of the range
	var neighbour = element.previousSibling;
	if (neighbour && (neighbour.nodeType != 3) && !/\S/.test(neighbour.textContent)) {
		HTMLArea.removeFromParent(neighbour);
	}
	neighbour = element.nextSibling;
	if (neighbour && (neighbour.nodeType != 3) && !/\S/.test(neighbour.textContent)) {
		HTMLArea.removeFromParent(neighbour);
	}
	this.selectNodeContents(element, false);
};

/*
 * Clean Apple wrapping span and font tags under the specified node
 *
 * @param	object	node: the node in the subtree of which cleaning is performed
 *
 * @return	void
 */
HTMLArea.Editor.prototype.cleanAppleStyleSpans = function(node) {
	if (Ext.isWebKit) {
		if (node.getElementsByClassName) {
			var spans = node.getElementsByClassName("Apple-style-span");
			for (var i = spans.length; --i >= 0;) {
				this.removeMarkup(spans[i]);
			}
		} else {
			var spans = node.getElementsByTagName("span");
			for (var i = spans.length; --i >= 0;) {
				if (HTMLArea.DOM.hasClass(spans[i], "Apple-style-span")) {
					this.removeMarkup(spans[i]);
				}
			}
			var fonts = node.getElementsByTagName("font");
			for (i = fonts.length; --i >= 0;) {
				if (HTMLArea.DOM.hasClass(fonts[i], "Apple-style-span")) {
					this.removeMarkup(fonts[i]);
				}
			}
		}
	}
};

/***************************************************
 *  EVENTS HANDLERS
 ***************************************************/
/*
 * Backspace event handler
 */
HTMLArea.Editor.prototype._checkBackspace = function() {
	var self = this;
	window.setTimeout(function() {
		var selection = self._getSelection();
		var range = self._createRange(selection);
		var startContainer = range.startContainer;
		var startOffset = range.startOffset;
			// If the selection is collapsed...
		if (self._selectionEmpty()) {
				// ... and the cursor lies in a direct child of body...
			if (/^(body)$/i.test(startContainer.nodeName)) {
				var node = startContainer.childNodes[startOffset];
			} else if (/^(body)$/i.test(startContainer.parentNode.nodeName)) {
				var node = startContainer;
			} else {
				return false;
			}
				// ... which is a br or text node containing no non-whitespace character
			if (/^(br|#text)$/i.test(node.nodeName) && !/\S/.test(node.textContent)) {
					// Get a meaningful previous sibling in which to reposition de cursor
				var previousSibling = node.previousSibling;
				while (previousSibling && /^(br|#text)$/i.test(previousSibling.nodeName) && !/\S/.test(previousSibling.textContent)) {
					previousSibling = previousSibling.previousSibling;
				}
					// If there is no meaningful previous sibling, the cursor is at the start of body
				if (previousSibling) {
						// Remove the node
					HTMLArea.removeFromParent(node);
						// Position the cursor
					if (/^(ol|ul|dl)$/i.test(previousSibling.nodeName)) {
						self.selectNodeContents(previousSibling.lastChild, false);
					} else if (/^(table)$/i.test(previousSibling.nodeName)) {
						self.selectNodeContents(previousSibling.rows[previousSibling.rows.length-1].cells[previousSibling.rows[previousSibling.rows.length-1].cells.length-1], false);
					} else if (!/\S/.test(previousSibling.textContent) && previousSibling.firstChild) {
						self.selectNode(previousSibling.firstChild, true);
					} else {
						self.selectNodeContents(previousSibling, false);
					}
				}
			}
		}
	}, 10);
	return false;
};

/*
 * Enter event handler
 */
HTMLArea.Editor.prototype._checkInsertP = function() {
	var editor = this;
	this.focus();
	var i, left, right, rangeClone,
		sel	= this._getSelection(),
		range	= this._createRange(sel),
		p	= this.getAllAncestors(),
		block	= null,
		a	= null,
		doc	= this._doc;
	for (i = 0; i < p.length; ++i) {
		if (HTMLArea.isBlockElement(p[i]) && !/^(html|body|table|tbody|thead|tfoot|tr|dl)$/i.test(p[i].nodeName)) {
			block = p[i];
			break;
		}
	}
	if (block && /^(td|th|tr|tbody|thead|tfoot|table)$/i.test(block.nodeName) && this.config.buttons.table && this.config.buttons.table.disableEnterParagraphs) return false;
	if (!range.collapsed) {
		range.deleteContents();
	}
	this.emptySelection(sel);
	if (!block || /^(td|div)$/i.test(block.nodeName)) {
		if (!block) {
			block = doc.body;
		}
		if (block.hasChildNodes()) {
			rangeClone = range.cloneRange();
			if (range.startContainer == block) {
					// Selection is directly under the block
				var blockOnLeft = null;
				var leftSibling = null;
					// Looking for the farthest node on the left that is not a block
				for (var i = range.startOffset; --i >= 0;) {
					if (HTMLArea.isBlockElement(block.childNodes[i])) {
						blockOnLeft = block.childNodes[i];
						break;
					} else {
						rangeClone.setStartBefore(block.childNodes[i]);
					}
				}
			} else {
					// Looking for inline or text container immediate child of block
				var inlineContainer = range.startContainer;
				while (inlineContainer.parentNode != block) {
					inlineContainer = inlineContainer.parentNode;
				}
					// Looking for the farthest node on the left that is not a block
				var leftSibling = inlineContainer;
				while (leftSibling.previousSibling && !HTMLArea.isBlockElement(leftSibling.previousSibling)) {
					leftSibling = leftSibling.previousSibling;
				}
				rangeClone.setStartBefore(leftSibling);
				var blockOnLeft = leftSibling.previousSibling;
			}
				// Avoiding surroundContents buggy in Opera and Safari
			left = doc.createElement('p');
			left.appendChild(rangeClone.extractContents());
			if (!left.textContent && !left.getElementsByTagName('img').length && !left.getElementsByTagName('table').length) {
				left.innerHTML = '<br />';
			}
			if (block.hasChildNodes()) {
				if (blockOnLeft) {
					left = block.insertBefore(left, blockOnLeft.nextSibling);
				} else {
					left = block.insertBefore(left, block.firstChild);
				}
			} else {
				left = block.appendChild(left);
			}
			block.normalize();
				// Looking for the farthest node on the right that is not a block
			var rightSibling = left;
			while (rightSibling.nextSibling && !HTMLArea.isBlockElement(rightSibling.nextSibling)) {
				rightSibling = rightSibling.nextSibling;
			}
			var blockOnRight = rightSibling.nextSibling;
			range.setEndAfter(rightSibling);
			range.setStartAfter(left);
				// Avoiding surroundContents buggy in Opera and Safari
			right = doc.createElement('p');
			right.appendChild(range.extractContents());
			if (!right.textContent && !right.getElementsByTagName('img').length && !right.getElementsByTagName('table').length) {
				right.innerHTML = '<br />';
			}
			if (!(left.childNodes.length == 1 && right.childNodes.length == 1 && left.firstChild.nodeName.toLowerCase() == 'br' && right.firstChild.nodeName.toLowerCase() == 'br')) {
				if (blockOnRight) {
					right = block.insertBefore(right, blockOnRight);
				} else {
					right = block.appendChild(right);
				}
				this.selectNodeContents(right, true);
			} else {
				this.selectNodeContents(left, true);
			}
			block.normalize();
		} else {
			var first = block.firstChild;
			if (first) {
				block.removeChild(first);
			}
			right = doc.createElement("p");
			if (Ext.isWebKit || Ext.isOpera) {
				right.innerHTML = "<br />";
			}
			right = block.appendChild(right);
			this.selectNodeContents(right, true);
		}
	} else {
		range.setEndAfter(block);
		var df = range.extractContents(), left_empty = false;
		if (!/\S/.test(block.innerHTML) || (!/\S/.test(block.textContent) && !/<(img|hr|table)/i.test(block.innerHTML))) {
			if (!Ext.isOpera) {
				block.innerHTML = "<br />";
			}
			left_empty = true;
		}
		p = df.firstChild;
		if (p) {
			if (!/\S/.test(p.innerHTML) || (!/\S/.test(p.textContent) && !/<(img|hr|table)/i.test(p.innerHTML))) {
 				if (/^h[1-6]$/i.test(p.nodeName)) {
					p = this.convertNode(p, "p");
				}
				if (/^(dt|dd)$/i.test(p.nodeName)) {
					 p = this.convertNode(p, (p.nodeName.toLowerCase() === "dt") ? "dd" : "dt");
				}
				if (!Ext.isOpera) {
					p.innerHTML = "<br />";
				}
				if (/^li$/i.test(p.nodeName) && left_empty && (!block.nextSibling || !/^li$/i.test(block.nextSibling.nodeName))) {
					left = block.parentNode;
					left.removeChild(block);
					range.setEndAfter(left);
					range.collapse(false);
					p = this.convertNode(p, /^(li|dd|td|th|p|h[1-6])$/i.test(left.parentNode.nodeName) ? "br" : "p");
				}
			}
			range.insertNode(df);
				// Remove any anchor created empty on both sides of the selection
			if (p.previousSibling) {
				var a = p.previousSibling.lastChild;
				if (a && /^a$/i.test(a.nodeName) && !/\S/.test(a.innerHTML)) {
					this.convertNode(a, 'br');
				}
			}
			var a = p.lastChild;
			if (a && /^a$/i.test(a.nodeName) && !/\S/.test(a.innerHTML)) {
				this.convertNode(a, 'br');
			}
				// Walk inside the deepest child element (presumably inline element)
			while (p.firstChild && p.firstChild.nodeType == 1 && !/^(br|img|hr|table)$/i.test(p.firstChild.nodeName)) {
				p = p.firstChild;
			}
			if (/^br$/i.test(p.nodeName)) {
				p = p.parentNode.insertBefore(doc.createTextNode('\x20'), p);
			} else if (!/\S/.test(p.innerHTML)) {
					// Need some element inside the deepest element
				p.appendChild(doc.createElement('br'));
			}
			this.selectNodeContents(p, true);
		} else {
			if (/^(li|dt|dd)$/i.test(block.nodeName)) {
				p = doc.createElement(block.nodeName);
			} else {
				p = doc.createElement("p");
			}
			if (!Ext.isOpera) {
				p.innerHTML = "<br />";
			}
			if (block.nextSibling) {
				p = block.parentNode.insertBefore(p, block.nextSibling);
			} else {
				p = block.parentNode.appendChild(p);
			}
			this.selectNodeContents(p, true);
		}
	}
	this.scrollToCaret();
	return true;
};

/*
 * Detect emails and urls as they are typed in Mozilla
 * Borrowed from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
 */
HTMLArea.Editor.prototype._detectURL = function(event) {
	var ev = event.browserEvent;
	var editor = this;
	var s = this._getSelection();
	if (this.getParentElement(s).nodeName.toLowerCase() != 'a') {
		var autoWrap = function (textNode, tag) {
			var rightText = textNode.nextSibling;
			if (typeof(tag) == 'string') tag = editor._doc.createElement(tag);
			var a = textNode.parentNode.insertBefore(tag, rightText);
			HTMLArea.removeFromParent(textNode);
			a.appendChild(textNode);
			s.collapse(rightText, 0);
			rightText.parentNode.normalize();
	
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
			case 13:
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
						autoWrap(midText, 'a').href = (m[1] ? m[1] : 'http://') + m[3];
						break;
					}
				}
				break;
			default:
				if(ev.keyCode == 27 || (editor._unlinkOnUndo && ev.ctrlKey && ev.which == 122) ) {
					if(this._unLink) {
						this._unLink();
						event.stopEvent();
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
									a.href = (m[1] ? m[1] : 'http://') + m[3];
									a._updateAnchTimeout = setTimeout(fn, 250);
								}
								a._updateAnchTimeout = setTimeout(fn, 250);
							}
						}
					}
				}
				break;
		}
	}
};
