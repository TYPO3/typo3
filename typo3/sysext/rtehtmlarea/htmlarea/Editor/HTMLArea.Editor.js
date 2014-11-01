/***************************************************
 *  HTMLArea.Editor extends Ext.util.Observable
 ***************************************************/
HTMLArea.Editor = Ext.extend(Ext.util.Observable, {
	/*
	 * HTMLArea.Editor constructor
	 */
	constructor: function (config) {
		HTMLArea.Editor.superclass.constructor.call(this, {});
			// Save the config
		this.config = config;
			// Establish references to this editor
		this.editorId = this.config.editorId;
		RTEarea[this.editorId].editor = this;
			// Get textarea size and wizard context
		this.textArea = Ext.get(this.config.id);
		this.textAreaInitialSize = {
			width: this.config.RTEWidthOverride ? this.config.RTEWidthOverride : this.textArea.getStyle('width'),
			height: this.config.fullScreen ? HTMLArea.util.TYPO3.getWindowSize().height - 20 : this.textArea.getStyle('height'),
			wizardsWidth: 0
		};
			// TYPO3 Inline elements and tabs
		this.nestedParentElements = {
			all: this.config.tceformsNested,
			sorted: HTMLArea.util.TYPO3.simplifyNested(this.config.tceformsNested)
		};
		this.isNested = !Ext.isEmpty(this.nestedParentElements.sorted);
			// If in BE, get width of wizards
		if (Ext.get('typo3-docheader')) {
			this.wizards = this.textArea.parent().parent().next();
			if (this.wizards) {
				if (!this.isNested || HTMLArea.util.TYPO3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
					this.textAreaInitialSize.wizardsWidth = this.wizards.getWidth();
				} else {
						// Clone the array of nested tabs and inline levels instead of using a reference as HTMLArea.util.TYPO3.accessParentElements will modify the array
					var parentElements = [].concat(this.nestedParentElements.sorted);
						// Walk through all nested tabs and inline levels to get correct size
					this.textAreaInitialSize.wizardsWidth = HTMLArea.util.TYPO3.accessParentElements(parentElements, 'args[0].getWidth()', [this.wizards]);
				}
					// Hide the wizards so that they do not move around while the editor framework is being sized
				this.wizards.hide();
			}
		}
		// Plugins register
		this.plugins = {};
		// Register the plugins included in the configuration
		for (var plugin in this.config.plugin) {
			if (this.config.plugin[plugin]) {
				this.registerPlugin(plugin);
			}
		}
			// Create Ajax object
		this.ajax = new HTMLArea.Ajax({
			editor: this
		});
			// Initialize keyboard input inhibit flag
		this.inhibitKeyboardInput = false;
		this.addEvents(
			/*
			 * @event HTMLAreaEventEditorReady
			 * Fires when initialization of the editor is complete
			 */
			'HTMLAreaEventEditorReady',
			/*
			 * @event HTMLAreaEventModeChange
			 * Fires when the editor changes mode
			 */
			'HTMLAreaEventModeChange'
		);
	},
	/*
	 * Flag set to true when the editor initialization has completed
	 */
	ready: false,
	/*
	 * The current mode of the editor: 'wysiwyg' or 'textmode'
	 */
	mode: 'textmode',
	/*
	 * Determine whether the editor document is currently contentEditable
	 *
	 * @return	boolean		true, if the document is contentEditable
	 */
 	isEditable: function () {
 		return Ext.isIE ? this.document.body.contentEditable : (this.document.designMode === 'on');
	},
	/*
	 * The selection object
	 */
	selection: null,
	getSelection: function () {
		if (!this.selection) {
			this.selection = new HTMLArea.DOM.Selection({
				editor: this
			});
		}
		return this.selection;
	},
	/*
	 * The bookmark object
	 */
	bookMark: null,
	getBookMark: function () {
		if (!this.bookMark) {
			this.bookMark = new HTMLArea.DOM.BookMark({
				editor: this
			});
		}
		return this.bookMark;
	},
	/*
	 * The DOM node object
	 */
	domNode: null,
	getDomNode: function () {
		if (!this.domNode) {
			this.domNode = new HTMLArea.DOM.Node({
				editor: this
			});
		}
		return this.domNode;
	},
	/*
	 * Create the htmlArea framework
	 */
	generate: function () {
			// Create the editor framework
		this.htmlArea = new HTMLArea.Framework({
			id: this.editorId + '-htmlArea',
			layout: 'anchor',
			baseCls: 'htmlarea',
			editorId: this.editorId,
			textArea: this.textArea,
			textAreaInitialSize: this.textAreaInitialSize,
			fullScreen: this.config.fullScreen,
			resizable: this.config.resizable,
			maxHeight: this.config.maxHeight,
			isNested: this.isNested,
			nestedParentElements: this.nestedParentElements,
				// The toolbar
			tbar: {
				xtype: 'htmlareatoolbar',
				id: this.editorId + '-toolbar',
				anchor: '100%',
				layout: 'form',
				cls: 'toolbar',
				editorId: this.editorId
			},
			items: [{
						// The iframe
					xtype: 'htmlareaiframe',
					itemId: 'iframe',
					anchor: '100%',
					width: (this.textAreaInitialSize.width.indexOf('%') === -1) ? parseInt(this.textAreaInitialSize.width) : 300,
					height: parseInt(this.textAreaInitialSize.height),
					autoEl: {
						id: this.editorId + '-iframe',
						tag: 'iframe',
						cls: 'editorIframe',
						src: Ext.isGecko ? 'javascript:void(0);' : (Ext.isWebKit ? 'javascript: \'' + HTMLArea.util.htmlEncode(this.config.documentType + this.config.blankDocument) + '\'' : HTMLArea.editorUrl + 'popups/blank.html')
					},
					isNested: this.isNested,
					nestedParentElements: this.nestedParentElements,
					editorId: this.editorId
				},{
						// Box container for the textarea
					xtype: 'box',
					itemId: 'textAreaContainer',
					anchor: '100%',
					width: (this.textAreaInitialSize.width.indexOf('%') === -1) ? parseInt(this.textAreaInitialSize.width) : 300,
						// Let the framework swallow the textarea and throw it back
					listeners: {
						afterrender: {
							fn: function (textAreaContainer) {
								this.originalParent = this.textArea.parent().dom;
								textAreaContainer.getEl().appendChild(this.textArea);
							},
							single: true,
							scope: this
						},
						beforedestroy: {
							fn: function (textAreaContainer) {
								this.originalParent.appendChild(this.textArea.dom);
								return true;
							},
							single: true,
							scope: this
						}
					}
				}
			],
				// The status bar
			bbar: {
				xtype: 'htmlareastatusbar',
				anchor: '100%',
				cls: 'statusBar',
				editorId: this.editorId
			}
		});
			// Set some references
		this.toolbar = this.htmlArea.getTopToolbar();
		this.statusBar = this.htmlArea.getBottomToolbar();
		this.iframe = this.htmlArea.getComponent('iframe');
		this.textAreaContainer = this.htmlArea.getComponent('textAreaContainer');
			// Get triggered when the framework becomes ready
		this.relayEvents(this.htmlArea, ['HTMLAreaEventFrameworkReady']);
		this.on('HTMLAreaEventFrameworkReady', this.onFrameworkReady, this, {single: true});
	},
	/*
	 * Initialize the editor
	 */
	onFrameworkReady: function () {
			// Initialize editor mode
		this.setMode('wysiwyg');
			// Create the selection object
		this.getSelection();
			// Create the bookmark object
		this.getBookMark();
			// Create the DOM node object
		this.getDomNode();
			// Initiate events listening
		this.initEventsListening();
			// Generate plugins
		this.generatePlugins();
			// Make the editor visible
		this.show();
			// Make the wizards visible again
		if (this.wizards) {
			this.wizards.show();
		}
		// Focus on the first editor that is not hidden
		for (var editorId in RTEarea) {
			var RTE = RTEarea[editorId];
			if (typeof RTE.editor === 'undefined' || (RTE.editor.isNested && !HTMLArea.util.TYPO3.allElementsAreDisplayed(RTE.editor.nestedParentElements.sorted))) {
				continue;
			} else {
				RTE.editor.focus();
				break;
			}
		}
		this.ready = true;
		this.fireEvent('HTMLAreaEventEditorReady');
		this.appendToLog('HTMLArea.Editor', 'onFrameworkReady', 'Editor ready.', 'info');
	},
	/*
	 * Set editor mode
	 *
	 * @param	string		mode: 'textmode' or 'wysiwyg'
	 *
	 * @return	void
	 */
	setMode: function (mode) {
		switch (mode) {
			case 'textmode':
				this.textArea.set({ value: this.getHTML() }, false);
				this.iframe.setDesignMode(false);
				this.iframe.hide();
				this.textAreaContainer.show();
				this.mode = mode;
				break;
			case 'wysiwyg':
				try {
					this.document.body.innerHTML = this.getHTML();
				} catch(e) {
					this.appendToLog('HTMLArea.Editor', 'setMode', 'The HTML document is not well-formed.', 'warn');
					TYPO3.Dialog.ErrorDialog({
						title: 'htmlArea RTE',
						msg: HTMLArea.localize('HTML-document-not-well-formed')
					});
					break;
				}
				this.textAreaContainer.hide();
				this.iframe.show();
				this.iframe.setDesignMode(true);
				this.mode = mode;
				break;
		}
		this.fireEvent('HTMLAreaEventModeChange', this.mode);
		this.focus();
		for (var pluginId in this.plugins) {
			this.getPlugin(pluginId).onMode(this.mode);
		}
	},
	/*
	 * Get current editor mode
	 */
	getMode: function () {
		return this.mode;
	},
	/*
	 * Retrieve the HTML
	 * In the case of the wysiwyg mode, the html content is rendered from the DOM tree
	 *
	 * @return	string		the textual html content from the current editing mode
	 */
	getHTML: function () {
		switch (this.mode) {
			case 'wysiwyg':
				return this.iframe.getHTML();
			case 'textmode':
					// Collapse repeated spaces non-editable in wysiwyg
					// Replace leading and trailing spaces non-editable in wysiwyg
				return this.textArea.getValue().
					replace(/[\x20]+/g, '\x20').
					replace(/^\x20/g, '&nbsp;').
					replace(/\x20$/g, '&nbsp;');
			default:
				return '';
		}
	},
	/*
	 * Retrieve raw HTML
	 *
	 * @return	string	the textual html content from the current editing mode
	 */
	getInnerHTML: function () {
		switch (this.mode) {
			case 'wysiwyg':
				return this.document.body.innerHTML;
			case 'textmode':
				return this.textArea.getValue();
			default:
				return '';
		}
	},
	/*
	 * Replace the html content
	 *
	 * @param	string		html: the textual html
	 *
	 * @return	void
	 */
	setHTML: function (html) {
		switch (this.mode) {
			case 'wysiwyg':
				this.document.body.innerHTML = html;
				break;
			case 'textmode':
				this.textArea.set({ value: html }, false);;
				break;
		}
	},
	/*
	 * Get the node given its position in the document tree.
	 * Adapted from FCKeditor
	 * See HTMLArea.DOM.Node::getPositionWithinTree
	 *
	 * @param	array		position: the position of the node in the document tree
	 * @param	boolean		normalized: if true, a normalized position is given
	 *
	 * @return	objet		the node
	 */
	getNodeByPosition: function (position, normalized) {
		var current = this.document.documentElement;
		var i, j, n, m;
		for (i = 0, n = position.length; current && i < n; i++) {
			var target = position[i];
			if (normalized) {
				var currentIndex = -1;
				for (j = 0, m = current.childNodes.length; j < m; j++) {
					var candidate = current.childNodes[j];
					if (
						candidate.nodeType == HTMLArea.DOM.TEXT_NODE
						&& candidate.previousSibling
						&& candidate.previousSibling.nodeType == HTMLArea.DOM.TEXT_NODE
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
	},
	/*
	 * Instantiate the specified plugin and register it with the editor
	 *
	 * @param	string		plugin: the name of the plugin
	 *
	 * @return	boolean		true if the plugin was successfully registered
	 */
	registerPlugin: function (pluginName) {
		var plugin = HTMLArea[pluginName],
			isRegistered = false;
		if (typeof plugin !== 'undefined' && typeof plugin === 'function') {
			var pluginInstance = new plugin(this, pluginName);
			if (pluginInstance) {
				var pluginInformation = pluginInstance.getPluginInformation();
				pluginInformation.instance = pluginInstance;
				this.plugins[pluginName] = pluginInformation;
				isRegistered = true;
			}
		}
		if (!isRegistered) {
			this.appendToLog('HTMLArea.Editor', 'registerPlugin', 'Could not register plugin ' + pluginName + '.', 'warn');
		}
		return isRegistered;
	},
	/*
	 * Generate registered plugins
	 */
	generatePlugins: function () {
		for (var pluginId in this.plugins) {
			var plugin = this.getPlugin(pluginId);
			plugin.onGenerate();
		}
	},
	/*
	 * Get the instance of the specified plugin, if it exists
	 *
	 * @param	string		pluginName: the name of the plugin
	 * @return	object		the plugin instance or null
	 */
	getPlugin: function(pluginName) {
		return (this.plugins[pluginName] ? this.plugins[pluginName].instance : null);
	},
	/*
	 * Unregister the instance of the specified plugin
	 *
	 * @param	string		pluginName: the name of the plugin
	 * @return	void
	 */
	unRegisterPlugin: function(pluginName) {
		delete this.plugins[pluginName].instance;
		delete this.plugins[pluginName];
	},
	/*
	 * Update the edito toolbar
	 */
	updateToolbar: function (noStatus) {
		this.toolbar.update(noStatus);
	},
	/*
	 * Focus on the editor
	 */
	focus: function () {
		switch (this.getMode()) {
			case 'wysiwyg':
				this.iframe.focus();
				break;
			case 'textmode':
				this.textArea.focus();
				break;
		}
	},
	/*
	 * Scroll the editor window to the current caret position
	 */
	scrollToCaret: function () {
		if (!Ext.isIE) {
			var e = this.getSelection().getParentElement(),
				w = this.iframe.getEl().dom.contentWindow ? this.iframe.getEl().dom.contentWindow : window,
				h = w.innerHeight || w.height,
				d = this.document,
				t = d.documentElement.scrollTop || d.body.scrollTop;
			if (e.offsetTop > h+t || e.offsetTop < t) {
				this.getSelection().getParentElement().scrollIntoView();
			}
		}
	},
	/*
	 * Add listeners
	 */
	initEventsListening: function () {
		if (Ext.isOpera) {
			this.iframe.startListening();
		}
			// Add unload handler
		var iframe = this.iframe.getEl().dom;
		Ext.EventManager.on(iframe.contentWindow ? iframe.contentWindow : iframe.contentDocument, 'unload', this.onUnload, this, {single: true});
	},
	/*
	 * Make the editor framework visible
	 */
	show: function () {
		document.getElementById('pleasewait' + this.editorId).style.display = 'none';
		document.getElementById('editorWrap' + this.editorId).style.visibility = 'visible';
	},
	/*
	 * Append an entry at the end of the troubleshooting log
	 *
	 * @param	string		functionName: the name of the editor function writing to the log
	 * @param	string		text: the text of the message
	 * @param	string		type: the type of message
	 *
	 * @return	void
	 */
	appendToLog: function (objectName, functionName, text, type) {
		HTMLArea.appendToLog(this.editorId, objectName, functionName, text, type);
	},
	/*
	 * Iframe unload handler: Update the textarea for submission and cleanup
	 */
	onUnload: function (event) {
			// Save the HTML content into the original textarea for submit, back/forward, etc.
		if (this.ready) {
			this.textArea.set({
				value: this.getHTML()
			}, false);
		}
		// Cleanup
		Ext.TaskMgr.stopAll();
		for (var pluginId in this.plugins) {
			this.unRegisterPlugin(pluginId);
		}
		this.purgeListeners();
			// Cleaning references to DOM in order to avoid IE memory leaks
		if (this.wizards) {
			this.wizards.dom = null;
			this.textArea.parent().parent().dom = null;
			this.textArea.parent().dom = null;
		}
		this.textArea.dom = null;
		RTEarea[this.editorId].editor = null;
		// ExtJS is not releasing any resources when the iframe is unloaded
		this.htmlArea.destroy();
	}
});
