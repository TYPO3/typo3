/*
 * HTMLArea.Iframe extends Ext.BoxComponent
 */
HTMLArea.Iframe = Ext.extend(Ext.BoxComponent, {
	/*
	 * Constructor
	 */
	initComponent: function () {
		HTMLArea.Iframe.superclass.initComponent.call(this);
		this.addEvents(
			/*
			 * @event HTMLAreaEventIframeReady
			 * Fires when the iframe style sheets become accessible
			 */
			'HTMLAreaEventIframeReady',
			/*
			 * @event HTMLAreaEventWordCountChange
			 * Fires when the word count may have changed
			 */
			'HTMLAreaEventWordCountChange'
		);
		this.addListener({
			afterrender: {
				fn: this.initEventListeners,
				single: true
			},
			beforedestroy: {
				fn: this.onBeforeDestroy,
				single: true
			}
		});
		this.config = this.getEditor().config;
		this.htmlRenderer = new HTMLArea.DOM.Walker({
			keepComments: !this.config.htmlRemoveComments,
			removeTags: this.config.htmlRemoveTags,
			removeTagsAndContents: this.config.htmlRemoveTagsAndContents,
			baseUrl: this.config.baseURL
		});
		if (!this.config.showStatusBar) {
			this.addClass('noStatusBar');
		}
	},
	/*
	 * Initialize event listeners and the document after the iframe has rendered
	 */
	initEventListeners: function () {
		this.initStyleChangeEventListener();
		if (Ext.isOpera) {
			this.mon(this.getEl(), 'load', this.initializeIframe , this, {single: true});
		} else {
			this.initializeIframe();
		}
	},
	/*
	 * The editor iframe may become hidden with style.display = "none" on some parent div
	 * This breaks the editor in Firefox: the designMode attribute needs to be reset after the style.display of the container div is reset to "block"
	 * In all browsers, it breaks the evaluation of the framework dimensions
	 */
	initStyleChangeEventListener: function () {
		if (this.isNested && Ext.isGecko) {
			var options = {
				stopEvent: true,
				delay: 50
			};
			for (var i = this.nestedParentElements.sorted.length; --i >= 0;) {
				var nestedElement = Ext.get(this.nestedParentElements.sorted[i]);
				this.mon(
					nestedElement,
					Ext.isIE ? 'propertychange' : 'DOMAttrModified',
					this.onNestedShow,
					this,
					options
				);
				this.mon(
					nestedElement.parent(),
					Ext.isIE ? 'propertychange' : 'DOMAttrModified',
					this.onNestedShow,
					this,
					options
				);
			}
		}
	},
	/*
	 * editorId should be set in config
	 */
	editorId: null,
	/*
	 * Get a reference to the editor
	 */
	getEditor: function() {
		return RTEarea[this.editorId].editor;
	},
	/*
	 * Get a reference to the toolbar
	 */
	getToolbar: function () {
		return this.ownerCt.getTopToolbar();
	},
	/*
	 * Get a reference to the statusBar
	 */
	getStatusBar: function () {
		return this.ownerCt.getBottomToolbar();
	},
	/*
	 * Get a reference to a button
	 */
	getButton: function (buttonId) {
		return this.getToolbar().getButton(buttonId);
	},
	/*
	 * Flag set to true when the iframe becomes usable for editing
	 */
	ready: false,
	/*
	 * Create the iframe element at rendering time
	 */
	onRender: function (ct, position){
			// from Ext.Component
		if (!this.el && this.autoEl) {
			if (typeof this.autoEl === 'string') {
				this.el = document.createElement(this.autoEl);
			} else {
					// ExtJS Default method will not work with iframe element
				this.el = Ext.DomHelper.append(ct, this.autoEl, true);
			}
			if (!this.el.id) {
				this.el.id = this.getId();
			}
		}
			// from Ext.BoxComponent
		if (this.resizeEl){
			this.resizeEl = Ext.get(this.resizeEl);
		}
		if (this.positionEl){
			this.positionEl = Ext.get(this.positionEl);
		}
	},
	/*
	 * Proceed to build the iframe document head and ensure style sheets are available after the iframe document becomes available
	 */
	initializeIframe: function () {
		var iframe = this.getEl().dom;
			// All browsers
		if (!iframe || (!iframe.contentWindow && !iframe.contentDocument)) {
			this.initializeIframe.defer(50, this);
			// All except WebKit
		} else if (iframe.contentWindow && !Ext.isWebKit && (!iframe.contentWindow.document || !iframe.contentWindow.document.documentElement)) {
			this.initializeIframe.defer(50, this);
			// WebKit
		} else if (Ext.isWebKit && (!iframe.contentDocument.documentElement || !iframe.contentDocument.body)) {
			this.initializeIframe.defer(50, this);
		} else {
			this.document = iframe.contentWindow ? iframe.contentWindow.document : iframe.contentDocument;
			this.getEditor().document = this.document;
			this.initializeCustomTags();
			this.createHead();
				// Style the document body
			Ext.get(this.document.body).addClass('htmlarea-content-body');
				// Start listening to things happening in the iframe
				// For some unknown reason, this is too early for Opera
			if (!Ext.isOpera) {
				this.startListening();
			}
				// Hide the iframe
			this.hide();
				// Set iframe ready
			this.ready = true;
			this.fireEvent('HTMLAreaEventIframeReady');
		}
	},
	/*
	 * Create one of each of the configured custom tags so they are properly parsed by the walker when using IE
	 * See: http://en.wikipedia.org/wiki/HTML5_Shiv
	 *
	 * @return	void
	 */
	initializeCustomTags: function () {
		if (HTMLArea.isIEBeforeIE9) {
			for (var i = this.config.customTags.length; --i >= 0;) {
				this.document.createElement(this.config.customTags[i]);
			}
		}
	},
	/*
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
			link0.href = ((Ext.isGecko && navigator.productSub < 2010072200 && !/^http(s?):\/{2}/.test(this.config.editedContentStyle)) ? this.config.baseURL : '') + this.config.editedContentStyle;
			head.appendChild(link0);
			this.getEditor().appendToLog('HTMLArea.Iframe', 'createHead', 'Skin CSS set to: ' + link0.href, 'info');
		}
		var pageStyle;
		for (var i = 0, n = this.config.pageStyle.length; i < n; i++) {
			pageStyle = this.config.pageStyle[i];
			var link = this.document.createElement('link');
			link.rel = 'stylesheet';
			link.type = 'text/css';
			link.href = ((Ext.isGecko && navigator.productSub < 2010072200 && !/^https?:\/{2}/.test(pageStyle)) ? this.config.baseURL : '') + pageStyle;
			head.appendChild(link);
 			this.getEditor().appendToLog('HTMLArea.Iframe', 'createHead', 'Content CSS set to: ' + link.href, 'info');
 		}
	},
	/*
	 * Focus on the iframe
	 */
	focus: function () {
		try {
			if (Ext.isWebKit) {
				this.getEl().dom.focus();
			} else {
				this.getEl().dom.contentWindow.focus();
			}
		} catch(e) { }
	},
	/*
	 * Flag indicating whether the framework is inside a tab or inline element that may be hidden
	 * Should be set in config
	 */
	isNested: false,
	/*
	 * All nested tabs and inline levels in the sorting order they were applied
	 * Should be set in config
	 */
	nestedParentElements: {},
	/*
	 * Set designMode
	 *
	 * @param	boolean		on: if true set designMode to on, otherwise set to off
	 *
	 * @rturn	void
	 */
	setDesignMode: function (on) {
		if (on) {
	 		if (!Ext.isIE) {
				if (Ext.isGecko) {
						// In Firefox, we can't set designMode when we are in a hidden TYPO3 tab or inline element
					if (!this.isNested || HTMLArea.util.TYPO3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
						this.document.designMode = 'on';
						this.setOptions();
					}
				} else {
					this.document.designMode = 'on';
					this.setOptions();
				}
			}
			if (Ext.isIE || Ext.isWebKit) {
				this.document.body.contentEditable = true;
			}
		} else {
	 		if (!Ext.isIE) {
	 			this.document.designMode = 'off';
	 		}
	 		if (Ext.isIE || Ext.isWebKit) {
	 			this.document.body.contentEditable = false;
	 		}
	 	}
	},
	/*
	 * Set editing mode options (if we can... raises exception in Firefox 3)
	 *
	 * @return	void
	 */
	setOptions: function () {
		if (!Ext.isIE) {
			try {
				if (this.document.queryCommandEnabled('insertBrOnReturn')) {
					this.document.execCommand('insertBrOnReturn', false, this.config.disableEnterParagraphs);
				}
				if (this.document.queryCommandEnabled('styleWithCSS')) {
					this.document.execCommand('styleWithCSS', false, this.config.useCSS);
				} else if (Ext.isGecko && this.document.queryCommandEnabled('useCSS')) {
					this.document.execCommand('useCSS', false, !this.config.useCSS);
				}
				if (Ext.isGecko) {
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
	/*
	 * Handler invoked when an hidden TYPO3 hidden nested tab or inline element is shown
	 */
	onNestedShow: function (event, target) {
		var styleEvent = true;
			// In older versions of Gecko attrName is not set and refering to it causes a non-catchable crash
		if ((Ext.isGecko && navigator.productSub > 2007112700) || Ext.isOpera) {
			styleEvent = (event.browserEvent.attrName == 'style') || (event.browserEvent.attrName == 'className');
		} else if (Ext.isIE) {
			styleEvent = (event.browserEvent.propertyName == 'style.display');
		}
		if (styleEvent && (this.nestedParentElements.sorted.indexOf(target.id) != -1 || this.nestedParentElements.sorted.indexOf(target.id.replace('_div', '_fields')) != -1)) {
				// Check if all container nested elements are displayed
			if (HTMLArea.util.TYPO3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
				if (this.getEditor().getMode() === 'wysiwyg') {
					if (Ext.isGecko) {
						this.setDesignMode(true);
					}
					this.fireEvent('show');
				} else {
					this.ownerCt.textAreaContainer.fireEvent('show');
				}
				this.getToolbar().update();
				return false;
			}
		}
	},
	/*
	 * Instance of DOM walker
	 */
	htmlRenderer: {},
	/*
	 * Get the HTML content of the iframe
	 */
	getHTML: function () {
		return this.htmlRenderer.render(this.document.body, false);
	},
	/*
	 * Start listening to things happening in the iframe
	 */
	startListening: function () {
			// Create keyMap so that plugins may bind key handlers
		this.keyMap = new Ext.KeyMap(Ext.get(this.document.documentElement), [], (Ext.isIE || Ext.isWebKit) ? 'keydown' : 'keypress');
			// Special keys map
		this.keyMap.addBinding([
			{
				key: [Ext.EventObject.DOWN, Ext.EventObject.UP, Ext.EventObject.LEFT, Ext.EventObject.RIGHT],
				alt: false,
				handler: this.onArrow,
				scope: this
			},
			{
				key: Ext.EventObject.TAB,
				ctrl: false,
				alt: false,
				handler: this.onTab,
				scope: this
			},
			{
				key: Ext.EventObject.SPACE,
				ctrl: true,
				shift: false,
				alt: false,
				handler: this.onCtrlSpace,
				scope: this
			}
		]);
		if (Ext.isGecko || Ext.isIE) {
			this.keyMap.addBinding(
			{
				key: [Ext.EventObject.BACKSPACE, Ext.EventObject.DELETE],
				alt: false,
				handler: this.onBackSpace,
				scope: this
			});
		}
		if (!Ext.isIE && !this.config.disableEnterParagraphs) {
			this.keyMap.addBinding(
			{
				key: Ext.EventObject.ENTER,
				shift: false,
				handler: this.onEnter,
				scope: this
			});
		}
		if (Ext.isWebKit) {
			this.keyMap.addBinding(
			{
				key: Ext.EventObject.ENTER,
				alt: false,
				handler: this.onWebKitEnter,
				scope: this
			});
		}
		// Hot key map (on keydown for all browsers)
		var hotKeys = '';
		for (var key in this.config.hotKeyList) {
			if (key.length == 1) {
				hotKeys += key.toUpperCase();
			}
		}
			// Make hot key map available, even if empty, so that plugins may add bindings
		this.hotKeyMap = new Ext.KeyMap(Ext.get(this.document.documentElement));
		if (!Ext.isEmpty(hotKeys)) {
			this.hotKeyMap.addBinding({
				key: hotKeys,
				ctrl: true,
				shift: false,
				alt: false,
				handler: this.onHotKey,
				scope: this
			});
		}
		this.mon(Ext.get(this.document.documentElement), (Ext.isIE || Ext.isWebKit) ? 'keydown' : 'keypress', this.onAnyKey, this);
		this.mon(Ext.get(this.document.documentElement), 'mouseup', this.onMouse, this);
		this.mon(Ext.get(this.document.documentElement), 'click', this.onMouse, this);
		if (Ext.isGecko) {
			this.mon(Ext.get(this.document.documentElement), 'paste', this.onPaste, this);
		}
		this.mon(Ext.get(this.document.documentElement), 'drop', this.onDrop, this);
		if (Ext.isWebKit) {
			this.mon(Ext.get(this.document.body), 'dragend', this.onDrop, this);
		}
	},
	/*
	 * Handler for other key events
	 */
	onAnyKey: function(event) {
		if (this.inhibitKeyboardInput(event)) {
			return false;
		}
		this.fireEvent('HTMLAreaEventWordCountChange', 100);
		if (!event.altKey && !event.ctrlKey) {
				// Detect URL in non-IE browsers
			if (!Ext.isIE && (event.getKey() != Ext.EventObject.ENTER || (event.shiftKey && !Ext.isWebKit))) {
				this.getEditor().getSelection().detectURL(event);
			}
				// Handle option+SPACE for Mac users
			if (Ext.isMac && event.browserEvent.charCode == 160) {
				return this.onOptionSpace(event.browserEvent.charCode, event);
			}
		}
		return true;
	},
	/*
	 * On any key input event, check if input is currently inhibited
	 */
	inhibitKeyboardInput: function (event) {
			// Inhibit key events while server-based cleaning is being processed
		if (this.getEditor().inhibitKeyboardInput) {
			event.stopEvent();
			return true;
		} else {
			return false;
		}
	},
	/*
	 * Handler for mouse events
	 */
	onMouse: function (event, target) {
			// In WebKit, select the image when it is clicked
		if (Ext.isWebKit && /^(img)$/i.test(target.nodeName) && event.browserEvent.type == 'click') {
			this.getEditor().getSelection().selectNode(target);
		}
		this.getToolbar().updateLater.delay(100);
		return true;
	},
	/*
	 * Handler for paste operations in Gecko
	 */
	onPaste: function (event) {
			// Make src and href urls absolute
		if (Ext.isGecko) {
			HTMLArea.DOM.makeUrlsAbsolute.defer(50, this, [this.getEditor().document.body, this.config.baseURL, this.htmlRenderer]);
		}
	},
	/*
	 * Handler for drag and drop operations
	 */
	onDrop: function (event, target) {
			// Clean up span elements added by WebKit
		if (Ext.isWebKit) {
			this.getEditor().getDomNode().cleanAppleStyleSpans.defer(50, this.getEditor(), [this.getEditor().document.body]);
		}
			// Make src url absolute in Firefox
		if (Ext.isGecko) {
			HTMLArea.DOM.makeUrlsAbsolute.defer(50, this, [target, this.config.baseURL, this.htmlRenderer]);
		}
		this.getToolbar().updateLater.delay(100);
	},
	/*
	 * Handler for UP, DOWN, LEFT and RIGHT keys
	 */
	onArrow: function () {
		this.getToolbar().updateLater.delay(100);
		return true;
	},
	/*
	 * Handler for TAB and SHIFT-TAB keys
	 *
	 * If available, BlockElements plugin will handle the TAB key
	 */
	onTab: function (key, event) {
		if (this.inhibitKeyboardInput(event)) {
			return false;
		}
		var keyName = (event.shiftKey ? 'SHIFT-' : '') + 'TAB';
		if (this.config.hotKeyList[keyName] && this.config.hotKeyList[keyName].cmd) {
			var button = this.getButton(this.config.hotKeyList[keyName].cmd);
			if (button) {
				event.stopEvent();
				button.fireEvent('HTMLAreaEventHotkey', keyName, event);
				return false;
			}
		}
		event.stopEvent();
		return false;
	},
	/*
	 * Handler for BACKSPACE and DELETE keys
	 */
	onBackSpace: function (key, event) {
		if (this.inhibitKeyboardInput(event)) {
			return false;
		}
		if ((!Ext.isIE && !event.shiftKey) || Ext.isIE) {
			if (this.getEditor().getSelection().handleBackSpace()) {
				event.stopEvent();
			}
		}
			// Update the toolbar state after some time
		this.getToolbar().updateLater.delay(200);
		return false;
	},
	/*
	 * Handler for ENTER key in non-IE browsers
	 */
	onEnter: function (key, event) {
		if (this.inhibitKeyboardInput(event)) {
			return false;
		}
		this.getEditor().getSelection().detectURL(event);
		if (this.getEditor().getSelection().checkInsertParagraph()) {
			event.stopEvent();
		}
			// Update the toolbar state after some time
		this.getToolbar().updateLater.delay(200);
		return false;
	},
	/*
	 * Handler for ENTER key in WebKit browsers
	 */
	onWebKitEnter: function (key, event) {
		if (this.inhibitKeyboardInput(event)) {
			return false;
		}
		if (event.shiftKey || this.config.disableEnterParagraphs) {
			var editor = this.getEditor();
			editor.getSelection().detectURL(event);
			if (Ext.isSafari) {
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
				event.stopEvent();
			}
		}
			// Update the toolbar state after some time
		this.getToolbar().updateLater.delay(200);
		return false;
	},
	/*
	 * Handler for CTRL-SPACE keys
	 */
	onCtrlSpace: function (key, event) {
		if (this.inhibitKeyboardInput(event)) {
			return false;
		}
		this.getEditor().getSelection().insertHtml('&nbsp;');
		event.stopEvent();
		return false;
	},
	/*
	 * Handler for OPTION-SPACE keys on Mac
	 */
	onOptionSpace: function (key, event) {
		if (this.inhibitKeyboardInput(event)) {
			return false;
		}
		this.getEditor().getSelection().insertHtml('&nbsp;');
		event.stopEvent();
		return false;
	},
	/*
	 * Handler for configured hotkeys
	 */
	onHotKey: function (key, event) {
		if (this.inhibitKeyboardInput(event)) {
			return false;
		}
		var hotKey = String.fromCharCode(key).toLowerCase();
		this.getButton(this.config.hotKeyList[hotKey].cmd).fireEvent('HTMLAreaEventHotkey', hotKey, event);
		return false;
	},
	/**
	 * Cleanup
	 */
	onBeforeDestroy: function () {
		// ExtJS KeyMap object makes IE leak memory
		// Nullify EXTJS private handlers
		for (var index = this.keyMap.bindings.length; --index >= 0;) {
			this.keyMap.bindings[index] = null;
		}
		this.keyMap.handleKeyDown = null;
		for (var index = this.hotKeyMap.bindings.length; --index >= 0;) {
			this.hotKeyMap.bindings[index] = null;
		}
		this.hotKeyMap.handleKeyDown = null;
		this.keyMap.disable();
		this.hotKeyMap.disable();
			// Cleaning references to DOM in order to avoid IE memory leaks
		Ext.get(this.document.body).purgeAllListeners();
		Ext.get(this.document.body).dom = null;
		Ext.get(this.document.documentElement).purgeAllListeners();
		Ext.get(this.document.documentElement).dom = null;
		this.document = null;
		this.getEditor().document = null;
		for (var index = this.nestedParentElements.sorted.length; --index >= 0;) {
			var nested = this.nestedParentElements.sorted[index];
			Ext.get(nested).purgeAllListeners();
			Ext.get(nested).dom = null;
		}
		Ext.destroy(this.autoEl, this.el, this.resizeEl, this.positionEl);
		return true;
	}
});
Ext.reg('htmlareaiframe', HTMLArea.Iframe);
