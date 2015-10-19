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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/Walker
 * HTMLArea.DOM.Walker: DOM tree walk
 ***************************************************/
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM'],
	function (UserAgent, Util, Dom) {

	/**
	 * Constructor method
	 *
	 * @param {Object} config: an object with property "editor" giving reference to the parent object
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/Walker
	 */
	var Walker = function (config) {
		// Configuration defaults
		var configDefaults = {
			keepComments: false,
			keepCDATASections: false,
			removeTags: /none/i,
			removeTagsAndContents: /none/i,
			keepTags: /.*/i,
			removeAttributes: /none/i,
			removeTrailingBR: true,
			baseUrl: ''
		};
		Util.apply(this, config, configDefaults);
	};

	/**
	 * Walk the DOM tree
	 *
	 * @param	object		node: the root node of the tree
	 * @param	boolean		includeNode: if set, apply callback to the node
	 * @param	string		startCallback: a function call to be evaluated on each node, before walking the children
	 * @param	string		endCallback: a function call to be evaluated on each node, after walking the children
	 * @param	array		args: array of arguments
	 * @return	void
	 */
	Walker.prototype.walk = function (node, includeNode, startCallback, endCallback, args) {
		if (!this.removeTagsAndContents.test(node.nodeName)) {
			if (includeNode) {
				eval(startCallback);
			}
				// Walk the children
			var child = node.firstChild;
			while (child) {
				this.walk(child, true, startCallback, endCallback, args);
				child = child.nextSibling;
			}
			if (includeNode) {
				eval(endCallback);
			}
		}
	};

	/**
	 * Generate html string from DOM tree
	 *
	 * @param	object		node: the root node of the tree
	 * @param	boolean		includeNode: if set, apply callback to root element
	 * @return	string		rendered html code
	 */
	Walker.prototype.render = function (node, includeNode) {
		this.html = '';
		this.walk(node, includeNode, 'args[0].renderNodeStart(node)', 'args[0].renderNodeEnd(node)', [this]);
		return this.html;
	};

	/**
	 * Generate html string for the start of a node
	 *
	 * @param	object		node: the root node of the tree
	 * @return	string		rendered html code (accumulated in this.html)
	 */
	Walker.prototype.renderNodeStart = function (node) {
		var html = '';
		switch (node.nodeType) {
			case Dom.ELEMENT_NODE:
				if (this.keepTags.test(node.nodeName) && !this.removeTags.test(node.nodeName)) {
					html += this.setOpeningTag(node);
				}
				break;
			case Dom.TEXT_NODE:
				html += /^(script|style)$/i.test(node.parentNode.nodeName) ? node.data : Util.htmlEncode(node.data);
				break;
			case Dom.ENTITY_NODE:
				html += node.nodeValue;
				break;
			case Dom.ENTITY_REFERENCE_NODE:
				html += '&' + node.nodeValue + ';';
				break;
			case Dom.COMMENT_NODE:
				if (this.keepComments) {
					html += '<!--' + node.data + '-->';
				}
				break;
			case Dom.CDATA_SECTION_NODE:
				if (this.keepCDATASections) {
					html += '<![CDATA[' + node.data + ']]>';
				}
				break;
			default:
					// Ignore all other node types
				break;
		}
		this.html += html;
	};

	/**
	 * Generate html string for the end of a node
	 *
	 * @param	object		node: the root node of the tree
	 * @return	string		rendered html code (accumulated in this.html)
	 */
	Walker.prototype.renderNodeEnd = function (node) {
		var html = '';
		if (node.nodeType === Dom.ELEMENT_NODE) {
			if (this.keepTags.test(node.nodeName) && !this.removeTags.test(node.nodeName)) {
				html += this.setClosingTag(node);
			}
		}
		this.html += html;
	};

	/**
	 * Get the attributes of the node, filtered and cleaned-up
	 *
	 * @param	object		node: the node
	 * @return	object		an object with attribute name as key and attribute value as value
	 */
	Walker.prototype.getAttributes = function (node) {
		var attributes = node.attributes;
		var filterededAttributes = [];
		var attribute, attributeName, attributeValue;
		for (var i = attributes.length; --i >= 0;) {
			attribute = attributes.item(i);
			attributeName = attribute.nodeName.toLowerCase();
			attributeValue = attribute.nodeValue;
				// Ignore some attributes and those configured to be removed
			if (/_moz|contenteditable|complete/.test(attributeName) || this.removeAttributes.test(attributeName)) {
				continue;
			}
				// Ignore default values except for the value attribute
			if (!attribute.specified && attributeName !== 'value') {
				continue;
			}
			if (UserAgent.isIE) {
				// May need to strip the base url
				if (attributeName === 'href' || attributeName === 'src') {
					attributeValue = this.stripBaseURL(attributeValue);
				// Ignore value="0" reported by IE on all li elements
				} else if (attributeName === 'value' && /^li$/i.test(node.nodeName) && attributeValue == 0) {
					continue;
				}
			} else if (UserAgent.isGecko) {
					// Ignore special values reported by Mozilla
				if (/(_moz|^$)/.test(attributeValue)) {
					continue;
					// Pasted internal url's are made relative by Mozilla: https://bugzilla.mozilla.org/show_bug.cgi?id=613517
				} else if (attributeName === 'href' || attributeName === 'src') {
					attributeValue = Dom.addBaseUrl(attributeValue, this.baseUrl);
				}
			}
				// Ignore id attributes generated by ExtJS
			if (attributeName === 'id' && /^ext-gen/.test(attributeValue)) {
				continue;
			}
			filterededAttributes.push({
				attributeName: attributeName,
				attributeValue: attributeValue
			});
		}
		return (UserAgent.isWebKit || UserAgent.isOpera) ? filterededAttributes.reverse() : filterededAttributes;
	};

	/**
	 * Set opening tag for a node
	 *
	 * @param	object		node: the node
	 * @return	object		opening tag
	 */
	Walker.prototype.setOpeningTag = function (node) {
		var html = '';
			// Handle br oddities
		if (/^br$/i.test(node.nodeName)) {
				// Remove Mozilla special br node
			if (UserAgent.isGecko && node.hasAttribute('_moz_editor_bogus_node')) {
				return html;
				// In Gecko, whenever some text is entered in an empty block, a trailing br tag is added by the browser.
				// If the br element is a trailing br in a block element with no other content or with content other than a br, it may be configured to be removed
			} else if (this.removeTrailingBR && !node.nextSibling && Dom.isBlockElement(node.parentNode) && (!node.previousSibling || !/^br$/i.test(node.previousSibling.nodeName))) {
						// If an empty paragraph with a class attribute, insert a non-breaking space so that RTE transform does not clean it away
					if (!node.previousSibling && node.parentNode && /^p$/i.test(node.parentNode.nodeName) && node.parentNode.className) {
						html += "&nbsp;";
					}
				return html;
			}
		}
			// Normal node
		var attributes = this.getAttributes(node);
		for (var i = 0, n = attributes.length; i < n; i++) {
			html +=  ' ' + attributes[i]['attributeName'] + '="' + Util.htmlEncode(attributes[i]['attributeValue']) + '"';
		}
		html = '<' + node.nodeName.toLowerCase() + html + (Dom.RE_noClosingTag.test(node.nodeName) ? ' />' : '>');
			// Fix orphan list elements
		if (/^li$/i.test(node.nodeName) && !/^[ou]l$/i.test(node.parentNode.nodeName)) {
			html = '<ul>' + html;
		}
		return html;
	};

	/**
	 * Set closing tag for a node
	 *
	 * @param	object		node: the node
	 * @return	object		closing tag, if required
	 */
	Walker.prototype.setClosingTag = function (node) {
		var html = Dom.RE_noClosingTag.test(node.nodeName) ? '' : '</' + node.nodeName.toLowerCase() + '>';
			// Fix orphan list elements
		if (/^li$/i.test(node.nodeName) && !/^[ou]l$/i.test(node.parentNode.nodeName)) {
			html += '</ul>';
		}
		return html;
	};

	/**
	 * Strip base url
	 * May be overridden by link handling plugin
	 *
	 * @param	string		value: value of a href or src attribute
	 * @return	tring		stripped value
	 */
	Walker.prototype.stripBaseURL = function (value) {
		return value;
	};

	return Walker;

});
