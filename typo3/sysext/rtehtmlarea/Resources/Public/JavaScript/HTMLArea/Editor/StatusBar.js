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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/StatusBar
 * The optional status bar at the bottom of the editor framework
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event'],
	function (UserAgent, Util, Dom, Event) {

	/**
	 * Status bar constructor
	 *
	 * @param {Object} config
	 * @constructor
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/StatusBar
	 */
	var StatusBar = function (config) {
		Util.apply(this, config);
	};

	StatusBar.prototype = {

		/**
		 * Render the status bar (called by framework rendering)
		 *
		 * @param object container: the container into which to insert the status bar (that is the framework)
		 * @return void
		 */
		render: function (container) {
			this.el = document.createElement('div');
			if (this.id) {
				this.el.setAttribute('id', this.id);
			}
			if (this.cls) {
				this.el.setAttribute('class', this.cls);
			}
			this.el = container.appendChild(this.el);
			this.addComponents();
			this.initEventListeners();
			if (!this.getEditor().config.showStatusBar) {
				this.hide();
			}
			this.rendered = true;
		},

		/**
		 * Initialize listeners (after rendering)
		 */
		initEventListeners: function () {
			var self = this;
			// Monitor editor changing mode
			Event.on(this.getEditor(), 'HTMLAreaEventModeChange', function (event, mode) { Event.stopEvent(event); self.onModeChange(mode); return false; });
			// Monitor word count change
			Event.on(this.framework.iframe, 'HTMLAreaEventWordCountChange', function (event, delay) { Event.stopEvent(event); self.onWordCountChange(delay); return false; });
		},

		/**
		 * Get the element to which the status bar is rendered
		 */
		getEl: function () {
			return this.el;
		},

		/**
		 * Get the current height of the status bar
		 */
		getHeight: function () {
			return Dom.getSize(this.el).height;
		},

		/**
		 * editorId should be set in config
		 */
		editorId: null,

		/**
		 * Get a reference to the editor
		 */
		getEditor: function() {
			return RTEarea[this.editorId].editor;
		},

		/**
		 * Create span elements to display when the status bar tree or a message when the editor is in text mode
		 */
		addComponents: function () {
			// Word count
			var wordCount = document.createElement('span');
			wordCount.id = this.editorId + '-statusBarWordCount';
			wordCount.style.display = 'block';
			wordCount.innerHTML = '&nbsp;';
			Dom.addClass(wordCount, 'statusBarWordCount');
			this.statusBarWordCount = this.getEl().appendChild(wordCount);
			// Element tree
			var tree = document.createElement('span');
			tree.id = this.editorId + '-statusBarTree';
			tree.style.display = 'block';
			tree.innerHTML = HTMLArea.localize('Path') + ': ';
			Dom.addClass(tree, 'statusBarTree');
			this.statusBarTree = this.getEl().appendChild(tree);
			// Text mode
			var textMode = document.createElement('span');
			textMode.id = this.editorId + '-statusBarTextMode';
			textMode.style.display = 'none';
			textMode.innerHTML = HTMLArea.localize('TEXT_MODE');
			Dom.addClass(textMode, 'statusBarTextMode');
			this.statusBarTextMode = this.getEl().appendChild(textMode);
		},

		/**
		 * Show the status bar
		 */
		show: function () {
			this.getEl().style.display = '';
		},

		/**
		 * Hide the status bar
		 */
		hide: function () {
			this.getEl().style.display = 'none';
		},

		/**
		 * Clear the status bar tree
		 */
		clear: function () {
			var node;
			while (node = this.statusBarTree.firstChild) {
				if (/^(a)$/i.test(node.nodeName)) {
					Event.off(node);
				}
				Dom.removeFromParent(node);
			}
			this.setSelection(null);
		},

		/**
		 * Flag indicating that the status bar should not be updated on this toolbar update
		 */
		noUpdate: false,

		/**
		 * Update the status bar when the toolbar was updated
		 *
		 * @return void
		 */
		onUpdateToolbar: function (mode, selectionEmpty, ancestors, endPointsInSameBlock) {
			if (mode === 'wysiwyg' && !this.noUpdate && this.getEditor().config.showStatusBar) {
				var self = this;
				var text,
					language,
					languageObject = this.getEditor().getPlugin('Language'),
					classes = new Array(),
					classText;
				this.clear();
				var path = document.createElement('span');
				path.innerHTML = HTMLArea.localize('Path') + ': ';
				path = this.statusBarTree.appendChild(path);
				var index, n, j, m;
				for (index = 0, n = ancestors.length; index < n; index++) {
					var ancestor = ancestors[index];
					if (!ancestor) {
						continue;
					}
					text = ancestor.nodeName.toLowerCase();
					// Do not show any id generated by ExtJS
					if (ancestor.id && text !== 'body' && ancestor.id.substr(0, 7) !== 'ext-gen') {
						text += '#' + ancestor.id;
					}
					if (languageObject && languageObject.getLanguageAttribute) {
						language = languageObject.getLanguageAttribute(ancestor);
						if (language != 'none') {
							text += '[' + language + ']';
						}
					}
					if (ancestor.className) {
						classText = '';
						classes = ancestor.className.trim().split(' ');
						for (j = 0, m = classes.length; j < m; ++j) {
							if (!HTMLArea.reservedClassNames.test(classes[j])) {
								classText += '.' + classes[j];
							}
						}
						text += classText;
					}
					var element = document.createElement('a');
					element.href = '#';
					if (ancestor.style.cssText) {
						element.setAttribute('title', HTMLArea.localize('statusBarStyle') + ':\x0D ' + ancestor.style.cssText.split(';').join('\x0D'));
					}
					element.innerHTML = text;
					element = path.parentNode.insertBefore(element, path.nextSibling);
					element.ancestor = ancestor;
					Event.on(element, 'click', function (event) { return self.onClick(event); });
					Event.on(element, 'mousedown', function (event) { return self.onClick(event); });
					if (!UserAgent.isOpera) {
						Event.on(element, 'contextmenu', function (event) { return self.onContextMenu(event); });
					}
					if (index) {
						var separator = document.createElement('span');
						separator.innerHTML = String.fromCharCode(0xbb);
						element.parentNode.insertBefore(separator, element.nextSibling);
					}
				}
			}
			this.updateWordCount();
			this.noUpdate = false;
		},

		/**
		 * Handler when the word count may have changed
		 *
		 * @param integer delay: the delay before updating the word count
		 * @return void
		 */
		onWordCountChange: function (delay) {
			if (this.updateWordCountLater) {
				window.clearTimeout(this.updateWordCountLater);
			}
			if (delay) {
				var self = this;
				this.updateWordCountLater = window.setTimeout(function () {
					self.updateWordCount();
				}, delay);
			} else {
				this.updateWordCount();
			}
		},

		/**
		 * Update the word count
		 */
		updateWordCount: function() {
			var wordCount = 0;
			if (this.getEditor().getMode() == 'wysiwyg') {
				// Get the html content
				var text = this.getEditor().getHTML();
				if (typeof text === 'string' && text.length > 0) {
					// Replace html tags with spaces
					text = text.replace(HTMLArea.RE_htmlTag, ' ');
					// Replace html space entities
					text = text.replace(/&nbsp;|&#160;/gi, ' ');
					// Remove numbers and punctuation
					text = text.replace(HTMLArea.RE_numberOrPunctuation, '');
					// Get the number of word
					wordCount = text.split(/\S\s+/g).length - 1;
				}
			}
			// Update the word count of the status bar
			this.statusBarWordCount.innerHTML = wordCount ? ( wordCount + ' ' + HTMLArea.localize((wordCount == 1) ? 'word' : 'words')) : '&nbsp;';
		},

		/**
		 * Adapt status bar to current editor mode
		 *
		 * @param string mode: the mode to which the editor got switched to
		 * @return void
		 */
		onModeChange: function (mode) {
			switch (mode) {
				case 'wysiwyg':
					this.statusBarTextMode.style.display = 'none';
					this.statusBarTree.style.display = 'block';
					break;
				case 'textmode':
				default:
					this.statusBarTree.style.display = 'none';
					this.statusBarTextMode.style.display = 'block';
					break;
			}
		},

		/**
		 * Reference to the element last selected on the status bar
		 */
		selected: null,

		/**
		 * Get the status bar selection
		 */
		getSelection: function() {
			return this.selected;
		},

		/**
		 * Set the status bar selection
		 *
		 * @param	object	element: set the status bar selection to the given element
		 */
		setSelection: function (element) {
			this.selected = element ? element : null;
		},

		/**
		 * Select the element that was clicked in the status bar and set the status bar selection
		 */
		selectElement: function (element) {
			var editor = this.getEditor();
			element.blur();
			if (!UserAgent.isIEBeforeIE9) {
				if (/^(img|table)$/i.test(element.ancestor.nodeName)) {
					editor.getSelection().selectNode(element.ancestor);
				} else {
					editor.getSelection().selectNodeContents(element.ancestor);
				}
			} else {
				if (/^(img|table)$/i.test(element.ancestor.nodeName)) {
					var range = editor.document.body.createControlRange();
					range.addElement(element.ancestor);
					range.select();
				} else {
					editor.getSelection().selectNode(element.ancestor);
				}
			}
			this.setSelection(element.ancestor);
			this.noUpdate = true;
			editor.toolbar.update();
		},

		/**
		 * Click handler
		 */
		onClick: function (event) {
			this.selectElement(event.target);
			Event.stopEvent(event);
			return false;
		},

		/**
		 * ContextMenu handler
		 */
		onContextMenu: function (event) {
			this.selectElement(event.target);
			return this.getEditor().getPlugin('ContextMenu') ? this.getEditor().getPlugin('ContextMenu').show(event, event.target.ancestor) : false;
		},

		/**
		 * Cleanup (called by framework)
		 */
		onBeforeDestroy: function() {
			this.clear();
			var node;
			while (node = this.el.firstChild) {
				this.el.removeChild(node);
			}
			this.statusBarTree = null;
			this.statusBarWordCount = null;
			this.el = null;
		}
	};

	return StatusBar;

});
