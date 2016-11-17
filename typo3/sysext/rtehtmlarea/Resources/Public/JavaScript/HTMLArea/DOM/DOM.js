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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM
 * HTMLArea.DOM: Utility functions for dealing with the DOM tree *
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (UserAgent, Util) {

	/**
	 *
	 * @type {{ELEMENT_NODE: number, ATTRIBUTE_NODE: number, TEXT_NODE: number, CDATA_SECTION_NODE: number, ENTITY_REFERENCE_NODE: number, ENTITY_NODE: number, PROCESSING_INSTRUCTION_NODE: number, COMMENT_NODE: number, DOCUMENT_NODE: number, DOCUMENT_TYPE_NODE: number, DOCUMENT_FRAGMENT_NODE: number, NOTATION_NODE: number, RE_blockTags: RegExp, RE_noClosingTag: RegExp, RE_bodyTag: RegExp, isBlockElement: Function, needsClosingTag: Function, getClassNames: Function, hasClass: Function, addClass: Function, removeClass: Function, isRequiredClass: Function, getInnerText: Function, getBlockAncestors: Function, getFirstAncestorOfType: Function, getPositionWithinParent: Function, hasAllowedAttributes: Function, removeFromParent: Function, convertNode: Function, rangeIntersectsNode: Function, makeUrlsAbsolute: Function, makeImageSourceAbsolute: Function, makeLinkHrefAbsolute: Function, addBaseUrl: Function, getPosition: Function, getSize: Function, setSize: Function, setStyle: Function}}
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM
	 */
	var Dom = {

		/***************************************************
		*  DOM NODES TYPES CONSTANTS
		***************************************************/
		ELEMENT_NODE: 1,
		ATTRIBUTE_NODE: 2,
		TEXT_NODE: 3,
		CDATA_SECTION_NODE: 4,
		ENTITY_REFERENCE_NODE: 5,
		ENTITY_NODE: 6,
		PROCESSING_INSTRUCTION_NODE: 7,
		COMMENT_NODE: 8,
		DOCUMENT_NODE: 9,
		DOCUMENT_TYPE_NODE: 10,
		DOCUMENT_FRAGMENT_NODE: 11,
		NOTATION_NODE: 12,

		/***************************************************
		*  DOM-RELATED REGULAR EXPRESSIONS
		***************************************************/
		RE_blockTags: /^(address|article|aside|body|blockquote|caption|dd|div|dl|dt|fieldset|footer|form|header|hr|h1|h2|h3|h4|h5|h6|iframe|li|ol|p|pre|nav|noscript|section|table|tbody|td|tfoot|th|thead|tr|ul)$/i,
		RE_noClosingTag: /^(area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)$/i,
		RE_bodyTag: new RegExp('<\/?(body)[^>]*>', 'gi'),

		/***************************************************
		*  STATIC METHODS ON DOM NODE
		***************************************************/
		/**
		 * Determine whether an element node is a block element
		 *
		 * @param	object		element: the element node
		 *
		 * @return	boolean		true, if the element node is a block element
		 */
		isBlockElement: function (element) {
			return element && element.nodeType === Dom.ELEMENT_NODE && Dom.RE_blockTags.test(element.nodeName);
		},

		/**
		 * Determine whether an element node needs a closing tag
		 *
		 * @param	object		element: the element node
		 *
		 * @return	boolean		true, if the element node needs a closing tag
		 */
		needsClosingTag: function (element) {
			return element && element.nodeType === Dom.ELEMENT_NODE && !Dom.RE_noClosingTag.test(element.nodeName);
		},

		/**
		 * Gets the class names assigned to a node, reserved classes removed
		 *
		 * @param	object		node: the node
		 * @return	array		array of class names on the node, reserved classes removed
		 */
		getClassNames: function (node) {
			var classNames = [];
			if (node) {
				if (node.className && /\S/.test(node.className)) {
					classNames = node.className.trim().split(' ');
				}
				if (HTMLArea.reservedClassNames.test(node.className)) {
					var cleanClassNames = [];
					var j = -1;
					for (var i = 0, n = classNames.length; i < n; ++i) {
						if (!HTMLArea.reservedClassNames.test(classNames[i])) {
							cleanClassNames[++j] = classNames[i];
						}
					}
					classNames = cleanClassNames;
				}
			}
			return classNames;
		},

		/**
		 * Check if a class name is in the class attribute of a node
		 *
		 * @param	object		node: the node
		 * @param	string		className: the class name to look for
		 * @param	boolean		substring: if true, look for a class name starting with the given string
		 * @return	boolean		true if the class name was found, false otherwise
		 */
		hasClass: function (node, className, substring) {
			var found = false;
			if (node && node.className) {
				var classes = node.className.trim().split(' ');
				for (var i = classes.length; --i >= 0;) {
					found = ((classes[i] == className) || (substring && classes[i].indexOf(className) == 0));
					if (found) {
						break;
					}
				}
			}
			return found;
		},

		/**
		 * Add a class name to the class attribute of a node
		 *
		 * @param object node: the node
		 * @param string className: the name of the class to be added
		 * @param integer recursionLevel: recursion level of current call
		 * @return void
		 */
		addClass: function (node, className, recursionLevel) {
			if (node) {
				var classNames = Dom.getClassNames(node);
				if (classNames.indexOf(className) === -1) {
					// Remove classes configured to be incompatible with the class to be added
					if (node.className && HTMLArea.classesXOR && HTMLArea.classesXOR[className] && typeof HTMLArea.classesXOR[className].test === 'function') {
						for (var i = classNames.length; --i >= 0;) {
							if (HTMLArea.classesXOR[className].test(classNames[i])) {
								Dom.removeClass(node, classNames[i]);
							}
						}
					}
					// Check dependencies to add required classes recursively
					if (typeof HTMLArea.classesRequires !== 'undefined' && typeof HTMLArea.classesRequires[className] !== 'undefined') {
						if (typeof recursionLevel === 'undefined') {
							var recursionLevel = 1;
						} else {
							recursionLevel++;
						}
						if (recursionLevel < 20) {
							for (var i = 0, n = HTMLArea.classesRequires[className].length; i < n; i++) {
								var classNames = Dom.getClassNames(node);
								if (classNames.indexOf(HTMLArea.classesRequires[className][i]) === -1) {
									Dom.addClass(node, HTMLArea.classesRequires[className][i], recursionLevel);
								}
							}
						}
					}
					if (node.className) {
						node.className += ' ' + className;
					} else {
						node.className = className;
					}
				}
			}
		},

		/**
		 * Remove a class name from the class attribute of a node
		 *
		 * @param	object		node: the node
		 * @param	string		className: the class name to removed
		 * @param	boolean		substring: if true, remove the class names starting with the given string
		 * @return	void
		 */
		removeClass: function (node, className, substring) {
			if (node && node.className) {
				var classes = node.className.trim().split(' ');
				var newClasses = [];
				for (var i = classes.length; --i >= 0;) {
					if ((!substring && classes[i] != className) || (substring && classes[i].indexOf(className) != 0)) {
						newClasses[newClasses.length] = classes[i];
					}
				}
				if (newClasses.length) {
					node.className = newClasses.join(' ');
				} else {
					if (!UserAgent.isOpera) {
						node.removeAttribute('class');
						if (UserAgent.isIEBeforeIE9) {
							node.removeAttribute('className');
						}
					} else {
						node.className = '';
					}
				}
				// Remove the first unselectable class that is no more required, the following ones being removed by recursive calls
				if (node.className && typeof HTMLArea.classesSelectable !== 'undefined') {
					classes = Dom.getClassNames(node);
					for (var i = classes.length; --i >= 0;) {
						if (typeof HTMLArea.classesSelectable[classes[i]] !== 'undefined' && !HTMLArea.classesSelectable[classes[i]] && !Dom.isRequiredClass(node, classes[i])) {
							Dom.removeClass(node, classes[i]);
							break;
						}
					}
				}
			}
		},

		/**
		 * Check if the class is required by another class assigned to the node
		 *
		 * @param object node: the node
		 * @param string className: the class name to check
		 * @return boolean
		 */
		isRequiredClass: function (node, className) {
			if (typeof HTMLArea.classesRequiredBy !== 'undefined') {
				var classes = Dom.getClassNames(node);
				for (var i = classes.length; --i >= 0;) {
					if (typeof HTMLArea.classesRequiredBy[className] !== 'undefined' && HTMLArea.classesRequiredBy[className].indexOf(classes[i]) !== -1) {
						return true;
					}
				}
			}
			return false;
		},

		/**
		 * Get the innerText of a given node
		 *
		 * @param	object		node: the node
		 *
		 * @return	string		the text inside the node
		 */
		getInnerText: function (node) {
			return UserAgent.isIEBeforeIE9 ? node.innerText : node.textContent;;
		},

		/**
		 * Get the block ancestors of a node within a given block
		 *
		 * @param	object		node: the given node
		 * @param	object		withinBlock: the containing node
		 *
		 * @return	array		array of block ancestors
		 */
		getBlockAncestors: function (node, withinBlock) {
			var ancestors = [];
			var ancestor = node;
			while (ancestor && (ancestor.nodeType === Dom.ELEMENT_NODE) && !/^(html|body)$/i.test(ancestor.nodeName) && ancestor != withinBlock) {
				if (Dom.isBlockElement(ancestor)) {
					ancestors.unshift(ancestor);
				}
				ancestor = ancestor.parentNode;
			}
			ancestors.unshift(ancestor);
			return ancestors;
		},

		/**
		 * Get the deepest element ancestor of a given node that is of one of the specified types
		 *
		 * @param	object		node: the given node
		 * @param	array		types: an array of nodeNames
		 *
		 * @return	object		the found ancestor of one of the given types or null
		 */
		getFirstAncestorOfType: function (node, types) {
			var ancestor = null,
				parent = node;
			if (typeof types === 'string') {
				var types = [types];
			}
			// Is types a non-empty array?
			if (types && Object.prototype.toString.call(types) === '[object Array]' && types.length > 0) {
				types = new RegExp( '^(' + types.join('|') + ')$', 'i');
				while (parent && parent.nodeType === Dom.ELEMENT_NODE && !/^(html|body)$/i.test(parent.nodeName)) {
					if (types.test(parent.nodeName)) {
						ancestor = parent;
						break;
					}
					parent = parent.parentNode;
				}
			}
			return ancestor;
		},

		/**
		 * Get the position of the node within the children of its parent
		 * Adapted from FCKeditor
		 *
		 * @param	object		node: the DOM node
		 * @param	boolean		normalized: if true, a normalized position is calculated
		 *
		 * @return	integer		the position of the node
		 */
		getPositionWithinParent: function (node, normalized) {
			var current = node,
				position = 0;
			while (current = current.previousSibling) {
				// For a normalized position, do not count any empty text node or any text node following another one
				if (
					normalized
					&& current.nodeType == Dom.TEXT_NODE
					&& (!current.nodeValue.length || (current.previousSibling && current.previousSibling.nodeType == Dom.TEXT_NODE))
				) {
					continue;
				}
				position++;
			}
			return position;
		},

		/**
		 * Determine whether a given node has any allowed attributes
		 *
		 * @param	object		node: the DOM node
		 * @param	array		allowedAttributes: array of allowed attribute names
		 *
		 * @return	boolean		true if the node has one of the allowed attributes
		 */
		 hasAllowedAttributes: function (node, allowedAttributes) {
			var value,
				hasAllowedAttributes = false;
			if (typeof allowedAttributes === 'string') {
				var allowedAttributes = [allowedAttributes];
			}
			// Is allowedAttributes an array?
			if (allowedAttributes && Object.prototype.toString.call(allowedAttributes) === '[object Array]') {
				for (var i = allowedAttributes.length; --i >= 0;) {
					value = node.getAttribute(allowedAttributes[i]);
					if (value) {
						if (allowedAttributes[i] === 'style') {
							if (node.style.cssText) {
								hasAllowedAttributes = true;
								break;
							}
						} else {
							hasAllowedAttributes = true;
							break;
						}
					}
				}
			}
			return hasAllowedAttributes;
		},

		/**
		 * Remove the given node from its parent
		 *
		 * @param	object		node: the DOM node
		 *
		 * @return	void
		 */
		removeFromParent: function (node) {
			var parent = node.parentNode;
			if (parent) {
				parent.removeChild(node);
			}
		},

		/**
		 * Change the nodeName of an element node
		 *
		 * @param	object		node: the node to convert (must belong to a document)
		 * @param	string		nodeName: the nodeName of the converted node
		 *
		 * @retrun	object		the converted node or the input node
		 */
		convertNode: function (node, nodeName) {
			var convertedNode = node,
				ownerDocument = node.ownerDocument;
			if (ownerDocument && node.nodeType === Dom.ELEMENT_NODE) {
				var convertedNode = ownerDocument.createElement(nodeName),
					parent = node.parentNode;
				while (node.firstChild) {
					convertedNode.appendChild(node.firstChild);
				}
				parent.insertBefore(convertedNode, node);
				parent.removeChild(node);
			}
			return convertedNode;
		},

		/**
		 * Determine whether a given range intersects a given node
		 *
		 * @param	object		range: the range
		 * @param	object		node: the DOM node (must belong to a document)
		 *
		 * @return	boolean		true if the range intersects the node
		 */
		rangeIntersectsNode: function (range, node) {
			var rangeIntersectsNode = false,
				ownerDocument = node.ownerDocument;
			if (ownerDocument) {
				if (UserAgent.isIEBeforeIE9) {
					var nodeRange = ownerDocument.body.createTextRange();
					nodeRange.moveToElementText(node);
					rangeIntersectsNode = (range.compareEndPoints('EndToStart', nodeRange) == -1 && range.compareEndPoints('StartToEnd', nodeRange) == 1) ||
						(range.compareEndPoints('EndToStart', nodeRange) == 1 && range.compareEndPoints('StartToEnd', nodeRange) == -1);
				} else {
					var nodeRange = ownerDocument.createRange();
					try {
						nodeRange.selectNode(node);
					} catch (e) {
						if (UserAgent.isWebKit) {
							nodeRange.setStart(node, 0);
							if (node.nodeType === Dom.TEXT_NODE || node.nodeType === Dom.COMMENT_NODE || node.nodeType === Dom.CDATA_SECTION_NODE) {
								nodeRange.setEnd(node, node.textContent.length);
							} else {
								nodeRange.setEnd(node, node.childNodes.length);
							}
						} else {
							nodeRange.selectNodeContents(node);
						}
					}
					// Note: sometimes WebKit inverts the end points
					rangeIntersectsNode = (range.compareBoundaryPoints(range.END_TO_START, nodeRange) == -1 && range.compareBoundaryPoints(range.START_TO_END, nodeRange) == 1) ||
						(range.compareBoundaryPoints(range.END_TO_START, nodeRange) == 1 && range.compareBoundaryPoints(range.START_TO_END, nodeRange) == -1);
				}
			}
			return rangeIntersectsNode;
		},

		/**
		 * Make url's absolute in the DOM tree under the root node
		 *
		 * @param	object		root: the root node
		 * @param	string		baseUrl: base url to use
		 * @param	string		walker: a HLMLArea.DOM.Walker object
		 * @return	void
		 */
		makeUrlsAbsolute: function (node, baseUrl, walker) {
			walker.walk(node, true, 'args[0].makeImageSourceAbsolute(node, args[2]) || args[0].makeLinkHrefAbsolute(node, args[2])', 'args[1].emptyFunction', [Dom, Util, baseUrl]);
		},

		/**
		 * Make the src attribute of an image node absolute
		 *
		 * @param	object		node: the image node
		 * @param	string		baseUrl: base url to use
		 * @return	void
		 */
		makeImageSourceAbsolute: function (node, baseUrl) {
			if (/^img$/i.test(node.nodeName)) {
				var src = node.getAttribute('src');
				if (src) {
					node.setAttribute('src', Dom.addBaseUrl(src, baseUrl));
				}
				return true;
			}
			return false;
		},

		/**
		 * Make the href attribute of an a node absolute
		 *
		 * @param	object		node: the image node
		 * @param	string		baseUrl: base url to use
		 * @return	void
		 */
		makeLinkHrefAbsolute: function (node, baseUrl) {
			if (/^a$/i.test(node.nodeName)) {
				var href = node.getAttribute('href');
				if (href) {
					node.setAttribute('href', Dom.addBaseUrl(href, baseUrl));
				}
				return true;
			}
			return false;
		},

		/**
		 * Add base url
		 *
		 * @param	string		url: value of a href or src attribute
		 * @param	string		baseUrl: base url to add
		 * @return	string		absolute url
		 */
		addBaseUrl: function (url, baseUrl) {
			var absoluteUrl = url;
				// If the url has no scheme...
			if (!/^[a-z0-9_]{2,}\:/i.test(absoluteUrl)) {
				var base = baseUrl;
				while (absoluteUrl.match(/^\.\.\/(.*)/)) {
						// Remove leading ../ from url
					absoluteUrl = RegExp.$1;
					base.match(/(.*\:\/\/.*\/)[^\/]+\/$/);
						// Remove lowest directory level from base
					base = RegExp.$1;
					absoluteUrl = base + absoluteUrl;
				}
					// If the url is still not absolute...
				if (!/^.*\:\/\//.test(absoluteUrl)) {
					absoluteUrl = baseUrl + absoluteUrl;
				}
			}
			return absoluteUrl;
		},

		/**
		 * Get the position of a node
		 *
		 * @param object node
		 * @return object left and top coordinates
		 */
		getPosition: function (node) {
			var x = 0, y = 0;
			while (node && !isNaN(node.offsetLeft) && !isNaN(node.offsetTop)) {
				x += node.offsetLeft - node.scrollLeft;
				y += node.offsetTop - node.scrollTop;
				node = node.offsetParent;
			}
			return { x: x, y: y };
		},

		/**
		 * Get the current size of a node
		 *
		 * @param object node
		 * @return object width and height
		 */
		getSize: function (node) {
			return {
				width:  Math.max(node.offsetWidth, node.clientWidth) || 0,
				height: Math.max(node.offsetHeight, node.clientHeight) || 0
			}
		},

		/**
		 * Set the size of a node
		 *
		 * @param object node
		 * @param object size: width and height
		 * @return void
		 */
		setSize: function (node, size) {
			if (typeof size.width !== 'undefined') {
				node.style.width = size.width + 'px';
			}
			if (typeof size.height !== 'undefined') {
				node.style.height = size.height + 'px';
			}
		},

		/**
		 * Set the style of a node
		 *
		 * @param object node
		 * @param object style
		 * @return void
		 */
		setStyle: function (node, style) {
			for (var property in style) {
				if (typeof style[property] !== 'undefined') {
					node.style[property] = style[property];
				}
			}
		}
	};

	return Dom;

});
