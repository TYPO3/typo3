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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/Selection
 * HTMLArea.DOM.Selection: Selection object
 ***************************************************/
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event'],
	function (UserAgent, Util, Dom, Event) {

	/**
	 * Constructor method
	 *
	 * @param {Object} config: an object with property "editor" giving reference to the parent object
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/Selection
	 */
	var Selection = function (config) {

		/**
		 * Reference to the editor MUST be set in config
		 */
		this.editor = null;

		/**
		 * The current selection
		 */
		this.selection = null;

		Util.apply(this, config);

		/**
		 * Reference to the editor document
		 */
		this.document = this.editor.document;

		/**
		 * Reference to the editor iframe window
		 */
		this.window = this.editor.iframe.getEl().contentWindow;

		// Set current selection
		this.get();
	};

	/**
	 * Get the current selection object
	 *
	 * @return	object		this
	 */
	Selection.prototype.get = function () {
		this.editor.focus();
	 	this.selection = this.window.getSelection ? this.window.getSelection() : this.document.selection;
	 	return this;
	};

	/**
	 * Get the type of the current selection
	 *
	 * @return	string		the type of selection ("None", "Text" or "Control")
	 */
	Selection.prototype.getType = function() {
		// By default set the type to "Text"
		var type = 'Text';
		this.get();
		if (typeof this.selection === 'object' && this.selection !== null) {
			// Check if the current selection is a Control
			if (this.selection && this.selection.rangeCount == 1) {
				var range = this.selection.getRangeAt(0);
				if (range.startContainer.nodeType === Dom.ELEMENT_NODE) {
					if (
						// Gecko
						(range.startContainer == range.endContainer && (range.endOffset - range.startOffset) == 1) ||
						// Opera and WebKit
						(range.endContainer.nodeType === Dom.TEXT_NODE && range.endOffset == 0 && range.startContainer.childNodes[range.startOffset].nextSibling == range.endContainer)
					) {
						if (/^(img|hr|li|table|tr|td|embed|object|ol|ul|dl)$/i.test(range.startContainer.childNodes[range.startOffset].nodeName)) {
							type = 'Control';
						}
					}
				}
			}
		}
		return type;
	};

	/**
	 * Empty the current selection
	 *
	 * @return object this
	 */
	Selection.prototype.empty = function () {
		this.get();
		if (typeof this.selection === 'object' && this.selection !== null) {
			if (typeof this.selection.removeAllRanges === 'function') {
				this.selection.removeAllRanges();
			} else {
				// Old version of WebKit
				this.selection.empty();
			}
			if (UserAgent.isOpera) {
				this.editor.focus();
			}
		}
		return this;
	};

	/**
	 * Determine whether the current selection is empty or not
	 *
	 * @return	boolean		true, if the selection is empty
	 */
	Selection.prototype.isEmpty = function () {
		var isEmpty = true;
		this.get();
		if (typeof this.selection === 'object' && this.selection !== null) {
			isEmpty = this.selection.isCollapsed;
		}
		return isEmpty;
	};

	/**
	 * Get a range corresponding to the current selection
	 *
	 * @return	object		the range of the selection
	 */
	Selection.prototype.createRange = function () {
		var range;
		this.get();
		if (typeof this.selection !== 'object' || this.selection === null) {
			range = this.document.createRange();
		} else {
			// Older versions of WebKit did not support getRangeAt
			if (UserAgent.isWebKit && typeof this.selection.getRangeAt !== 'function') {
				range = this.document.createRange();
				if (this.selection.baseNode == null) {
					range.setStart(this.document.body, 0);
					range.setEnd(this.document.body, 0);
				} else {
					range.setStart(this.selection.baseNode, this.selection.baseOffset);
					range.setEnd(this.selection.extentNode, this.selection.extentOffset);
					if (range.collapsed != this.selection.isCollapsed) {
						range.setStart(this.selection.extentNode, this.selection.extentOffset);
						range.setEnd(this.selection.baseNode, this.selection.baseOffset);
					}
				}
			} else {
				try {
					range = this.selection.getRangeAt(0);
				} catch (e) {
					range = this.document.createRange();
				}
			}
		}
		return range;
	};

	/**
	 * Return the ranges of the selection
	 *
	 * @return	array		array of ranges
	 */
	Selection.prototype.getRanges = function () {
		this.get();
		var ranges = [];
		// Older versions of WebKit did not support getRangeAt
		if (typeof this.selection === 'object' && this.selection !== null && typeof this.selection.getRangeAt === 'function') {
			for (var i = this.selection.rangeCount; --i >= 0;) {
				ranges.push(this.selection.getRangeAt(i));
			}
		} else {
			ranges.push(this.createRange());
		}
		return ranges;
	};

	/**
	 * Add a range to the selection
	 *
	 * @param	object		range: the range to be added to the selection
	 *
	 * @return	object		this
	 */
	Selection.prototype.addRange = function (range) {
		this.get();
		if (typeof this.selection === 'object' && this.selection !== null) {
			if (typeof this.selection.addRange === 'function') {
				this.selection.addRange(range);
			} else if (UserAgent.isWebKit) {
				this.selection.setBaseAndExtent(range.startContainer, range.startOffset, range.endContainer, range.endOffset);
			}
		}
		return this;
	};

	/**
	 * Set the ranges of the selection
	 *
	 * @param	array		ranges: array of range to be added to the selection
	 *
	 * @return	object		this
	 */
	Selection.prototype.setRanges = function (ranges) {
		this.get();
		this.empty();
		for (var i = ranges.length; --i >= 0;) {
			this.addRange(ranges[i]);
		}
		return this;
	};

	/**
	 * Set the selection to a given range
	 *
	 * @param	object		range: the range to be selected
	 *
	 * @return	object		this
	 */
	Selection.prototype.selectRange = function (range) {
		this.get();
		if (typeof this.selection === 'object' && this.selection !== null) {
			this.empty().addRange(range);
		}
		return this;
	};

	/**
	 * Set the selection to a given node
	 *
	 * @param	object		node: the node to be selected
	 * @param	boolean		endPoint: collapse the selection at the start point (true) or end point (false) of the node
	 *
	 * @return	object		this
	 */
	Selection.prototype.selectNode = function (node, endPoint) {
		this.get();
		if (typeof this.selection === 'object' && this.selection !== null) {
			if (UserAgent.isWebKit && /^(img)$/i.test(node.nodeName)) {
				this.selection.setBaseAndExtent(node, 0, node, 1);
			} else {
				var range = this.document.createRange();
				if (node.nodeType === Dom.ELEMENT_NODE && /^(html|body)$/i.test(node.nodeName)) {
					if (UserAgent.isWebKit) {
						range.setStart(node, 0);
						range.setEnd(node, node.childNodes.length);
					} else {
						range.selectNodeContents(node);
					}
				} else {
					range.selectNode(node);
				}
				if (typeof endPoint !== 'undefined') {
					range.collapse(endPoint);
				}
				this.selectRange(range);
			}
		}
		return this;
	};

	/**
	 * Set the selection to the inner contents of a given node
	 *
	 * @param	object		node: the node of which the contents are to be selected
	 * @param	boolean		endPoint: collapse the selection at the start point (true) or end point (false)
	 *
	 * @return	object		this
	 */
	Selection.prototype.selectNodeContents = function (node, endPoint) {
		var range;
		this.get();
		if (typeof this.selection === 'object' && this.selection !== null) {
			range = this.document.createRange();
			if (UserAgent.isWebKit) {
				range.setStart(node, 0);
				if (node.nodeType === Dom.TEXT_NODE || node.nodeType === Dom.COMMENT_NODE || node.nodeType === Dom.CDATA_SECTION_NODE) {
					range.setEnd(node, node.textContent.length);
				} else {
					range.setEnd(node, node.childNodes.length);
				}
			} else {
				range.selectNodeContents(node);
			}
			if (typeof endPoint !== 'undefined') {
				range.collapse(endPoint);
			}
			this.selectRange(range);
		}
		return this;
	};

	/**
	 * Get the deepest node that contains both endpoints of the current selection.
	 *
	 * @return	object		the deepest node that contains both endpoints of the current selection.
	 */
	Selection.prototype.getParentElement = function () {
		var parentElement,
			range;
		this.get();
		if (this.getType() === 'Control') {
			parentElement = this.getElement();
		} else {
			range = this.createRange();
			parentElement = range.commonAncestorContainer;
				// Firefox 3 may report the document as commonAncestorContainer
			if (parentElement.nodeType === Dom.DOCUMENT_NODE) {
				parentElement = this.document.body;
			} else {
				while (parentElement && parentElement.nodeType === Dom.TEXT_NODE) {
					parentElement = parentElement.parentNode;
				}
			}
		}
		return parentElement;
	};

	/**
	 * Get the selected element (if any), in the case that a single element (object like and image or a table) is selected
	 * In IE language, we have a range of type 'Control'
	 *
	 * @return	object		the selected node
	 */
	Selection.prototype.getElement = function () {
		var element = null;
		this.get();
		if (typeof this.selection === 'object' && this.selection !== null && this.selection.anchorNode && this.selection.anchorNode.nodeType === Dom.ELEMENT_NODE && this.getType() == 'Control') {
			element = this.selection.anchorNode.childNodes[this.selection.anchorOffset];
				// For Safari, the anchor node for a control selection is the control itself
			if (!element) {
				element = this.selection.anchorNode;
			} else if (element.nodeType !== Dom.ELEMENT_NODE) {
				element = null;
			}
		}
		return element;
	};

	/**
	 * Get the deepest element ancestor of the selection that is of one of the specified types
	 *
	 * @param	array		types: an array of nodeNames
	 *
	 * @return	object		the found ancestor of one of the given types or null
	 */
	Selection.prototype.getFirstAncestorOfType = function (types) {
		var node = this.getParentElement();
		return Dom.getFirstAncestorOfType(node, types);
	};

	/**
	 * Get an array with all the ancestor nodes of the current selection
	 *
	 * @return	array		the ancestor nodes
	 */
	Selection.prototype.getAllAncestors = function () {
		var parent = this.getParentElement(),
			ancestors = [];
		while (parent && parent.nodeType === Dom.ELEMENT_NODE && !/^(html|body)$/i.test(parent.nodeName)) {
			ancestors.push(parent);
			parent = parent.parentNode;
		}
		ancestors.push(this.document.body);
		return ancestors;
	};

	/**
	 * Get an array with the parent elements of a multiple selection
	 *
	 * @return	array		the selected elements
	 */
	Selection.prototype.getElements = function () {
		var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null,
			elements = [];
		if (statusBarSelection) {
			elements.push(statusBarSelection);
		} else {
			var ranges = this.getRanges();
				parent;
			if (ranges.length > 1) {
				for (var i = ranges.length; --i >= 0;) {
					parent = range[i].commonAncestorContainer;
						// Firefox 3 may report the document as commonAncestorContainer
					if (parent.nodeType === Dom.DOCUMENT_NODE) {
						parent = this.document.body;
					} else {
						while (parent && parent.nodeType === Dom.TEXT_NODE) {
							parent = parent.parentNode;
						}
					}
					elements.push(parent);
				}
			} else {
				elements.push(this.getParentElement());
			}
		}
		return elements;
	};

	/**
	 * Get the node whose contents are currently fully selected
	 *
	 * @return	object		the fully selected node, if any, null otherwise
	 */
	Selection.prototype.getFullySelectedNode = function () {
		var node = null,
			isFullySelected = false;
		this.get();
		if (!this.isEmpty()) {
			var type = this.getType();
			var range = this.createRange();
			var ancestors = this.getAllAncestors();
			for (var i = 0, n = ancestors.length; i < n; i++) {
				var ancestor = ancestors[i];
				isFullySelected = (ancestor.textContent == range.toString());
				if (isFullySelected) {
					node = ancestor;
					break;
				}
			}
				// Working around bug with WebKit selection
			if (UserAgent.isWebKit && !isFullySelected) {
				var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
				if (statusBarSelection && statusBarSelection.textContent == range.toString()) {
					isFullySelected = true;
					node = statusBarSelection;
				}
			}
		}
		return node;
	};

	/**
	 * Get the block elements containing the start and the end points of the selection
	 *
	 * @return	object		object with properties start and end set to the end blocks of the selection
	 */
	Selection.prototype.getEndBlocks = function () {
		var range = this.createRange(),
			parentStart,
			parentEnd;
		parentStart = range.startContainer;
		if (/^(html|body)$/i.test(parentStart.nodeName)) {
			parentStart = parentStart.firstChild;
		}
		parentEnd = range.endContainer;
		if (/^(html|body)$/i.test(parentEnd.nodeName)) {
			parentEnd = parentEnd.lastChild;
		}
		while (parentStart && !Dom.isBlockElement(parentStart)) {
			parentStart = parentStart.parentNode;
		}
		while (parentEnd && !Dom.isBlockElement(parentEnd)) {
			parentEnd = parentEnd.parentNode;
		}
		return {
			start: parentStart,
			end: parentEnd
		};
	};

	/**
	 * Determine whether the end poins of the current selection are within the same block
	 *
	 * @return	boolean		true if the end points of the current selection are in the same block
	 */
	Selection.prototype.endPointsInSameBlock = function() {
		var endPointsInSameBlock = true;
		this.get();
		if (!this.isEmpty()) {
			var parent = this.getParentElement();
			var endBlocks = this.getEndBlocks();
			endPointsInSameBlock = (endBlocks.start === endBlocks.end && !/^(table|thead|tbody|tfoot|tr)$/i.test(parent.nodeName));
		}
		return endPointsInSameBlock;
	};

	/**
	 * Retrieve the HTML contents of the current selection
	 *
	 * @return	string		HTML text of the current selection
	 */
	Selection.prototype.getHtml = function () {
		var range = this.createRange(),
			html = '';
		if (!range.collapsed) {
			var cloneContents = range.cloneContents();
			if (!cloneContents) {
				cloneContents = this.document.createDocumentFragment();
			}
			html = this.editor.iframe.htmlRenderer.render(cloneContents, false);
		}
		return html;
	};

	/**
	 * Insert a node at the current position
	 * Delete the current selection, if any.
	 * Split the text node, if needed.
	 *
	 * @param	object		toBeInserted: the node to be inserted
	 *
	 * @return	object		this
	 */
	Selection.prototype.insertNode = function (toBeInserted) {
		var range = this.createRange();
		range.deleteContents();
		toBeSelected = (toBeInserted.nodeType === Dom.DOCUMENT_FRAGMENT_NODE) ? toBeInserted.lastChild : toBeInserted;
		range.insertNode(toBeInserted);
		this.selectNodeContents(toBeSelected, false);
		return this;
	};

	/**
	 * Insert HTML source code at the current position
	 * Delete the current selection, if any.
	 *
	 * @param	string		html: the HTML source code
	 *
	 * @return	object		this
	 */
	Selection.prototype.insertHtml = function (html) {
		this.editor.focus();
		var fragment = this.document.createDocumentFragment();
		var div = this.document.createElement('div');
		div.innerHTML = html;
		while (div.firstChild) {
			fragment.appendChild(div.firstChild);
		}
		this.insertNode(fragment);
		return this;
	};

	/**
	 * Surround the selection with an element specified by its start and end tags
	 * Delete the selection, if any.
	 *
	 * @param	string		startTag: the start tag
	 * @param	string		endTag: the end tag
	 *
	 * @return	void
	 */
	Selection.prototype.surroundHtml = function (startTag, endTag) {
		this.insertHtml(startTag + this.getHtml().replace(Dom.RE_bodyTag, '') + endTag);
	};

	/**
	 * Execute some native execCommand command on the current selection
	 *
	 * @param	string		cmdID: the command name or id
	 * @param	object		UI:
	 * @param	object		param:
	 *
	 * @return	boolean		false
	 */
	Selection.prototype.execCommand = function (cmdID, UI, param) {
		var success = true;
		this.editor.focus();
		try {
			this.document.execCommand(cmdID, UI, param);
		} catch (e) {
			success = false;
			this.editor.appendToLog('HTMLArea/DOM/Selection', 'execCommand', e + ' by execCommand(' + cmdID + ')', 'error');
		}
		this.editor.updateToolbar();
		return success;
	};

	/**
	 * Handle backspace event on the current selection
	 *
	 * @return	boolean		true to stop the event and cancel the default action
	 */
	Selection.prototype.handleBackSpace = function () {
		var self = this;
		window.setTimeout(function() {
			var range = self.createRange();
			var startContainer = range.startContainer;
			var startOffset = range.startOffset;
			// If the selection is collapsed...
			if (self.isEmpty()) {
				// ... and the cursor lies in a direct child of body...
				if (/^(html|body)$/i.test(startContainer.nodeName)) {
					var node = startContainer.childNodes[startOffset-1];
				} else if (/^(html|body)$/i.test(startContainer.parentNode.nodeName)) {
					var node = startContainer;
				// ... or, in Google, a span tag may have been inserted inside a heading element
				} else if (UserAgent.isWebKit && /^(#text)$/i.test(startContainer.nodeName)) {
					var node = startContainer.parentNode;
					if (/^(h[1-6])$/i.test(node.nodeName)) {
						self.editor.getDomNode().cleanAppleStyleSpans(node);
					} else if (node.parentNode && /^(h[1-6])$/i.test(node.parentNode.nodeName)) {
						self.editor.getDomNode().cleanAppleStyleSpans(node.parentNode);
					}
					return false;
				} else {
					return false;
				}
				if (typeof node !== 'undefined') {
					// ... which is a br or text node containing no non-whitespace character...
					node.normalize();
					if (/^(br|#text)$/i.test(node.nodeName) && !/\S/.test(node.textContent)) {
						// Get a meaningful previous sibling in which to reposition de cursor
						var previousSibling = node.previousSibling;
						while (previousSibling && /^(br|#text)$/i.test(previousSibling.nodeName) && !/\S/.test(previousSibling.textContent)) {
							previousSibling = previousSibling.previousSibling;
						}
						// If there is no meaningful previous sibling, the cursor is at the start of body or the start of a direct child of body
						if (previousSibling) {
							// Remove the node
							Dom.removeFromParent(node);
							// Position the cursor
							if (/^(ol|ul|dl)$/i.test(previousSibling.nodeName)) {
								self.selectNodeContents(previousSibling.lastChild, false);
							} else if (/^(table)$/i.test(previousSibling.nodeName)) {
								self.selectNodeContents(previousSibling.rows[previousSibling.rows.length - 1].cells[previousSibling.rows[previousSibling.rows.length - 1].cells.length - 1], false);
							} else if (!/\S/.test(previousSibling.textContent) && previousSibling.firstChild) {
								self.selectNode(previousSibling.firstChild, true);
							} else {
								self.selectNodeContents(previousSibling, false);
							}
						}
						// ... or the only child of body and having no child (IE) or only a br child (FF)
					} else if (
						/^(html|body)$/i.test(node.parentNode.nodeName)
						&& !/\S/.test(node.parentNode.textContent)
						&& (node.childNodes.length === 0 || (node.childNodes.length === 1 && /^(br)$/i.test(node.firstChild.nodeName)))
					) {
						var parentNode = node.parentNode;
						Dom.removeFromParent(node);
						parentNode.innerHTML = '<br />';
						self.selectNodeContents(parentNode, true);
					}
				}
			}
		}, 10);
		return false;

	};

	/**
	 * Detect emails and urls as they are typed in non-IE browsers
	 * Borrowed from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
	 *
	 * @param object event: the browser key event
	 *
	 * @return void
	 */
	Selection.prototype.detectURL = function (event) {
		var key = Event.getKey(event);
		var editor = this.editor;
		var selection = this.get().selection;
		if (!/^(a)$/i.test(this.getParentElement().nodeName)) {
			var autoWrap = function (textNode, tag) {
				var rightText = textNode.nextSibling;
				if (typeof tag === 'string') {
					tag = editor.document.createElement(tag);
				}
				var a = textNode.parentNode.insertBefore(tag, rightText);
				Dom.removeFromParent(textNode);
				a.appendChild(textNode);
				selection.collapse(rightText, 0);
				rightText.parentNode.normalize();

				editor.unLink = function() {
					var t = a.firstChild;
					a.removeChild(t);
					a.parentNode.insertBefore(t, a);
					Dom.removeFromParent(a);
					t.parentNode.normalize();
					editor.unLink = null;
					editor.unlinkOnUndo = false;
				};

				editor.unlinkOnUndo = true;
				return a;
			};
			switch (key) {
				// Space or Enter, see if the text just typed looks like a URL, or email address and link it accordingly
				case Event.ENTER:
				case Event.SPACE:
					if (selection && selection.isCollapsed && selection.anchorNode.nodeType === Dom.TEXT_NODE && selection.anchorNode.data.length > 3 && selection.anchorNode.data.indexOf('.') >= 0) {
						var midStart = selection.anchorNode.data.substring(0,selection.anchorOffset).search(/[a-zA-Z0-9]+\S{3,}$/);
						if (midStart == -1) {
							break;
						}
						if (this.getFirstAncestorOfType('a')) {
							// already in an anchor
							break;
						}
						var matchData = selection.anchorNode.data.substring(0,selection.anchorOffset).replace(/^.*?(\S*)$/, '$1');
						if (matchData.indexOf('@') != -1) {
							var m = matchData.match(HTMLArea.RE_email);
							if (m) {
								var leftText  = selection.anchorNode;
								var rightText = leftText.splitText(selection.anchorOffset);
								var midText   = leftText.splitText(midStart);
								var midEnd = midText.data.search(/[^a-zA-Z0-9\.@_\-]/);
								if (midEnd != -1) {
									var endText = midText.splitText(midEnd);
								}
								autoWrap(midText, 'a').href = 'mailto:' + m[0];
								break;
							}
						}
						var m = matchData.match(HTMLArea.RE_url);
						if (m) {
							var leftText  = selection.anchorNode;
							var rightText = leftText.splitText(selection.anchorOffset);
							var midText   = leftText.splitText(midStart);
							var midEnd = midText.data.search(/[^a-zA-Z0-9\._\-\/\&\?=:@]/);
							if (midEnd != -1) {
								var endText = midText.splitText(midEnd);
							}
							autoWrap(midText, 'a').href = (m[1] ? m[1] : 'http://') + m[3];
							break;
						}
					}
					break;
				default:
					if (key === Event.ESC || (editor.unlinkOnUndo && (event.ctrlKey || event.metaKey) && key === Event.F11)) {
						if (editor.unLink) {
							editor.unLink();
							Event.stopEvent(event);
						}
						break;
					} else if (key) {
						editor.unlinkOnUndo = false;
						if (selection.anchorNode && selection.anchorNode.nodeType === Dom.TEXT_NODE) {
							// See if we might be changing a link
							var a = this.getFirstAncestorOfType('a');
							if (!a) {
								break;
							}
							if (!a.updateAnchorTimeout) {
								if (selection.anchorNode.data.match(HTMLArea.RE_email) && (a.href.match('mailto:' + selection.anchorNode.data.trim()))) {
									var textNode = selection.anchorNode;
									var fn = function() {
										a.href = 'mailto:' + textNode.data.trim();
										a.updateAnchorTimeout = setTimeout(fn, 250);
									};
									a.updateAnchorTimeout = setTimeout(fn, 250);
									break;
								}
								var m = selection.anchorNode.data.match(HTMLArea.RE_url);
								if (m && a.href.match(selection.anchorNode.data.trim())) {
									var textNode = selection.anchorNode;
									var fn = function() {
										var m = textNode.data.match(HTMLArea.RE_url);
										a.href = (m[1] ? m[1] : 'http://') + m[3];
										a.updateAnchorTimeout = setTimeout(fn, 250);
									}
									a.updateAnchorTimeout = setTimeout(fn, 250);
								}
							}
						}
					}
					break;
			}
		}
	};

	/**
	 * Enter event handler
	 *
	 * @return boolean true to stop the event and cancel the default action
	 */
	Selection.prototype.checkInsertParagraph = function() {
		var editor = this.editor;
		var left, right, rangeClone,
			sel	= this.get().selection,
			range	= this.createRange(),
			p	= this.getAllAncestors(),
			block	= null,
			a	= null,
			doc	= this.document;
		for (var i = 0, n = p.length; i < n; ++i) {
			if (Dom.isBlockElement(p[i]) && !/^(html|body|table|tbody|thead|tfoot|tr|dl)$/i.test(p[i].nodeName)) {
				block = p[i];
				break;
			}
		}
		if (block && /^(td|th|tr|tbody|thead|tfoot|table)$/i.test(block.nodeName) && this.editor.config.buttons.table && this.editor.config.buttons.table.disableEnterParagraphs) {
			return false;
		}
		if (!range.collapsed) {
			range.deleteContents();
		}
		this.empty();
		if (!block || /^(td|div|article|aside|footer|header|nav|section)$/i.test(block.nodeName)) {
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
						if (Dom.isBlockElement(block.childNodes[i])) {
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
					while (leftSibling.previousSibling && !Dom.isBlockElement(leftSibling.previousSibling)) {
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
				while (rightSibling.nextSibling && !Dom.isBlockElement(rightSibling.nextSibling)) {
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
				right = doc.createElement('p');
				if (UserAgent.isWebKit || UserAgent.isOpera) {
					right.innerHTML = '<br />';
				}
				right = block.appendChild(right);
				this.selectNodeContents(right, true);
			}
		} else {
			range.setEndAfter(block);
			var df = range.extractContents(), left_empty = false;
			if (!/\S/.test(block.innerHTML) || (!/\S/.test(block.textContent) && !/<(img|hr|table)/i.test(block.innerHTML))) {
				if (!UserAgent.isOpera) {
					block.innerHTML = '<br />';
				}
				left_empty = true;
			}
			p = df.firstChild;
			if (p) {
				if (!/\S/.test(p.innerHTML) || (!/\S/.test(p.textContent) && !/<(img|hr|table)/i.test(p.innerHTML))) {
					if (/^h[1-6]$/i.test(p.nodeName)) {
						p = Dom.convertNode(p, 'p');
					}
					if (/^(dt|dd)$/i.test(p.nodeName)) {
						 p = Dom.convertNode(p, /^(dt)$/i.test(p.nodeName) ? 'dd' : 'dt');
					}
					if (!UserAgent.isOpera) {
						p.innerHTML = '<br />';
					}
					if (/^li$/i.test(p.nodeName) && left_empty && (!block.nextSibling || !/^li$/i.test(block.nextSibling.nodeName))) {
						left = block.parentNode;
						left.removeChild(block);
						range.setEndAfter(left);
						range.collapse(false);
						p = Dom.convertNode(p, /^(li|dd|td|th|p|h[1-6])$/i.test(left.parentNode.nodeName) ? 'br' : 'p');
					}
				}
				range.insertNode(df);
					// Remove any anchor created empty on both sides of the selection
				if (p.previousSibling) {
					var a = p.previousSibling.lastChild;
					if (a && /^a$/i.test(a.nodeName) && !/\S/.test(a.innerHTML)) {
						Dom.convertNode(a, 'br');
					}
				}
				var a = p.lastChild;
				if (a && /^a$/i.test(a.nodeName) && !/\S/.test(a.innerHTML)) {
					Dom.convertNode(a, 'br');
				}
					// Walk inside the deepest child element (presumably inline element)
				while (p.firstChild && p.firstChild.nodeType === Dom.ELEMENT_NODE && !/^(br|img|hr|table)$/i.test(p.firstChild.nodeName)) {
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
					p = doc.createElement('p');
				}
				if (!UserAgent.isOpera) {
					p.innerHTML = '<br />';
				}
				if (block.nextSibling) {
					p = block.parentNode.insertBefore(p, block.nextSibling);
				} else {
					p = block.parentNode.appendChild(p);
				}
				this.selectNodeContents(p, true);
			}
		}
		this.editor.scrollToCaret();
		return true;
	};

	return Selection;

});
