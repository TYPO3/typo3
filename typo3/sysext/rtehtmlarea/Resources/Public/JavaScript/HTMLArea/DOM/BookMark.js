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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/BookMark
 * HTMLArea.DOM.BookMark: BookMark object
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM'],
	function (UserAgent, Util, Dom) {

	/**
	 * Constructor method
	 *
	 * @param {Object} config: an object with property "editor" giving reference to the parent object
	 * @constructor
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/BookMark
	 */
	var BookMark = function (config) {

		/**
		 * Reference to the editor MUST be set in config
		 */
		this.editor = null;

		Util.apply(this, config);

		/**
		 * Reference to the editor document
		 */
		this.document = this.editor.document;

		/**
		 * Reference to the editor selection object
		 */
		this.selection = this.editor.getSelection();
	};

	/**
	 * Get a bookMark
	 *
	 * @param {Object} range: the range to bookMark
	 * @param {Boolean} nonIntrusive: if true, a non-intrusive bookmark is requested
	 *
	 * @return {Object} the bookMark
	 */
	BookMark.prototype.get = function (range, nonIntrusive) {
		var bookMark;
		if (nonIntrusive) {
			bookMark = this.getNonIntrusiveBookMark(range, true);
		} else {
			bookMark = this.getIntrusiveBookMark(range);
		}
		return bookMark;
	};

	/**
	 * Get an intrusive bookMark
	 * Adapted from FCKeditor
	 * This is an "intrusive" way to create a bookMark. It includes <span> tags
	 * in the range boundaries. The advantage of it is that it is possible to
	 * handle DOM mutations when moving back to the bookMark.
	 *
	 * @param {Object} range: the range to bookMark
	 *
	 * @return {Object} the bookMark
	 */
	BookMark.prototype.getIntrusiveBookMark = function (range) {
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
	};

	/**
	 * Get a non-intrusive bookMark
	 * Adapted from FCKeditor
	 *
	 * @param {Object} range: the range to bookMark
	 * @param {Boolean} normalized: if true, normalized enpoints are calculated
	 *
	 * @return {Object} the bookMark
	 */
	BookMark.prototype.getNonIntrusiveBookMark = function (range, normalized) {
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
				if (startContainer.nodeType == Dom.NODE_ELEMENT) {
					child = startContainer.childNodes[startOffset];
					// In this case, move the start to that text node
					if (
						child
						&& child.nodeType == Dom.NODE_TEXT
						&& startOffset > 0
						&& child.previousSibling.nodeType == Dom.NODE_TEXT
					) {
						startContainer = child;
						startOffset = 0;
					}
					// Get the normalized offset
					if (child && child.nodeType == Dom.NODE_ELEMENT) {
						startOffset = Dom.getPositionWithinParent(child, true);
					}
				}
				// Normalize the start
				while (
					startContainer.nodeType == Dom.NODE_TEXT
					&& (previous = startContainer.previousSibling)
					&& previous.nodeType == Dom.NODE_TEXT
				) {
					startContainer = previous;
					startOffset += previous.nodeValue.length;
				}
				// Process the end only if not collapsed
				if (!collapsed) {
					// Find out if the start is pointing to a text node that will be normalized
					if (endContainer.nodeType == Dom.NODE_ELEMENT) {
						child = endContainer.childNodes[endOffset];
						// In this case, move the end to that text node
						if (
							child
							&& child.nodeType == Dom.NODE_TEXT
							&& endOffset > 0
							&& child.previousSibling.nodeType == Dom.NODE_TEXT
						) {
							endContainer = child;
							endOffset = 0;
						}
						// Get the normalized offset
						if (child && child.nodeType == Dom.NODE_ELEMENT) {
							endOffset = Dom.getPositionWithinParent(child, true);
						}
					}
					// Normalize the end
					while (
						endContainer.nodeType == Dom.NODE_TEXT
						&& (previous = endContainer.previousSibling)
						&& previous.nodeType == Dom.NODE_TEXT
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
	};

	/**
	 * Get the end point of the bookMark
	 * Adapted from FCKeditor
	 *
	 * @param	object		bookMark: the bookMark
	 * @param	boolean		endPoint: true, for startPoint, false for endPoint
	 *
	 * @return	object		the endPoint node
	 */
	BookMark.prototype.getEndPoint = function (bookMark, endPoint) {
		if (endPoint) {
			return this.document.getElementById(bookMark.startId);
		} else {
			return this.document.getElementById(bookMark.endId);
		}
	};

	/**
	 * Get a range and move it to the bookMark
	 *
	 * @param	object		bookMark: the bookmark to move to
	 *
	 * @return	object		the range that was bookmarked
	 */
	BookMark.prototype.moveTo = function (bookMark) {
		var range = this.selection.createRange();
		if (bookMark.nonIntrusive) {
			range = this.moveToNonIntrusiveBookMark(range, bookMark);
		} else {
			range = this.moveToIntrusiveBookMark(range, bookMark);
		}
		return range;
	};

	/**
	 * Move the range to the intrusive bookMark
	 * Adapted from FCKeditor
	 *
	 * @param	object		range: the range to be moved
	 * @param	object		bookMark: the bookmark to move to
	 *
	 * @return	object		the range that was bookmarked
	 */
	BookMark.prototype.moveToIntrusiveBookMark = function (range, bookMark) {
		var startSpan = this.getEndPoint(bookMark, true),
			endSpan = this.getEndPoint(bookMark, false),
			parent;
		if (startSpan) {
			// If the previous sibling is a text node, let the anchorNode have it as parent
			if (startSpan.previousSibling && startSpan.previousSibling.nodeType === Dom.TEXT_NODE) {
				range.setStart(startSpan.previousSibling, startSpan.previousSibling.data.length);
			} else {
				range.setStartBefore(startSpan);
			}
			Dom.removeFromParent(startSpan);
		} else {
			// For some reason, the startSpan was removed or its id attribute was removed so that it cannot be retrieved
			range.setStart(this.document.body, 0);
		}
		// If the bookmarked range was collapsed, the end span will not be available
		if (endSpan) {
			// If the next sibling is a text node, let the focusNode have it as parent
			if (endSpan.nextSibling && endSpan.nextSibling.nodeType === Dom.TEXT_NODE) {
				range.setEnd(endSpan.nextSibling, 0);
			} else {
				range.setEndBefore(endSpan);
			}
			Dom.removeFromParent(endSpan);
		} else {
			range.collapse(true);
		}
		return range;
	};

	/**
	 * Move the range to the non-intrusive bookMark
	 * Adapted from FCKeditor
	 *
	 * @param	object		range: the range to be moved
	 * @param	object		bookMark: the bookMark to move to
	 *
	 * @return	object		the range that was bookmarked
	 */
	BookMark.prototype.moveToNonIntrusiveBookMark = function (range, bookMark) {
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
	};

	return BookMark;

});
