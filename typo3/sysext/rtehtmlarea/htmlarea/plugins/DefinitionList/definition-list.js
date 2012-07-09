/***************************************************************
*  Copyright notice
*
*  (c) 2008-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * DefinitionList Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.DefinitionList = Ext.extend(HTMLArea.BlockElements, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		/*
		 * Setting up some properties from PageTSConfig
		 */
		this.buttonsConfiguration = this.editorConfiguration.buttons;
		var parentPlugin = this.getPluginInstance('BlockElements');
		this.tags = parentPlugin.tags;
		this.useClass = parentPlugin.useClass;
		this.useBlockquote = parentPlugin.useBlockquote;
		this.useAlignAttribute = parentPlugin.useAlignAttribute;
		this.allowedBlockElements = parentPlugin.allowedBlockElements;
		this.indentedList = null;
		this.standardBlockElements = parentPlugin.standardBlockElements;
		this.formatBlockItems = parentPlugin.formatBlockItems;
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '2.0',
			developer	: 'Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Stanislas Rolland',
			sponsor		: this.localize('Technische Universitat Ilmenau'),
			sponsorUrl	: 'http://www.tu-ilmenau.de/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the buttons
		 */
		Ext.each(this.buttonList, function (button) {
			var buttonId = button[0];
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + '-Tooltip'),
				contextMenuTitle: this.localize(buttonId + '-contextMenuTitle'),
				helpText	: this.localize(buttonId + '-helpText'),
				iconCls		: 'htmlarea-action-' + button[5],
				action		: 'onButtonPress',
				context		: button[1],
				hotKey		: ((this.buttonsConfiguration[button[3]] && this.buttonsConfiguration[button[3]].hotKey) ? this.buttonsConfiguration[button[3]].hotKey : (button[2] ? button[2] : null)),
				noAutoUpdate	: button[4]
			};
			this.registerButton(buttonConfiguration);
		}, this);
		return true;
	},
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList: [
		['Indent', null, 'TAB', 'indent', false, 'indent'],
		['Outdent', null, 'SHIFT-TAB', 'outdent', false, 'outdent'],
		['DefinitionList', null, null, 'definitionlist', true, 'definition-list'],
		['DefinitionItem', 'dd,dt', null, 'definitionitem', false, 'definition-list-item']
	 ],
	/*
	 * This function gets called when the plugin is generated
	 * Avoid re-execution of the base function
	 */
	onGenerate: Ext.emptyFn,
	/*
	 * This function gets called when a button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 * @param	object		target: the target element of the contextmenu event, when invoked from the context menu
	 * @param	string		className: the className to be assigned to the element
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress: function (editor, id, target, className) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		var range = this.editor.getSelection().createRange();
		var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
		var parentElement = statusBarSelection ? statusBarSelection : this.editor.getSelection().getParentElement();
		if (target) {
			parentElement = target;
		}
		while (parentElement && (!HTMLArea.DOM.isBlockElement(parentElement) || /^(li)$/i.test(parentElement.nodeName))) {
			parentElement = parentElement.parentNode;
		}
		switch (buttonId) {
			case 'Indent' :
				if (/^(dd|dt)$/i.test(parentElement.nodeName) && this.indentDefinitionList(parentElement)) {
					break;
				} else {
					HTMLArea.DefinitionList.superclass.onButtonPress.call(this, editor, id, target, className);
				}
				break;
			case 'Outdent' :
				if (/^(dt)$/i.test(parentElement.nodeName) && this.outdentDefinitionList()) {
					break;
				} else {
					HTMLArea.DefinitionList.superclass.onButtonPress.call(this, editor, id, target, className);
				}
				break;
			case 'DefinitionList':
				var bookmark = this.editor.getBookMark().get(range);
				this.insertDefinitionList();
				this.editor.getSelection().selectRange(this.editor.getBookMark().moveTo(bookmark));
				break;
			case 'DefinitionItem':
				var bookmark = this.editor.getBookMark().get(range);
				this.remapNode(parentElement, (parentElement.nodeName.toLowerCase() === 'dt') ? 'dd' : 'dt');
				this.editor.getSelection().selectRange(this.editor.getBookMark().moveTo(bookmark));
				break;
			default:
				HTMLArea.DefinitionList.superclass.onButtonPress.call(this, editor, id, target, className);
		}
		return false;
	},
	/*
	 * This function remaps a node to the specified node name
	 */
	remapNode: function (node, nodeName) {
		var newNode = HTMLArea.DOM.convertNode(node, nodeName);
		var attributes = node.attributes, attributeName, attributeValue;
		for (var i = attributes.length; --i >= 0;) {
			attributeName = attributes.item(i).nodeName;
			attributeValue = node.getAttribute(attributeName);
			if (attributeValue) newNode.setAttribute(attributeName, attributeValue);
		}
			// In IE, the above fails to update the classname and style attributes.
		if (HTMLArea.isIEBeforeIE9) {
			if (node.style.cssText) {
				newNode.style.cssText = node.style.cssText;
			}
			if (node.className) {
				newNode.setAttribute('class', node.className);
				if (!newNode.className) {
						// IE before IE8
					newNode.setAttribute('className', node.className);
				}
			} else {
				newNode.removeAttribute('class');
				newNode.removeAttribute('className');
			}
		}

		if (this.tags && this.tags[nodeName] && this.tags[nodeName].allowedClasses) {
			if (newNode.className && /\S/.test(newNode.className)) {
				var allowedClasses = this.tags[nodeName].allowedClasses;
				var classNames = newNode.className.trim().split(' ');
				for (var i = classNames.length; --i >= 0;) {
					if (!allowedClasses.test(classNames[i])) {
						HTMLArea.DOM.removeClass(newNode, classNames[i]);
					}
				}
			}
		}
		return newNode;
	},
	/*
	 * Insert a definition list
	 */
	insertDefinitionList: function () {
		var endBlocks = this.editor.getSelection().getEndBlocks();
		var list = null;
		if (this.editor.getSelection().isEmpty()) {
			if (/^(body|div|address|pre|blockquote|li|td|dd)$/i.test(endBlocks.start.nodeName)) {
				list = this.editor.document.createElement('dl');
				var term = list.appendChild(this.editor.document.createElement('dt'));
				while (endBlocks.start.firstChild) {
					term.appendChild(endBlocks.start.firstChild);
				}
				list = endBlocks.start.appendChild(list);
			} else if (/^(p|h[1-6])$/i.test(endBlocks.start.nodeName)) {
				var list = endBlocks.start.parentNode.insertBefore(this.editor.document.createElement('dl'), endBlocks.start);
				endBlocks.start = list.appendChild(endBlocks.start);
				endBlocks.start = this.remapNode(endBlocks.start, 'dt');
			}
		} else if (endBlocks.start != endBlocks.end && /^(p|h[1-6])$/i.test(endBlocks.start.nodeName)) {
				// We wrap the selected elements in a dl element
			var paragraphs = endBlocks.start.nodeName.toLowerCase() === 'p';
			list = this.wrapSelectionInBlockElement('dl');
			var items = list.childNodes;
			for (var i = 0, n = items.length; i < n; ++i) {
				var paragraphItem = items[i].nodeName.toLowerCase() === 'p';
				this.remapNode(items[i],  paragraphs ? ((i % 2) ? 'dd' : 'dt') : (paragraphItem ? 'dd' : 'dt'));
			}
		}
		return list;
	},
	/*
	 * Indent a definition list
	 */
	indentDefinitionList: function (parentElement) {
		var range = this.editor.getSelection().createRange();
		var endBlocks = this.editor.getSelection().getEndBlocks();
		if (this.editor.getSelection().isEmpty() && /^dd$/i.test(parentElement.nodeName)) {
			var list = parentElement.appendChild(this.editor.document.createElement('dl'));
			var term = list.appendChild(this.editor.document.createElement('dt'));
			if (!HTMLArea.isIEBeforeIE9) {
				if (Ext.isWebKit) {
					term.innerHTML = '<br />';
				} else {
					term.appendChild(this.editor.document.createTextNode(''));
				}
			} else {
				term.innerHTML = '\x20';
			}
			this.editor.getSelection().selectNodeContents(term, false);
			return true;
		} else if (endBlocks.start && /^dt$/i.test(endBlocks.start.nodeName) && endBlocks.start.previousSibling) {
			var sibling = endBlocks.start.previousSibling;
			var bookmark = this.editor.getBookMark().get(range);
			if (/^dd$/i.test(sibling.nodeName)) {
				var list = this.wrapSelectionInBlockElement('dl');
				list = sibling.appendChild(list);
					// May need to merge the list if it has a previous sibling
				if (list.previousSibling && /^dl$/i.test(list.previousSibling.nodeName)) {
					while (list.firstChild) {
						list.previousSibling.appendChild(list.firstChild);
					}
					HTMLArea.DOM.removeFromParent(list);
				}
			} else if (/^dt$/i.test(sibling.nodeName)) {
				var definition = this.editor.document.createElement('dd');
				definition.appendChild(this.wrapSelectionInBlockElement('dl'));
				sibling.parentNode.insertBefore(definition, sibling.nextSibling);
			}
			this.editor.getSelection().selectRange(this.editor.getBookMark().moveTo(bookmark));
			return true;
		}
		return false;
	},
	/*
	 * Outdent a definition list
	 */
	outdentDefinitionList: function () {
		var endBlocks = this.editor.getSelection().getEndBlocks();
		if (/^dt$/i.test(endBlocks.start.nodeName)
				&& /^dl$/i.test(endBlocks.start.parentNode.nodeName)
				&& /^dd$/i.test(endBlocks.start.parentNode.parentNode.nodeName)
				&& !endBlocks.end.nextSibling) {
			var bookmark = this.editor.getBookMark().get(this.editor.getSelection().createRange());
			var dl = endBlocks.start.parentNode;
			var dd = dl.parentNode;
			if (this.editor.getSelection().isEmpty()) {
				dd.parentNode.insertBefore(endBlocks.start, dd.nextSibling);
			} else {
				var selected = this.wrapSelectionInBlockElement('dl');
				while (selected.lastChild) {
					dd.parentNode.insertBefore(selected.lastChild, dd.nextSibling);
				}
				selected.parentNode.removeChild(selected);
			}
				// We may have outdented all the child nodes of a list
			if (!dl.hasChildNodes()) {
				dd.removeChild(dl);
				if (!dd.hasChildNodes()) {
					dd.parentNode.removeChild(dd);
				}
			}
			this.editor.getSelection().selectRange(this.editor.getBookMark().moveTo(bookmark));
			return true;
		}
		return false;
	},

	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
		var editor = this.editor;
		if (mode === 'wysiwyg' && this.editor.isEditable()) {
			var statusBarSelection = editor.statusBar ? editor.statusBar.getSelection() : null;
			var parentElement = statusBarSelection ? statusBarSelection : editor.getSelection().getParentElement();
			if (!/^(body)$/i.test(parentElement.nodeName)) {
				var endBlocks = editor.getSelection().getEndBlocks();
				switch (button.itemId) {
					case 'Outdent':
						if (/^(dt)$/i.test(endBlocks.start.nodeName)
								&& /^(dl)$/i.test(endBlocks.start.parentNode.nodeName)
								&& /^(dd)$/i.test(endBlocks.start.parentNode.parentNode.nodeName)
								&& !endBlocks.end.nextSibling) {
							button.setDisabled(false);
						} else {
							HTMLArea.DefinitionList.superclass.onUpdateToolbar.call(this, button, mode, selectionEmpty, ancestors);
						}
						break;
					case 'DefinitionList':
						button.setDisabled(!(selectionEmpty && /^(p|div|address|pre|blockquote|h[1-6]|li|td|dd)$/i.test(endBlocks.start.nodeName))
													&& !(endBlocks.start != endBlocks.end && /^(p|h[1-6])$/i.test(endBlocks.start.nodeName)));
						break;
				}
			} else {
				switch (button.itemId) {
					case 'Outdent':
						HTMLArea.DefinitionList.superclass.onUpdateToolbar.call(this, button, mode, selectionEmpty, ancestors);
						break;
				}
			}
		}
	}
});
