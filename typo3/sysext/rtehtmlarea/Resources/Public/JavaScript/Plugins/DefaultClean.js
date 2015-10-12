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
 * Default Clean Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event'],
	function (Plugin, UserAgent, Util, Dom, Event) {

	var DefaultClean = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(DefaultClean, Plugin);
	Util.apply(DefaultClean.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {

			this.pageTSConfiguration = this.editorConfiguration.buttons.cleanword;

			/**
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

		/**
		 * This function gets called when the button was pressed.
		 *
		 * @param object editor: the editor instance
		 * @param string id: the button id or the key
		 * @return boolean false if action is completed
		 */
		onButtonPress: function (editor, id, target) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			this.clean();
			return false;
		},

		/**
		 * This function gets called when the editor is generated
		 */
		onGenerate: function () {
			var self = this;
			Event.on(UserAgent.isIE ? this.editor.document.body : this.editor.document.documentElement, 'paste', function (event) { return self.wordCleanHandler(event); });
		},

		/**
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
						if (!UserAgent.isOpera) {
							node.removeAttribute('class');
						} else {
							node.className = '';
						}
					}
				}
			}
			function clearStyle(node) {
				var style = node.getAttribute('style');
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
				var txt = document.createTextNode(Dom.getInnerText(el));
				el.parentNode.insertBefore(txt,el);
				el.parentNode.removeChild(el);
			}
			function checkEmpty(el) {
				if (/^(span|b|strong|i|em|font)$/i.test(el.nodeName) && !el.firstChild) {
					el.parentNode.removeChild(el);
				}
			}
			function parseTree(root) {
				var tag = root.nodeName.toLowerCase(), next;
				switch (root.nodeType) {
					case Dom.ELEMENT_NODE:
						if (/^(meta|style|title|link)$/.test(tag)) {
							root.parentNode.removeChild(root);
							return false;
							break;
						}
					case Dom.TEXT_NODE:
					case Dom.DOCUMENT_NODE:
					case Dom.DOCUMENT_FRAGMENT_NODE:
						if (/:/.test(tag)) {
							stripTag(root);
							return false;
						} else {
							clearClass(root);
							clearStyle(root);
							for (var i = root.firstChild; i; i = next) {
								next = i.nextSibling;
								if (i.nodeType !== Dom.TEXT_NODE && parseTree(i)) {
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
			if (UserAgent.isWebKit) {
				this.editor.getDomNode().cleanAppleStyleSpans(this.editor.document.body);
			}
		},

		/**
		 * Handler for paste, dragdrop and drop events
		 */
		wordCleanHandler: function (event) {
			var self = this;
			window.setTimeout(function () {
				self.clean();
			}, 250);
			return true;
		}
	});

	return DefaultClean;

});
