/***************************************************
 *  HTMLArea.DOM.BookMark: BookMark object
 ***************************************************/
HTMLArea.DOM.BookMark = function (config) {
};
HTMLArea.DOM.BookMark = Ext.extend(HTMLArea.DOM.BookMark, {
	/*
	 * Reference to the editor MUST be set in config
	 */
	editor: null,
	/*
	 * Reference to the editor document
	 */
	document: null,
	/*
	 * Reference to the editor selection object
	 */
	selection: null,
	/*
	 * HTMLArea.DOM.Selection constructor
	 */
	constructor: function (config) {
		 	// Apply config
		Ext.apply(this, config);
			// Initialize references
		this.document = this.editor.document;
		this.selection = this.editor.getSelection();
	},
	/*
	 * Get a bookMark
	 *
	 * @param	object		range: the range to bookMark
	 * @param	boolean		nonIntrusive: if true, a non-intrusive bookmark is requested
	 *
	 * @return	object		the bookMark
	 */
	get: function (range, nonIntrusive) {
		var bookMark;
		if (HTMLArea.UserAgent.isIEBeforeIE9) {
			// Bookmarking will not work on control ranges
			try {
				bookMark = range.getBookmark();
			} catch (e) {
				bookMark = null;
			}
		} else {
			if (nonIntrusive) {
				bookMark = this.getNonIntrusiveBookMark(range, true);
			} else {
				bookMark = this.getIntrusiveBookMark(range);
			}
		}
		return bookMark;
	},
	/*
	 * Get an intrusive bookMark
	 * Adapted from FCKeditor
	 * This is an "intrusive" way to create a bookMark. It includes <span> tags
	 * in the range boundaries. The advantage of it is that it is possible to
	 * handle DOM mutations when moving back to the bookMark.
	 *
	 * @param	object		range: the range to bookMark
	 *
	 * @return	object		the bookMark
	 */
	getIntrusiveBookMark: function (range) {
		// Create the bookmark info (random IDs).
		var bookMark = {
			nonIntrusive: false,
			startId: (new Date()).valueOf() + Math.floor(Math.random()*1000) + 'S',
			endId: (new Date()).valueOf() + Math.floor(Math.random()*1000) + 'E'
		};
		var startSpan;
		var endSpan;
		var rangeClone = range.cloneRange();
		// For collapsed ranges, add just the start marker
		if (!range.collapsed ) {
			endSpan = this.document.createElement('span');
			endSpan.style.display = 'none';
			endSpan.id = bookMark.endId;
			endSpan.setAttribute('data-htmlarea-bookmark', true);
			endSpan.innerHTML = '&nbsp;';
			rangeClone.collapse(false);
			rangeClone.insertNode(endSpan);
		}
		startSpan = this.document.createElement('span');
		startSpan.style.display = 'none';
		startSpan.id = bookMark.startId;
		startSpan.setAttribute('data-htmlarea-bookmark', true);
		startSpan.innerHTML = '&nbsp;';
		var rangeClone = range.cloneRange();
		rangeClone.collapse(true);
		rangeClone.insertNode(startSpan);
		bookMark.startNode = startSpan;
		bookMark.endNode = endSpan;
		// Update the range position.
		if (endSpan) {
			range.setEndBefore(endSpan);
			range.setStartAfter(startSpan);
		} else {
			range.setEndAfter(startSpan);
			range.collapse(false);
		}
		return bookMark;
	},
	/*
	 * Get a non-intrusive bookMark
	 * Adapted from FCKeditor
	 *
	 * @param	object		range: the range to bookMark
	 * @param	boolean		normalized: if true, normalized enpoints are calculated
	 *
	 * @return	object		the bookMark
	 */
	getNonIntrusiveBookMark: function (range, normalized) {
		var startContainer = range.startContainer,
			endContainer = range.endContainer,
			startOffset = range.startOffset,
			endOffset = range.endOffset,
			collapsed = range.collapsed,
			child,
			previous,
			bookMark = {};
		if (!startContainer || !endContainer) {
			bookMark = {
				nonIntrusive: true,
				start: 0,
				end: 0
			};
		} else {
			if (normalized) {
				// Find out if the start is pointing to a text node that might be normalized
				if (startContainer.nodeType == HTMLArea.DOM.NODE_ELEMENT) {
					child = startContainer.childNodes[startOffset];
					// In this case, move the start to that text node
					if (
						child
						&& child.nodeType == HTMLArea.DOM.NODE_TEXT
						&& startOffset > 0
						&& child.previousSibling.nodeType == HTMLArea.DOM.NODE_TEXT
					) {
						startContainer = child;
						startOffset = 0;
					}
					// Get the normalized offset
					if (child && child.nodeType == HTMLArea.DOM.NODE_ELEMENT) {
						startOffset = HTMLArea.DOM.getPositionWithinParent(child, true);
					}
				}
				// Normalize the start
				while (
					startContainer.nodeType == HTMLArea.DOM.NODE_TEXT
					&& (previous = startContainer.previousSibling)
					&& previous.nodeType == HTMLArea.DOM.NODE_TEXT
				) {
					startContainer = previous;
					startOffset += previous.nodeValue.length;
				}
				// Process the end only if not collapsed
				if (!collapsed) {
					// Find out if the start is pointing to a text node that will be normalized
					if (endContainer.nodeType == HTMLArea.DOM.NODE_ELEMENT) {
						child = endContainer.childNodes[endOffset];
						// In this case, move the end to that text node
						if (
							child
							&& child.nodeType == HTMLArea.DOM.NODE_TEXT
							&& endOffset > 0
							&& child.previousSibling.nodeType == HTMLArea.DOM.NODE_TEXT
						) {
							endContainer = child;
							endOffset = 0;
						}
						// Get the normalized offset
						if (child && child.nodeType == HTMLArea.DOM.NODE_ELEMENT) {
							endOffset = HTMLArea.DOM.getPositionWithinParent(child, true);
						}
					}
					// Normalize the end
					while (
						endContainer.nodeType == HTMLArea.DOM.NODE_TEXT
						&& (previous = endContainer.previousSibling)
						&& previous.nodeType == HTMLArea.DOM.NODE_TEXT
					) {
						endContainer = previous;
						endOffset += previous.nodeValue.length;
					}
				}
			}
			bookMark = {
				start: this.editor.getDomNode().getPositionWithinTree(startContainer, normalized),
				end: collapsed ? null : this.editor.getDomNode().getPositionWithinTree(endContainer, normalized),
				startOffset: startOffset,
				endOffset: endOffset,
				normalized: normalized,
				collapsed: collapsed,
				nonIntrusive: true
			};
		}
		return bookMark;
	},
	/*
	 * Get the end point of the bookMark
	 * Adapted from FCKeditor
	 *
	 * @param	object		bookMark: the bookMark
	 * @param	boolean		endPoint: true, for startPoint, false for endPoint
	 *
	 * @return	object		the endPoint node
	 */
	getEndPoint: function (bookMark, endPoint) {
		if (endPoint) {
			return this.document.getElementById(bookMark.startId);
		} else {
			return this.document.getElementById(bookMark.endId);
		}
	},
	/*
	 * Get a range and move it to the bookMark
	 *
	 * @param	object		bookMark: the bookmark to move to
	 *
	 * @return	object		the range that was bookmarked
	 */
	moveTo: function (bookMark) {
		var range = this.selection.createRange();
		if (HTMLArea.UserAgent.isIEBeforeIE9) {
			if (bookMark) {
				range.moveToBookmark(bookMark);
			}
		} else {
			if (bookMark.nonIntrusive) {
				range = this.moveToNonIntrusiveBookMark(range, bookMark);
			} else {
				range = this.moveToIntrusiveBookMark(range, bookMark);
			}
		}
		return range;
	},
	/*
	 * Move the range to the intrusive bookMark
	 * Adapted from FCKeditor
	 *
	 * @param	object		range: the range to be moved
	 * @param	object		bookMark: the bookmark to move to
	 *
	 * @return	object		the range that was bookmarked
	 */
	moveToIntrusiveBookMark: function (range, bookMark) {
		var startSpan = this.getEndPoint(bookMark, true),
			endSpan = this.getEndPoint(bookMark, false),
			parent;
		if (startSpan) {
			// If the previous sibling is a text node, let the anchorNode have it as parent
			if (startSpan.previousSibling && startSpan.previousSibling.nodeType === HTMLArea.DOM.TEXT_NODE) {
				range.setStart(startSpan.previousSibling, startSpan.previousSibling.data.length);
			} else {
				range.setStartBefore(startSpan);
			}
			HTMLArea.DOM.removeFromParent(startSpan);
		} else {
			// For some reason, the startSpan was removed or its id attribute was removed so that it cannot be retrieved
			range.setStart(this.document.body, 0);
		}
		// If the bookmarked range was collapsed, the end span will not be available
		if (endSpan) {
			// If the next sibling is a text node, let the focusNode have it as parent
			if (endSpan.nextSibling && endSpan.nextSibling.nodeType === HTMLArea.DOM.TEXT_NODE) {
				range.setEnd(endSpan.nextSibling, 0);
			} else {
				range.setEndBefore(endSpan);
			}
			HTMLArea.DOM.removeFromParent(endSpan);
		} else {
			range.collapse(true);
		}
		return range;
	},
	/*
	 * Move the range to the non-intrusive bookMark
	 * Adapted from FCKeditor
	 *
	 * @param	object		range: the range to be moved
	 * @param	object		bookMark: the bookMark to move to
	 *
	 * @return	object		the range that was bookmarked
	 */
	moveToNonIntrusiveBookMark: function (range, bookMark) {
		if (bookMark.start) {
			// Get the start information
			var startContainer = this.editor.getDomNode().getNodeByPosition(bookMark.start, bookMark.normalized),
				startOffset = bookMark.startOffset;
			// Set the start boundary
			range.setStart(startContainer, startOffset);
			// Get the end information
			var endContainer = bookMark.end && this.editor.getDomNode().getNodeByPosition(bookMark.end, bookMark.normalized),
				endOffset = bookMark.endOffset;
			// Set the end boundary. If not available, collapse the range
			if (endContainer) {
				range.setEnd(endContainer, endOffset);
			} else {
				range.collapse(true);
			}
		}
		return range;
	}
});
