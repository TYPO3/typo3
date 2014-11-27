/**
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
 * Framework extends Ext.Panel and is the visual component of the Editor and contains the tool bar, the iframe, the textarea and the status bar
 */
HTMLArea.Framework = function(Typo3) {

	var Framework = Ext.extend(Ext.Panel, {

		/**
		 * Constructor
		 */
		initComponent: function () {
			Framework.superclass.initComponent.call(this);
			// Set some references
			this.toolbar = this.getTopToolbar();
			this.statusBar = this.getBottomToolbar();
			this.iframe = this.getComponent('iframe');
			this.textAreaContainer = this.getComponent('textAreaContainer');
			this.addEvents(
				/*
				 * @event HTMLAreaEventFrameworkReady
				 * Fires when the iframe is ready and all components are rendered
				 */
				'HTMLAreaEventFrameworkReady'
			);
			this.addListener({
				beforedestroy: {
					fn: this.onBeforeDestroy,
					single: true
				}
			});
			// Monitor iframe becoming ready
			this.mon(this.iframe, 'HTMLAreaEventIframeReady', this.onIframeReady, this, {single: true});
			// Let the framefork render itself, but it will fail to do so if inside a hidden tab or inline element
			if (!this.isNested || Typo3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
				this.render(this.textArea.parentNode, this.textArea.id);
			} else {
				// Clone the array of nested tabs and inline levels instead of using a reference as HTMLArea.util.TYPO3.accessParentElements will modify the array
				var parentElements = [].concat(this.nestedParentElements.sorted);
				// Walk through all nested tabs and inline levels to get correct sizes
				Typo3.accessParentElements(parentElements, 'args[0].render(args[0].textArea.parentNode, args[0].textArea.id)', [this]);
			}
		},

		/**
		 * Initiate events monitoring
		 */
		initEventListeners: function () {
			// Make the framework resizable, if configured by the user
			this.makeResizable();
			// Monitor textArea container becoming shown or hidden as it may change the height of the status bar
			this.mon(this.textAreaContainer, 'show', this.resizable ? this.onTextAreaShow : this.onWindowResize, this);
			// Monitor iframe becoming shown or hidden as it may change the height of the status bar
			this.mon(this.iframe, 'show', this.resizable ? this.onIframeShow : this.onWindowResize, this);
			// Monitor window resizing
			Ext.EventManager.onWindowResize(this.onWindowResize, this);
			// If the textarea is inside a form, on reset, re-initialize the HTMLArea content and update the toolbar
			var form = this.textArea.form;
			if (form) {
				if (typeof form.onreset === 'function') {
					if (typeof form.htmlAreaPreviousOnReset === 'undefined') {
						form.htmlAreaPreviousOnReset = [];
					}
					form.htmlAreaPreviousOnReset.push(form.onreset);
				}
				this.mon(Ext.get(form), 'reset', this.onReset, this);
			}
			this.addListener({
				resize: {
					fn: this.onFrameworkResize
				}
			});
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
		 * Flag set to true when the framework is ready
		 */
		ready: false,

		/**
		 * All nested tabs and inline levels in the sorting order they were applied
		 * Should be set in config
		 */
		nestedParentElements: {},

		/**
		 * Whether the framework should be made resizable
		 * May be set in config
		 */
		resizable: false,

		/**
		 * Maximum height to which the framework may resized (in pixels)
		 * May be set in config
		 */
		maxHeight: 2000,

		/**
		 * Initial textArea dimensions
		 * Should be set in config
		 */
		textAreaInitialSize: {
			width: 0,
			contextWidth: 0,
			height: 0
		},

		/**
		 * doLayout will fail if inside a hidden tab or inline element
		 */
		doLayout: function () {
			if (!this.isNested || Typo3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
				Framework.superclass.doLayout.call(this);
			} else {
				// Clone the array of nested tabs and inline levels instead of using a reference as HTMLArea.util.TYPO3.accessParentElements will modify the array
				var parentElements = [].concat(this.nestedParentElements.sorted);
				// Walk through all nested tabs and inline levels to get correct sizes
				Typo3.accessParentElements(parentElements, 'HTMLArea.Framework.superclass.doLayout.call(args[0])', [this]);
			}
		},

		/**
		 * onLayout will fail if inside a hidden tab or inline element
		 */
		onLayout: function () {
			if (!this.isNested || Typo3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
				Framework.superclass.onLayout.call(this);
			} else {
				// Clone the array of nested tabs and inline levels instead of using a reference as HTMLArea.util.TYPO3.accessParentElements will modify the array
				var parentElements = [].concat(this.nestedParentElements.sorted);
				// Walk through all nested tabs and inline levels to get correct sizes
				Typo3.accessParentElements(parentElements, 'HTMLArea.Framework.superclass.onLayout.call(args[0])', [this]);
			}
		},

		/**
		 * Make the framework resizable, if configured
		 */
		makeResizable: function () {
			if (this.resizable) {
				this.addClass('resizable');
				this.resizer = new Ext.Resizable(this.getEl(), {
					minWidth: 300,
					maxHeight: this.maxHeight,
					dynamic: false
				});
				this.resizer.on('resize', this.onHtmlAreaResize, this);
			}
		},

		/**
		 * Resize the framework when the resizer handles are used
		 */
		onHtmlAreaResize: function (resizer, width, height, event) {
			// Set width first as it may change the height of the toolbar and of the statusBar
			this.setWidth(width);
			// Set height of iframe and textarea
			this.iframe.setHeight(this.getInnerHeight());
			this.textArea.style.width = (this.getInnerWidth()-9) + 'px';
			this.textArea.style.height = this.getInnerHeight() + 'px';
		},

		/**
		 * Size the iframe according to initial textarea size as set by Page and User TSConfig
		 */
		onWindowResize: function (width, height) {
			if (!this.isNested || Typo3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
				this.resizeFramework(width, height);
			} else {
				// Clone the array of nested tabs and inline levels instead of using a reference as HTMLArea.util.TYPO3.accessParentElements will modify the array
				var parentElements = [].concat(this.nestedParentElements.sorted);
				// Walk through all nested tabs and inline levels to get correct sizes
				Typo3.accessParentElements(parentElements, 'args[0].resizeFramework(args[1], args[2])', [this, width, height]);
			}
		},

		/**
		 * Resize the framework to its initial size
		 */
		resizeFramework: function (width, height) {
			var frameworkHeight = parseInt(this.textAreaInitialSize.height);
			if (this.textAreaInitialSize.width.indexOf('%') === -1) {
				// Width is specified in pixels
				var frameworkWidth = parseInt(this.textAreaInitialSize.width) - this.getFrameWidth();
			} else {
				// Width is specified in %
				if (typeof width === 'number' && isFinite(width)) {
					// Framework sizing on actual window resize
					var frameworkWidth = parseInt(((width - this.textAreaInitialSize.wizardsWidth - (this.fullScreen ? 10 : Ext.getScrollBarWidth()) - this.getBox().x - 15) * parseInt(this.textAreaInitialSize.width))/100);
				} else {
					// Initial framework sizing
					var frameworkWidth = parseInt(((Typo3.getWindowSize().width - this.textAreaInitialSize.wizardsWidth - (this.fullScreen ? 10 : Ext.getScrollBarWidth()) - this.getBox().x - 15) * parseInt(this.textAreaInitialSize.width))/100);
				}
			}
			if (this.resizable) {
				this.resizer.resizeTo(frameworkWidth, frameworkHeight);
			} else {
				this.setSize(frameworkWidth, frameworkHeight);
				this.doLayout();
			}
		},

		/**
		 * Resize the framework components
		 */
		onFrameworkResize: function () {
			this.iframe.getEl().dom.style.width = (this.getInnerWidth()-9) + 'px';
			this.iframe.setHeight(this.getInnerHeight());
			this.textArea.style.width = (this.getInnerWidth()-9) + 'px';
			this.textArea.style.height = this.getInnerHeight() + 'px';
		},

		/**
		 * Adjust the height to the changing size of the statusbar when the textarea is shown
		 */
		onTextAreaShow: function () {
			this.iframe.setHeight(this.getInnerHeight());
			this.textArea.style.height = this.getInnerHeight() + 'px';
		},

		/**
		 * Adjust the height to the changing size of the statusbar when the iframe is shown
		 */
		onIframeShow: function () {
			if (this.getInnerHeight() <= 0) {
				this.onWindowResize();
			} else {
				this.iframe.setHeight(this.getInnerHeight());
				this.textArea.style.height = this.getInnerHeight() + 'px';
			}
		},

		/**
		 * Calculate the height available for the editing iframe
		 */
		getInnerHeight: function () {
			return this.getSize().height - this.toolbar.getHeight() - this.statusBar.getHeight();
		},

		/**
		 * Fire the editor when all components of the framework are rendered and ready
		 */
		onIframeReady: function () {
			this.ready = this.rendered && this.toolbar.rendered && this.statusBar.rendered && this.textAreaContainer.rendered;
			if (this.ready) {
				this.initEventListeners();
				this.textAreaContainer.show();
				if (!this.getEditor().config.showStatusBar) {
					this.statusBar.hide();
				}
					// Set the initial size of the framework
				this.onWindowResize();
				this.fireEvent('HTMLAreaEventFrameworkReady');
			} else {
				this.onIframeReady.defer(50, this);
			}
		},

		/**
		 * Handler invoked if we are inside a form and the form is reset
		 * On reset, re-initialize the HTMLArea content and update the toolbar
		 */
		onReset: function (event) {
			this.getEditor().setHTML(this.textArea.value);
			this.toolbar.update();
			// Invoke previous reset handlers, if any
			var htmlAreaPreviousOnReset = event.getTarget().dom.htmlAreaPreviousOnReset;
			if (typeof htmlAreaPreviousOnReset !== 'undefined') {
				for (var i = 0, n = htmlAreaPreviousOnReset.length; i < n; i++) {
					htmlAreaPreviousOnReset[i]();
				}
			}
		},

		/**
		 * Cleanup on framework destruction
		 */
		onBeforeDestroy: function () {
			Ext.EventManager.removeResizeListener(this.onWindowResize, this);
			// Cleaning references to DOM in order to avoid IE memory leaks
			var form = this.textArea.form;
			if (form) {
				form.htmlAreaPreviousOnReset = null;
				Ext.get(form).dom = null;
			}
			Ext.getBody().dom = null;
			// ExtJS is not releasing any resources when the iframe is unloaded
			this.toolbar.destroy();
			this.statusBar.destroy();
			this.removeAll(true);
			if (this.resizer) {
				this.resizer.destroy();
			}
			return true;
		}
	});

	return Framework;

}(HTMLArea.util.TYPO3);
Ext.reg('htmlareaframework', HTMLArea.Framework);
