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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/Node
 * HTMLArea.DOM.Node: Node object
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
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/Node
	 */
	var Node = function (config) {

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

		/**
		 * Reference to the editor bookmark object
		 */
		this.bookMark = this.editor.getBookMark();
	};

	/**
	 * Remove the given element
	 *
	 * @param	object		element: the element to be removed, content and selection being preserved
	 *
	 * @return	void
	 */
	Node.prototype.removeMarkup = function (element) {
		var bookMark = this.bookMark.get(this.selection.createRange());
		var parent = element.parentNode;
		while (element.firstChild) {
			parent.insertBefore(element.firstChild, element);
		}
		parent.removeChild(element);
		this.selection.selectRange(this.bookMark.moveTo(bookMark));
	};

	/**
	 * Wrap the range with an inline element
	 *
	 * @param	string	element: the node that will wrap the range
	 * @param	object	range: the range to be wrapped
	 *
	 * @return	void
	 */
	Node.prototype.wrapWithInlineElement = function (element, range) {
		element.appendChild(range.extractContents());
		range.insertNode(element);
		element.normalize();
		// Sometimes Firefox inserts empty elements just outside the boundaries of the range
		var neighbour = element.previousSibling;
		if (neighbour && (neighbour.nodeType !== Dom.TEXT_NODE) && !/\S/.test(neighbour.textContent)) {
			Dom.removeFromParent(neighbour);
		}
		neighbour = element.nextSibling;
		if (neighbour && (neighbour.nodeType !== Dom.TEXT_NODE) && !/\S/.test(neighbour.textContent)) {
			Dom.removeFromParent(neighbour);
		}
		this.selection.selectNodeContents(element, false);
	};

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
	Node.prototype.getPositionWithinTree = function (node, normalized) {
		var documentElement = this.document.documentElement,
			current = node,
			position = [];
		while (current && current != documentElement) {
			var parentNode = current.parentNode;
			if (parentNode) {
				// Get the current node position
				position.unshift(Dom.getPositionWithinParent(current, normalized));
			}
			current = parentNode;
		}
		return position;
	};

	/**
	 * Get the node given its position in the document tree.
	 * Adapted from FCKeditor
	 * See Node.prototype.getPositionWithinTree
	 *
	 * @param	array		position: the position of the node in the document tree
	 * @param	boolean		normalized: if true, a normalized position is given
	 *
	 * @return	objet		the node
	 */
	Node.prototype.getNodeByPosition = function (position, normalized) {
		var current = this.document.documentElement;
		var i, j, n, m;
		for (i = 0, n = position.length; current && i < n; i++) {
			var target = position[i];
			if (normalized) {
				var currentIndex = -1;
				for (j = 0, m = current.childNodes.length; j < m; j++) {
					var candidate = current.childNodes[j];
					if (
						candidate.nodeType == Dom.TEXT_NODE
						&& candidate.previousSibling
						&& candidate.previousSibling.nodeType == Dom.TEXT_NODE
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
	};

	/**
	 * Clean Apple wrapping span and font elements under the specified node
	 *
	 * @param	object		node: the node in the subtree of which cleaning is performed
	 *
	 * @return	void
	 */
	Node.prototype.cleanAppleStyleSpans = function (node) {
		if (UserAgent.isWebKit || UserAgent.isOpera) {
			if (node.getElementsByClassName) {
				var spans = node.getElementsByClassName('Apple-style-span');
				for (var i = spans.length; --i >= 0;) {
					this.removeMarkup(spans[i]);
				}
			}
			var spans = node.getElementsByTagName('span');
			for (var i = spans.length; --i >= 0;) {
				if (Dom.hasClass(spans[i], 'Apple-style-span')) {
					this.removeMarkup(spans[i]);
				}
				if (/^(li|h[1-6])$/i.test(spans[i].parentNode.nodeName) && (spans[i].style.cssText.indexOf('line-height') !== -1 || spans[i].style.cssText.indexOf('font-family') !== -1 || spans[i].style.cssText.indexOf('font-size') !== -1)) {
					this.removeMarkup(spans[i]);
				}
			}
			var fonts = node.getElementsByTagName('font');
			for (i = fonts.length; --i >= 0;) {
				if (Dom.hasClass(fonts[i], 'Apple-style-span')) {
					this.removeMarkup(fonts[i]);
				}
			}
			var uls = node.getElementsByTagName('ul');
			for (i = uls.length; --i >= 0;) {
				if (uls[i].style.cssText.indexOf('line-height') !== -1) {
					uls[i].style.lineHeight = '';
				}
			}
			var ols = node.getElementsByTagName('ol');
			for (i = ols.length; --i >= 0;) {
				if (ols[i].style.cssText.indexOf('line-height') !== -1) {
					ols[i].style.lineHeight = '';
				}
			}
		}
	};

	return Node;

});
