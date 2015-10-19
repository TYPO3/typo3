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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/Iframe
 * The editor iframe
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/Walker',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/TYPO3',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/KeyMap'],
	function (UserAgent, Walker, Typo3, Util, Dom, Event, KeyMap) {

	/**
	 * Editor iframe constructor
	 *
	 * @param {Object} config
	 * @constructor
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/Iframe
	 */
	var Iframe = function (config) {
		Util.apply(this, config);
	};

	Iframe.prototype = {

		/**
		 * Render the iframe (called by framework rendering)
		 *
		 * @param object container: the container into which to insert the iframe (that is the framework)
		 * @return void
		 */
		render: function (container) {
			this.config = this.getEditor().config;
			this.createIframe(container);
			if (!this.config.showStatusBar) {
				Dom.addClass(this.getEl(), 'noStatusBar');
			}
			this.initStyleChangeEventListener();
			if (UserAgent.isOpera) {
				var self = this;
				Event.one(this.getEl(), 'load', function (event) { self.initializeIframe(); return true; })
			} else {
				this.initializeIframe();
			}
		},

		/**
		 * Get the element to which the iframe is rendered
		 */
		getEl: function () {
			return this.el;
		},

		/**
		 * The editor iframe may become hidden with style.display = "none" on some parent div
		 * This breaks the editor in Firefox: the designMode attribute needs to be reset after the style.display of the container div is reset to "block"
		 * In all browsers, it breaks the evaluation of the framework dimensions
		 */
		initStyleChangeEventListener: function () {
			if (this.isNested) {
				if (typeof MutationObserver === 'function') {
					var self = this;
					this.mutationObserver = new MutationObserver( function (mutations) { self.onNestedShowMutation(mutations); });
					var options = {
						attributes: true,
						attributeFilter: ['class', 'style']
					};
					for (var i = this.nestedParentElements.sorted.length; --i >= 0;) {
						var nestedElement = document.getElementById(this.nestedParentElements.sorted[i]);
						this.mutationObserver.observe(nestedElement, options);
						this.mutationObserver.observe(nestedElement.parentNode, options);
					}
				} else {
					this.initMutationEventsListeners();
				}
			}
		},

		/**
		 * When Mutation Observer is not available, listen to DOMAttrModified events
		 */
		initMutationEventsListeners: function () {
			var self = this;
			var options = {
				delay: 50
			};
			for (var i = this.nestedParentElements.sorted.length; --i >= 0;) {
				var nestedElement = document.getElementById(this.nestedParentElements.sorted[i]);
				Event.on(
					nestedElement,
					'DOMAttrModified',
					function (event) { return self.onNestedShow(event); },
					options
				);
				Event.on(
					nestedElement.parentNode,
					'DOMAttrModified',
					function (event) { return self.onNestedShow(event); },
					options
				);
			}
		},

		/**
		 * editorId should be set in config
		 */
		editorId: null,

		/**
		 * Get a reference to the editor
		 */
		getEditor: function () {
			return RTEarea[this.editorId].editor;
		},

		/**
		 * Get a reference to the toolbar
		 */
		getToolbar: function () {
			return this.framework.toolbar;
		},

		/**
		 * Get a reference to the statusBar
		 */
		getStatusBar: function () {
			return this.framework.statusBar;
		},

		/**
		 * Get a reference to a button
		 */
		getButton: function (buttonId) {
			return this.getToolbar().getButton(buttonId);
		},

		/**
		 * Flag set to true when the iframe becomes usable for editing
		 */
		ready: false,

		/**
		 * Create the iframe element at rendering time
		 *
		 * @param object container: the container into which to insert the iframe (that is the framework)
		 * @return void
		 */
		createIframe: function (container) {
			if (this.autoEl && this.autoEl.tag) {
				this.el = document.createElement(this.autoEl.tag);
				if (this.autoEl.id) {
					this.el.setAttribute('id', this.autoEl.id);
				}
				if (this.autoEl.cls) {
					this.el.setAttribute('class', this.autoEl.cls);
				}
				if (this.autoEl.src) {
					this.el.setAttribute('src', this.autoEl.src);
				}
				this.el = container.appendChild(this.el);
			}
		},

		/**
		 * Get the content window of the iframe
		 */
		getIframeWindow: function () {
			return this.el.contentWindow ? this.el.contentWindow : this.el.contentDocument;
		},

		/**
		 * Proceed to build the iframe document head and ensure style sheets are available after the iframe document becomes available
		 */
		initializeIframe: function () {
			var self = this;
			var iframe = this.getEl();
			// All browsers
			if (!iframe || (!iframe.contentWindow && !iframe.contentDocument)) {
				window.setTimeout(function () {
					self.initializeIframe();
				}, 50);
			// All except WebKit
			} else if (iframe.contentWindow && !UserAgent.isWebKit && (!iframe.contentWindow.document || !iframe.contentWindow.document.documentElement)) {
				window.setTimeout(function () {
					self.initializeIframe();
				}, 50);
			// WebKit
			} else if (UserAgent.isWebKit && (!iframe.contentDocument.documentElement || !iframe.contentDocument.body)) {
				window.setTimeout(function () {
					self.initializeIframe();
				}, 50);
			} else {
				this.document = iframe.contentWindow ? iframe.contentWindow.document : iframe.contentDocument;
				this.getEditor().document = this.document;
				this.createHead();
				// Style the document body
				Dom.addClass(this.document.body, 'htmlarea-content-body');
				// Start listening to things happening in the iframe
				// For some unknown reason, this is too early for Opera
				if (!UserAgent.isOpera) {
					this.startListening();
				}
				// Hide the iframe
				this.hide();
				// Set iframe ready
				this.ready = true;
				/**
				 * @event HTMLAreaEventIframeReady
				 * Fires when the iframe style sheets become accessible
				 */
				Event.trigger(this, 'HTMLAreaEventIframeReady');
			}
		},

		/**
		 * Show the iframe
		 */
		show: function () {
			this.getEl().style.display = '';
			Event.trigger(this, 'HTMLAreaEventIframeShow');
		},

		/**
		 * Hide the iframe
		 */
		hide: function () {
			this.getEl().style.display = 'none';
		},

		/**
		 * Build the iframe document head
		 */
		createHead: function () {
			var head = this.document.getElementsByTagName('head')[0];
			if (!head) {
				head = this.document.createElement('head');
				this.document.documentElement.appendChild(head);
			}
			if (this.config.baseURL) {
				var base = this.document.getElementsByTagName('base')[0];
				if (!base) {
					base = this.document.createElement('base');
					base.href = this.config.baseURL;
					head.appendChild(base);
				}
				this.getEditor().appendToLog('HTMLArea.Iframe', 'createHead', 'Iframe baseURL set to: ' + base.href, 'info');
			}
			var link0 = this.document.getElementsByTagName('link')[0];
			if (!link0) {
				link0 = this.document.createElement('link');
				link0.rel = 'stylesheet';
				link0.type = 'text/css';
					// Firefox 3.0.1 does not apply the base URL while Firefox 3.6.8 does so. Do not know in what version this was fixed.
					// Therefore, for versions before 3.6.8, we prepend the url with the base, if the url is not absolute
				link0.href = ((UserAgent.isGecko && navigator.productSub < 2010072200 && !/^http(s?):\/{2}/.test(this.config.editedContentStyle)) ? this.config.baseURL : '') + this.config.editedContentStyle;
				head.appendChild(link0);
				this.getEditor().appendToLog('HTMLArea.Iframe', 'createHead', 'Skin CSS set to: ' + link0.href, 'info');
			}
			var pageStyle;
			for (var i = 0, n = this.config.pageStyle.length; i < n; i++) {
				pageStyle = this.config.pageStyle[i];
				var link = this.document.createElement('link');
				link.rel = 'stylesheet';
				link.type = 'text/css';
				link.href = ((UserAgent.isGecko && navigator.productSub < 2010072200 && !/^https?:\/{2}/.test(pageStyle)) ? this.config.baseURL : '') + pageStyle;
				head.appendChild(link);
				this.getEditor().appendToLog('HTMLArea.Iframe', 'createHead', 'Content CSS set to: ' + link.href, 'info');
			}
		},

		/**
		 * Focus on the iframe
		 */
		focus: function () {
			try {
				if (UserAgent.isWebKit) {
					this.getEl().focus();
				}
				this.getEl().contentWindow.focus();
			} catch(e) { }
		},

		/**
		 * Flag indicating whether the framework is inside a tab or inline element that may be hidden
		 * Should be set in config
		 */
		isNested: false,

		/**
		 * All nested tabs and inline levels in the sorting order they were applied
		 * Should be set in config
		 */
		nestedParentElements: {},

		/**
		 * Set designMode
		 *
		 * @param	boolean		on: if true set designMode to on, otherwise set to off
		 *
		 * @rturn	void
		 */
		setDesignMode: function (on) {
			if (on) {
				if (!UserAgent.isIE) {
					if (UserAgent.isGecko) {
							// In Firefox, we can't set designMode when we are in a hidden TYPO3 tab or inline element
						if (!this.isNested || Typo3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
							this.document.designMode = 'on';
							this.setOptions();
						}
					} else {
						this.document.designMode = 'on';
						this.setOptions();
					}
				}
				if (UserAgent.isIE || UserAgent.isWebKit) {
					this.document.body.contentEditable = true;
				}
			} else {
				if (!UserAgent.isIE) {
					this.document.designMode = 'off';
				}
				if (UserAgent.isIE || UserAgent.isWebKit) {
					this.document.body.contentEditable = false;
				}
			}
		},

		/**
		 * Set editing mode options (if we can... raises exception in Firefox 3)
		 *
		 * @return	void
		 */
		setOptions: function () {
			if (!UserAgent.isIE) {
				try {
					if (this.document.queryCommandEnabled('insertBrOnReturn')) {
						this.document.execCommand('insertBrOnReturn', false, this.config.disableEnterParagraphs);
					}
					if (this.document.queryCommandEnabled('styleWithCSS')) {
						this.document.execCommand('styleWithCSS', false, this.config.useCSS);
					} else if (UserAgent.isGecko && this.document.queryCommandEnabled('useCSS')) {
						this.document.execCommand('useCSS', false, !this.config.useCSS);
					}
					if (UserAgent.isGecko) {
						if (this.document.queryCommandEnabled('enableObjectResizing')) {
							this.document.execCommand('enableObjectResizing', false, !this.config.disableObjectResizing);
						}
						if (this.document.queryCommandEnabled('enableInlineTableEditing')) {
							this.document.execCommand('enableInlineTableEditing', false, (this.config.buttons.table && this.config.buttons.table.enableHandles) ? true : false);
						}
					}
				} catch(e) {}
			}
		},

		/**
		 * Mutations handler invoked when an hidden TYPO3 hidden nested tab or inline element is shown
		 */
		onNestedShowMutation: function (mutations) {
			for (var i = mutations.length; --i >= 0;) {
				var targetId = mutations[i].target.id;
				if (this.nestedParentElements.sorted.indexOf(targetId) !== -1 || this.nestedParentElements.sorted.indexOf(targetId.replace('_div', '_fields')) !== -1) {
					this.onNestedShowAction();
				}
			}
		},

		/**
		 * Handler invoked when an hidden TYPO3 hidden nested tab or inline element is shown
		 */
		onNestedShow: function (event) {
			Event.stopEvent(event);
			var target = event.target;
			var delay = event.data.delay;
			var self = this;
			window.setTimeout(function () {
				var styleEvent = true;
				// In older versions of Gecko attrName is not set and refering to it causes a non-catchable crash
				if ((UserAgent.isGecko && navigator.productSub > 2007112700) || UserAgent.isOpera || UserAgent.isIE) {
					styleEvent = (event.originalEvent.attrName === 'style') || (event.originalEvent.attrName === 'className') || (event.originalEvent.attrName === 'class');
				}
				if (styleEvent && (self.nestedParentElements.sorted.indexOf(target.id) != -1 || self.nestedParentElements.sorted.indexOf(target.id.replace('_div', '_fields')) != -1)) {
					self.onNestedShowAction();
				}
			}, delay);
			return false;
		},

		/**
		 * Take action when nested tab or inline element is shown
		 */
		onNestedShowAction: function () {
			// Check if all container nested elements are displayed
			if (Typo3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
				if (this.getEditor().getMode() === 'wysiwyg') {
					if (UserAgent.isGecko) {
						this.setDesignMode(true);
					}
					Event.trigger(this, 'HTMLAreaEventIframeShow');
				} else {
					Event.trigger(this.framework.getTextAreaContainer(), 'HTMLAreaEventTextAreaContainerShow');
				}
				this.getToolbar().update();
			}
		},

		/**
		 * Instance of DOM walker
		 */
		htmlRenderer: null,

		/**
		 * Getter for the instance of DOM walker
		 */
		getHtmlRenderer: function () {
			if (!this.htmlRenderer) {
				this.htmlRenderer = new Walker({
					keepComments: !this.config.htmlRemoveComments,
					removeTags: this.config.htmlRemoveTags,
					removeTagsAndContents: this.config.htmlRemoveTagsAndContents,
					baseUrl: this.config.baseURL
				});
			}
			return this.htmlRenderer;
		},

		/**
		 * Get the HTML content of the iframe
		 */
		getHTML: function () {
			return this.getHtmlRenderer().render(this.document.body, false);
		},

		/**
		 * Start listening to things happening in the iframe
		 */
		startListening: function () {
			var self = this;
			// Create keyMap so that plugins may bind key handlers
			this.keyMap = new KeyMap(this.document.documentElement, (UserAgent.isIE || UserAgent.isWebKit) ? 'keydown' : 'keypress');
			// Special keys map
			this.keyMap.addBinding(
				{
					key: [Event.DOWN, Event.UP, Event.LEFT, Event.RIGHT],
					alt: false,
					handler: function (event) { return self.onArrow(event); }
				}
			);
			this.keyMap.addBinding(
				{
					key: Event.TAB,
					ctrl: false,
					alt: false,
					handler: function (event) { return self.onTab(event); }
				}
			);
			this.keyMap.addBinding(
				{
					key: Event.SPACE,
					ctrl: true,
					shift: false,
					alt: false,
					handler: function (event) { return self.onCtrlSpace(event); }
				}
			);
			if (UserAgent.isGecko || UserAgent.isIE || UserAgent.isWebKit) {
				this.keyMap.addBinding(
				{
					key: [Event.BACKSPACE, Event.DELETE],
					alt: false,
					handler: function (event) { return self.onBackSpace(event); }
				});
			}
			if (!UserAgent.isIE && !this.config.disableEnterParagraphs) {
				this.keyMap.addBinding(
				{
					key: Event.ENTER,
					shift: false,
					handler: function (event) { return self.onEnter(event); }
				});
			}
			if (UserAgent.isWebKit) {
				this.keyMap.addBinding(
				{
					key: Event.ENTER,
					alt: false,
					handler: function (event) { return self.onWebKitEnter(event); }
				});
			}
			// Hot key map (on keydown for all browsers)
			var hotKeys = [];
			for (var key in this.config.hotKeyList) {
				if (key.length === 1) {
					hotKeys.push(key);
				}
			}
			// Make hot key map available, even if empty, so that plugins may add bindings
			this.hotKeyMap = new KeyMap(this.document.documentElement, 'keydown');
			if (hotKeys.length > 0) {
				this.hotKeyMap.addBinding({
					key: hotKeys,
					ctrl: true,
					shift: false,
					alt: false,
					handler: function (event) { return self.onHotKey(event); }
				});
			}
			Event.on(
				this.document.documentElement,
				(UserAgent.isIE || UserAgent.isWebKit) ? 'keydown' : 'keypress',
				function (event) { return self.onAnyKey(event); }
			);
			Event.on(
				this.document.documentElement,
				'mouseup',
				function (event) { return self.onMouse(event); }
			);
			Event.on(
				this.document.documentElement,
				'click',
				function (event) { return self.onMouse(event); }
			);
			if (UserAgent.isGecko) {
				Event.on(
					this.document.documentElement,
					'paste',
					function (event) { return self.onPaste(event); }
				);
			}
			Event.on(
				this.document.documentElement,
				'drop',
				function (event) { return self.onDrop(event); }
			);
			if (UserAgent.isWebKit) {
				Event.on(
					this.document.body,
					'dragend',
					function (event) { return self.onDrop(event); }
				);
			}
		},

		/**
		 * Handler for other key events
		 */
		onAnyKey: function (event) {
			if (this.inhibitKeyboardInput(event)) {
				return false;
			}
			/**
			 * @event HTMLAreaEventWordCountChange
			 * Fires when the word count may have changed
			 */
			Event.trigger(this, 'HTMLAreaEventWordCountChange', [100]);
			if (!event.altKey && !(event.ctrlKey || event.metaKey)) {
				var key = Event.getKey(event);
				// Detect URL in non-IE browsers
				if (!UserAgent.isIE && (key !== Event.ENTER || (event.shiftKey && !UserAgent.isWebKit))) {
					this.getEditor().getSelection().detectURL(event);
				}
				// Handle option+SPACE for Mac users
				if (UserAgent.isMac && key === Event.NON_BREAKING_SPACE) {
					return this.onOptionSpace(key, event);
				}
			}
			return true;
		},

		/**
		 * On any key input event, check if input is currently inhibited
		 */
		inhibitKeyboardInput: function (event) {
			// Inhibit key events while server-based cleaning is being processed
			if (this.getEditor().inhibitKeyboardInput) {
				Event.stopEvent(event);
				return true;
			} else {
				return false;
			}
		},

		/**
		 * Handler for mouse events
		 */
		onMouse: function (event) {
			// In WebKit, select the image when it is clicked
			if (UserAgent.isWebKit && /^(img)$/i.test(event.target.nodeName) && event.type === 'click') {
				this.getEditor().getSelection().selectNode(event.target);
			}
			this.getToolbar().updateLater(100);
			return true;
		},

		/**
		 * Handler for paste operations in Gecko
		 */
		onPaste: function (event) {
			// Make src and href urls absolute
			if (UserAgent.isGecko) {
				var self = this;
				window.setTimeout(function () {
					Dom.makeUrlsAbsolute(self.getEditor().document.body, self.config.baseURL, self.getHtmlRenderer());
				}, 50);
			}
			return true;
		},

		/**
		 * Handler for drag and drop operations
		 */
		onDrop: function (event) {
			var self = this;
			// Clean up span elements added by WebKit
			if (UserAgent.isWebKit) {
				window.setTimeout(function () {
					self.getEditor().getDomNode().cleanAppleStyleSpans(self.getEditor().document.body);
				}, 50);
			}
			// Make src url absolute in Firefox
			if (UserAgent.isGecko) {
				window.setTimeout(function () {
					Dom.makeUrlsAbsolute(event.target, self.config.baseURL, self.getHtmlRenderer());
				}, 50);
			}
			this.getToolbar().updateLater(100);
			return true;
		},

		/**
		 * Handler for UP, DOWN, LEFT and RIGHT arrow keys
		 */
		onArrow: function (event) {
			this.getToolbar().updateLater(100);
			return true;
		},

		/**
		 * Handler for TAB and SHIFT-TAB keys
		 *
		 * If available, BlockElements plugin will handle the TAB key
		 */
		onTab: function (event) {
			if (this.inhibitKeyboardInput(event)) {
				return false;
			}
			var keyName = (event.shiftKey ? 'SHIFT-' : '') + 'TAB';
			if (this.config.hotKeyList[keyName] && this.config.hotKeyList[keyName].cmd) {
				var button = this.getButton(this.config.hotKeyList[keyName].cmd);
				if (button) {
					Event.stopEvent(event);
					/**
					 * @event HTMLAreaEventHotkey
					 * Fires when the button hotkey is pressed
					 */
					Event.trigger(button, 'HTMLAreaEventHotkey', [keyName, event]);
					return false;
				}
			}
			return true;
		},

		/**
		 * Handler for BACKSPACE and DELETE keys
		 */
		onBackSpace: function (event) {
			if (this.inhibitKeyboardInput(event)) {
				return false;
			}
			if ((!UserAgent.isIE && !event.shiftKey) || UserAgent.isIE) {
				if (this.getEditor().getSelection().handleBackSpace()) {
					Event.stopEvent(event);
					return false;
				}
			}
			// Update the toolbar state after some time
			this.getToolbar().updateLater(200);
			return true;
		},

		/**
		 * Handler for ENTER key in non-IE browsers
		 */
		onEnter: function (event) {
			if (this.inhibitKeyboardInput(event)) {
				return false;
			}
			this.getEditor().getSelection().detectURL(event);
			if (this.getEditor().getSelection().checkInsertParagraph()) {
				Event.stopEvent(event);
				// Update the toolbar state after some time
				this.getToolbar().updateLater(200);
				return false;
			}
			// Update the toolbar state after some time
			this.getToolbar().updateLater(200);
			return true;
		},

		/**
		 * Handler for ENTER key in WebKit browsers
		 */
		onWebKitEnter: function (event) {
			if (this.inhibitKeyboardInput(event)) {
				return false;
			}
			if (event.shiftKey || this.config.disableEnterParagraphs) {
				var editor = this.getEditor();
				editor.getSelection().detectURL(event);
				if (UserAgent.isSafari) {
					var brNode = editor.document.createElement('br');
					editor.getSelection().insertNode(brNode);
					brNode.parentNode.normalize();
					// Selection issue when an URL was detected
					if (editor._unlinkOnUndo) {
						brNode = brNode.parentNode.parentNode.insertBefore(brNode, brNode.parentNode.nextSibling);
					}
					if (!brNode.nextSibling || !/\S+/i.test(brNode.nextSibling.textContent)) {
						var secondBrNode = editor.document.createElement('br');
						secondBrNode = brNode.parentNode.appendChild(secondBrNode);
					}
					editor.getSelection().selectNode(brNode, false);
					Event.stopEvent(event);
					// Update the toolbar state after some time
					this.getToolbar().updateLater(200);
					return false;
				}
			}
			// Update the toolbar state after some time
			this.getToolbar().updateLater(200);
			return true;
		},

		/**
		 * Handler for CTRL-SPACE keys
		 */
		onCtrlSpace: function (event) {
			if (this.inhibitKeyboardInput(event)) {
				return false;
			}
			this.getEditor().getSelection().insertHtml('&nbsp;');
			Event.stopEvent(event);
			return false;
		},

		/**
		 * Handler for OPTION-SPACE keys on Mac
		 */
		onOptionSpace: function (key, event) {
			if (this.inhibitKeyboardInput(event)) {
				return false;
			}
			this.getEditor().getSelection().insertHtml('&nbsp;');
			Event.stopEvent(event);
			return false;
		},

		/**
		 * Handler for configured hotkeys
		 */
		onHotKey: function (event) {
			var key = Event.getKey(event);
			if (this.inhibitKeyboardInput(event)) {
				return false;
			}
			var hotKey = String.fromCharCode(key).toLowerCase();
			/**
			 * @event HTMLAreaEventHotkey
			 * Fires when the button hotkey is pressed
			 */
			Event.trigger(this.getButton(this.config.hotKeyList[hotKey].cmd), 'HTMLAreaEventHotkey', [hotKey, event]);
			return false;
		},

		/**
		 * Cleanup (called by framework)
		 */
		onBeforeDestroy: function () {
			// Remove listeners on nested elements
			if (this.isNested) {
				if (this.mutationObserver) {
					this.mutationObserver.disconnect();
				} else {
					for (var i = this.nestedParentElements.sorted.length; --i >= 0;) {
						var nestedElement = document.getElementById(this.nestedParentElements.sorted[i]);
						Event.off(nestedElement);
						Event.off(nestedElement.parentNode);
					}
				}
			}
			Event.off(this);
			Event.off(this.getEl());
			Event.off(this.document.body);
			Event.off(this.document.documentElement);
			// Cleaning references to DOM in order to avoid IE memory leaks
			this.document = null;
			this.el = null;
		}
	};

	return Iframe;

});
