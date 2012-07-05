/***************************************************************
*  Copyright notice
*
*  (c) 2008-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Default Clean Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.DefaultClean = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function(editor) {
		this.pageTSConfiguration = this.editorConfiguration.buttons.cleanword;
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '2.2',
			developer	: 'Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Stanislas Rolland',
			sponsor		: 'SJBR',
			sponsorUrl	: 'http://www.sjbr.ca/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the (hidden) button
		 */
		var buttonId = 'CleanWord';
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize(buttonId + '-Tooltip'),
			action		: 'onButtonPress',
			hide		: true,
			hideInContextMenu: true
		};
		this.registerButton(buttonConfiguration);
	},
	/*
	 * This function gets called when the button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress: function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		this.clean();
		return false;
	},
	/*
	 * This function gets called when the editor is generated
	 */
	onGenerate: function () {
		this.editor.iframe.mon(Ext.get(Ext.isIE ? this.editor.document.body : this.editor.document.documentElement), 'paste', this.wordCleanHandler, this);
	},
	/*
	 * This function cleans all nodes in the node tree below the input node
	 *
	 * @param	object	node: the root of the node tree to clean
	 *
	 * @return 	void
	 */
	clean: function () {
		function clearClass(node) {
			var newc = node.className.replace(/(^|\s)mso.*?(\s|$)/ig,' ');
			if (newc != node.className) {
				node.className = newc;
				if (!/\S/.test(node.className)) {
					if (!Ext.isOpera) {
						node.removeAttribute('class');
						if (HTMLArea.isIEBeforeIE9) {
							node.removeAttribute('className');
						}
					} else {
						node.className = '';
					}
				}
			}
		}
		function clearStyle(node) {
			var style = HTMLArea.isIEBeforeIE9 ? node.style.cssText : node.getAttribute('style');
			if (style) {
				var declarations = style.split(/\s*;\s*/);
				for (var i = declarations.length; --i >= 0;) {
					if (/^mso|^tab-stops/i.test(declarations[i]) || /^margin\s*:\s*0..\s+0..\s+0../i.test(declarations[i])) {
						declarations.splice(i, 1);
					}
				}
				node.setAttribute('style', declarations.join('; '));
			}
		}
		function stripTag(el) {
			if (HTMLArea.isIEBeforeIE9) {
				el.outerHTML = HTMLArea.util.htmlEncode(el.innerText);
			} else {
				var txt = document.createTextNode(HTMLArea.DOM.getInnerText(el));
				el.parentNode.insertBefore(txt,el);
				el.parentNode.removeChild(el);
			}
		}
		function checkEmpty(el) {
			if (/^(span|b|strong|i|em|font)$/i.test(el.nodeName) && !el.firstChild) {
				el.parentNode.removeChild(el);
			}
		}
		function parseTree(root) {
			var tag = root.nodeName.toLowerCase(), next;
			switch (root.nodeType) {
				case HTMLArea.DOM.ELEMENT_NODE:
					if (/^(meta|style|title|link)$/.test(tag)) {
						root.parentNode.removeChild(root);
						return false;
						break;
					}
				case HTMLArea.DOM.TEXT_NODE:
				case HTMLArea.DOM.DOCUMENT_NODE:
				case HTMLArea.DOM.DOCUMENT_FRAGMENT_NODE:
					if ((HTMLArea.isIEBeforeIE9 && root.scopeName != 'HTML') || (!HTMLArea.isIEBeforeIE9 && /:/.test(tag)) || /o:p/.test(tag)) {
						stripTag(root);
						return false;
					} else {
						clearClass(root);
						clearStyle(root);
						for (var i = root.firstChild; i; i = next) {
							next = i.nextSibling;
							if (i.nodeType !== HTMLArea.DOM.TEXT_NODE && parseTree(i)) {
								checkEmpty(i);
							}
						}
					}
					return true;
					break;
				default:
					root.parentNode.removeChild(root);
					return false;
					break;
			}
		}
		parseTree(this.editor.document.body);
		if (Ext.isWebKit) {
			this.editor.getDomNode().cleanAppleStyleSpans(this.editor.document.body);
		}
	},
	/*
	 * Handler for paste, dragdrop and drop events
	 */
	wordCleanHandler: function (event) {
		this.clean.defer(250, this);
	}
});
