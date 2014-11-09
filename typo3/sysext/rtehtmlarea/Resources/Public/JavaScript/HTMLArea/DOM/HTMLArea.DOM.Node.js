/***************************************************
 *  HTMLArea.DOM.Node: Node object
 ***************************************************/
HTMLArea.DOM.Node = function (config) {
};
HTMLArea.DOM.Node = Ext.extend(HTMLArea.DOM.Node, {
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
	 * Reference to the editor bookmark object
	 */
	bookMark: null,
	/*
	 * HTMLArea.DOM.Selection constructor
	 */
	constructor: function (config) {
		 	// Apply config
		Ext.apply(this, config);
			// Initialize references
		this.document = this.editor.document;
		this.selection = this.editor.getSelection();
		this.bookMark = this.editor.getBookMark();
	},
	/*
	 * Remove the given element
	 *
	 * @param	object		element: the element to be removed, content and selection being preserved
	 *
	 * @return	void
	 */
	removeMarkup: function (element) {
		var bookMark = this.bookMark.get(this.selection.createRange());
		var parent = element.parentNode;
		while (element.firstChild) {
			parent.insertBefore(element.firstChild, element);
		}
		parent.removeChild(element);
		this.selection.selectRange(this.bookMark.moveTo(bookMark));
	},
	/*
	 * Wrap the range with an inline element
	 *
	 * @param	string	element: the node that will wrap the range
	 * @param	object	range: the range to be wrapped
	 *
	 * @return	void
	 */
	wrapWithInlineElement: function (element, range) {
		if (HTMLArea.UserAgent.isIEBeforeIE9) {
			var nodeName = element.nodeName;
			var bookMark = this.bookMark.get(range);
			if (range.parentElement) {
				var parent = range.parentElement();
				var rangeStart = range.duplicate();
				rangeStart.collapse(true);
				var parentStart = rangeStart.parentElement();
				var rangeEnd = range.duplicate();
				rangeEnd.collapse(true);
				var newRange = this.selection.createRange();

				var parentEnd = rangeEnd.parentElement();
				var upperParentStart = parentStart;
				if (parentStart !== parent) {
					while (upperParentStart.parentNode !== parent) {
						upperParentStart = upperParentStart.parentNode;
					}
				}

				element.innerHTML = range.htmlText;
					// IE eats spaces on the start boundary
				if (range.htmlText.charAt(0) === '\x20') {
					element.innerHTML = '&nbsp;' + element.innerHTML;
				}
				var elementClone = element.cloneNode(true);
				range.pasteHTML(element.outerHTML);
					// IE inserts the element as the last child of the start container
				if (parentStart !== parent
						&& parentStart.lastChild
						&& parentStart.lastChild.nodeType === HTMLArea.DOM.ELEMENT_NODE
						&& parentStart.lastChild.nodeName.toLowerCase() === nodeName) {
					parent.insertBefore(elementClone, upperParentStart.nextSibling);
					parentStart.removeChild(parentStart.lastChild);
						// Sometimes an empty previous sibling was created
					if (elementClone.previousSibling
							&& elementClone.previousSibling.nodeType === HTMLArea.DOM.ELEMENT_NODE
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
						var newRange = this.selection.createRange();
						if (newRange.moveToBookmark(bookMark)) {
							newRange.collapse(false);
							newRange.select();
						}
					} else {
						range.collapse(false);
					}
				}
				parent.normalize();
			} else {
				var parent = range.item(0);
				element = parent.parentNode.insertBefore(element, parent);
				element.appendChild(parent);
				this.bookMark.moveTo(bookMark);
			}
		} else {
			element.appendChild(range.extractContents());
			range.insertNode(element);
			element.normalize();
				// Sometimes Firefox inserts empty elements just outside the boundaries of the range
			var neighbour = element.previousSibling;
			if (neighbour && (neighbour.nodeType !== HTMLArea.DOM.TEXT_NODE) && !/\S/.test(neighbour.textContent)) {
				HTMLArea.DOM.removeFromParent(neighbour);
			}
			neighbour = element.nextSibling;
			if (neighbour && (neighbour.nodeType !== HTMLArea.DOM.TEXT_NODE) && !/\S/.test(neighbour.textContent)) {
				HTMLArea.DOM.removeFromParent(neighbour);
			}
			this.selection.selectNodeContents(element, false);
		}
	},
	/**
	 * Get the position of the node within the document tree.
	 * The tree address returned is an array of integers, with each integer
	 * indicating a child index of a DOM node, starting from
	 * document.documentElement.
	 * The position cannot be used for finding back the DOM tree node once
	 * the DOM tree structure has been modified.
	 * Adapted from FCKeditor
	 *
	 * @param	object		node: the DOM node
	 * @param	boolean		normalized: if true, a normalized position is calculated
	 *
	 * @return	array		the position of the node
	 */
	getPositionWithinTree: function (node, normalized) {
		var documentElement = this.document.documentElement,
			current = node,
			position = [];
		while (current && current != documentElement) {
			var parentNode = current.parentNode;
			if (parentNode) {
				// Get the current node position
				position.unshift(HTMLArea.DOM.getPositionWithinParent(current, normalized));
			}
			current = parentNode;
		}
		return position;
	},

	/**
	 * Get the node given its position in the document tree.
	 * Adapted from FCKeditor
	 * See HTMLArea.DOM.Node::getPositionWithinTree
	 *
	 * @param	array		position: the position of the node in the document tree
	 * @param	boolean		normalized: if true, a normalized position is given
	 *
	 * @return	objet		the node
	 */
	getNodeByPosition: function (position, normalized) {
		var current = this.document.documentElement;
		var i, j, n, m;
		for (i = 0, n = position.length; current && i < n; i++) {
			var target = position[i];
			if (normalized) {
				var currentIndex = -1;
				for (j = 0, m = current.childNodes.length; j < m; j++) {
					var candidate = current.childNodes[j];
					if (
						candidate.nodeType == HTMLArea.DOM.TEXT_NODE
						&& candidate.previousSibling
						&& candidate.previousSibling.nodeType == HTMLArea.DOM.TEXT_NODE
					) {
						continue;
					}
					currentIndex++;
					if (currentIndex == target) {
						current = candidate;
						break;
					}
				}
			} else {
				current = current.childNodes[target];
			}
		}
		return current ? current : null;
	},

	/**
	 * Clean Apple wrapping span and font elements under the specified node
	 *
	 * @param	object		node: the node in the subtree of which cleaning is performed
	 *
	 * @return	void
	 */
	cleanAppleStyleSpans: function (node) {
		if (HTMLArea.UserAgent.isWebKit || HTMLArea.UserAgent.isOpera) {
			if (node.getElementsByClassName) {
				var spans = node.getElementsByClassName('Apple-style-span');
				for (var i = spans.length; --i >= 0;) {
					this.removeMarkup(spans[i]);
				}
			}
			var spans = node.getElementsByTagName('span');
			for (var i = spans.length; --i >= 0;) {
				if (HTMLArea.DOM.hasClass(spans[i], 'Apple-style-span')) {
					this.removeMarkup(spans[i]);
				}
				if (/^(li)$/i.test(spans[i].parentNode.nodeName) && (spans[i].style.cssText.indexOf('line-height') !== -1 || spans[i].style.cssText.indexOf('font-family') !== -1 || spans[i].style.cssText.indexOf('font-size') !== -1)) {
					this.removeMarkup(spans[i]);
				}
			}
			var fonts = node.getElementsByTagName('font');
			for (i = fonts.length; --i >= 0;) {
				if (HTMLArea.DOM.hasClass(fonts[i], 'Apple-style-span')) {
					this.removeMarkup(fonts[i]);
				}
			}
		}
	}
});
