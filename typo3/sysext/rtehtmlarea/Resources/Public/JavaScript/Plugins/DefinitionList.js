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
 * DefinitionList Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/Plugins/BlockElements',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM'],
	function (Plugin, BlockElements, UserAgent, Util, Dom) {

	var DefinitionList = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(DefinitionList, Plugin);
	Util.apply(DefinitionList.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {

			/**
			 * Setting up some properties from PageTSConfig
			 */
			this.buttonsConfiguration = this.editorConfiguration.buttons;
			this.parentPlugin = this.getPluginInstance('BlockElements');
			var parentPlugin = this.parentPlugin;
			this.tags = parentPlugin.tags;
			this.useClass = parentPlugin.useClass;
			this.useBlockquote = parentPlugin.useBlockquote;
			this.useAlignAttribute = parentPlugin.useAlignAttribute;
			this.allowedBlockElements = parentPlugin.allowedBlockElements;
			this.indentedList = null;
			this.standardBlockElements = parentPlugin.standardBlockElements;
			this.formatBlockItems = parentPlugin.formatBlockItems;

			/**
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

			/**
			 * Registering the buttons
			 */
			var button, buttonId;
			for (var i = 0, n = this.buttonList.length; i < n; i++) {
				button = this.buttonList[i];
				buttonId = button[0];
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
			}
			return true;
		},

		/**
		 * The list of buttons added by this plugin
		 */
		buttonList: [
			['Indent', null, 'TAB', 'indent', false, 'indent'],
			['Outdent', null, 'SHIFT-TAB', 'outdent', false, 'outdent'],
			['DefinitionList', null, null, 'definitionlist', true, 'definition-list'],
			['DefinitionItem', 'dd,dt', null, 'definitionitem', false, 'definition-list-item']
		],

		/**
		 * This function gets called when the plugin is generated
		 * Avoid re-execution of the base function
		 */
		onGenerate: Util.emptyFunction,

		/**
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
			while (parentElement && (!Dom.isBlockElement(parentElement) || /^(li)$/i.test(parentElement.nodeName))) {
				parentElement = parentElement.parentNode;
			}
			switch (buttonId) {
				case 'Indent' :
					if (/^(dd|dt)$/i.test(parentElement.nodeName) && this.indentDefinitionList(parentElement)) {
						break;
					} else {
						this.parentPlugin.onButtonPress(editor, id, target, className);
					}
					break;
				case 'Outdent' :
					if (/^(dt)$/i.test(parentElement.nodeName) && this.outdentDefinitionList()) {
						break;
					} else {
						this.parentPlugin.onButtonPress(editor, id, target, className);
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
					this.parentPlugin.onButtonPress(editor, id, target, className);
			}
			return false;
		},

		/**
		 * This function remaps a node to the specified node name
		 */
		remapNode: function (node, nodeName) {
			var newNode = Dom.convertNode(node, nodeName);
			var attributes = node.attributes, attributeName, attributeValue;
			for (var i = attributes.length; --i >= 0;) {
				attributeName = attributes.item(i).nodeName;
				attributeValue = node.getAttribute(attributeName);
				if (attributeValue) newNode.setAttribute(attributeName, attributeValue);
			}
			if (this.tags && this.tags[nodeName] && this.tags[nodeName].allowedClasses) {
				if (newNode.className && /\S/.test(newNode.className)) {
					var allowedClasses = this.tags[nodeName].allowedClasses;
					var classNames = newNode.className.trim().split(' ');
					for (var i = classNames.length; --i >= 0;) {
						if (!allowedClasses.test(classNames[i])) {
							Dom.removeClass(newNode, classNames[i]);
						}
					}
				}
			}
			return newNode;
		},

		/**
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
				list = this.parentPlugin.wrapSelectionInBlockElement('dl');
				var items = list.childNodes;
				for (var i = 0, n = items.length; i < n; ++i) {
					var paragraphItem = items[i].nodeName.toLowerCase() === 'p';
					this.remapNode(items[i],  paragraphs ? ((i % 2) ? 'dd' : 'dt') : (paragraphItem ? 'dd' : 'dt'));
				}
			}
			return list;
		},

		/**
		 * Indent a definition list
		 */
		indentDefinitionList: function (parentElement) {
			var range = this.editor.getSelection().createRange();
			var endBlocks = this.editor.getSelection().getEndBlocks();
			if (this.editor.getSelection().isEmpty() && /^dd$/i.test(parentElement.nodeName)) {
				var list = parentElement.appendChild(this.editor.document.createElement('dl'));
				var term = list.appendChild(this.editor.document.createElement('dt'));
				if (UserAgent.isWebKit) {
					term.innerHTML = '<br />';
				} else {
					term.appendChild(this.editor.document.createTextNode(''));
				}
				this.editor.getSelection().selectNodeContents(term, false);
				return true;
			} else if (endBlocks.start && /^dt$/i.test(endBlocks.start.nodeName) && endBlocks.start.previousSibling) {
				var sibling = endBlocks.start.previousSibling;
				var bookmark = this.editor.getBookMark().get(range);
				if (/^dd$/i.test(sibling.nodeName)) {
					var list = this.parentPlugin.wrapSelectionInBlockElement('dl');
					list = sibling.appendChild(list);
					// May need to merge the list if it has a previous sibling
					if (list.previousSibling && /^dl$/i.test(list.previousSibling.nodeName)) {
						while (list.firstChild) {
							list.previousSibling.appendChild(list.firstChild);
						}
						Dom.removeFromParent(list);
					}
				} else if (/^dt$/i.test(sibling.nodeName)) {
					var definition = this.editor.document.createElement('dd');
					definition.appendChild(this.parentPlugin.wrapSelectionInBlockElement('dl'));
					sibling.parentNode.insertBefore(definition, sibling.nextSibling);
				}
				this.editor.getSelection().selectRange(this.editor.getBookMark().moveTo(bookmark));
				return true;
			}
			return false;
		},

		/**
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
					var selected = this.parentPlugin.wrapSelectionInBlockElement('dl');
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

		/**
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			var editor = this.editor;
			if (mode === 'wysiwyg' && this.editor.isEditable()) {
				var statusBarSelection = editor.statusBar ? editor.statusBar.getSelection() : null;
				var parentElement = statusBarSelection ? statusBarSelection : editor.getSelection().getParentElement();
				if (!/^(html|body)$/i.test(parentElement.nodeName)) {
					var endBlocks = editor.getSelection().getEndBlocks();
					switch (button.itemId) {
						case 'Outdent':
							if (/^(dt)$/i.test(endBlocks.start.nodeName)
									&& /^(dl)$/i.test(endBlocks.start.parentNode.nodeName)
									&& /^(dd)$/i.test(endBlocks.start.parentNode.parentNode.nodeName)
									&& !endBlocks.end.nextSibling) {
								button.setDisabled(false);
							} else {
								this.parentPlugin.onUpdateToolbar(button, mode, selectionEmpty, ancestors);
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
							this.parentPlugin.onUpdateToolbar(button, mode, selectionEmpty, ancestors);
							break;
					}
				}
			}
		}
	});

	return DefinitionList;

});
