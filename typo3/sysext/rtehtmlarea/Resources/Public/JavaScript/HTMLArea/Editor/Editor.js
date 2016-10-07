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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/Editor
 * Editor extends Ext.util.Observable
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Ajax/Ajax',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/Selection',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/BookMark',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/Node',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/TYPO3',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/Framework',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/Toolbar',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/Iframe',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/TextAreaContainer',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/StatusBar',
	'TYPO3/CMS/Backend/FormEngine'],
	function (UserAgent, Util, Ajax, Dom, Event, Selection, BookMark, Node, Typo3, Framework, Toolbar, Iframe, TextAreaContainer, StatusBar, FormEngine) {

	/**
	 * Editor constructor method
	 *
	 * @param {Object} config: editor configuration object
	 * @constructor
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/Editor
	 */
	var Editor = function (config) {
		// Save the config
		this.config = config;
		// Establish references to this editor
		this.editorId = this.config.editorId;
		RTEarea[this.editorId].editor = this;
		// Get textarea size and wizard context
		this.textArea = document.getElementById(this.config.id);
		var computedStyle = window.getComputedStyle ? window.getComputedStyle(this.textArea) : null;
		this.textAreaInitialSize = {
			width: this.config.RTEWidthOverride ? this.config.RTEWidthOverride : (this.textArea.style.width ? this.textArea.style.width : (computedStyle ? computedStyle.width : 0)),
			height: this.config.fullScreen ? Typo3.getWindowSize().height - 25 : (this.textArea.style.height ? this.textArea.style.height : (computedStyle ? computedStyle.height : 0)),
			wizardsWidth: 0
		};
		// TYPO3 Inline elements and tabs
		this.nestedParentElements = {
			all: this.config.tceformsNested,
			sorted: Typo3.simplifyNested(this.config.tceformsNested)
		};
		this.isNested = this.nestedParentElements.sorted.length > 0;
		// If in BE, get width of wizards
		if (document.getElementById('typo3-docheader') && !this.config.fullScreen) {
			this.wizards = this.textArea.parentNode.parentNode.nextSibling;
			if (this.wizards && this.wizards.nodeType === Dom.ELEMENT_NODE) {
				if (!this.isNested || Typo3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
					this.textAreaInitialSize.wizardsWidth = this.wizards.offsetWidth;
				} else {
					// Clone the array of nested tabs and inline levels instead of using a reference as HTMLArea.util.TYPO3.accessParentElements will modify the array
					var parentElements = [].concat(this.nestedParentElements.sorted);
					// Walk through all nested tabs and inline levels to get correct size
					this.textAreaInitialSize.wizardsWidth = Typo3.accessParentElements(parentElements, 'args[0].offsetWidth', [this.wizards]);
				}
				// Hide the wizards so that they do not move around while the editor framework is being sized
				this.wizards.style.display = 'none';
			}
		}

		// Create Ajax object
		this.ajax = new Ajax({
			editor: this
		});

		// Plugins register
		this.plugins = {};
		// Register the plugins included in the configuration
		for (var plugin in this.config.plugin) {
			if (this.config.plugin[plugin]) {
				this.registerPlugin(plugin);
			}
		}

		// Initiate loading of the CSS classes configuration
		this.getClassesConfiguration();

		// Initialize keyboard input inhibit flag
		this.inhibitKeyboardInput = false;

		/**
		 * Flag set to true when the editor initialization has completed
		 */
		this.ready = false;

		/**
		 * The current mode of the editor: 'wysiwyg' or 'textmode'
		 */
		this.mode = 'textmode';

		/**
		 * The Selection object
		 */
		this.selection = null;

		/**
		 * The BookMark object
		 */
		this.bookMark = null;

		/**
		 * The DomNode object
		 */
		this.domNode = null;
	};

	/**
	 * Determine whether the editor document is currently contentEditable
	 *
	 * @return	boolean		true, if the document is contentEditable
	 */
	Editor.prototype.isEditable = function () {
		return UserAgent.isIE ? this.document.body.contentEditable : (this.document.designMode === 'on');
	};

	/**
	 * The selection object
	 */
	Editor.prototype.getSelection = function () {
		if (!this.selection) {
			this.selection = new Selection({
				editor: this
			});
		}
		return this.selection;
	};

	/**
	 * The bookmark object
	 */
	Editor.prototype.getBookMark = function () {
		if (!this.bookMark) {
			this.bookMark = new BookMark({
				editor: this
			});
		}
		return this.bookMark;
	};

	/**
	 * The DOM node object
	 */
	Editor.prototype.getDomNode = function () {
		if (!this.domNode) {
			this.domNode = new Node({
				editor: this
			});
		}
		return this.domNode;
	};

	/**
	 * Generate the editor framework
	 */
	Editor.prototype.generate = function () {
		if (this.allPluginsRegistered()) {
			this.createFramework();
		} else {
			var self = this;
			window.setTimeout(function () {
				self.generate();
			}, 50);
		}
	};

	/**
	 * Create the htmlArea framework
	 */
	Editor.prototype.createFramework = function () {
		// Create the editor framework
		this.htmlArea = new Framework({
			id: this.editorId + '-htmlArea',
			cls: 'htmlarea',
			editorId: this.editorId,
			textArea: this.textArea,
			textAreaInitialSize: this.textAreaInitialSize,
			fullScreen: this.config.fullScreen,
			resizable: this.config.resizable,
			maxHeight: this.config.maxHeight,
			isNested: this.isNested,
			nestedParentElements: this.nestedParentElements,
			items: [new Toolbar({
					// The toolbar
					id: this.editorId + '-toolbar',
					itemId: 'toolbar',
					editorId: this.editorId
				}),
				new Iframe({
					// The iframe
					id: this.editorId + '-iframe',
					itemId: 'iframe',
					width: (this.textAreaInitialSize.width.indexOf('%') === -1) ? parseInt(this.textAreaInitialSize.width) : 300,
					height: parseInt(this.textAreaInitialSize.height),
					autoEl: {
						id: this.editorId + '-iframe',
						tag: 'iframe',
						cls: 'editorIframe',
						src: UserAgent.isGecko ? 'javascript:void(0);' : (UserAgent.isWebKit ? 'javascript: \'' + Util.htmlEncode(this.config.documentType + this.config.blankDocument) + '\'' : HTMLArea.editorUrl + 'Resources/Public/Html/blank.html')
					},
					isNested: this.isNested,
					nestedParentElements: this.nestedParentElements,
					editorId: this.editorId
				}),
				new TextAreaContainer({
					// The container for the textarea
					id: this.editorId + '-textAreaContainer',
					itemId: 'textAreaContainer',
					width: (this.textAreaInitialSize.width.indexOf('%') === -1) ? parseInt(this.textAreaInitialSize.width) : 300,
					textArea: this.textArea
				}),
				new StatusBar({
					// The status bar
					id: this.editorId + '-statusBar',
					itemId: 'statusBar',
					cls: 'statusBar',
					editorId: this.editorId
				})
			]
		});
		// Set some references
		this.toolbar = this.htmlArea.getToolbar();
		this.iframe = this.htmlArea.getIframe();
		this.textAreaContainer = this.htmlArea.getTextAreaContainer();
		this.statusBar = this.htmlArea.getStatusBar();
		// Get triggered when the framework becomes ready
		var self = this;
		Event.one(this.htmlArea, 'HTMLAreaEventFrameworkReady', function (event) { Event.stopEvent(event); self.onFrameworkReady(); return false; });
	};

	/**
	 * Initialize the editor
	 */
	Editor.prototype.onFrameworkReady = function () {
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
		this.toolbar.update();
		// Make the wizards visible again
		if (this.wizards && this.wizards.nodeType === Dom.ELEMENT_NODE) {
			this.wizards.style.display = '';
		}
		// Focus on the first editor that is not hidden
		for (var editorId in RTEarea) {
			var RTE = RTEarea[editorId];
			if (typeof RTE.editor !== 'object' || RTE.editor === null || (RTE.editor.isNested && !Typo3.allElementsAreDisplayed(RTE.editor.nestedParentElements.sorted))) {
				continue;
			} else {
				RTE.editor.focus();
				break;
			}
		}
		this.ready = true;
		/**
		 * @event EditorReady
		 * Fires when initialization of the editor is complete
		 */
		Event.trigger(this, 'HtmlAreaEventEditorReady');
		this.appendToLog('HTMLArea.Editor', 'onFrameworkReady', 'Editor ready.', 'info');
		this.onDOMSubtreeModified();
	};

	/**
	 * Get the CSS classes configuration
	 *
	 * @return void
	 */
	Editor.prototype.getClassesConfiguration = function () {
		this.classesConfigurationLoaded = false;
		if (this.config.classesUrl && typeof HTMLArea.classesLabels === 'undefined') {
			this.ajax.getJavascriptFile(this.config.classesUrl, function (options, success, response) {
				if (success) {
					try {
						if (typeof HTMLArea.classesLabels === 'undefined') {
							eval(response.responseText);
						}
					} catch(e) {
						this.appendToLog('HTMLArea.Editor', 'getClassesConfiguration', 'Error evaluating contents of Javascript file: ' + this.config.classesUrl, 'error');
					}
					this.classesConfigurationLoaded = true;
				}
			}, this);
		} else {
			// There is no classes configuration to be loaded
			this.classesConfigurationLoaded = true;
		}
	};

	/**
	 * Gets the status of the loading process of the CSS classes configuration
	 *
	 * @return boolean true if the classes configuration is loaded
	 */
	Editor.prototype.classesConfigurationIsLoaded = function() {
		return this.classesConfigurationLoaded;
	};

	/**
	 * Set editor mode
	 *
	 * @param string mode: 'textmode' or 'wysiwyg'
	 * @return void
	 */
	Editor.prototype.setMode = function (mode) {
		switch (mode) {
			case 'textmode':
				this.textArea.value = this.getHTML();
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
		/**
		 * @event HTMLAreaEventModeChange
		 * Fires when the editor changes mode
		 */
		Event.trigger(this, 'HTMLAreaEventModeChange', [this.mode]);
		this.focus();
		for (var pluginId in this.plugins) {
			this.getPlugin(pluginId).onMode(this.mode);
		}
	};

	/**
	 * Get current editor mode
	 */
	Editor.prototype.getMode = function () {
		return this.mode;
	};

	/**
	 * Retrieve the HTML
	 * In the case of the wysiwyg mode, the html content is rendered from the DOM tree
	 *
	 * @return string the textual html content from the current editing mode
	 */
	Editor.prototype.getHTML = function () {
		switch (this.mode) {
			case 'wysiwyg':
				return this.iframe.getHTML();
			case 'textmode':
				// Collapse repeated spaces non-editable in wysiwyg
				// Replace leading and trailing spaces non-editable in wysiwyg
				return this.textArea.value.
					replace(/^\x20/g, '&nbsp;').
					replace(/\x20$/g, '&nbsp;');
			default:
				return '';
		}
	};

	/**
	 * Retrieve raw HTML
	 *
	 * @return string the textual html content from the current editing mode
	 */
	Editor.prototype.getInnerHTML = function () {
		switch (this.mode) {
			case 'wysiwyg':
				return this.document.body.innerHTML;
			case 'textmode':
				return this.textArea.value;
			default:
				return '';
		}
	};

	/**
	 * Replace the html content
	 *
	 * @param string html: the textual html
	 * @return void
	 */
	Editor.prototype.setHTML = function (html) {
		switch (this.mode) {
			case 'wysiwyg':
				this.document.body.innerHTML = html;
				break;
			case 'textmode':
				this.textArea.value = html;
				break;
		}
	};

	/**
	 * Require and instantiate the specified plugin and register it with the editor
	 *
	 * @param string plugin: the name of the plugin
	 * @return void
	 */
	Editor.prototype.registerPlugin = function (pluginName) {
		var self = this;
		require(['TYPO3/CMS/Rtehtmlarea/Plugins/' + pluginName], function (Plugin) {
			var pluginInstance = new Plugin(self, pluginName);
			if (pluginInstance) {
				var pluginInformation = pluginInstance.getPluginInformation();
				pluginInformation.instance = pluginInstance;
				self.plugins[pluginName] = pluginInformation;
			} else {
				self.appendToLog('HTMLArea.Editor', 'registerPlugin', 'Could not register plugin ' + pluginName + '.', 'warn');
			}
		});
	};

	/**
	 * Determine if all configured plugins are registered
	 *
	 * @return true if all configured plugins are registered
	 */
	Editor.prototype.allPluginsRegistered = function () {
		for (var plugin in this.config.plugin) {
			if (this.config.plugin[plugin]) {
				if (!this.plugins[plugin]) {
					return false;
				}
			}
		}
		return true;
	};

	/**
	 * Generate registered plugins
	 */
	Editor.prototype.generatePlugins = function () {
		for (var pluginId in this.plugins) {
			var plugin = this.getPlugin(pluginId);
			plugin.onGenerate();
		}
	};

	/**
	 * Get the instance of the specified plugin, if it exists
	 *
	 * @param string pluginName: the name of the plugin
	 * @return object the plugin instance or null
	 */
	Editor.prototype.getPlugin = function(pluginName) {
		return (this.plugins[pluginName] ? this.plugins[pluginName].instance : null);
	};

	/**
	 * Unregister the instance of the specified plugin
	 *
	 * @param string pluginName: the name of the plugin
	 * @return void
	 */
	Editor.prototype.unRegisterPlugin = function(pluginName) {
		delete this.plugins[pluginName].instance;
		delete this.plugins[pluginName];
	};

	/**
	 * Update the editor toolbar
	 */
	Editor.prototype.updateToolbar = function (noStatus) {
		this.toolbar.update(noStatus);
	};

	/**
	 * Focus on the editor
	 */
	Editor.prototype.focus = function () {
		if (document.activeElement.tagName.toLowerCase() !== 'body') {
			// Only focus the editor if the body tag is focused, which is
			// the default after loading a page
			return;
		}
		switch (this.getMode()) {
			case 'wysiwyg':
				this.iframe.focus();
				break;
			case 'textmode':
				this.textArea.focus();
				break;
		}
	};

	/**
	 * Scroll the editor window to the current caret position
	 */
	Editor.prototype.scrollToCaret = function () {
		if (!UserAgent.isIE) {
			var contentWindow = this.iframe.getEl().contentWindow;
			if (contentWindow) {
				var windowHeight = contentWindow.innerHeight,
					element = this.getSelection().getParentElement(),
					elementOffset = element.offsetTop,
					elementHeight = Dom.getSize(element).height,
					bodyScrollTop = contentWindow.document.body.scrollTop;
				// If the current selection is out of view
				if (elementOffset > windowHeight + bodyScrollTop || elementOffset < bodyScrollTop) {
					// Scroll the iframe contentWindow
					contentWindow.scrollTo(0, elementOffset - windowHeight + elementHeight);
				}
			}
		}
	};

	/**
	 * Add listeners
	 */
	Editor.prototype.initEventsListening = function () {
		if (UserAgent.isOpera) {
			this.iframe.startListening();
		}
		// Add unload handler
		var self = this;
		Event.one(this.iframe.getIframeWindow(), 'unload', function (event) { return self.onUnload(event); });
		Event.on(this.iframe.getIframeWindow(), 'DOMSubtreeModified', function (event) { return self.onDOMSubtreeModified(event); });
	};

	/**
	 * Make the editor framework visible
	 */
	Editor.prototype.show = function () {
		document.getElementById('pleasewait' + this.editorId).style.display = 'none';
		document.getElementById('editorWrap' + this.editorId).style.visibility = 'visible';
	};

	/**
	 * Append an entry at the end of the troubleshooting log
	 *
	 * @param string functionName: the name of the editor function writing to the log
	 * @param string text: the text of the message
	 * @param string type: the type of message
	 * @return void
	 */
	Editor.prototype.appendToLog = function (objectName, functionName, text, type) {
		HTMLArea.appendToLog(this.editorId, objectName, functionName, text, type);
	};

	/**
	 *
	 * @param {Event} event
	 */
	Editor.prototype.onDOMSubtreeModified = function(event) {
		this.textArea.value = this.getHTML().trim();
		FormEngine.Validation.validate();
	};


	/**
	 * Iframe unload handler: Update the textarea for submission and cleanup
	 */
	Editor.prototype.onUnload = function (event) {
		// Save the HTML content into the original textarea for submit, back/forward, etc.
		if (this.ready) {
			this.textArea.value = this.getHTML();
		}
		// Cleanup
		for (var pluginId in this.plugins) {
			this.unRegisterPlugin(pluginId);
		}
		Event.off(this.textarea);
		this.htmlArea.onBeforeDestroy();
		RTEarea[this.editorId].editor = null;
		return true;
	};

	return Editor;

});
