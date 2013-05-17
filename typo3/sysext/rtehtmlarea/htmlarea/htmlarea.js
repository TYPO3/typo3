/***************************************************************
*  Copyright notice
*
*  (c) 2002-2004 interactivetools.com, inc.
*  (c) 2003-2004 dynarch.com
*  (c) 2004-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*  This script is a modified version of a script published under the htmlArea License.
*  A copy of the htmlArea License may be found in the textfile HTMLAREA_LICENSE.txt.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Main script of TYPO3 htmlArea RTE
 */
	// Avoid re-initialization on AJax call when HTMLArea object was already initialized
if (typeof(HTMLArea) == 'undefined') {
	// Establish HTMLArea name space
Ext.namespace('HTMLArea.CSS', 'HTMLArea.util.TYPO3', 'HTMLArea.util.Tips', 'HTMLArea.util.Color', 'Ext.ux.form', 'Ext.ux.menu', 'Ext.ux.Toolbar');
Ext.apply(HTMLArea, {
	/***************************************************
	 * COMPILED REGULAR EXPRESSIONS                    *
	 ***************************************************/
	RE_htmlTag		: /<.[^<>]*?>/g,
	RE_tagName		: /(<\/|<)\s*([^ \t\n>]+)/ig,
	RE_head			: /<head>((.|\n)*?)<\/head>/i,
	RE_body			: /<body>((.|\n)*?)<\/body>/i,
		// This expression is deprecated as of TYPO3 4.7
	Reg_body		: new RegExp('<\/?(body)[^>]*>', 'gi'),
	reservedClassNames	: /htmlarea/,
	RE_email		: /([0-9a-z]+([a-z0-9_-]*[0-9a-z])*){1}(\.[0-9a-z]+([a-z0-9_-]*[0-9a-z])*)*@([0-9a-z]+([a-z0-9_-]*[0-9a-z])*\.)+[a-z]{2,9}/i,
	RE_url			: /(([^:/?#]+):\/\/)?(([a-z0-9_]+:[a-z0-9_]+@)?[a-z0-9_-]{2,}(\.[a-z0-9_-]{2,})+\.[a-z]{2,5}(:[0-9]+)?(\/\S+)*\/?)/i,
	RE_numberOrPunctuation	: /[0-9.(),;:!¡?¿%#$'"_+=\\\/-]*/g,
	/***************************************************
	 * BROWSER IDENTIFICATION                          *
	 ***************************************************/
	isIEBeforeIE9: Ext.isIE6 || Ext.isIE7 || Ext.isIE8 || (Ext.isIE && typeof(document.documentMode) !== 'undefined' && document.documentMode < 9),
	/***************************************************
	 * LOCALIZATION                                    *
	 ***************************************************/
	localize: function (label, plural) {
		var i = plural || 0;
		var localized = HTMLArea.I18N.dialogs[label] || HTMLArea.I18N.tooltips[label] || HTMLArea.I18N.msg[label] || '';
		if (typeof localized === 'object' && typeof localized[i] !== 'undefined') {
			localized = localized[i]['target'];
		}
		return localized;
	},
	/***************************************************
	 * INITIALIZATION                                  *
	 ***************************************************/
	init: function () {
		if (!HTMLArea.isReady) {
				// Apply global configuration settings
			Ext.apply(HTMLArea, RTEarea[0]);
			Ext.applyIf(HTMLArea, {
				editorSkin	: HTMLArea.editorUrl + 'skins/default/',
				editorCSS	: HTMLArea.editorUrl + 'skins/default/htmlarea.css'
			});
			if (!Ext.isString(HTMLArea.editedContentCSS)) {
				HTMLArea.editedContentCSS = HTMLArea.editorSkin + 'htmlarea-edited-content.css';
			}
			HTMLArea.isReady = true;
			HTMLArea.appendToLog('', 'HTMLArea', 'init', 'Editor url set to: ' + HTMLArea.editorUrl, 'info');
			HTMLArea.appendToLog('', 'HTMLArea', 'init', 'Editor skin CSS set to: ' + HTMLArea.editorCSS, 'info');
			HTMLArea.appendToLog('', 'HTMLArea', 'init', 'Editor content skin CSS set to: ' + HTMLArea.editedContentCSS, 'info');
		}
	},
	/*
	 * Create an editor when HTMLArea is loaded and when Ext is ready
	 *
	 * @param	string		editorId: the id of the editor
	 *
	 * @return 	boolean		false if successful
	 */
	initEditor: function (editorId) {
		if (document.getElementById('pleasewait' + editorId)) {
			if (HTMLArea.checkSupportedBrowser()) {
				document.getElementById('pleasewait' + editorId).style.display = 'block';
				document.getElementById('editorWrap' + editorId).style.visibility = 'hidden';
				if (!HTMLArea.isReady) {
					HTMLArea.initEditor.defer(150, null, [editorId]);
				} else {
						// Create an editor for the textarea
					var editor = new HTMLArea.Editor(Ext.apply(new HTMLArea.Config(editorId), RTEarea[editorId]));
					editor.generate();
					return false;
				}
			} else {
				document.getElementById('pleasewait' + editorId).style.display = 'none';
				document.getElementById('editorWrap' + editorId).style.visibility = 'visible';
			}
		}
		return true;
	},
	/*
	 * Check if the client agent is supported
	 *
	 * @return	boolean		true if the client is supported
	 */
	checkSupportedBrowser: function () {
		return Ext.isGecko || Ext.isWebKit || Ext.isOpera || Ext.isIE;
	},
	/*
	 * Write message to JavaScript console
	 *
	 * @param	string		editorId: the id of the editor issuing the message
	 * @param	string		objectName: the name of the object issuing the message
	 * @param	string		functionName: the name of the function issuing the message
	 * @param	string		text: the text of the message
	 * @param	string		type: the type of message: 'log', 'info', 'warn' or 'error'
	 *
	 * @return	void
	 */
	appendToLog: function (editorId, objectName, functionName, text, type) {
		var str = 'RTE[' + editorId + '][' + objectName + '::' + functionName + ']: ' + text;
		if (typeof(type) === 'undefined') {
			var type = 'info';
		}
		if (typeof(console) !== 'undefined' && typeof(console) === 'object') {
			// If console is TYPO3.Backend.DebugConsole, write only error messages
			if (Ext.isFunction(console.addTab)) {
				if (type === 'error') {
					console[type](str);
				}
			// IE may not have any console
			} else if (typeof(console[type]) !== 'undefined') {
				console[type](str);
			}
		}
	}
});
/***************************************************
 *  EDITOR CONFIGURATION
 ***************************************************/
HTMLArea.Config = function (editorId) {
	this.editorId = editorId;
		// if the site is secure, create a secure iframe
	this.useHTTPS = false;
		// for Mozilla
	this.useCSS = false;
	this.enableMozillaExtension = true;
	this.disableEnterParagraphs = false;
	this.disableObjectResizing = false;
	this.removeTrailingBR = true;
		// style included in the iframe document
	this.editedContentStyle = HTMLArea.editedContentCSS;
		// content style
	this.pageStyle = "";
		// Maximum attempts at accessing the stylesheets
	this.styleSheetsMaximumAttempts = 20;
		// Remove tags (must be a regular expression)
	this.htmlRemoveTags = /none/i;
		// Remove tags and their contents (must be a regular expression)
	this.htmlRemoveTagsAndContents = /none/i;
		// Remove comments
	this.htmlRemoveComments = false;
		// Array of custom tags
	this.customTags = [];
		// BaseURL to be included in the iframe document
	this.baseURL = document.baseURI;
		// IE does not support document.baseURI
		// Since document.URL is incorrect when using realurl, get first base tag and extract href
	if (!this.baseURL) {
		var baseTags = document.getElementsByTagName ('base');
		if (baseTags.length > 0) {
			this.baseURL = baseTags[0].href;
		} else {
			this.baseURL = document.URL;
		}
	}
	if (this.baseURL && this.baseURL.match(/(.*\:\/\/.*\/)[^\/]*/)) {
		this.baseURL = RegExp.$1;
	}
		// URL-s
	this.popupURL = "popups/";
		// DocumentType
	this.documentType = '<!DOCTYPE html\r'
			+ '    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"\r'
			+ '    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\r';
	this.blankDocument = '<html><head></head><body></body></html>';
		// Hold the configuration of buttons and hot keys registered by plugins
	this.buttonsConfig = {};
	this.hotKeyList = {};
		// Default configurations for toolbar items
	this.configDefaults = {
		all: {
			xtype: 'htmlareabutton',
			disabledClass: 'buttonDisabled',
			textMode: false,
			selection: false,
			dialog: false,
			hidden: false,
			hideMode: 'display'
		},
		htmlareabutton: {
			cls: 'button',
			overCls: 'buttonHover',
				// Erratic behaviour of click event in WebKit and IE browsers
			clickEvent: (Ext.isWebKit || Ext.isIE) ? 'mousedown' : 'click'
		},
		htmlareacombo: {
			cls: 'select',
			typeAhead: true,
			lastQuery: '',
			triggerAction: 'all',
			editable: !Ext.isIE,
			selectOnFocus: !Ext.isIE,
			validationEvent: false,
			validateOnBlur: false,
			submitValue: false,
			forceSelection: true,
			mode: 'local',
			storeRoot: 'options',
			storeFields: [ { name: 'text'}, { name: 'value'}],
			valueField: 'value',
			displayField: 'text',
			labelSeparator: '',
			hideLabel: true,
			tpl: '<tpl for="."><div ext:qtip="{value}" style="text-align:left;font-size:11px;" class="x-combo-list-item">{text}</div></tpl>'
		}
	};
};
HTMLArea.Config = Ext.extend(HTMLArea.Config, {
	/**
	 * Registers a button for inclusion in the toolbar, adding some standard configuration properties for the ExtJS widgets
	 *
	 * @param	object		buttonConfiguration: the configuration object of the button:
	 *					id		: unique id for the button
	 *					tooltip		: tooltip for the button
	 *					textMode	: enable in text mode
	 *					context		: disable if not inside one of listed elements
	 *					hidden		: hide in menu and show only in context menu
	 *					selection	: disable if there is no selection
	 *					hotkey		: hotkey character
	 *					dialog		: if true, the button opens a dialogue
	 *					dimensions	: the opening dimensions object of the dialogue window: { width: nn, height: mm }
	 *					and potentially other ExtJS config properties (will be forwarded)
	 *
	 * @return	boolean		true if the button was successfully registered
	 */
	registerButton: function (config) {
		config.itemId = config.id;
		if (Ext.type(this.buttonsConfig[config.id])) {
			HTMLArea.appendToLog('', 'HTMLArea.Config', 'registerButton', 'A toolbar item with the same Id: ' + config.id + ' already exists and will be overidden.', 'warn');
		}
			// Apply defaults
		config = Ext.applyIf(config, this.configDefaults['all']);
		config = Ext.applyIf(config, this.configDefaults[config.xtype]);
			// Set some additional properties
		switch (config.xtype) {
			case 'htmlareacombo':
				if (config.options) {
						// Create combo array store
					config.store = new Ext.data.ArrayStore({
						autoDestroy:  true,
						fields: config.storeFields,
						data: config.options
					});
				} else if (config.storeUrl) {
						// Create combo json store
					config.store = new Ext.data.JsonStore({
						autoDestroy:  true,
						autoLoad: true,
						root: config.storeRoot,
						fields: config.storeFields,
						url: config.storeUrl
					});
				}
				config.hideLabel = Ext.isEmpty(config.fieldLabel) || Ext.isIE6;
				config.helpTitle = config.tooltip;
				break;
			default:
				if (!config.iconCls) {
					config.iconCls = config.id;
				}
				break;
		}
		config.cmd = config.id;
		config.tooltip = { title: config.tooltip };
		this.buttonsConfig[config.id] = config;
		return true;
	},
	/*
	 * Register a hotkey with the editor configuration.
	 */
	registerHotKey: function (hotKeyConfiguration) {
		if (Ext.isDefined(this.hotKeyList[hotKeyConfiguration.id])) {
			HTMLArea.appendToLog('', 'HTMLArea.Config', 'registerHotKey', 'A hotkey with the same key ' + hotKeyConfiguration.id + ' already exists and will be overidden.', 'warn');
		}
		if (Ext.isDefined(hotKeyConfiguration.cmd) && !Ext.isEmpty(hotKeyConfiguration.cmd) && Ext.isDefined(this.buttonsConfig[hotKeyConfiguration.cmd])) {
			this.hotKeyList[hotKeyConfiguration.id] = hotKeyConfiguration;
			return true;
		} else {
			HTMLArea.appendToLog('', 'HTMLArea.Config', 'registerHotKey', 'A hotkey with key ' + hotKeyConfiguration.id + ' could not be registered because toolbar item with id ' + hotKeyConfiguration.cmd + ' was not registered.', 'warn');
			return false;
		}
	},
	/*
	 * Get the configured document type for dialogue windows
	 */
	getDocumentType: function () {
		return this.documentType;
	}
});
/***************************************************
 *  TOOLBAR COMPONENTS
 ***************************************************/
/*
 * Ext.ux.HTMLAreaButton extends Ext.Button
 */
Ext.ux.HTMLAreaButton = Ext.extend(Ext.Button, {
	/*
	 * Component initialization
	 */
	initComponent: function () {
		Ext.ux.HTMLAreaButton.superclass.initComponent.call(this);
		this.addEvents(
			/*
			 * @event HTMLAreaEventHotkey
			 * Fires when the button hotkey is pressed
			 */
			'HTMLAreaEventHotkey',
			/*
			 * @event HTMLAreaEventContextMenu
			 * Fires when the button is triggered from the context menu
			 */
			'HTMLAreaEventContextMenu'
		);
		this.addListener({
			afterrender: {
				fn: this.initEventListeners,
				single: true
			}
		});
	},
	/*
	 * Initialize listeners
	 */
	initEventListeners: function () {
		this.addListener({
			HTMLAreaEventHotkey: {
				fn: this.onHotKey
			},
			HTMLAreaEventContextMenu: {
				fn: this.onButtonClick
			}
		});
		this.setHandler(this.onButtonClick, this);
			// Monitor toolbar updates in order to refresh the state of the button
		this.mon(this.getToolbar(), 'HTMLAreaEventToolbarUpdate', this.onUpdateToolbar, this);
	},
	/*
	 * Get a reference to the editor
	 */
	getEditor: function() {
		return RTEarea[this.ownerCt.editorId].editor;
	},
	/*
	 * Get a reference to the toolbar
	 */
	getToolbar: function() {
		return this.ownerCt;
	},
	/*
	 * Add properties and function to set button active or not depending on current selection
	 */
	inactive: true,
	activeClass: 'buttonActive',
	setInactive: function (inactive) {
		this.inactive = inactive;
		return inactive ? this.removeClass(this.activeClass) : this.addClass(this.activeClass);
	},
	/*
	 * Determine if the button should be enabled based on the current selection and context configuration property
	 */
	isInContext: function (mode, selectionEmpty, ancestors) {
		var editor = this.getEditor();
		var inContext = true;
		if (mode === 'wysiwyg' && this.context) {
			var attributes = [],
				contexts = [];
			if (/(.*)\[(.*?)\]/.test(this.context)) {
				contexts = RegExp.$1.split(',');
				attributes = RegExp.$2.split(',');
			} else {
				contexts = this.context.split(',');
			}
			contexts = new RegExp( '^(' + contexts.join('|') + ')$', 'i');
			var matchAny = contexts.test('*');
			Ext.each(ancestors, function (ancestor) {
				inContext = matchAny || contexts.test(ancestor.nodeName);
				if (inContext) {
					Ext.each(attributes, function (attribute) {
						inContext = eval("ancestor." + attribute);
						return inContext;
					});
				}
				return !inContext;
			});
		}
		return inContext && (!this.selection || !selectionEmpty);
	},
	/*
	 * Handler invoked when the button is clicked
	 */
	onButtonClick: function (button, event, key) {
		if (!this.disabled) {
			if (!this.plugins[this.action](this.getEditor(), key || this.itemId) && event) {
				event.stopEvent();
			}
			if (Ext.isOpera) {
				this.getEditor().focus();
			}
			if (this.dialog) {
				this.setDisabled(true);
			} else {
				this.getToolbar().update();
			}
		}
		return false;
	},
	/*
	 * Handler invoked when the hotkey configured for this button is pressed
	 */
	onHotKey: function (key, event) {
		return this.onButtonClick(this, event, key);
	},
	/*
	 * Handler invoked when the toolbar is updated
	 */
	onUpdateToolbar: function (mode, selectionEmpty, ancestors, endPointsInSameBlock) {
		this.setDisabled(mode === 'textmode' && !this.textMode);
		if (!this.disabled) {
			if (!this.noAutoUpdate) {
				this.setDisabled(!this.isInContext(mode, selectionEmpty, ancestors));
			}
			this.plugins['onUpdateToolbar'](this, mode, selectionEmpty, ancestors, endPointsInSameBlock);
		}
	}
});
Ext.reg('htmlareabutton', Ext.ux.HTMLAreaButton);
/*
 * Ext.ux.Toolbar.HTMLAreaToolbarText extends Ext.Toolbar.TextItem
 */
Ext.ux.Toolbar.HTMLAreaToolbarText = Ext.extend(Ext.Toolbar.TextItem, {
	/*
	 * Constructor
	 */
	initComponent: function () {
		Ext.ux.Toolbar.HTMLAreaToolbarText.superclass.initComponent.call(this);
		this.addListener({
			afterrender: {
				fn: this.initEventListeners,
				single: true
			}
		});
	},
	/*
	 * Initialize listeners
	 */
	initEventListeners: function () {
			// Monitor toolbar updates in order to refresh the state of the button
		this.mon(this.getToolbar(), 'HTMLAreaEventToolbarUpdate', this.onUpdateToolbar, this);
	},
	/*
	 * Get a reference to the editor
	 */
	getEditor: function() {
		return RTEarea[this.ownerCt.editorId].editor;
	},
	/*
	 * Get a reference to the toolbar
	 */
	getToolbar: function() {
		return this.ownerCt;
	},
	/*
	 * Handler invoked when the toolbar is updated
	 */
	onUpdateToolbar: function (mode, selectionEmpty, ancestors, endPointsInSameBlock) {
		this.setDisabled(mode === 'textmode' && !this.textMode);
		if (!this.disabled) {
			this.plugins['onUpdateToolbar'](this, mode, selectionEmpty, ancestors, endPointsInSameBlock);
		}
	}
});
Ext.reg('htmlareatoolbartext', Ext.ux.Toolbar.HTMLAreaToolbarText);
/*
 * Ext.ux.form.HTMLAreaCombo extends Ext.form.ComboBox
 */
Ext.ux.form.HTMLAreaCombo = Ext.extend(Ext.form.ComboBox, {
	/*
	 * Constructor
	 */
	initComponent: function () {
		Ext.ux.form.HTMLAreaCombo.superclass.initComponent.call(this);
		this.addEvents(
			/*
			 * @event HTMLAreaEventHotkey
			 * Fires when a hotkey configured for the combo is pressed
			 */
			'HTMLAreaEventHotkey'
		);
		this.addListener({
			afterrender: {
				fn: this.initEventListeners,
				single: true
			}
		});
	},
	/*
	 * Initialize listeners
	 */
	initEventListeners: function () {
		this.addListener({
			select: {
				fn: this.onComboSelect
			},
			specialkey: {
				fn: this.onSpecialKey
			},
			HTMLAreaEventHotkey: {
				fn: this.onHotKey
			},
			beforedestroy: {
				fn: this.onBeforeDestroy,
				single: true
			}
		});
			// Monitor toolbar updates in order to refresh the state of the combo
		this.mon(this.getToolbar(), 'HTMLAreaEventToolbarUpdate', this.onUpdateToolbar, this);
			// Monitor framework becoming ready
		this.mon(this.getToolbar().ownerCt, 'HTMLAreaEventFrameworkReady', this.onFrameworkReady, this);
	},
	/*
	 * Get a reference to the editor
	 */
	getEditor: function() {
		return RTEarea[this.ownerCt.editorId].editor;
	},
	/*
	 * Get a reference to the toolbar
	 */
	getToolbar: function() {
		return this.ownerCt;
	},
	/*
	 * Handler invoked when an item is selected in the dropdown list
	 */
	onComboSelect: function (combo, record, index) {
		if (!combo.disabled) {
			var editor = this.getEditor();
				// In IE, reclaim lost focus on the editor iframe and restore the bookmarked selection
			if (Ext.isIE) {
				if (!Ext.isEmpty(this.savedRange)) {
					editor.getSelection().selectRange(this.savedRange);
					this.savedRange = null;
				}
			}
				// Invoke the plugin onChange handler
			this.plugins[this.action](editor, combo, record, index);
				// In IE, bookmark the updated selection as the editor will be loosing focus
			if (Ext.isIE) {
				this.savedRange = editor.getSelection().createRange();
				this.triggered = true;
			}
			if (Ext.isOpera) {
				editor.focus();
			}
			this.getToolbar().update();
		}
		return false;
	},
	/*
	 * Handler invoked when the trigger element is clicked
	 * In IE, need to reclaim lost focus for the editor in order to restore the selection
	 */
	onTriggerClick: function () {
		Ext.ux.form.HTMLAreaCombo.superclass.onTriggerClick.call(this);
			// In IE, avoid focus being stolen and selection being lost
		if (Ext.isIE) {
			this.triggered = true;
			this.getEditor().focus();
		}
	},
	/*
	 * Handler invoked when the list of options is clicked in
	 */
	onViewClick: function (doFocus) {
			// Avoid stealing focus from the editor
		Ext.ux.form.HTMLAreaCombo.superclass.onViewClick.call(this, false);
	},
	/*
	 * Handler invoked in IE when the mouse moves out of the editor iframe
	 */
	saveSelection: function (event) {
		var editor = this.getEditor();
		if (editor.document.hasFocus()) {
			this.savedRange = editor.getSelection().createRange();
		}
	},
	/*
	 * Handler invoked in IE when the editor gets the focus back
	 */
	restoreSelection: function (event) {
		if (!Ext.isEmpty(this.savedRange) && this.triggered) {
			this.getEditor().getSelection().selectRange(this.savedRange);
			this.triggered = false;
		}
	},
	/*
	 * Handler invoked when the enter key is pressed while the combo has focus
	 */
	onSpecialKey: function (combo, event) {
		if (event.getKey() == event.ENTER) {
			event.stopEvent();
                }
		return false;
	},
	/*
	 * Handler invoked when a hot key configured for this dropdown list is pressed
	 */
	onHotKey: function (key) {
		if (!this.disabled) {
			this.plugins.onHotKey(this.getEditor(), key);
			if (Ext.isOpera) {
				this.getEditor().focus();
			}
			this.getToolbar().update();
		}
		return false;
	},
	/*
	 * Handler invoked when the toolbar is updated
	 */
	onUpdateToolbar: function (mode, selectionEmpty, ancestors, endPointsInSameBlock) {
		this.setDisabled(mode === 'textmode' && !this.textMode);
		if (!this.disabled) {
			this.plugins['onUpdateToolbar'](this, mode, selectionEmpty, ancestors, endPointsInSameBlock);
		}
	},
	/*
	 * The iframe must have been rendered
	 */
	onFrameworkReady: function () {
		var iframe = this.getEditor().iframe;
			// Close the combo on a click in the iframe
			// Note: ExtJS is monitoring events only on the parent window
		this.mon(Ext.get(iframe.document.documentElement), 'click', this.collapse, this);
			// Special handling for combo stealing focus in IE
		if (Ext.isIE) {
				// Take a bookmark in case the editor looses focus by activation of this combo
			this.mon(iframe.getEl(), 'mouseleave', this.saveSelection, this);
				// Restore the selection if combo was triggered
			this.mon(iframe.getEl(), 'focus', this.restoreSelection, this);
		}
	},
	/*
	 * Cleanup
	 */
	onBeforeDestroy: function () {
		this.savedRange = null;
		this.getStore().removeAll();
		this.getStore().destroy();
	}
});
Ext.reg('htmlareacombo', Ext.ux.form.HTMLAreaCombo);
/***************************************************
 *  EDITOR FRAMEWORK
 ***************************************************/
/*
 * HTMLArea.Toolbar extends Ext.Container
 */
HTMLArea.Toolbar = Ext.extend(Ext.Container, {
	/*
	 * Constructor
	 */
	initComponent: function () {
		HTMLArea.Toolbar.superclass.initComponent.call(this);
		this.addEvents(
			/*
			 * @event HTMLAreaEventToolbarUpdate
			 * Fires when the toolbar is updated
			 */
			'HTMLAreaEventToolbarUpdate'
		);
			// Build the deferred toolbar update task
		this.updateLater = new Ext.util.DelayedTask(this.update, this);
			// Add the toolbar items
		this.addItems();
		this.addListener({
			afterrender: {
				fn: this.initEventListeners,
				single: true
			}
		});
	},
	/*
	 * Initialize listeners
	 */
	initEventListeners: function () {
		this.addListener({
			beforedestroy: {
				fn: this.onBeforeDestroy,
				single: true
			}
		});
			// Monitor editor becoming ready
		this.mon(this.getEditor(), 'HTMLAreaEventEditorReady', this.update, this, {single: true});
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
	 * Create the toolbar items based on editor toolbar configuration
	 */
	addItems: function () {
		var editor = this.getEditor();
			// Walk through the editor toolbar configuration nested arrays: [ toolbar [ row [ group ] ] ]
		var firstOnRow = true;
		var firstInGroup = true;
		Ext.each(editor.config.toolbar, function (row) {
			if (!firstOnRow) {
					// If a visible item was added to the previous line
				this.add({
					xtype: 'tbspacer',
					cls: 'x-form-clear-left'
				});
			}
			firstOnRow = true;
				// Add the groups
			Ext.each(row, function (group) {
					// To do: this.config.keepButtonGroupTogether ...
				if (!firstOnRow && !firstInGroup) {
						// If a visible item was added to the line
					this.add({
						xtype: 'tbseparator',
						cls: 'separator'
					});
				}
				firstInGroup = true;
					// Add each item
				Ext.each(group, function (item) {
					if (item == 'space') {
						this.add({
							xtype: 'tbspacer',
							cls: 'space'
						});
					} else {
							// Get the item's config as registered by some plugin
						var itemConfig = editor.config.buttonsConfig[item];
						if (!Ext.isEmpty(itemConfig)) {
							itemConfig.id = this.editorId + '-' + itemConfig.id;
							this.add(itemConfig);
							firstInGroup = firstInGroup && itemConfig.hidden;
							firstOnRow = firstOnRow && firstInGroup;
						}
					}
					return true;
				}, this);
				return true;
			}, this);
			return true;
		}, this);
		this.add({
			xtype: 'tbspacer',
			cls: 'x-form-clear-left'
		});
	},
	/*
	 * Retrieve a toolbar item by itemId
	 */
	getButton: function (buttonId) {
		return this.find('itemId', buttonId)[0];
	},
	/*
	 * Update the state of the toolbar
	 */
	update: function() {
		var editor = this.getEditor(),
			mode = editor.getMode(),
			selection = editor.getSelection(),
			selectionEmpty = true,
			ancestors = null,
			endPointsInSameBlock = true;
		if (editor.getMode() === 'wysiwyg') {
			selectionEmpty = selection.isEmpty();
			ancestors = selection.getAllAncestors();
			endPointsInSameBlock = selection.endPointsInSameBlock();
		}
		this.fireEvent('HTMLAreaEventToolbarUpdate', mode, selectionEmpty, ancestors, endPointsInSameBlock);
	},
	/*
	 * Cleanup
	 */
	onBeforeDestroy: function () {
		this.removeAll(true);
		return true;
	}
});
Ext.reg('htmlareatoolbar', HTMLArea.Toolbar);
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
			Ext.each(this.nestedParentElements.sorted, function (nested) {
				var nestedElement = Ext.get(nested);
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
			}, this);
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
			if (Ext.isString(this.autoEl)) {
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
			this.getEditor()._doc = this.document;
			this.getEditor()._iframe = iframe;
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
			Ext.each(this.config.customTags, function (tag) {
				this.document.createElement(tag);
			}, this);
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
		if (this.config.pageStyle) {
			var link = this.document.getElementsByTagName('link')[1];
			if (!link) {
				link = this.document.createElement('link');
				link.rel = 'stylesheet';
				link.type = 'text/css';
				link.href = ((Ext.isGecko && navigator.productSub < 2010072200 && !/^https?:\/{2}/.test(this.config.pageStyle)) ? this.config.baseURL : '') + this.config.pageStyle;
				head.appendChild(link);
			}
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
		Ext.iterate(this.config.hotKeyList, function (key) {
			if (key.length == 1) {
				hotKeys += key.toUpperCase();
			}
		});
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
			this.getEditor().cleanAppleStyleSpans.defer(50, this.getEditor(), [this.getEditor().document.body]);
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
				editor.insertNodeAtSelection(brNode);
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
	/*
	 * Cleanup
	 */
	onBeforeDestroy: function () {
			// ExtJS KeyMap object makes IE leak memory
			// Nullify EXTJS private handlers
		Ext.each(this.keyMap.bindings, function (binding, index) {
			this.keyMap.bindings[index] = null;
		}, this);
		this.keyMap.handleKeyDown = null;
		Ext.each(this.hotKeyMap.bindings, function (binding, index) {
			this.hotKeyMap.bindings[index] = null;
		}, this);
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
		this.getEditor()._doc = null;
		this.getEditor()._iframe = null;
		Ext.each(this.nestedParentElements.sorted, function (nested) {
			Ext.get(nested).purgeAllListeners();
			Ext.get(nested).dom = null;
		});
		Ext.destroy(this.autoEl, this.el, this.resizeEl, this.positionEl);
		return true;
	}
});
Ext.reg('htmlareaiframe', HTMLArea.Iframe);
/*
 * HTMLArea.StatusBar extends Ext.Container
 */
HTMLArea.StatusBar = Ext.extend(Ext.Container, {
	/*
	 * Constructor
	 */
	initComponent: function () {
		HTMLArea.StatusBar.superclass.initComponent.call(this);
			// Build the deferred word count update task
		this.updateWordCountLater = new Ext.util.DelayedTask(this.updateWordCount, this);
		this.addListener({
			render: {
				fn: this.addComponents,
				single: true
			},
			afterrender: {
				fn: this.initEventListeners,
				single: true
			}
		});
	},
	/*
	 * Initialize listeners
	 */
	initEventListeners: function () {
		this.addListener({
			beforedestroy: {
				fn: this.onBeforeDestroy,
				single: true
			}
		});
			// Monitor toolbar updates in order to refresh the contents of the statusbar
			// The toolbar must have been rendered
		this.mon(this.ownerCt.toolbar, 'HTMLAreaEventToolbarUpdate', this.onUpdateToolbar, this);
			// Monitor editor changing mode
		this.mon(this.getEditor(), 'HTMLAreaEventModeChange', this.onModeChange, this);
			// Monitor word count change
		this.mon(this.ownerCt.iframe, 'HTMLAreaEventWordCountChange', this.onWordCountChange, this);
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
	 * Create span elements to display when the status bar tree or a message when the editor is in text mode
	 */
	addComponents: function () {
		this.statusBarWordCount = Ext.DomHelper.append(this.getEl(), {
			id: this.editorId + '-statusBarWordCount',
			tag: 'span',
			cls: 'statusBarWordCount',
			html: '&nbsp;'
		}, true);
		this.statusBarTree = Ext.DomHelper.append(this.getEl(), {
			id: this.editorId + '-statusBarTree',
			tag: 'span',
			cls: 'statusBarTree',
			html: HTMLArea.localize('Path') + ': '
		}, true).setVisibilityMode(Ext.Element.DISPLAY).setVisible(true);
		this.statusBarTextMode = Ext.DomHelper.append(this.getEl(), {
			id: this.editorId + '-statusBarTextMode',
			tag: 'span',
			cls: 'statusBarTextMode',
			html: HTMLArea.localize('TEXT_MODE')
		}, true).setVisibilityMode(Ext.Element.DISPLAY).setVisible(false);
	},
	/*
	 * Clear the status bar tree
	 */
	clear: function () {
		this.statusBarTree.removeAllListeners();
		Ext.each(this.statusBarTree.query('a'), function (node) {
			Ext.QuickTips.unregister(node);
			Ext.get(node).dom.ancestor = null;
			Ext.destroy(node);
		});
		this.statusBarTree.update('');
		this.setSelection(null);
	},
	/*
	 * Flag indicating that the status bar should not be updated on this toolbar update
	 */
	noUpdate: false,
	/*
	 * Update the status bar
	 */
	onUpdateToolbar: function (mode, selectionEmpty, ancestors, endPointsInSameBlock) {
		if (mode === 'wysiwyg' && !this.noUpdate) {
			var text,
				language,
				languageObject = this.getEditor().getPlugin('Language'),
				classes = new Array(),
				classText;
			this.clear();
			var path = Ext.DomHelper.append(this.statusBarTree, {
				tag: 'span',
				html: HTMLArea.localize('Path') + ': '
			},true);
			Ext.each(ancestors, function (ancestor, index) {
				if (!ancestor) {
					return true;
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
					for (var j = 0, n = classes.length; j < n; ++j) {
						if (!HTMLArea.reservedClassNames.test(classes[j])) {
							classText += '.' + classes[j];
						}
					}
					text += classText;
				}
				var element = Ext.DomHelper.insertAfter(path, {
					tag: 'a',
					href: '#',
					'ext:qtitle': HTMLArea.localize('statusBarStyle'),
					'ext:qtip': ancestor.style.cssText.split(';').join('<br />'),
					html: text
				}, true);
					// Ext.DomHelper does not honour the custom attribute
				element.dom.ancestor = ancestor;
				element.on('click', this.onClick, this);
				element.on('mousedown', this.onClick, this);
				if (!Ext.isOpera) {
					element.on('contextmenu', this.onContextMenu, this);
				}
				if (index) {
					Ext.DomHelper.insertAfter(element, {
						tag: 'span',
						html: String.fromCharCode(0xbb)
					});
				}
			}, this);
		}
		this.updateWordCount();
		this.noUpdate = false;
	},
	/*
	 * Handler when the word count may have changed
	 */
	onWordCountChange: function(delay) {
		this.updateWordCountLater.delay(delay ? delay : 0);
	},
	/*
	 * Update the word count
	 */
	updateWordCount: function() {
		var wordCount = 0;
		if (this.getEditor().getMode() == 'wysiwyg') {
				// Get the html content
			var text = this.getEditor().getHTML();
			if (!Ext.isEmpty(text)) {
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
		this.statusBarWordCount.dom.innerHTML = wordCount ? ( wordCount + ' ' + HTMLArea.localize((wordCount == 1) ? 'word' : 'words')) : '&nbsp;';
	},
	/*
	 * Adapt status bar to current editor mode
	 *
	 * @param	string	mode: the mode to which the editor got switched to
	 */
	onModeChange: function (mode) {
		switch (mode) {
			case 'wysiwyg':
				this.statusBarTextMode.setVisible(false);
				this.statusBarTree.setVisible(true);
				break;
			case 'textmode':
			default:
				this.statusBarTree.setVisible(false);
				this.statusBarTextMode.setVisible(true);
				break;
		}
	},
	/*
	 * Refrence to the element last selected on the status bar
	 */
	selected: null,
	/*
	 * Get the status bar selection
	 */
	getSelection: function() {
		return this.selected;
	},
	/*
	 * Set the status bar selection
	 *
	 * @param	object	element: set the status bar selection to the given element
	 */
	setSelection: function (element) {
		this.selected = element ? element : null;
	},
	/*
	 * Select the element that was clicked in the status bar and set the status bar selection
	 */
	selectElement: function (element) {
		var editor = this.getEditor();
		element.blur();
		if (!HTMLArea.isIEBeforeIE9) {
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
	/*
	 * Click handler
	 */
	onClick: function (event, element) {
		this.selectElement(element);
		event.stopEvent();
		return false;
	},
	/*
	 * ContextMenu handler
	 */
	onContextMenu: function (event, target) {
		this.selectElement(target);
		return this.getEditor().getPlugin('ContextMenu') ? this.getEditor().getPlugin('ContextMenu').show(event, target.ancestor) : false;
	},
	/*
	 * Cleanup
	 */
	onBeforeDestroy: function() {
		this.clear();
		this.removeAll(true);
		Ext.destroy(this.statusBarTree, this.statusBarTextMode);
		return true;
	}
});
Ext.reg('htmlareastatusbar', HTMLArea.StatusBar);
/*
 * HTMLArea.Framework extends Ext.Panel
 */
HTMLArea.Framework = Ext.extend(Ext.Panel, {
	/*
	 * Constructor
	 */
	initComponent: function () {
		HTMLArea.Framework.superclass.initComponent.call(this);
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
		if (!this.isNested || HTMLArea.util.TYPO3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
			this.render(this.textArea.parent(), this.textArea.id);
		} else {
				// Clone the array of nested tabs and inline levels instead of using a reference as HTMLArea.util.TYPO3.accessParentElements will modify the array
			var parentElements = [].concat(this.nestedParentElements.sorted);
				// Walk through all nested tabs and inline levels to get correct sizes
			HTMLArea.util.TYPO3.accessParentElements(parentElements, 'args[0].render(args[0].textArea.parent(), args[0].textArea.id)', [this]);
		}
	},
	/*
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
		var form = this.textArea.dom.form;
		if (form) {
			if (Ext.isFunction(form.onreset)) {
				if (typeof(form.htmlAreaPreviousOnReset) == 'undefined') {
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
	 * Flag set to true when the framework is ready
	 */
	ready: false,
	/*
	 * All nested tabs and inline levels in the sorting order they were applied
	 * Should be set in config
	 */
	nestedParentElements: {},
	/*
	 * Whether the framework should be made resizable
	 * May be set in config
	 */
	resizable: false,
	/*
	 * Maximum height to which the framework may resized (in pixels)
	 * May be set in config
	 */
	maxHeight: 2000,
	/*
	 * Initial textArea dimensions
	 * Should be set in config
	 */
	textAreaInitialSize: {
		width: 0,
		contextWidth: 0,
		height: 0
	},
	/*
	 * doLayout will fail if inside a hidden tab or inline element
	 */
	doLayout: function () {
		if (!this.isNested || HTMLArea.util.TYPO3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
			HTMLArea.Framework.superclass.doLayout.call(this);
		} else {
				// Clone the array of nested tabs and inline levels instead of using a reference as HTMLArea.util.TYPO3.accessParentElements will modify the array
			var parentElements = [].concat(this.nestedParentElements.sorted);
				// Walk through all nested tabs and inline levels to get correct sizes
			HTMLArea.util.TYPO3.accessParentElements(parentElements, 'HTMLArea.Framework.superclass.doLayout.call(args[0])', [this]);
		}
	},
	/*
	 * onLayout will fail if inside a hidden tab or inline element
	 */
	onLayout: function () {
		if (!this.isNested || HTMLArea.util.TYPO3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
			HTMLArea.Framework.superclass.onLayout.call(this);
		} else {
				// Clone the array of nested tabs and inline levels instead of using a reference as HTMLArea.util.TYPO3.accessParentElements will modify the array
			var parentElements = [].concat(this.nestedParentElements.sorted);
				// Walk through all nested tabs and inline levels to get correct sizes
				HTMLArea.util.TYPO3.accessParentElements(parentElements, 'HTMLArea.Framework.superclass.onLayout.call(args[0])', [this]);
		}
	},
	/*
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
	/*
	 * Resize the framework when the resizer handles are used
	 */
	onHtmlAreaResize: function (resizer, width, height, event) {
			// Set width first as it may change the height of the toolbar and of the statusBar
		this.setWidth(width);
			// Set height of iframe and textarea
		this.iframe.setHeight(this.getInnerHeight());
		this.textArea.setSize(this.getInnerWidth(), this.getInnerHeight());
	},
	/*
	 * Size the iframe according to initial textarea size as set by Page and User TSConfig
	 */
	onWindowResize: function (width, height) {
		if (!this.isNested || HTMLArea.util.TYPO3.allElementsAreDisplayed(this.nestedParentElements.sorted)) {
			this.resizeFramework(width, height);
		} else {
				// Clone the array of nested tabs and inline levels instead of using a reference as HTMLArea.util.TYPO3.accessParentElements will modify the array
			var parentElements = [].concat(this.nestedParentElements.sorted);
				// Walk through all nested tabs and inline levels to get correct sizes
			HTMLArea.util.TYPO3.accessParentElements(parentElements, 'args[0].resizeFramework(args[1], args[2])', [this, width, height]);
		}
	},
	/*
	 * Resize the framework to its initial size
	 */
	resizeFramework: function (width, height) {
		var frameworkHeight = parseInt(this.textAreaInitialSize.height);
		if (this.textAreaInitialSize.width.indexOf('%') === -1) {
				// Width is specified in pixels
			var frameworkWidth = parseInt(this.textAreaInitialSize.width) - this.getFrameWidth();
		} else {
				// Width is specified in %
			if (Ext.isNumber(width)) {
					// Framework sizing on actual window resize
				var frameworkWidth = parseInt(((width - this.textAreaInitialSize.wizardsWidth - (this.fullScreen ? 10 : Ext.getScrollBarWidth()) - this.getBox().x - 15) * parseInt(this.textAreaInitialSize.width))/100);
			} else {
					// Initial framework sizing
				var frameworkWidth = parseInt(((HTMLArea.util.TYPO3.getWindowSize().width - this.textAreaInitialSize.wizardsWidth - (this.fullScreen ? 10 : Ext.getScrollBarWidth()) - this.getBox().x - 15) * parseInt(this.textAreaInitialSize.width))/100);
			}
		}
		if (this.resizable) {
			this.resizer.resizeTo(frameworkWidth, frameworkHeight);
		} else {
			this.setSize(frameworkWidth, frameworkHeight);
			this.doLayout();
		}
	},
	/*
	 * Resize the framework components
	 */
	onFrameworkResize: function () {
		this.iframe.setSize(this.getInnerWidth(), this.getInnerHeight());
		this.textArea.setSize(this.getInnerWidth(), this.getInnerHeight());
	},
	/*
	 * Adjust the height to the changing size of the statusbar when the textarea is shown
	 */
	onTextAreaShow: function () {
		this.iframe.setHeight(this.getInnerHeight());
		this.textArea.setHeight(this.getInnerHeight());
	},
	/*
	 * Adjust the height to the changing size of the statusbar when the iframe is shown
	 */
	onIframeShow: function () {
		if (this.getInnerHeight() <= 0) {
			this.onWindowResize();
		} else {
			this.iframe.setHeight(this.getInnerHeight());
			this.textArea.setHeight(this.getInnerHeight());
		}
	},
	/*
	 * Calculate the height available for the editing iframe
	 */
	getInnerHeight: function () {
		return this.getSize().height - this.toolbar.getHeight() - this.statusBar.getHeight() -  5;
	},
	/*
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
	/*
	 * Handler invoked if we are inside a form and the form is reset
	 * On reset, re-initialize the HTMLArea content and update the toolbar
	 */
	onReset: function (event) {
		this.getEditor().setHTML(this.textArea.getValue());
		this.toolbar.update();
			// Invoke previous reset handlers, if any
		var htmlAreaPreviousOnReset = event.getTarget().dom.htmlAreaPreviousOnReset;
		if (typeof(htmlAreaPreviousOnReset) != 'undefined') {
			Ext.each(htmlAreaPreviousOnReset, function (onReset) {
				onReset();
				return true;
			});
		}
	},
	/*
	 * Cleanup on framework destruction
	 */
	onBeforeDestroy: function () {
		Ext.EventManager.removeResizeListener(this.onWindowResize, this);
			// Cleaning references to DOM in order to avoid IE memory leaks
		var form = this.textArea.dom.form;
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
Ext.reg('htmlareaframework', HTMLArea.Framework);
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
		Ext.iterate(this.config.plugin, function (plugin) {
			if (this.config.plugin[plugin]) {
				this.registerPlugin(plugin);
			}
		}, this);
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
						src: Ext.isGecko ? 'javascript:void(0);' : (Ext.isWebKit ? 'javascript: \'' + HTMLArea.htmlEncode(this.config.documentType + this.config.blankDocument) + '\'' : HTMLArea.editorUrl + 'popups/blank.html')
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
		Ext.iterate(RTEarea, function (editorId, RTE) {
			if (!Ext.isDefined(RTE.editor) || (RTE.editor.isNested && !HTMLArea.util.TYPO3.allElementsAreDisplayed(RTE.editor.nestedParentElements.sorted))) {
				return true;
			} else {
				RTE.editor.focus();
				return false;
			}
		}, this);
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
		Ext.iterate(this.plugins, function(pluginId) {
			this.getPlugin(pluginId).onMode(this.mode);
		}, this);
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
		for (var i = 0, n = position.length; current && i < n; i++) {
			var target = position[i];
			if (normalized) {
				var currentIndex = -1;
				for (var j = 0, m = current.childNodes.length; j < m; j++) {
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
		if (typeof(plugin) !== 'undefined' && Ext.isFunction(plugin)) {
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
		Ext.iterate(this.plugins, function (pluginId) {
			var plugin = this.getPlugin(pluginId);
			plugin.onGenerate();
		}, this);
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
		Ext.iterate(this.plugins, function (pluginId) {
			this.unRegisterPlugin(pluginId);
		}, this);
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
HTMLArea.Ajax = function (config) {
	Ext.apply(this, config);
};
HTMLArea.Ajax = Ext.extend(HTMLArea.Ajax, {
	/*
	 * Load a Javascript file asynchronously
	 *
	 * @param	string		url: url of the file to load
	 * @param	function	callBack: the callBack function
	 * @param	object		scope: scope of the callbacks
	 *
	 * @return	boolean		true on success of the request submission
	 */
	getJavascriptFile: function (url, callback, scope) {
		var success = false;
		var self = this;
		Ext.Ajax.request({
			method: 'GET',
			url: url,
			callback: callback,
			success: function (response) {
				success = true;
			},
			failure: function (response) {
				self.editor.inhibitKeyboardInput = false;
				self.editor.appendToLog('HTMLArea.Ajax', 'getJavascriptFile', 'Unable to get ' + url + ' . Server reported ' + response.status, 'error');
			},
			scope: scope
		});
		return success;
	},
	/*
	 * Post data to the server
	 *
	 * @param	string		url: url to post data to
	 * @param	object		data: data to be posted
	 * @param	function	callback: function that will handle the response returned by the server
	 * @param	object		scope: scope of the callbacks
	 *
	 * @return	boolean		true on success
	 */
	postData: function (url, data, callback, scope) {
		var success = false;
		var self = this;
		data.charset = this.editor.config.typo3ContentCharset ? this.editor.config.typo3ContentCharset : 'utf-8';
		var params = '';
		Ext.iterate(data, function (parameter, value) {
			params += (params.length ? '&' : '') + parameter + '=' + encodeURIComponent(value);
		});
		params += this.editor.config.RTEtsConfigParams;
		Ext.Ajax.request({
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			url: url,
			params: params,
			callback: Ext.isFunction(callback) ? callback: function (options, success, response) {
				if (!success) {
					self.editor.appendToLog('HTMLArea.Ajax', 'postData', 'Post request to ' + url + ' failed. Server reported ' + response.status, 'error');
				}
			},
			success: function (response) {
				success = true;
			},
			failure: function (response) {
				self.editor.appendToLog('HTMLArea.Ajax', 'postData', 'Unable to post ' + url + ' . Server reported ' + response.status, 'error');
			},
			scope: scope
		});
		return success;
	}
});
/***************************************************
 * HTMLArea.util.TYPO3: Utility functions for dealing with tabs and inline elements in TYPO3 forms
 ***************************************************/
HTMLArea.util.TYPO3 = function () {
	return {
		/*
		 * Simplify the array of nested levels. Create an indexed array with the correct names of the elements.
		 *
		 * @param	object		nested: The array with the nested levels
		 * @return	object		The simplified array
		 * @author	Oliver Hader <oh@inpublica.de>
		 */
		simplifyNested: function(nested) {
			var i, type, level, elementId, max, simplifiedNested=[],
				elementIdSuffix = {
					tab: '-DIV',
					inline: '_fields',
					flex: '-content'
				};
			if (nested && nested.length) {
				if (nested[0][0]=='inline') {
					nested = inline.findContinuedNestedLevel(nested, nested[0][1]);
				}
				for (i=0, max=nested.length; i<max; i++) {
					type = nested[i][0];
					level = nested[i][1];
					elementId = level + elementIdSuffix[type];
					if (Ext.get(elementId)) {
						simplifiedNested.push(elementId);
					}
				}
			}
			return simplifiedNested;
		},
		/*
		 * Access an inline relational element or tab menu and make it "accessible".
		 * If a parent or ancestor object has the style "display: none", offsetWidth & offsetHeight are '0'.
		 *
		 * @params	arry		parentElements: array of parent elements id's; note that this input array will be modified
		 * @params	object		callbackFunc: A function to be called, when the embedded objects are "accessible".
		 * @params	array		args: array of arguments
		 * @return	object		An object returned by the callbackFunc.
		 * @author	Oliver Hader <oh@inpublica.de>
		 */
		accessParentElements: function (parentElements, callbackFunc, args) {
			var result = {};
			if (parentElements.length) {
				var currentElement = parentElements.pop();
				currentElement = Ext.get(currentElement);
				var actionRequired = (currentElement.getStyle('display') == 'none');
				if (actionRequired) {
					var visibility = currentElement.dom.style.visibility;
					var position = currentElement.dom.style.position;
					var top = currentElement.dom.style.top;
					var display = currentElement.dom.style.display;
					var className = currentElement.dom.parentNode.className;
					currentElement.setStyle({
						visibility: 'hidden',
						position: 'absolute',
						top: '-10000px',
						display: ''
					});
					currentElement.dom.parentElement.className = '';
				}
				result = this.accessParentElements(parentElements, callbackFunc, args);
				if (actionRequired) {
					currentElement.dom.style.visibility = visibility;
					currentElement.dom.style.position = position;
					currentElement.dom.style.top = top;
					currentElement.dom.style.display = display;
					currentElement.dom.parentNode.className = className;
				}
			} else {
				result = eval(callbackFunc);
			}
			return result;
		},
		/*
		 * Check if all elements in input array are currently displayed
		 *
		 * @param	array		elements: array of element id's
		 * @return	boolean		true if all elements are displayed
		 */
		allElementsAreDisplayed: function(elements) {
			var allDisplayed = true;
			Ext.each(elements, function (element) {
				allDisplayed = Ext.get(element).getStyle('display') != 'none';
				return allDisplayed;
			});
			return allDisplayed;
		},
		/*
		 * Get current size of window
		 *
		 * @return	object		width and height of window
		 */
		getWindowSize: function () {
			if (Ext.isIE) {
				var size = Ext.getBody().getSize();
			} else {
				var size = {
					width: window.innerWidth,
					height: window.innerHeight
				};
			}
				// Subtract the docheader height from the calculated window height
			var docHeader = Ext.get('typo3-docheader');
			if (docHeader) {
				size.height -= docHeader.getHeight();
				docHeader.dom = null;
			}
			return size;
		}
	}
}();
 /*
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.forceRedraw = function() {
	this.appendToLog('HTMLArea.Editor', 'forceRedraw', 'Reference to deprecated method', 'warn');
	this.htmlArea.doLayout();
};
/*
 * Surround the currently selected HTML source code with the given tags.
 * Delete the selection, if any.
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.surroundHTML = function(startTag,endTag) {
	this.appendToLog('HTMLArea.Editor', 'surroundHTML', 'Reference to deprecated method', 'warn');
	this.getSelection().surroundHtml(startTag, endTag);
};

/*
 * Change the tag name of a node.
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.convertNode = function(el,newTagName) {
	this.appendToLog('HTMLArea.Editor', 'surroundHTML', 'Reference to deprecated method', 'warn');
	return HTMLArea.DOM.convertNode(el, newTagName);
};

/*
 * This function removes the given markup element
 *
 * @param	object	element: the inline element to be removed, content and selection being preserved
 *
 * @return	void
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.removeMarkup = function(element) {
	this.appendToLog('HTMLArea.Editor', 'removeMarkup', 'Reference to deprecated method', 'warn');
	this.getDomNode().removeMarkup(element);
};
/*
 * Return true if we have some selected content
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.hasSelectedText = function() {
	this.appendToLog('HTMLArea.Editor', 'hasSelectedText', 'Reference to deprecated method', 'warn');
	return !this.getSelection().isEmpty();
};

/*
 * Get an array with all the ancestor nodes of the selection
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.getAllAncestors = function() {
	this.appendToLog('HTMLArea.Editor', 'getAllAncestors', 'Reference to deprecated method', 'warn');
	return this.getSelection().getAllAncestors();
};

/*
 * Get the block elements containing the start and the end points of the selection
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.getEndBlocks = function(selection) {
	this.appendToLog('HTMLArea.Editor', 'getEndBlocks', 'Reference to deprecated method', 'warn');
	return this.getSelection().getEndBlocks();
};

/*
 * This function determines if the end poins of the current selection are within the same block
 *
 * @return	boolean	true if the end points of the current selection are inside the same block element
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.endPointsInSameBlock = function() {
	this.appendToLog('HTMLArea.Editor', 'endPointsInSameBlock', 'Reference to deprecated method', 'warn');
	return this.getSelection().endPointsInSameBlock();
};

/*
 * Get the deepest ancestor of the selection that is of the specified type
 * Borrowed from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype._getFirstAncestor = function(sel,types) {
	this.appendToLog('HTMLArea.Editor', '_getFirstAncestor', 'Reference to deprecated method', 'warn');
	return this.getSelection().getFirstAncestorOfType(types);
};
/*
 * Get the node whose contents are currently fully selected
 *
 * @param 	array		selection: the current selection
 * @param 	array		range: the range of the current selection
 * @param 	array		ancestors: the array of ancestors node of the current selection
 *
 * @return	object		the fully selected node, if any, null otherwise
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.getFullySelectedNode = function (selection, range, ancestors) {
	this.appendToLog('HTMLArea.Editor', 'getFullySelectedNode', 'Reference to deprecated method', 'warn');
	return this.getSelection().getFullySelectedNode();
};
/*
 * Intercept some native execCommand commands
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.execCommand = function(cmdID, UI, param) {
	this.appendToLog('HTMLArea.Editor', 'execCommand', 'Reference to deprecated method', 'warn');
	return this.getSelection().execCommand(cmdID, UI, param);
};

/***************************************************
 *  UTILITY FUNCTIONS
 ***************************************************/
Ext.apply(HTMLArea.util, {
	/*
	 * Perform HTML encoding of some given string
	 * Borrowed in part from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
	 */
	htmlDecode: function (str) {
		str = str.replace(/&lt;/g, '<').replace(/&gt;/g, '>');
		str = str.replace(/&nbsp;/g, '\xA0'); // Decimal 160, non-breaking-space
		str = str.replace(/&quot;/g, '\x22');
		str = str.replace(/&#39;/g, "'");
		str = str.replace(/&amp;/g, '&');
		return str;
	},
	htmlEncode: function (str) {
		if (typeof(str) != 'string') {
			str = str.toString();
		}
		str = str.replace(/&/g, '&amp;');
		str = str.replace(/</g, '&lt;').replace(/>/g, '&gt;');
		str = str.replace(/\xA0/g, '&nbsp;'); // Decimal 160, non-breaking-space
		str = str.replace(/\x22/g, '&quot;'); // \x22 means '"'
		return str;
	}
});
/*
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.htmlDecode = HTMLArea.util.htmlDecode;
/*
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.htmlEncode = HTMLArea.util.htmlEncode;

/*****************************************************************
 * HTMLArea.DOM: Utility functions for dealing with the DOM tree *
 *****************************************************************/
HTMLArea.DOM = function () {
	return {
		/***************************************************
		*  DOM-RELATED CONSTANTS
		***************************************************/
			// DOM node types
		ELEMENT_NODE: 1,
		ATTRIBUTE_NODE: 2,
		TEXT_NODE: 3,
		CDATA_SECTION_NODE: 4,
		ENTITY_REFERENCE_NODE: 5,
		ENTITY_NODE: 6,
		PROCESSING_INSTRUCTION_NODE: 7,
		COMMENT_NODE: 8,
		DOCUMENT_NODE: 9,
		DOCUMENT_TYPE_NODE: 10,
		DOCUMENT_FRAGMENT_NODE: 11,
		NOTATION_NODE: 12,
		/***************************************************
		*  DOM-RELATED REGULAR EXPRESSIONS
		***************************************************/
		RE_blockTags: /^(address|article|aside|body|blockquote|caption|dd|div|dl|dt|fieldset|footer|form|header|hr|h1|h2|h3|h4|h5|h6|iframe|li|ol|p|pre|nav|noscript|section|table|tbody|td|tfoot|th|thead|tr|ul)$/i,
		RE_noClosingTag: /^(area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)$/i,
		RE_bodyTag: new RegExp('<\/?(body)[^>]*>', 'gi'),
		/***************************************************
		*  STATIC METHODS ON DOM NODE
		***************************************************/
		/*
		 * Determine whether an element node is a block element
		 *
		 * @param	object		element: the element node
		 *
		 * @return	boolean		true, if the element node is a block element
		 */
		isBlockElement: function (element) {
			return element && element.nodeType === HTMLArea.DOM.ELEMENT_NODE && HTMLArea.DOM.RE_blockTags.test(element.nodeName);
		},
		/*
		 * Determine whether an element node needs a closing tag
		 *
		 * @param	object		element: the element node
		 *
		 * @return	boolean		true, if the element node needs a closing tag
		 */
		needsClosingTag: function (element) {
			return element && element.nodeType === HTMLArea.DOM.ELEMENT_NODE && !HTMLArea.DOM.RE_noClosingTag.test(element.nodeName);
		},
		/*
		 * Gets the class names assigned to a node, reserved classes removed
		 *
		 * @param	object		node: the node
		 * @return	array		array of class names on the node, reserved classes removed
		 */
		getClassNames: function (node) {
			var classNames = [];
			if (node) {
				if (node.className && /\S/.test(node.className)) {
					classNames = node.className.trim().split(' ');
				}
				if (HTMLArea.reservedClassNames.test(node.className)) {
					var cleanClassNames = [];
					var j = -1;
					for (var i = 0; i < classNames.length; ++i) {
						if (!HTMLArea.reservedClassNames.test(classNames[i])) {
							cleanClassNames[++j] = classNames[i];
						}
					}
					classNames = cleanClassNames;
				}
			}
			return classNames;
		},
		/*
		 * Check if a class name is in the class attribute of a node
		 *
		 * @param	object		node: the node
		 * @param	string		className: the class name to look for
		 * @param	boolean		substring: if true, look for a class name starting with the given string
		 * @return	boolean		true if the class name was found, false otherwise
		 */
		hasClass: function (node, className, substring) {
			var found = false;
			if (node && node.className) {
				var classes = node.className.trim().split(' ');
				for (var i = classes.length; --i >= 0;) {
					found = ((classes[i] == className) || (substring && classes[i].indexOf(className) == 0));
					if (found) {
						break;
					}
				}
			}
			return found;
		},
		/*
		 * Add a class name to the class attribute of a node
		 *
		 * @param	object		node: the node
		 * @param	string		className: the name of the class to be added
		 * @return	void
		 */
		addClass: function (node, className) {
			if (node) {
				HTMLArea.DOM.removeClass(node, className);
					// Remove classes configured to be incompatible with the class to be added
				if (node.className && HTMLArea.classesXOR && HTMLArea.classesXOR[className] && Ext.isFunction(HTMLArea.classesXOR[className].test)) {
					var classNames = node.className.trim().split(' ');
					for (var i = classNames.length; --i >= 0;) {
						if (HTMLArea.classesXOR[className].test(classNames[i])) {
							HTMLArea.DOM.removeClass(node, classNames[i]);
						}
					}
				}
				if (node.className) {
					node.className += ' ' + className;
				} else {
					node.className = className;
				}
			}
		},
		/*
		 * Remove a class name from the class attribute of a node
		 *
		 * @param	object		node: the node
		 * @param	string		className: the class name to removed
		 * @param	boolean		substring: if true, remove the class names starting with the given string
		 * @return	void
		 */
		removeClass: function (node, className, substring) {
			if (node && node.className) {
				var classes = node.className.trim().split(' ');
				var newClasses = [];
				for (var i = classes.length; --i >= 0;) {
					if ((!substring && classes[i] != className) || (substring && classes[i].indexOf(className) != 0)) {
						newClasses[newClasses.length] = classes[i];
					}
				}
				if (newClasses.length) {
					node.className = newClasses.join(' ');
				} else {
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
		},
		/*
		 * Get the innerText of a given node
		 *
		 * @param	object		node: the node
		 *
		 * @return	string		the text inside the node
		 */
		getInnerText: function (node) {
			return HTMLArea.isIEBeforeIE9 ? node.innerText : node.textContent;;
		},
		/*
		 * Get the block ancestors of a node within a given block
		 *
		 * @param	object		node: the given node
		 * @param	object		withinBlock: the containing node
		 *
		 * @return	array		array of block ancestors
		 */
		getBlockAncestors: function (node, withinBlock) {
			var ancestors = [];
			var ancestor = node;
			while (ancestor && (ancestor.nodeType === HTMLArea.DOM.ELEMENT_NODE) && !/^(body)$/i.test(ancestor.nodeName) && ancestor != withinBlock) {
				if (HTMLArea.DOM.isBlockElement(ancestor)) {
					ancestors.unshift(ancestor);
				}
				ancestor = ancestor.parentNode;
			}
			ancestors.unshift(ancestor);
			return ancestors;
		},
		/*
		 * Get the deepest element ancestor of a given node that is of one of the specified types
		 *
		 * @param	object		node: the given node
		 * @param	array		types: an array of nodeNames
		 *
		 * @return	object		the found ancestor of one of the given types or null
		 */
		getFirstAncestorOfType: function (node, types) {
			var ancestor = null,
				parent = node;
			if (!Ext.isEmpty(types)) {
				if (Ext.isString(types)) {
					var types = [types];
				}
				types = new RegExp( '^(' + types.join('|') + ')$', 'i');
				while (parent && parent.nodeType === HTMLArea.DOM.ELEMENT_NODE && !/^(body)$/i.test(parent.nodeName)) {
					if (types.test(parent.nodeName)) {
						ancestor = parent;
						break;
					}
					parent = parent.parentNode;
				}
			}
			return ancestor;
		},
		/*
		 * Get the position of the node within the children of its parent
		 * Adapted from FCKeditor
		 *
		 * @param	object		node: the DOM node
		 * @param	boolean		normalized: if true, a normalized position is calculated
		 *
		 * @return	integer		the position of the node
		 */
		getPositionWithinParent: function (node, normalized) {
			var current = node,
				position = 0;
			while (current = current.previousSibling) {
				// For a normalized position, do not count any empty text node or any text node following another one
				if (
					normalized
					&& current.nodeType == HTMLArea.DOM.TEXT_NODE
					&& (!current.nodeValue.length || (current.previousSibling && current.previousSibling.nodeType == HTMLArea.DOM.TEXT_NODE))
				) {
					continue;
				}
				position++;
			}
			return position;
		},
		/*
		 * Determine whether a given node has any allowed attributes
		 *
		 * @param	object		node: the DOM node
		 * @param	array		allowedAttributes: array of allowed attribute names
		 *
		 * @return	boolean		true if the node has one of the allowed attributes
		 */
		 hasAllowedAttributes: function (node, allowedAttributes) {
			var value,
				hasAllowedAttributes = false;
			if (Ext.isString(allowedAttributes)) {
				allowedAttributes = [allowedAttributes];
			}
			allowedAttributes = allowedAttributes || [];
			for (var i = allowedAttributes.length; --i >= 0;) {
				value = node.getAttribute(allowedAttributes[i]);
				if (value) {
					if (allowedAttributes[i] === 'style') {
						if (node.style.cssText) {
							hasAllowedAttributes = true;
							break;
						}
					} else {
						hasAllowedAttributes = true;
						break;
					}
				}
			}
			return hasAllowedAttributes;
		},
		/*
		 * Remove the given node from its parent
		 *
		 * @param	object		node: the DOM node
		 *
		 * @return	void
		 */
		removeFromParent: function (node) {
			var parent = node.parentNode;
			if (parent) {
				parent.removeChild(node);
			}
		},
		/*
		 * Change the nodeName of an element node
		 *
		 * @param	object		node: the node to convert (must belong to a document)
		 * @param	string		nodeName: the nodeName of the converted node
		 *
		 * @retrun	object		the converted node or the input node
		 */
		convertNode: function (node, nodeName) {
			var convertedNode = node,
				ownerDocument = node.ownerDocument;
			if (ownerDocument && node.nodeType === HTMLArea.DOM.ELEMENT_NODE) {
				var convertedNode = ownerDocument.createElement(nodeName),
					parent = node.parentNode;
				while (node.firstChild) {
					convertedNode.appendChild(node.firstChild);
				}
				parent.insertBefore(convertedNode, node);
				parent.removeChild(node);
			}
			return convertedNode;
		},
		/*
		 * Determine whether a given range intersects a given node
		 *
		 * @param	object		range: the range
		 * @param	object		node: the DOM node (must belong to a document)
		 *
		 * @return	boolean		true if the range intersects the node
		 */
		rangeIntersectsNode: function (range, node) {
			var rangeIntersectsNode = false,
				ownerDocument = node.ownerDocument;
			if (ownerDocument) {
				if (HTMLArea.isIEBeforeIE9) {
					var nodeRange = ownerDocument.body.createTextRange();
					nodeRange.moveToElementText(node);
					rangeIntersectsNode = (range.compareEndPoints('EndToStart', nodeRange) == -1 && range.compareEndPoints('StartToEnd', nodeRange) == 1) ||
						(range.compareEndPoints('EndToStart', nodeRange) == 1 && range.compareEndPoints('StartToEnd', nodeRange) == -1);
				} else {
					var nodeRange = ownerDocument.createRange();
					try {
						nodeRange.selectNode(node);
					} catch (e) {
						if (Ext.isWebKit) {
							nodeRange.setStart(node, 0);
							if (node.nodeType === HTMLArea.DOM.TEXT_NODE || node.nodeType === HTMLArea.DOM.COMMENT_NODE || node.nodeType === HTMLArea.DOM.CDATA_SECTION_NODE) {
								nodeRange.setEnd(node, node.textContent.length);
							} else {
								nodeRange.setEnd(node, node.childNodes.length);
							}
						} else {
							nodeRange.selectNodeContents(node);
						}
					}
						// Note: sometimes WebKit inverts the end points
					rangeIntersectsNode = (range.compareBoundaryPoints(range.END_TO_START, nodeRange) == -1 && range.compareBoundaryPoints(range.START_TO_END, nodeRange) == 1) ||
						(range.compareBoundaryPoints(range.END_TO_START, nodeRange) == 1 && range.compareBoundaryPoints(range.START_TO_END, nodeRange) == -1);
				}
			}
			return rangeIntersectsNode;
		},
		/*
		 * Make url's absolute in the DOM tree under the root node
		 *
		 * @param	object		root: the root node
		 * @param	string		baseUrl: base url to use
		 * @param	string		walker: a HLMLArea.DOM.Walker object
		 * @return	void
		 */
		makeUrlsAbsolute: function (node, baseUrl, walker) {
			walker.walk(node, true, 'HTMLArea.DOM.makeImageSourceAbsolute(node, args[0]) || HTMLArea.DOM.makeLinkHrefAbsolute(node, args[0])', 'Ext.emptyFn', [baseUrl]);
		},
		/*
		 * Make the src attribute of an image node absolute
		 *
		 * @param	object		node: the image node
		 * @param	string		baseUrl: base url to use
		 * @return	void
		 */
		makeImageSourceAbsolute: function (node, baseUrl) {
			if (/^img$/i.test(node.nodeName)) {
				var src = node.getAttribute('src');
				if (src) {
					node.setAttribute('src', HTMLArea.DOM.addBaseUrl(src, baseUrl));
				}
				return true;
			}
			return false;
		},
		/*
		 * Make the href attribute of an a node absolute
		 *
		 * @param	object		node: the image node
		 * @param	string		baseUrl: base url to use
		 * @return	void
		 */
		makeLinkHrefAbsolute: function (node, baseUrl) {
			if (/^a$/i.test(node.nodeName)) {
				var href = node.getAttribute('href');
				if (href) {
					node.setAttribute('href', HTMLArea.DOM.addBaseUrl(href, baseUrl));
				}
				return true;
			}
			return false;
		},
		/*
		 * Add base url
		 *
		 * @param	string		url: value of a href or src attribute
		 * @param	string		baseUrl: base url to add
		 * @return	string		absolute url
		 */
		addBaseUrl: function (url, baseUrl) {
			var absoluteUrl = url;
				// If the url has no scheme...
			if (!/^[a-z0-9_]{2,}\:/i.test(absoluteUrl)) {
				var base = baseUrl;
				while (absoluteUrl.match(/^\.\.\/(.*)/)) {
						// Remove leading ../ from url
					absoluteUrl = RegExp.$1;
					base.match(/(.*\:\/\/.*\/)[^\/]+\/$/);
						// Remove lowest directory level from base
					base = RegExp.$1;
					absoluteUrl = base + absoluteUrl;
				}
					// If the url is still not absolute...
				if (!/^.*\:\/\//.test(absoluteUrl)) {
					absoluteUrl = baseUrl + absoluteUrl;
				}
			}
			return absoluteUrl;
		}
	};
}();
/***************************************************
 *  HTMLArea.DOM.Walker: DOM tree walk
 ***************************************************/
HTMLArea.DOM.Walker = function (config) {
	var configDefaults = {
		keepComments: false,
		keepCDATASections: false,
		removeTags: /none/i,
		removeTagsAndContents: /none/i,
		keepTags: /.*/i,
		removeAttributes: /none/i,
		removeTrailingBR: true,
		baseUrl: ''
	};
	Ext.apply(this, config, configDefaults);
};
HTMLArea.DOM.Walker = Ext.extend(HTMLArea.DOM.Walker, {
	/*
	 * Walk the DOM tree
	 *
	 * @param	object		node: the root node of the tree
	 * @param	boolean		includeNode: if set, apply callback to the node
	 * @param	string		startCallback: a function call to be evaluated on each node, before walking the children
	 * @param	string		endCallback: a function call to be evaluated on each node, after walking the children
	 * @param	array		args: array of arguments
	 * @return	void
	 */
	walk: function (node, includeNode, startCallback, endCallback, args) {
		if (!this.removeTagsAndContents.test(node.nodeName)) {
			if (includeNode) {
				eval(startCallback);
			}
				// Walk the children
			var child = node.firstChild;
			while (child) {
				this.walk(child, true, startCallback, endCallback, args);
				child = child.nextSibling;
			}
			if (includeNode) {
				eval(endCallback);
			}
		}
	},
	/*
	 * Generate html string from DOM tree
	 *
	 * @param	object		node: the root node of the tree
	 * @param	boolean		includeNode: if set, apply callback to root element
	 * @return	string		rendered html code
	 */
	render: function (node, includeNode) {
		this.html = '';
		this.walk(node, includeNode, 'args[0].renderNodeStart(node)', 'args[0].renderNodeEnd(node)', [this]);
		return this.html;
	},
	/*
	 * Generate html string for the start of a node
	 *
	 * @param	object		node: the root node of the tree
	 * @return	string		rendered html code (accumulated in this.html)
	 */
	renderNodeStart: function (node) {
		var html = '';
		switch (node.nodeType) {
			case HTMLArea.DOM.ELEMENT_NODE:
				if (this.keepTags.test(node.nodeName) && !this.removeTags.test(node.nodeName)) {
					html += this.setOpeningTag(node);
				}
				break;
			case HTMLArea.DOM.TEXT_NODE:
				html += /^(script|style)$/i.test(node.parentNode.nodeName) ? node.data : HTMLArea.util.htmlEncode(node.data);
				break;
			case HTMLArea.DOM.ENTITY_NODE:
				html += node.nodeValue;
				break;
			case HTMLArea.DOM.ENTITY_REFERENCE_NODE:
				html += '&' + node.nodeValue + ';';
				break;
			case HTMLArea.DOM.COMMENT_NODE:
				if (this.keepComments) {
					html += '<!--' + node.data + '-->';
				}
				break;
			case HTMLArea.DOM.CDATA_SECTION_NODE:
				if (this.keepCDATASections) {
					html += '<![CDATA[' + node.data + ']]>';
				}
				break;
			default:
					// Ignore all other node types
				break;
		}
		this.html += html;
	},
	/*
	 * Generate html string for the end of a node
	 *
	 * @param	object		node: the root node of the tree
	 * @return	string		rendered html code (accumulated in this.html)
	 */
	renderNodeEnd: function (node) {
		var html = '';
		if (node.nodeType === HTMLArea.DOM.ELEMENT_NODE) {
			if (this.keepTags.test(node.nodeName) && !this.removeTags.test(node.nodeName)) {
				html += this.setClosingTag(node);
			}
		}
		this.html += html;
	},
	/*
	 * Get the attributes of the node, filtered and cleaned-up
	 *
	 * @param	object		node: the node
	 * @return	object		an object with attribute name as key and attribute value as value
	 */
	getAttributes: function (node) {
		var attributes = node.attributes;
		var filterededAttributes = [];
		var attribute, attributeName, attributeValue;
		for (var i = attributes.length; --i >= 0;) {
			attribute = attributes.item(i);
			attributeName = attribute.nodeName.toLowerCase();
			attributeValue = attribute.nodeValue;
				// Ignore some attributes and those configured to be removed
			if (/_moz|contenteditable|complete/.test(attributeName) || this.removeAttributes.test(attributeName)) {
				continue;
			}
				// Ignore default values except for the value attribute
			if (!attribute.specified && attributeName !== 'value') {
				continue;
			}
			if (Ext.isIE) {
					// IE before I9 fails to put style in attributes list.
				if (attributeName === 'style') {
					if (HTMLArea.isIEBeforeIE9) {
						attributeValue = node.style.cssText;
					}
					// May need to strip the base url
				} else if (attributeName === 'href' || attributeName === 'src') {
					attributeValue = this.stripBaseURL(attributeValue);
					// Ignore value="0" reported by IE on all li elements
				} else if (attributeName === 'value' && /^li$/i.test(node.nodeName) && attributeValue == 0) {
					continue;
				}
			} else if (Ext.isGecko) {
					// Ignore special values reported by Mozilla
				if (/(_moz|^$)/.test(attributeValue)) {
					continue;
					// Pasted internal url's are made relative by Mozilla: https://bugzilla.mozilla.org/show_bug.cgi?id=613517
				} else if (attributeName === 'href' || attributeName === 'src') {
					attributeValue = HTMLArea.DOM.addBaseUrl(attributeValue, this.baseUrl);
				}
			}
				// Ignore id attributes generated by ExtJS
			if (attributeName === 'id' && /^ext-gen/.test(attributeValue)) {
				continue;
			}
			filterededAttributes.push({
				attributeName: attributeName,
				attributeValue: attributeValue
			});
		}
		return (Ext.isWebKit || Ext.isOpera) ? filterededAttributes.reverse() : filterededAttributes;
	},
	/*
	 * Set opening tag for a node
	 *
	 * @param	object		node: the node
	 * @return	object		opening tag
	 */
	setOpeningTag: function (node) {
		var html = '';
			// Handle br oddities
		if (/^br$/i.test(node.nodeName)) {
				// Remove Mozilla special br node
			if (Ext.isGecko && node.hasAttribute('_moz_editor_bogus_node')) {
				return html;
				// In Gecko, whenever some text is entered in an empty block, a trailing br tag is added by the browser.
				// If the br element is a trailing br in a block element with no other content or with content other than a br, it may be configured to be removed
			} else if (this.removeTrailingBR && !node.nextSibling && HTMLArea.DOM.isBlockElement(node.parentNode) && (!node.previousSibling || !/^br$/i.test(node.previousSibling.nodeName))) {
						// If an empty paragraph with a class attribute, insert a non-breaking space so that RTE transform does not clean it away
					if (!node.previousSibling && node.parentNode && /^p$/i.test(node.parentNode.nodeName) && node.parentNode.className) {
						html += "&nbsp;";
					}
				return html;
			}
		}
			// Normal node
		var attributes = this.getAttributes(node);
		for (var i = 0, n = attributes.length; i < n; i++) {
			html +=  ' ' + attributes[i]['attributeName'] + '="' + HTMLArea.util.htmlEncode(attributes[i]['attributeValue']) + '"';
		}
		html = '<' + node.nodeName.toLowerCase() + html + (HTMLArea.DOM.RE_noClosingTag.test(node.nodeName) ? ' />' : '>');
			// Fix orphan list elements
		if (/^li$/i.test(node.nodeName) && !/^[ou]l$/i.test(node.parentNode.nodeName)) {
			html = '<ul>' + html;
		}
		return html;
	},
	/*
	 * Set closing tag for a node
	 *
	 * @param	object		node: the node
	 * @return	object		closing tag, if required
	 */
	setClosingTag: function (node) {
		var html = HTMLArea.DOM.RE_noClosingTag.test(node.nodeName) ? '' : '</' + node.nodeName.toLowerCase() + '>';
			// Fix orphan list elements
		if (/^li$/i.test(node.nodeName) && !/^[ou]l$/i.test(node.parentNode.nodeName)) {
			html += '</ul>';
		}
		return html;
	},
	/*
	 * Strip base url
	 * May be overridden by link handling plugin
	 *
	 * @param	string		value: value of a href or src attribute
	 * @return	tring		stripped value
	 */
	stripBaseURL: function (value) {
		return value;
	}
});
/***************************************************
 *  HTMLArea.DOM.Selection: Selection object
 ***************************************************/
HTMLArea.DOM.Selection = function (config) {
};
HTMLArea.DOM.Selection = Ext.extend(HTMLArea.DOM.Selection, {
	/*
	 * Reference to the editor MUST be set in config
	 */
	editor: null,
	/*
	 * Reference to the editor document
	 */
	document: null,
	/*
	 * Reference to the editor iframe window
	 */
	window: null,
	/*
	 * The current selection
	 */
	selection: null,
	/*
	 * HTMLArea.DOM.Selection constructor
	 */
	constructor: function (config) {
		 	// Apply config
		Ext.apply(this, config);
			// Initialize references
		this.document = this.editor.document;
		this.window = this.editor.iframe.getEl().dom.contentWindow;
			// Set current selection
		this.get();
	},
	/*
	 * Get the current selection object
	 *
	 * @return	object		this
	 */
	get: function () {
		this.editor.focus();
	 	this.selection = this.window.getSelection ? this.window.getSelection() : this.document.selection;
	 	return this;
	},
	/*
	 * Get the type of the current selection
	 *
	 * @return	string		the type of selection ("None", "Text" or "Control")
	 */
	getType: function() {
			// By default set the type to "Text"
		var type = 'Text';
		this.get();
		if (!Ext.isEmpty(this.selection)) {
			if (Ext.isFunction(this.selection.getRangeAt)) {
					// Check if the current selection is a Control
				if (this.selection && this.selection.rangeCount == 1) {
					var range = this.selection.getRangeAt(0);
					if (range.startContainer.nodeType === HTMLArea.DOM.ELEMENT_NODE) {
						if (
								// Gecko
							(range.startContainer == range.endContainer && (range.endOffset - range.startOffset) == 1) ||
								// Opera and WebKit
							(range.endContainer.nodeType === HTMLArea.DOM.TEXT_NODE && range.endOffset == 0 && range.startContainer.childNodes[range.startOffset].nextSibling == range.endContainer)
						) {
							if (/^(img|hr|li|table|tr|td|embed|object|ol|ul|dl)$/i.test(range.startContainer.childNodes[range.startOffset].nodeName)) {
								type = 'Control';
							}
						}
					}
				}
			} else {
					// IE8 or IE7
				type = this.selection.type;
			}
		}
		return type;
	},
	/*
	 * Empty the current selection
	 *
	 * @return	object		this
	 */
	empty: function () {
		this.get();
		if (!Ext.isEmpty(this.selection)) {
			if (Ext.isFunction(this.selection.removeAllRanges)) {
				this.selection.removeAllRanges();
			} else {
					// IE8, IE7 or old version of WebKit
				this.selection.empty();
			}
			if (Ext.isOpera) {
				this.editor.focus();
			}
		}
		return this;
	},
	/*
	 * Determine whether the current selection is empty or not
	 *
	 * @return	boolean		true, if the selection is empty
	 */
	isEmpty: function () {
		var isEmpty = true;
		this.get();
		if (!Ext.isEmpty(this.selection)) {
			if (HTMLArea.isIEBeforeIE9) {
				switch (this.selection.type) {
					case 'None':
						isEmpty = true;
						break;
					case 'Text':
						isEmpty = !this.createRange().text;
						break;
					default:
						isEmpty = !this.createRange().htmlText;
						break;
				}
			} else {
				isEmpty = this.selection.isCollapsed;
			}
		}
		return isEmpty;
	},
	/*
	 * Get a range corresponding to the current selection
	 *
	 * @return	object		the range of the selection
	 */
	createRange: function () {
		var range;
		this.get();
		if (HTMLArea.isIEBeforeIE9) {
			range = this.selection.createRange();
		} else {
			if (Ext.isEmpty(this.selection)) {
				range = this.document.createRange();
			} else {
					// Older versions of WebKit did not support getRangeAt
				if (Ext.isWebKit && !Ext.isFunction(this.selection.getRangeAt)) {
					range = this.document.createRange();
					if (this.selection.baseNode == null) {
						range.setStart(this.document.body, 0);
						range.setEnd(this.document.body, 0);
					} else {
						range.setStart(this.selection.baseNode, this.selection.baseOffset);
						range.setEnd(this.selection.extentNode, this.selection.extentOffset);
						if (range.collapsed != this.selection.isCollapsed) {
							range.setStart(this.selection.extentNode, this.selection.extentOffset);
							range.setEnd(this.selection.baseNode, this.selection.baseOffset);
						}
					}
				} else {
					try {
						range = this.selection.getRangeAt(0);
					} catch (e) {
						range = this.document.createRange();
					}
				}
			}
		}
		return range;
	},
	/*
	 * Return the ranges of the selection
	 *
	 * @return	array		array of ranges
	 */
	getRanges: function () {
		this.get();
		var ranges = [];
			// Older versions of WebKit, IE7 and IE8 did not support getRangeAt
		if (!Ext.isEmpty(this.selection) && Ext.isFunction(this.selection.getRangeAt)) {
			for (var i = this.selection.rangeCount; --i >= 0;) {
				ranges.push(this.selection.getRangeAt(i));
			}
		} else {
			ranges.push(this.createRange());
		}
		return ranges;
	},
	/*
	 * Add a range to the selection
	 *
	 * @param	object		range: the range to be added to the selection
	 *
	 * @return	object		this
	 */
	addRange: function (range) {
		this.get();
		if (!Ext.isEmpty(this.selection)) {
			if (Ext.isFunction(this.selection.addRange)) {
				this.selection.addRange(range);
			} else if (Ext.isWebKit) {
				this.selection.setBaseAndExtent(range.startContainer, range.startOffset, range.endContainer, range.endOffset);
			}
		}
		return this;
	},
	/*
	 * Set the ranges of the selection
	 *
	 * @param	array		ranges: array of range to be added to the selection
	 *
	 * @return	object		this
	 */
	setRanges: function (ranges) {
		this.get();
		this.empty();
		for (var i = ranges.length; --i >= 0;) {
			this.addRange(ranges[i]);
		}
		return this;
	},
	/*
	 * Set the selection to a given range
	 *
	 * @param	object		range: the range to be selected
	 *
	 * @return	object		this
	 */
	selectRange: function (range) {
		this.get();
		if (!Ext.isEmpty(this.selection)) {
			if (Ext.isFunction(this.selection.getRangeAt)) {
				this.empty().addRange(range);
			} else {
					// IE8 or IE7
				range.select();
			}
		}
		return this;
	},
	/*
	 * Set the selection to a given node
	 *
	 * @param	object		node: the node to be selected
	 * @param	boolean		endPoint: collapse the selection at the start point (true) or end point (false) of the node
	 *
	 * @return	object		this
	 */
	selectNode: function (node, endPoint) {
		this.get();
		if (!Ext.isEmpty(this.selection)) {
			if (HTMLArea.isIEBeforeIE9) {
					// IE8/7/6 cannot set this type of selection
				this.selectNodeContents(node, endPoint);
			} else if (Ext.isWebKit && /^(img)$/i.test(node.nodeName)) {
				this.selection.setBaseAndExtent(node, 0, node, 1);
			} else {
				var range = this.document.createRange();
				if (node.nodeType === HTMLArea.DOM.ELEMENT_NODE && /^(body)$/i.test(node.nodeName)) {
					if (Ext.isWebKit) {
						range.setStart(node, 0);
						range.setEnd(node, node.childNodes.length);
					} else {
						range.selectNodeContents(node);
					}
				} else {
					range.selectNode(node);
				}
				if (typeof(endPoint) !== 'undefined') {
					range.collapse(endPoint);
				}
				this.selectRange(range);
			}
		}
		return this;
	},
	/*
	 * Set the selection to the inner contents of a given node
	 *
	 * @param	object		node: the node of which the contents are to be selected
	 * @param	boolean		endPoint: collapse the selection at the start point (true) or end point (false)
	 *
	 * @return	object		this
	 */
	selectNodeContents: function (node, endPoint) {
		var range;
		this.get();
		if (!Ext.isEmpty(this.selection)) {
			if (HTMLArea.isIEBeforeIE9) {
				range = this.document.body.createTextRange();
				range.moveToElementText(node);
			} else {
				range = this.document.createRange();
				if (Ext.isWebKit) {
					range.setStart(node, 0);
					if (node.nodeType === HTMLArea.DOM.TEXT_NODE || node.nodeType === HTMLArea.DOM.COMMENT_NODE || node.nodeType === HTMLArea.DOM.CDATA_SECTION_NODE) {
						range.setEnd(node, node.textContent.length);
					} else {
						range.setEnd(node, node.childNodes.length);
					}
				} else {
					range.selectNodeContents(node);
				}
			}
			if (typeof(endPoint) !== 'undefined') {
				range.collapse(endPoint);
			}
			this.selectRange(range);
		}
		return this;
	},
	/*
	 * Get the deepest node that contains both endpoints of the current selection.
	 *
	 * @return	object		the deepest node that contains both endpoints of the current selection.
	 */
	getParentElement: function () {
		var parentElement,
			range;
		this.get();
		if (HTMLArea.isIEBeforeIE9) {
			range = this.createRange();
			switch (this.selection.type) {
				case 'Text':
				case 'None':
					parentElement = range.parentElement();
					if (/^(form)$/i.test(parentElement.nodeName)) {
						parentElement = this.document.body;
					} else if (/^(li)$/i.test(parentElement.nodeName) && range.htmlText.replace(/\s/g, '') == parentElement.parentNode.outerHTML.replace(/\s/g, '')) {
						parentElement = parentElement.parentNode;
					}
					break;
				case 'Control':
					parentElement = range.item(0);
					break;
				default:
					parentElement = this.document.body;
					break;
			}
		} else {
			if (this.getType() === 'Control') {
				parentElement = this.getElement();
			} else {
				range = this.createRange();
				parentElement = range.commonAncestorContainer;
					// Firefox 3 may report the document as commonAncestorContainer
				if (parentElement.nodeType === HTMLArea.DOM.DOCUMENT_NODE) {
					parentElement = this.document.body;
				} else {
					while (parentElement && parentElement.nodeType === HTMLArea.DOM.TEXT_NODE) {
						parentElement = parentElement.parentNode;
					}
				}
			}
		}
		return parentElement;
	},
	/*
	 * Get the selected element (if any), in the case that a single element (object like and image or a table) is selected
	 * In IE language, we have a range of type 'Control'
	 *
	 * @return	object		the selected node
	 */
	getElement: function () {
		var element = null;
		this.get();
		if (!Ext.isEmpty(this.selection) && this.selection.anchorNode && this.selection.anchorNode.nodeType === HTMLArea.DOM.ELEMENT_NODE && this.getType() == 'Control') {
			element = this.selection.anchorNode.childNodes[this.selection.anchorOffset];
				// For Safari, the anchor node for a control selection is the control itself
			if (!element) {
				element = this.selection.anchorNode;
			} else if (element.nodeType !== HTMLArea.DOM.ELEMENT_NODE) {
				element = null;
			}
		}
		return element;
	},
	/*
	 * Get the deepest element ancestor of the selection that is of one of the specified types
	 *
	 * @param	array		types: an array of nodeNames
	 *
	 * @return	object		the found ancestor of one of the given types or null
	 */
	getFirstAncestorOfType: function (types) {
		var node = this.getParentElement();
		return HTMLArea.DOM.getFirstAncestorOfType(node, types);
	},
	/*
	 * Get an array with all the ancestor nodes of the current selection
	 *
	 * @return	array		the ancestor nodes
	 */
	getAllAncestors: function () {
		var parent = this.getParentElement(),
			ancestors = [];
		while (parent && parent.nodeType === HTMLArea.DOM.ELEMENT_NODE && !/^(body)$/i.test(parent.nodeName)) {
			ancestors.push(parent);
			parent = parent.parentNode;
		}
		ancestors.push(this.document.body);
		return ancestors;
	},
	/*
	 * Get an array with the parent elements of a multiple selection
	 *
	 * @return	array		the selected elements
	 */
	getElements: function () {
		var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null,
			elements = [];
		if (statusBarSelection) {
			elements.push(statusBarSelection);
		} else {
			var ranges = this.getRanges();
				parent;
			if (ranges.length > 1) {
				for (var i = ranges.length; --i >= 0;) {
					parent = range[i].commonAncestorContainer;
						// Firefox 3 may report the document as commonAncestorContainer
					if (parent.nodeType === HTMLArea.DOM.DOCUMENT_NODE) {
						parent = this.document.body;
					} else {
						while (parent && parent.nodeType === HTMLArea.DOM.TEXT_NODE) {
							parent = parent.parentNode;
						}
					}
					elements.push(parent);
				}
			} else {
				elements.push(this.getParentElement());
			}
		}
		return elements;
	},
	/*
	 * Get the node whose contents are currently fully selected
	 *
	 * @return	object		the fully selected node, if any, null otherwise
	 */
	getFullySelectedNode: function () {
		var node = null,
			isFullySelected = false;
		this.get();
		if (!this.isEmpty()) {
			var type = this.getType();
			var range = this.createRange();
			var ancestors = this.getAllAncestors();
			Ext.each(ancestors, function (ancestor) {
				if (HTMLArea.isIEBeforeIE9) {
					isFullySelected = (type !== 'Control' && ancestor.innerText == range.text) || (type === 'Control' && ancestor.innerText == range.item(0).text);
				} else {
					isFullySelected = (ancestor.textContent == range.toString());
				}
				if (isFullySelected) {
					node = ancestor;
					return false;
				}
			});
				// Working around bug with WebKit selection
			if (Ext.isWebKit && !isFullySelected) {
				var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
				if (statusBarSelection && statusBarSelection.textContent == range.toString()) {
					isFullySelected = true;
					node = statusBarSelection;
				}
			}
		}
		return node;
	},
	/*
	 * Get the block elements containing the start and the end points of the selection
	 *
	 * @return	object		object with properties start and end set to the end blocks of the selection
	 */
	getEndBlocks: function () {
		var range = this.createRange(),
			parentStart,
			parentEnd;
		if (HTMLArea.isIEBeforeIE9) {
			if (this.getType() === 'Control') {
				parentStart = range.item(0);
				parentEnd = parentStart;
			} else {
				var rangeEnd = range.duplicate();
				range.collapse(true);
				parentStart = range.parentElement();
				rangeEnd.collapse(false);
				parentEnd = rangeEnd.parentElement();
			}
		} else {
			parentStart = range.startContainer;
			if (/^(body)$/i.test(parentStart.nodeName)) {
				parentStart = parentStart.firstChild;
			}
			parentEnd = range.endContainer;
			if (/^(body)$/i.test(parentEnd.nodeName)) {
				parentEnd = parentEnd.lastChild;
			}
		}
		while (parentStart && !HTMLArea.DOM.isBlockElement(parentStart)) {
			parentStart = parentStart.parentNode;
		}
		while (parentEnd && !HTMLArea.DOM.isBlockElement(parentEnd)) {
			parentEnd = parentEnd.parentNode;
		}
		return {
			start: parentStart,
			end: parentEnd
		};
	},
	/*
	 * Determine whether the end poins of the current selection are within the same block
	 *
	 * @return	boolean		true if the end points of the current selection are in the same block
	 */
	endPointsInSameBlock: function() {
		var endPointsInSameBlock = true;
		this.get();
		if (!this.isEmpty()) {
			var parent = this.getParentElement();
			var endBlocks = this.getEndBlocks();
			endPointsInSameBlock = (endBlocks.start === endBlocks.end && !/^(table|thead|tbody|tfoot|tr)$/i.test(parent.nodeName));
		}
		return endPointsInSameBlock;
	},
	/*
	 * Retrieve the HTML contents of the current selection
	 *
	 * @return	string		HTML text of the current selection
	 */
	getHtml: function () {
		var range = this.createRange(),
			html = '';
		if (HTMLArea.isIEBeforeIE9) {
			if (this.getType() === 'Control') {
					// We have a controlRange collection
				var bodyRange = this.document.body.createTextRange();
				bodyRange.moveToElementText(range(0));
				html = bodyRange.htmlText;
			} else {
				html = range.htmlText;
			}
		} else if (!range.collapsed) {
			var cloneContents = range.cloneContents();
			if (!cloneContents) {
				cloneContents = this.document.createDocumentFragment();
			}
			html = this.editor.iframe.htmlRenderer.render(cloneContents, false);
		}
		return html;
	},
	 /*
	 * Insert a node at the current position
	 * Delete the current selection, if any.
	 * Split the text node, if needed.
	 *
	 * @param	object		toBeInserted: the node to be inserted
	 *
	 * @return	object		this
	 */
	insertNode: function (toBeInserted) {
		if (HTMLArea.isIEBeforeIE9) {
			this.insertHtml(toBeInserted.outerHTML);
		} else {
			var range = this.createRange();
			range.deleteContents();
			toBeSelected = (toBeInserted.nodeType === HTMLArea.DOM.DOCUMENT_FRAGMENT_NODE) ? toBeInserted.lastChild : toBeInserted;
			range.insertNode(toBeInserted);
			this.selectNodeContents(toBeSelected, false);
		}
		return this;
	},
	/*
	 * Insert HTML source code at the current position
	 * Delete the current selection, if any.
	 *
	 * @param	string		html: the HTML source code
	 *
	 * @return	object		this
	 */
	insertHtml: function (html) {
		if (HTMLArea.isIEBeforeIE9) {
			this.get();
			if (this.getType() === 'Control') {
				this.selection.clear();
				this.get();
			}
			var range = this.createRange();
			range.pasteHTML(html);
		} else {
			this.editor.focus();
			var fragment = this.document.createDocumentFragment();
			var div = this.document.createElement('div');
			div.innerHTML = html;
			while (div.firstChild) {
				fragment.appendChild(div.firstChild);
			}
			this.insertNode(fragment);
		}
		return this;
	},
	/*
	 * Surround the selection with an element specified by its start and end tags
	 * Delete the selection, if any.
	 *
	 * @param	string		startTag: the start tag
	 * @param	string		endTag: the end tag
	 *
	 * @return	void
	 */
	surroundHtml: function (startTag, endTag) {
		this.insertHtml(startTag + this.getHtml().replace(HTMLArea.DOM.RE_bodyTag, '') + endTag);
	},
	/*
	 * Execute some native execCommand command on the current selection
	 *
	 * @param	string		cmdID: the command name or id
	 * @param	object		UI:
	 * @param	object		param:
	 *
	 * @return	boolean		false
	 */
	execCommand: function (cmdID, UI, param) {
		var success = true;
		this.editor.focus();
		try {
			this.document.execCommand(cmdID, UI, param);
		} catch (e) {
			success = false;
			this.editor.appendToLog('HTMLArea.DOM.Selection', 'execCommand', e + ' by execCommand(' + cmdID + ')', 'error');
		}
		this.editor.updateToolbar();
		return success;
	},
	/*
	 * Handle backspace event on the current selection
	 *
	 * @return	boolean		true to stop the event and cancel the default action
	 */
	handleBackSpace: function () {
		var range = this.createRange();
		if (HTMLArea.isIEBeforeIE9) {
			if (this.getType() === 'Control') {
					// Deleting or backspacing on a control selection : delete the element
				var element = this.getParentElement();
				var parent = element.parentNode;
				parent.removeChild(el);
				return true;
			} else if (this.isEmpty()) {
					// Check if deleting an empty block with a table as next sibling
				var element = this.getParentElement();
				if (!element.innerHTML && HTMLArea.DOM.isBlockElement(element) && element.nextSibling && /^table$/i.test(element.nextSibling.nodeName)) {
					var previous = element.previousSibling;
					if (!previous) {
						this.selectNodeContents(element.nextSibling.rows[0].cells[0], true);
					} else if (/^table$/i.test(previous.nodeName)) {
						this.selectNodeContents(previous.rows[previous.rows.length-1].cells[previous.rows[previous.rows.length-1].cells.length-1], false);
					} else {
						range.moveStart('character', -1);
						range.collapse(true);
						range.select();
					}
					el.parentNode.removeChild(element);
					return true;
				}
			} else {
					// Backspacing into a link
				var range2 = range.duplicate();
				range2.moveStart('character', -1);
				var a = range2.parentElement();
				if (a != range.parentElement() && /^a$/i.test(a.nodeName)) {
					range2.collapse(true);
					range2.moveEnd('character', 1);
					range2.pasteHTML('');
					range2.select();
					return true;
				}
				return false;
			}
		} else {
			var self = this;
			window.setTimeout(function() {
				var range = self.createRange();
				var startContainer = range.startContainer;
				var startOffset = range.startOffset;
					// If the selection is collapsed...
				if (self.isEmpty()) {
						// ... and the cursor lies in a direct child of body...
					if (/^(body)$/i.test(startContainer.nodeName)) {
						var node = startContainer.childNodes[startOffset];
					} else if (/^(body)$/i.test(startContainer.parentNode.nodeName)) {
						var node = startContainer;
					} else {
						return false;
					}
						// ... which is a br or text node containing no non-whitespace character
					if (/^(br|#text)$/i.test(node.nodeName) && !/\S/.test(node.textContent)) {
							// Get a meaningful previous sibling in which to reposition de cursor
						var previousSibling = node.previousSibling;
						while (previousSibling && /^(br|#text)$/i.test(previousSibling.nodeName) && !/\S/.test(previousSibling.textContent)) {
							previousSibling = previousSibling.previousSibling;
						}
							// If there is no meaningful previous sibling, the cursor is at the start of body
						if (previousSibling) {
								// Remove the node
							HTMLArea.DOM.removeFromParent(node);
								// Position the cursor
							if (/^(ol|ul|dl)$/i.test(previousSibling.nodeName)) {
								self.selectNodeContents(previousSibling.lastChild, false);
							} else if (/^(table)$/i.test(previousSibling.nodeName)) {
								self.selectNodeContents(previousSibling.rows[previousSibling.rows.length-1].cells[previousSibling.rows[previousSibling.rows.length-1].cells.length-1], false);
							} else if (!/\S/.test(previousSibling.textContent) && previousSibling.firstChild) {
								self.selectNode(previousSibling.firstChild, true);
							} else {
								self.selectNodeContents(previousSibling, false);
							}
						}
					}
				}
			}, 10);
			return false;
		}
	},
	/*
	 * Detect emails and urls as they are typed in non-IE browsers
	 * Borrowed from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
	 *
	 * @param	object		event: the ExtJS key event
	 *
	 * @return	void
	 */
	detectURL: function (event) {
		var ev = event.browserEvent;
		var editor = this.editor;
		var selection = this.get().selection;
		if (!/^(a)$/i.test(this.getParentElement().nodeName)) {
			var autoWrap = function (textNode, tag) {
				var rightText = textNode.nextSibling;
				if (typeof(tag) === 'string') {
					tag = editor.document.createElement(tag);
				}
				var a = textNode.parentNode.insertBefore(tag, rightText);
				HTMLArea.DOM.removeFromParent(textNode);
				a.appendChild(textNode);
				selection.collapse(rightText, 0);
				rightText.parentNode.normalize();

				editor.unLink = function() {
					var t = a.firstChild;
					a.removeChild(t);
					a.parentNode.insertBefore(t, a);
					HTMLArea.DOM.removeFromParent(a);
					t.parentNode.normalize();
					editor.unLink = null;
					editor.unlinkOnUndo = false;
				};

				editor.unlinkOnUndo = true;
				return a;
			};
			switch (ev.which) {
					// Space or Enter or >, see if the text just typed looks like a URL, or email address and link it accordingly
				case 13:
				case 32:
					if (selection && selection.isCollapsed && selection.anchorNode.nodeType === HTMLArea.DOM.TEXT_NODE && selection.anchorNode.data.length > 3 && selection.anchorNode.data.indexOf('.') >= 0) {
						var midStart = selection.anchorNode.data.substring(0,selection.anchorOffset).search(/[a-zA-Z0-9]+\S{3,}$/);
						if (midStart == -1) {
							break;
						}
						if (this.getFirstAncestorOfType('a')) {
								// already in an anchor
							break;
						}
						var matchData = selection.anchorNode.data.substring(0,selection.anchorOffset).replace(/^.*?(\S*)$/, '$1');
						if (matchData.indexOf('@') != -1) {
							var m = matchData.match(HTMLArea.RE_email);
							if (m) {
								var leftText  = selection.anchorNode;
								var rightText = leftText.splitText(selection.anchorOffset);
								var midText   = leftText.splitText(midStart);
								var midEnd = midText.data.search(/[^a-zA-Z0-9\.@_\-]/);
								if (midEnd != -1) {
									var endText = midText.splitText(midEnd);
								}
								autoWrap(midText, 'a').href = 'mailto:' + m[0];
								break;
							}
						}
						var m = matchData.match(HTMLArea.RE_url);
						if (m) {
							var leftText  = selection.anchorNode;
							var rightText = leftText.splitText(selection.anchorOffset);
							var midText   = leftText.splitText(midStart);
							var midEnd = midText.data.search(/[^a-zA-Z0-9\._\-\/\&\?=:@]/);
							if (midEnd != -1) {
								var endText = midText.splitText(midEnd);
							}
							autoWrap(midText, 'a').href = (m[1] ? m[1] : 'http://') + m[3];
							break;
						}
					}
					break;
				default:
					if (ev.keyCode == 27 || (editor.unlinkOnUndo && ev.ctrlKey && ev.which == 122)) {
						if (editor.unLink) {
							editor.unLink();
							event.stopEvent();
						}
						break;
					} else if (ev.which || ev.keyCode == 8 || ev.keyCode == 46) {
						editor.unlinkOnUndo = false;
						if (selection.anchorNode && selection.anchorNode.nodeType === HTMLArea.DOM.TEXT_NODE) {
								// See if we might be changing a link
							var a = this.getFirstAncestorOfType('a');
							if (!a) {
								break;
							}
							if (!a.updateAnchorTimeout) {
								if (selection.anchorNode.data.match(HTMLArea.RE_email) && (a.href.match('mailto:' + selection.anchorNode.data.trim()))) {
									var textNode = selection.anchorNode;
									var fn = function() {
										a.href = 'mailto:' + textNode.data.trim();
										a.updateAnchorTimeout = setTimeout(fn, 250);
									};
									a.updateAnchorTimeout = setTimeout(fn, 250);
									break;
								}
								var m = selection.anchorNode.data.match(HTMLArea.RE_url);
								if (m && a.href.match(selection.anchorNode.data.trim())) {
									var textNode = selection.anchorNode;
									var fn = function() {
										var m = textNode.data.match(HTMLArea.RE_url);
										a.href = (m[1] ? m[1] : 'http://') + m[3];
										a.updateAnchorTimeout = setTimeout(fn, 250);
									}
									a.updateAnchorTimeout = setTimeout(fn, 250);
								}
							}
						}
					}
					break;
			}
		}
	},
	/*
	 * Enter event handler
	 *
	 * @return	boolean		true to stop the event and cancel the default action
	 */
	checkInsertParagraph: function() {
		var editor = this.editor;
		var i, left, right, rangeClone,
			sel	= this.get().selection,
			range	= this.createRange(),
			p	= this.getAllAncestors(),
			block	= null,
			a	= null,
			doc	= this.document;
		for (i = 0; i < p.length; ++i) {
			if (HTMLArea.DOM.isBlockElement(p[i]) && !/^(html|body|table|tbody|thead|tfoot|tr|dl)$/i.test(p[i].nodeName)) {
				block = p[i];
				break;
			}
		}
		if (block && /^(td|th|tr|tbody|thead|tfoot|table)$/i.test(block.nodeName) && this.editor.config.buttons.table && this.editor.config.buttons.table.disableEnterParagraphs) {
			return false;
		}
		if (!range.collapsed) {
			range.deleteContents();
		}
		this.empty();
		if (!block || /^(td|div|article|aside|footer|header|nav|section)$/i.test(block.nodeName)) {
			if (!block) {
				block = doc.body;
			}
			if (block.hasChildNodes()) {
				rangeClone = range.cloneRange();
				if (range.startContainer == block) {
						// Selection is directly under the block
					var blockOnLeft = null;
					var leftSibling = null;
						// Looking for the farthest node on the left that is not a block
					for (var i = range.startOffset; --i >= 0;) {
						if (HTMLArea.DOM.isBlockElement(block.childNodes[i])) {
							blockOnLeft = block.childNodes[i];
							break;
						} else {
							rangeClone.setStartBefore(block.childNodes[i]);
						}
					}
				} else {
						// Looking for inline or text container immediate child of block
					var inlineContainer = range.startContainer;
					while (inlineContainer.parentNode != block) {
						inlineContainer = inlineContainer.parentNode;
					}
						// Looking for the farthest node on the left that is not a block
					var leftSibling = inlineContainer;
					while (leftSibling.previousSibling && !HTMLArea.DOM.isBlockElement(leftSibling.previousSibling)) {
						leftSibling = leftSibling.previousSibling;
					}
					rangeClone.setStartBefore(leftSibling);
					var blockOnLeft = leftSibling.previousSibling;
				}
					// Avoiding surroundContents buggy in Opera and Safari
				left = doc.createElement('p');
				left.appendChild(rangeClone.extractContents());
				if (!left.textContent && !left.getElementsByTagName('img').length && !left.getElementsByTagName('table').length) {
					left.innerHTML = '<br />';
				}
				if (block.hasChildNodes()) {
					if (blockOnLeft) {
						left = block.insertBefore(left, blockOnLeft.nextSibling);
					} else {
						left = block.insertBefore(left, block.firstChild);
					}
				} else {
					left = block.appendChild(left);
				}
				block.normalize();
					// Looking for the farthest node on the right that is not a block
				var rightSibling = left;
				while (rightSibling.nextSibling && !HTMLArea.DOM.isBlockElement(rightSibling.nextSibling)) {
					rightSibling = rightSibling.nextSibling;
				}
				var blockOnRight = rightSibling.nextSibling;
				range.setEndAfter(rightSibling);
				range.setStartAfter(left);
					// Avoiding surroundContents buggy in Opera and Safari
				right = doc.createElement('p');
				right.appendChild(range.extractContents());
				if (!right.textContent && !right.getElementsByTagName('img').length && !right.getElementsByTagName('table').length) {
					right.innerHTML = '<br />';
				}
				if (!(left.childNodes.length == 1 && right.childNodes.length == 1 && left.firstChild.nodeName.toLowerCase() == 'br' && right.firstChild.nodeName.toLowerCase() == 'br')) {
					if (blockOnRight) {
						right = block.insertBefore(right, blockOnRight);
					} else {
						right = block.appendChild(right);
					}
					this.selectNodeContents(right, true);
				} else {
					this.selectNodeContents(left, true);
				}
				block.normalize();
			} else {
				var first = block.firstChild;
				if (first) {
					block.removeChild(first);
				}
				right = doc.createElement('p');
				if (Ext.isWebKit || Ext.isOpera) {
					right.innerHTML = '<br />';
				}
				right = block.appendChild(right);
				this.selectNodeContents(right, true);
			}
		} else {
			range.setEndAfter(block);
			var df = range.extractContents(), left_empty = false;
			if (!/\S/.test(block.innerHTML) || (!/\S/.test(block.textContent) && !/<(img|hr|table)/i.test(block.innerHTML))) {
				if (!Ext.isOpera) {
					block.innerHTML = '<br />';
				}
				left_empty = true;
			}
			p = df.firstChild;
			if (p) {
				if (!/\S/.test(p.innerHTML) || (!/\S/.test(p.textContent) && !/<(img|hr|table)/i.test(p.innerHTML))) {
					if (/^h[1-6]$/i.test(p.nodeName)) {
						p = HTMLArea.DOM.convertNode(p, 'p');
					}
					if (/^(dt|dd)$/i.test(p.nodeName)) {
						 p = HTMLArea.DOM.convertNode(p, /^(dt)$/i.test(p.nodeName) ? 'dd' : 'dt');
					}
					if (!Ext.isOpera) {
						p.innerHTML = '<br />';
					}
					if (/^li$/i.test(p.nodeName) && left_empty && (!block.nextSibling || !/^li$/i.test(block.nextSibling.nodeName))) {
						left = block.parentNode;
						left.removeChild(block);
						range.setEndAfter(left);
						range.collapse(false);
						p = HTMLArea.DOM.convertNode(p, /^(li|dd|td|th|p|h[1-6])$/i.test(left.parentNode.nodeName) ? 'br' : 'p');
					}
				}
				range.insertNode(df);
					// Remove any anchor created empty on both sides of the selection
				if (p.previousSibling) {
					var a = p.previousSibling.lastChild;
					if (a && /^a$/i.test(a.nodeName) && !/\S/.test(a.innerHTML)) {
						HTMLArea.DOM.convertNode(a, 'br');
					}
				}
				var a = p.lastChild;
				if (a && /^a$/i.test(a.nodeName) && !/\S/.test(a.innerHTML)) {
					HTMLArea.DOM.convertNode(a, 'br');
				}
					// Walk inside the deepest child element (presumably inline element)
				while (p.firstChild && p.firstChild.nodeType === HTMLArea.DOM.ELEMENT_NODE && !/^(br|img|hr|table)$/i.test(p.firstChild.nodeName)) {
					p = p.firstChild;
				}
				if (/^br$/i.test(p.nodeName)) {
					p = p.parentNode.insertBefore(doc.createTextNode('\x20'), p);
				} else if (!/\S/.test(p.innerHTML)) {
						// Need some element inside the deepest element
					p.appendChild(doc.createElement('br'));
				}
				this.selectNodeContents(p, true);
			} else {
				if (/^(li|dt|dd)$/i.test(block.nodeName)) {
					p = doc.createElement(block.nodeName);
				} else {
					p = doc.createElement('p');
				}
				if (!Ext.isOpera) {
					p.innerHTML = '<br />';
				}
				if (block.nextSibling) {
					p = block.parentNode.insertBefore(p, block.nextSibling);
				} else {
					p = block.parentNode.appendChild(p);
				}
				this.selectNodeContents(p, true);
			}
		}
		this.editor.scrollToCaret();
		return true;
	}
});
/***************************************************
 *  HTMLArea.DOM.BookMark: BookMark object
 ***************************************************/
HTMLArea.DOM.BookMark = function (config) {
};
HTMLArea.DOM.BookMark = Ext.extend(HTMLArea.DOM.BookMark, {
	/*
	 * Reference to the editor MUST be set in config
	 */
	editor: null,
	/*
	 * Reference to the editor document
	 */
	document: null,
	/*
	 * Reference to the editor selection object
	 */
	selection: null,
	/*
	 * HTMLArea.DOM.Selection constructor
	 */
	constructor: function (config) {
		 	// Apply config
		Ext.apply(this, config);
			// Initialize references
		this.document = this.editor.document;
		this.selection = this.editor.getSelection();
	},
	/*
	 * Get a bookMark
	 *
	 * @param	object		range: the range to bookMark
	 * @param	boolean		nonIntrusive: if true, a non-intrusive bookmark is requested
	 *
	 * @return	object		the bookMark
	 */
	get: function (range, nonIntrusive) {
		var bookMark;
		if (HTMLArea.isIEBeforeIE9) {
			// Bookmarking will not work on control ranges
			try {
				bookMark = range.getBookmark();
			} catch (e) {
				bookMark = null;
			}
		} else {
			if (nonIntrusive) {
				bookMark = this.getNonIntrusiveBookMark(range, true);
			} else {
				bookMark = this.getIntrusiveBookMark(range);
			}
		}
		return bookMark;
	},
	/*
	 * Get an intrusive bookMark
	 * Adapted from FCKeditor
	 * This is an "intrusive" way to create a bookMark. It includes <span> tags
	 * in the range boundaries. The advantage of it is that it is possible to
	 * handle DOM mutations when moving back to the bookMark.
	 *
	 * @param	object		range: the range to bookMark
	 *
	 * @return	object		the bookMark
	 */
	getIntrusiveBookMark: function (range) {
		// Create the bookmark info (random IDs).
		var bookMark = {
			nonIntrusive: false,
			startId: (new Date()).valueOf() + Math.floor(Math.random()*1000) + 'S',
			endId: (new Date()).valueOf() + Math.floor(Math.random()*1000) + 'E'
		};
		var startSpan;
		var endSpan;
		var rangeClone = range.cloneRange();
		// For collapsed ranges, add just the start marker
		if (!range.collapsed ) {
			endSpan = this.document.createElement('span');
			endSpan.style.display = 'none';
			endSpan.id = bookMark.endId;
			endSpan.setAttribute('data-htmlarea-bookmark', true);
			endSpan.innerHTML = '&nbsp;';
			rangeClone.collapse(false);
			rangeClone.insertNode(endSpan);
		}
		startSpan = this.document.createElement('span');
		startSpan.style.display = 'none';
		startSpan.id = bookMark.startId;
		startSpan.setAttribute('data-htmlarea-bookmark', true);
		startSpan.innerHTML = '&nbsp;';
		var rangeClone = range.cloneRange();
		rangeClone.collapse(true);
		rangeClone.insertNode(startSpan);
		bookMark.startNode = startSpan;
		bookMark.endNode = endSpan;
		// Update the range position.
		if (endSpan) {
			range.setEndBefore(endSpan);
			range.setStartAfter(startSpan);
		} else {
			range.setEndAfter(startSpan);
			range.collapse(false);
		}
		return bookMark;
	},
	/*
	 * Get a non-intrusive bookMark
	 * Adapted from FCKeditor
	 *
	 * @param	object		range: the range to bookMark
	 * @param	boolean		normalized: if true, normalized enpoints are calculated
	 *
	 * @return	object		the bookMark
	 */
	getNonIntrusiveBookMark: function (range, normalized) {
		var startContainer = range.startContainer,
			endContainer = range.endContainer,
			startOffset = range.startOffset,
			endOffset = range.endOffset,
			collapsed = range.collapsed,
			child,
			previous,
			bookMark = {};
		if (!startContainer || !endContainer) {
			bookMark = {
				nonIntrusive: true,
				start: 0,
				end: 0
			};
		} else {
			if (normalized) {
				// Find out if the start is pointing to a text node that might be normalized
				if (startContainer.nodeType == HTMLArea.DOM.NODE_ELEMENT) {
					child = startContainer.childNodes[startOffset];
					// In this case, move the start to that text node
					if (
						child
						&& child.nodeType == HTMLArea.DOM.NODE_TEXT
						&& startOffset > 0
						&& child.previousSibling.nodeType == HTMLArea.DOM.NODE_TEXT
					) {
						startContainer = child;
						startOffset = 0;
					}
					// Get the normalized offset
					if (child && child.nodeType == HTMLArea.DOM.NODE_ELEMENT) {
						startOffset = HTMLArea.DOM.getPositionWithinParent(child, true);
					}
				}
				// Normalize the start
				while (
					startContainer.nodeType == HTMLArea.DOM.NODE_TEXT
					&& (previous = startContainer.previousSibling)
					&& previous.nodeType == HTMLArea.DOM.NODE_TEXT
				) {
					startContainer = previous;
					startOffset += previous.nodeValue.length;
				}
				// Process the end only if not collapsed
				if (!collapsed) {
					// Find out if the start is pointing to a text node that will be normalized
					if (endContainer.nodeType == HTMLArea.DOM.NODE_ELEMENT) {
						child = endContainer.childNodes[endOffset];
						// In this case, move the end to that text node
						if (
							child
							&& child.nodeType == HTMLArea.DOM.NODE_TEXT
							&& endOffset > 0
							&& child.previousSibling.nodeType == HTMLArea.DOM.NODE_TEXT
						) {
							endContainer = child;
							endOffset = 0;
						}
						// Get the normalized offset
						if (child && child.nodeType == HTMLArea.DOM.NODE_ELEMENT) {
							endOffset = HTMLArea.DOM.getPositionWithinParent(child, true);
						}
					}
					// Normalize the end
					while (
						endContainer.nodeType == HTMLArea.DOM.NODE_TEXT
						&& (previous = endContainer.previousSibling)
						&& previous.nodeType == HTMLArea.DOM.NODE_TEXT
					) {
						endContainer = previous;
						endOffset += previous.nodeValue.length;
					}
				}
			}
			bookMark = {
				start: this.editor.domNode.getPositionWithinTree(startContainer, normalized),
				end: collapsed ? null : getPositionWithinTree(endContainer, normalized),
				startOffset: startOffset,
				endOffset: endOffset,
				normalized: normalized,
				collapsed: collapsed,
				nonIntrusive: true
			};
		}
		return bookMark;
	},
	/*
	 * Get the end point of the bookMark
	 * Adapted from FCKeditor
	 *
	 * @param	object		bookMark: the bookMark
	 * @param	boolean		endPoint: true, for startPoint, false for endPoint
	 *
	 * @return	object		the endPoint node
	 */
	getEndPoint: function (bookMark, endPoint) {
		if (endPoint) {
			return this.document.getElementById(bookMark.startId);
		} else {
			return this.document.getElementById(bookMark.endId);
		}
	},
	/*
	 * Get a range and move it to the bookMark
	 *
	 * @param	object		bookMark: the bookmark to move to
	 *
	 * @return	object		the range that was bookmarked
	 */
	moveTo: function (bookMark) {
		var range = this.selection.createRange();
		if (HTMLArea.isIEBeforeIE9) {
			if (bookMark) {
				range.moveToBookmark(bookMark);
			}
		} else {
			if (bookMark.nonIntrusive) {
				range = this.moveToNonIntrusiveBookMark(range, bookMark);
			} else {
				range = this.moveToIntrusiveBookMark(range, bookMark);
			}
		}
		return range;
	},
	/*
	 * Move the range to the intrusive bookMark
	 * Adapted from FCKeditor
	 *
	 * @param	object		range: the range to be moved
	 * @param	object		bookMark: the bookmark to move to
	 *
	 * @return	object		the range that was bookmarked
	 */
	moveToIntrusiveBookMark: function (range, bookMark) {
		var startSpan = this.getEndPoint(bookMark, true),
			endSpan = this.getEndPoint(bookMark, false),
			parent;
		if (startSpan) {
			// If the previous sibling is a text node, let the anchorNode have it as parent
			if (startSpan.previousSibling && startSpan.previousSibling.nodeType === HTMLArea.DOM.TEXT_NODE) {
				range.setStart(startSpan.previousSibling, startSpan.previousSibling.data.length);
			} else {
				range.setStartBefore(startSpan);
			}
			HTMLArea.DOM.removeFromParent(startSpan);
		} else {
			// For some reason, the startSpan was removed or its id attribute was removed so that it cannot be retrieved
			range.setStart(this.document.body, 0);
		}
		// If the bookmarked range was collapsed, the end span will not be available
		if (endSpan) {
			// If the next sibling is a text node, let the focusNode have it as parent
			if (endSpan.nextSibling && endSpan.nextSibling.nodeType === HTMLArea.DOM.TEXT_NODE) {
				range.setEnd(endSpan.nextSibling, 0);
			} else {
				range.setEndBefore(endSpan);
			}
			HTMLArea.DOM.removeFromParent(endSpan);
		} else {
			range.collapse(true);
		}
		return range;
	},
	/*
	 * Move the range to the non-intrusive bookMark
	 * Adapted from FCKeditor
	 *
	 * @param	object		range: the range to be moved
	 * @param	object		bookMark: the bookMark to move to
	 *
	 * @return	object		the range that was bookmarked
	 */
	moveToNonIntrusiveBookMark: function (range, bookMark) {
		if (bookMark.start) {
			// Get the start information
			var startContainer = this.editor.getNodeByPosition(bookMark.start, bookMark.normalized),
				startOffset = bookMark.startOffset;
			// Set the start boundary
			range.setStart(startContainer, startOffset);
			// Get the end information
			var endContainer = bookMark.end && this.editor.getNodeByPosition(bookMark.end, bookMark.normalized),
				endOffset = bookMark.endOffset;
			// Set the end boundary. If not available, collapse the range
			if (endContainer) {
				range.setEnd(endContainer, endOffset);
			} else {
				range.collapse(true);
			}
		}
		return range;
	}
});
/***************************************************
 *  HTMLArea.DOM.Node: Node object
 ***************************************************/
HTMLArea.DOM.Node = function (config) {
};
HTMLArea.DOM.Node = Ext.extend(HTMLArea.DOM.Node, {
	/*
	 * Reference to the editor MUST be set in config
	 */
	editor: null,
	/*
	 * Reference to the editor document
	 */
	document: null,
	/*
	 * Reference to the editor selection object
	 */
	selection: null,
	/*
	 * Reference to the editor bookmark object
	 */
	bookMark: null,
	/*
	 * HTMLArea.DOM.Selection constructor
	 */
	constructor: function (config) {
		 	// Apply config
		Ext.apply(this, config);
			// Initialize references
		this.document = this.editor.document;
		this.selection = this.editor.getSelection();
		this.bookMark = this.editor.getBookMark();
	},
	/*
	 * Remove the given element
	 *
	 * @param	object		element: the element to be removed, content and selection being preserved
	 *
	 * @return	void
	 */
	removeMarkup: function (element) {
		var bookMark = this.bookMark.get(this.selection.createRange());
		var parent = element.parentNode;
		while (element.firstChild) {
			parent.insertBefore(element.firstChild, element);
		}
		parent.removeChild(element);
		this.selection.selectRange(this.bookMark.moveTo(bookMark));
	},
	/*
	 * Wrap the range with an inline element
	 *
	 * @param	string	element: the node that will wrap the range
	 * @param	object	range: the range to be wrapped
	 *
	 * @return	void
	 */
	wrapWithInlineElement: function (element, range) {
		if (HTMLArea.isIEBeforeIE9) {
			var nodeName = element.nodeName;
			var bookMark = this.bookMark.get(range);
			if (range.parentElement) {
				var parent = range.parentElement();
				var rangeStart = range.duplicate();
				rangeStart.collapse(true);
				var parentStart = rangeStart.parentElement();
				var rangeEnd = range.duplicate();
				rangeEnd.collapse(true);
				var newRange = this.selection.createRange();

				var parentEnd = rangeEnd.parentElement();
				var upperParentStart = parentStart;
				if (parentStart !== parent) {
					while (upperParentStart.parentNode !== parent) {
						upperParentStart = upperParentStart.parentNode;
					}
				}

				element.innerHTML = range.htmlText;
					// IE eats spaces on the start boundary
				if (range.htmlText.charAt(0) === '\x20') {
					element.innerHTML = '&nbsp;' + element.innerHTML;
				}
				var elementClone = element.cloneNode(true);
				range.pasteHTML(element.outerHTML);
					// IE inserts the element as the last child of the start container
				if (parentStart !== parent
						&& parentStart.lastChild
						&& parentStart.lastChild.nodeType === HTMLArea.DOM.ELEMENT_NODE
						&& parentStart.lastChild.nodeName.toLowerCase() === nodeName) {
					parent.insertBefore(elementClone, upperParentStart.nextSibling);
					parentStart.removeChild(parentStart.lastChild);
						// Sometimes an empty previous sibling was created
					if (elementClone.previousSibling
							&& elementClone.previousSibling.nodeType === HTMLArea.DOM.ELEMENT_NODE
							&& !elementClone.previousSibling.innerText) {
						parent.removeChild(elementClone.previousSibling);
					}
						// The bookmark will not work anymore
					newRange.moveToElementText(elementClone);
					newRange.collapse(false);
					newRange.select();
				} else {
						// Working around IE boookmark bug
					if (parentStart != parentEnd) {
						var newRange = this.selection.createRange();
						if (newRange.moveToBookmark(bookMark)) {
							newRange.collapse(false);
							newRange.select();
						}
					} else {
						range.collapse(false);
					}
				}
				parent.normalize();
			} else {
				var parent = range.item(0);
				element = parent.parentNode.insertBefore(element, parent);
				element.appendChild(parent);
				this.bookMark.moveTo(bookMark);
			}
		} else {
			element.appendChild(range.extractContents());
			range.insertNode(element);
			element.normalize();
				// Sometimes Firefox inserts empty elements just outside the boundaries of the range
			var neighbour = element.previousSibling;
			if (neighbour && (neighbour.nodeType !== HTMLArea.DOM.TEXT_NODE) && !/\S/.test(neighbour.textContent)) {
				HTMLArea.DOM.removeFromParent(neighbour);
			}
			neighbour = element.nextSibling;
			if (neighbour && (neighbour.nodeType !== HTMLArea.DOM.TEXT_NODE) && !/\S/.test(neighbour.textContent)) {
				HTMLArea.DOM.removeFromParent(neighbour);
			}
			this.selection.selectNodeContents(element, false);
		}
	},
	/*
	 * Get the position of the node within the document tree.
	 * The tree address returned is an array of integers, with each integer
	 * indicating a child index of a DOM node, starting from
	 * document.documentElement.
	 * The position cannot be used for finding back the DOM tree node once
	 * the DOM tree structure has been modified.
	 * Adapted from FCKeditor
	 *
	 * @param	object		node: the DOM node
	 * @param	boolean		normalized: if true, a normalized position is calculated
	 *
	 * @return	array		the position of the node
	 */
	getPositionWithinTree: function (node, normalized) {
		var documentElement = this.document.documentElement,
			current = node,
			position = [];
		while (current && current != documentElement) {
			var parentNode = current.parentNode;
			if (parentNode) {
				// Get the current node position
				position.unshift(HTMLArea.DOM.getPositionWithinParent(current, normalized));
			}
			current = parentNode;
		}
		return position;
	},
	/*
	 * Clean Apple wrapping span and font elements under the specified node
	 *
	 * @param	object		node: the node in the subtree of which cleaning is performed
	 *
	 * @return	void
	 */
	cleanAppleStyleSpans: function (node) {
		if (Ext.isWebKit) {
			if (node.getElementsByClassName) {
				var spans = node.getElementsByClassName('Apple-style-span');
				for (var i = spans.length; --i >= 0;) {
					this.removeMarkup(spans[i]);
				}
			} else {
				var spans = node.getElementsByTagName('span');
				for (var i = spans.length; --i >= 0;) {
					if (HTMLArea.DOM.hasClass(spans[i], 'Apple-style-span')) {
						this.removeMarkup(spans[i]);
					}
				}
				var fonts = node.getElementsByTagName('font');
				for (i = fonts.length; --i >= 0;) {
					if (HTMLArea.DOM.hasClass(fonts[i], 'Apple-style-span')) {
						this.removeMarkup(fonts[i]);
					}
				}
			}
		}
	}
});
 /*
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.getInnerText = HTMLArea.DOM.getInnerText;
 /*
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.removeFromParent = HTMLArea.DOM.removeFromParent;
/*
 * Get the block ancestors of an element within a given block
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.getBlockAncestors = HTMLArea.DOM.getBlockAncestors;
/*
 * This function verifies if the element has any allowed attributes
 *
 * @param	object	element: the DOM element
 * @param	array	allowedAttributes: array of allowed attribute names
 *
 * @return	boolean	true if the element has one of the allowed attributes
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.hasAllowedAttributes = HTMLArea.DOM.hasAllowedAttributes;
/*
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.isBlockElement = HTMLArea.DOM.isBlockElement;
/*
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.needsClosingTag = HTMLArea.DOM.needsClosingTag;
/*
 * Get the current selection object
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype._getSelection = function() {
	this.appendToLog('HTMLArea.Editor', '_getSelection', 'Reference to deprecated method', 'warn');
	return this.getSelection().get().selection;
};
/*
 * Empty the selection object
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.emptySelection = function (selection) {
	this.appendToLog('HTMLArea.Editor', 'emptySelection', 'Reference to deprecated method', 'warn');
	this.getSelection().empty();
};
/*
 * Add a range to the selection
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.addRangeToSelection = function(selection, range) {
	this.appendToLog('HTMLArea.Editor', 'addRangeToSelection', 'Reference to deprecated method', 'warn');
	this.getSelection().addRange(range);
};
/*
 * Create a range for the current selection
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype._createRange = function(sel) {
	this.appendToLog('HTMLArea.Editor', '_createRange', 'Reference to deprecated method', 'warn');
	return this.getSelection().createRange();
};
/*
 * Select a node AND the contents inside the node
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.selectNode = function(node, endPoint) {
	this.appendToLog('HTMLArea.Editor', 'selectNode', 'Reference to deprecated method', 'warn');
	this.getSelection().selectNode(node, endPoint);
};
/*
 * Select ONLY the contents inside the given node
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.selectNodeContents = function(node, endPoint) {
	this.appendToLog('HTMLArea.Editor', 'selectNodeContents', 'Reference to deprecated method', 'warn');
	this.getSelection().selectNodeContents(node, endPoint);
};
/*
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.rangeIntersectsNode = function(range, node) {
	this.appendToLog('HTMLArea.Editor', 'rangeIntersectsNode', 'Reference to deprecated method', 'warn');
	this.focus();
	return HTMLArea.DOM.rangeIntersectsNode(range, node);
};
/*
 * Get the selection type
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.getSelectionType = function(selection) {
	this.appendToLog('HTMLArea.Editor', 'getSelectionType', 'Reference to deprecated method', 'warn');
	return this.getSelection().getType();
};
/*
 * Return the ranges of the selection
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.getSelectionRanges = function(selection) {
	this.appendToLog('HTMLArea.Editor', 'getSelectionRanges', 'Reference to deprecated method', 'warn');
	return this.getSelection().getRanges();
};
/*
 * Add ranges to the selection
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.setSelectionRanges = function(ranges, selection) {
	this.appendToLog('HTMLArea.Editor', 'setSelectionRanges', 'Reference to deprecated method', 'warn');
	this.getSelection().setRanges(ranges);
};
/*
 * Retrieves the selected element (if any), just in the case that a single element (object like and image or a table) is selected.
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.getSelectedElement = function(selection) {
	this.appendToLog('HTMLArea.Editor', 'getSelectedElement', 'Reference to deprecated method', 'warn');
	return this.getSelection().getElement();
};
/*
 * Retrieve the HTML contents of selected block
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.getSelectedHTML = function() {
	this.appendToLog('HTMLArea.Editor', 'getSelectedHTML', 'Reference to deprecated method', 'warn');
	return this.getSelection().getHtml();
};
/*
 * Retrieve simply HTML contents of the selected block, IE ignoring control ranges
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.getSelectedHTMLContents = function() {
	this.appendToLog('HTMLArea.Editor', 'getSelectedHTMLContents', 'Reference to deprecated method', 'warn');
	return this.getSelection().getHtml();
};
/*
 * Get the deepest node that contains both endpoints of the current selection.
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.getParentElement = function(selection, range) {
	this.appendToLog('HTMLArea.Editor', 'getParentElement', 'Reference to deprecated method', 'warn');
	return this.getSelection().getParentElement();
};
/*
 * Determine if the current selection is empty or not.
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype._selectionEmpty = function(sel) {
	this.appendToLog('HTMLArea.Editor', '_selectionEmpty', 'Reference to deprecated method', 'warn');
	return this.getSelection().isEmpty();
};
/*
 * Get a bookmark
 * Adapted from FCKeditor
 * This is an "intrusive" way to create a bookmark. It includes <span> tags
 * in the range boundaries. The advantage of it is that it is possible to
 * handle DOM mutations when moving back to the bookmark.
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.getBookmark = function (range) {
	this.appendToLog('HTMLArea.Editor', 'getBookmark', 'Reference to deprecated method', 'warn');
	return this.getBookMark().get(range);
};
/*
 * Get the end point of the bookmark
 * Adapted from FCKeditor
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.getBookmarkNode = function(bookmark, endPoint) {
	this.appendToLog('HTMLArea.Editor', 'getBookmarkNode', 'Reference to deprecated method', 'warn');
	return this.getBookMark().getEndPoint(bookmark, endPoint);
};
/*
 * Move the range to the bookmark
 * Adapted from FCKeditor
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.moveToBookmark = function (bookmark) {
	this.appendToLog('HTMLArea.Editor', 'moveToBookmark', 'Reference to deprecated method', 'warn');
	return this.getBookMark().moveTo(bookmark);
};
/*
 * Select range
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.selectRange = function (range) {
	this.appendToLog('HTMLArea.Editor', 'selectRange', 'Reference to deprecated method', 'warn');
	this.selection.selectRange(range);
};
 /*
 * Insert a node at the current position.
 * Delete the current selection, if any.
 * Split the text node, if needed.
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.insertNodeAtSelection = function(toBeInserted) {
	this.appendToLog('HTMLArea.Editor', 'insertNodeAtSelection', 'Reference to deprecated method', 'warn');
	this.getSelection().insertNode(toBeInserted);
};
/*
 * Insert HTML source code at the current position.
 * Delete the current selection, if any.
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.insertHTML = function(html) {
	this.appendToLog('HTMLArea.Editor', 'insertHTML', 'Reference to deprecated method', 'warn');
	this.getSelection().insertHtml(html);
};
/*
 * Wrap the range with an inline element
 *
 * @param	string	element: the node that will wrap the range
 * @param	object	range: the range to be wrapped
 *
 * @return	void
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.wrapWithInlineElement = function(element, selection,range) {
	this.appendToLog('HTMLArea.Editor', 'wrapWithInlineElement', 'Reference to deprecated method', 'warn');
	this.getDomNode().wrapWithInlineElement(element, range);
};
/*
 * Clean Apple wrapping span and font elements under the specified node
 *
 * @param	object		node: the node in the subtree of which cleaning is performed
 *
 * @return	void
 *
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.7 *
 ***********************************************
 */
HTMLArea.Editor.prototype.cleanAppleStyleSpans = function(node) {
	this.appendToLog('HTMLArea.Editor', 'cleanAppleStyleSpans', 'Reference to deprecated method', 'warn');
	this.getDomNode().cleanAppleStyleSpans(node);
};
/***************************************************
 *  HTMLArea.CSS.Parser: CSS Parser
 ***************************************************/
HTMLArea.CSS.Parser = Ext.extend(Ext.util.Observable, {
	/*
	 * HTMLArea.CSS.Parser constructor
	 */
	constructor: function (config) {
		HTMLArea.CSS.Parser.superclass.constructor.call(this, {});
		var configDefaults = {
			parseAttemptsMaximumNumber: 20,
			prefixLabelWithClassName: false,
			postfixLabelWithClassName: false,
			showTagFreeClasses: false,
			tags: null,
			editor: null
		};
		Ext.apply(this, config, configDefaults);
		if (this.editor.config.styleSheetsMaximumAttempts) {
			this.parseAttemptsMaximumNumber = this.editor.config.styleSheetsMaximumAttempts;
		}
		this.addEvents(
			/*
			 * @event HTMLAreaEventCssParsingComplete
			 * Fires when parsing of the stylesheets of the iframe is complete
			 */
			'HTMLAreaEventCssParsingComplete'
		);
	},
	/*
	 * The parsed classes
	 */
	parsedClasses: {},
	/*
	 * Boolean indicating whether are not parsing is complete
	 */
	isReady: false,
	/*
	 * Boolean indicating whether or not the stylesheets were accessible
	 */
	cssLoaded: false,
	/*
	 * Counter of the number of attempts at parsing the stylesheets
	 */
	parseAttemptsCounter: 0,
	/*
	 * Parsing attempt timeout id
	 */
	attemptTimeout: null,
	/*
	 * The error that occurred on the last attempt at parsing the stylesheets
	 */
	error: null,
	/*
	 * This function gets the parsed css classes
	 *
	 * @return	object	this.parsedClasses
	 */
	getClasses: function() {
		return this.parsedClasses;
	},
	/*
	 * This function initiates parsing of the stylesheets
	 *
	 * @return	void
	 */
	initiateParsing: function () {
		if (this.editor.config.classesUrl && (typeof(HTMLArea.classesLabels) === 'undefined')) {
			this.editor.ajax.getJavascriptFile(this.editor.config.classesUrl, function (options, success, response) {
				if (success) {
					try {
						if (typeof(HTMLArea.classesLabels) === 'undefined') {
							eval(response.responseText);
						}
					} catch(e) {
						this.editor.appendToLog('HTMLArea.CSS.Parser', 'initiateParsing', 'Error evaluating contents of Javascript file: ' + this.editor.config.classesUrl, 'error');
					}
				}
				this.parse();
			}, this);
		} else {
			this.parse();
		}
	},
	/*
	 * This function parses the stylesheets of the iframe set in config
	 *
	 * @return	void	parsed css classes are accumulated in this.parsedClasses
	 */
	parse: function() {
		if (this.editor.document) {
			this.parseStyleSheets();
			if (!this.cssLoaded) {
				if (/Security/i.test(this.error)) {
					this.editor.appendToLog('HTMLArea.CSS.Parser', 'parse', 'A security error occurred. Make sure all stylesheets are accessed from the same domain/subdomain and using the same protocol as the current script.', 'error');
					this.fireEvent('HTMLAreaEventCssParsingComplete');
				} else if (this.parseAttemptsCounter < this.parseAttemptsMaximumNumber) {
					this.parseAttemptsCounter++;
					this.attemptTimeout = this.parse.defer(200, this);
				} else {
					this.editor.appendToLog('HTMLArea.CSS.Parser', 'parse', 'The stylesheets could not be parsed. Reported error: ' + this.error, 'error');
					this.fireEvent('HTMLAreaEventCssParsingComplete');
				}
			} else {
				this.attemptTimeout = null;
				this.isReady = true;
				this.filterAllowedClasses();
				this.sort();
				this.fireEvent('HTMLAreaEventCssParsingComplete');
			}
		}
	},
	/*
	 * This function parses the stylesheets of an iframe
	 *
	 * @return	void	parsed css classes are accumulated in this.parsedClasses
	 */
	parseStyleSheets: function () {
		this.cssLoaded = true;
		this.error = null;
			// Test if the styleSheets array is at all accessible
		if (Ext.isOpera) {
			if (this.editor.document.readyState !== 'complete') {
				this.cssLoaded = false;
				this.error = 'Document.readyState not complete';
			}
		} else {
			if (HTMLArea.isIEBeforeIE9) {
				try {
					var rules = this.editor.document.styleSheets[0].rules;
					var imports = this.editor.document.styleSheets[0].imports;
					if (!rules.length && !imports.length) {
						this.cssLoaded = false;
						this.error = 'Empty rules and imports arrays';
					}
				} catch(e) {
					this.cssLoaded = false;
					this.error = e;
				}
			} else {
				try {
					this.editor.document.styleSheets && this.editor.document.styleSheets[0] && this.editor.document.styleSheets[0].rules;
				} catch(e) {
					this.cssLoaded = false;
					this.error = e;
				}
			}
		}
		if (this.cssLoaded) {
				// Expecting 2 stylesheets...
			if (this.editor.document.styleSheets.length > 1) {
				Ext.each(this.editor.document.styleSheets, function (styleSheet, index) {
					try {
						if (HTMLArea.isIEBeforeIE9) {
							var rules = styleSheet.rules;
							var imports = styleSheet.imports;
							if (!rules.length && !imports.length) {
								this.cssLoaded = false;
								this.error = 'Empty rules and imports arrays of styleSheets[' + index + ']';
								return false;
							}
							if (styleSheet.imports) {
								this.parseIeRules(styleSheet.imports);
							}
							if (styleSheet.rules) {
								this.parseRules(styleSheet.rules);
							}
						} else {
							this.parseRules(styleSheet.cssRules);
						}
					} catch (e) {
						this.error = e;
						this.cssLoaded = false;
						this.parsedClasses = {};
						return false;
					}
				}, this);
			} else {
				this.cssLoaded = false;
				this.error = 'Empty stylesheets array or missing linked stylesheets';
			}
		}
	},
	/*
	 * This function parses the set of rules from a standard stylesheet
	 *
	 * @param	array		cssRules: the array of rules of a stylesheet
	 * @return	void
	 */
	parseRules: function (cssRules) {
		for (var rule = 0; rule < cssRules.length; rule++) {
				// Style rule
			if (cssRules[rule].selectorText) {
				this.parseSelectorText(cssRules[rule].selectorText);
			} else {
					// Import rule
				if (cssRules[rule].styleSheet && cssRules[rule].styleSheet.cssRules) {
					this.parseRules(cssRules[rule].styleSheet.cssRules);
				}
					// Media rule
				if (cssRules[rule].cssRules) {
					this.parseRules(cssRules[rule].cssRules);
				}
			}
		}
	},
	/*
	 * This function parses the set of rules from an IE stylesheet
	 *
	 * @param	array		cssRules: the array of rules of a stylesheet
	 * @return	void
	 */
	parseIeRules: function (cssRules) {
		for (var rule = 0; rule < cssRules.length; rule++) {
				// Import rule
			if (cssRules[rule].imports) {
				this.parseIeRules(cssRules[rule].imports);
			}
				// Style rule
			if (cssRules[rule].rules) {
				this.parseRules(cssRules[rule].rules);
			}
		}
	},
	/*
	 * This function parses a selector rule
	 *
	 * @param 	string		selectorText: the text of the rule to parsed
	 * @return	void
	 */
	parseSelectorText: function (selectorText) {
		var cssElements = [],
			cssElement = [],
			nodeName, className,
			pattern = /(\S*)\.(\S+)/;
		if (selectorText.search(/:+/) == -1) {
				// Split equal styles
			cssElements = selectorText.split(',');
			for (var k = 0; k < cssElements.length; k++) {
					// Match all classes (<element name (optional)>.<class name>) in selector rule
				var s = cssElements[k], index;
				while ((index = s.search(pattern)) > -1) {
					var match = pattern.exec(s.substring(index));
					s = s.substring(index+match[0].length);
					nodeName = (match[1] && (match[1] != '*')) ? match[1].toLowerCase().trim() : 'all';
					className = match[2];
					if (className && !HTMLArea.reservedClassNames.test(className)) {
						if (((nodeName != 'all') && (!this.tags || !this.tags[nodeName]))
							|| ((nodeName == 'all') && (!this.tags || !this.tags[nodeName]) && this.showTagFreeClasses)
							|| (this.tags && this.tags[nodeName] && this.tags[nodeName].allowedClasses && this.tags[nodeName].allowedClasses.test(className))) {
							if (!this.parsedClasses[nodeName]) {
								this.parsedClasses[nodeName] = {};
							}
							cssName = className;
							if (HTMLArea.classesLabels && HTMLArea.classesLabels[className]) {
								cssName = this.prefixLabelWithClassName ? (className + ' - ' + HTMLArea.classesLabels[className]) : HTMLArea.classesLabels[className];
								cssName = this.postfixLabelWithClassName ? (cssName + ' - ' + className) : cssName;
							}
							this.parsedClasses[nodeName][className] = cssName;
						}
					}
				}
			}
		}
	},
	/*
	 * This function filters the class selectors allowed for each nodeName
	 *
	 * @return	void
	 */
	filterAllowedClasses: function() {
		Ext.iterate(this.tags, function (nodeName) {
			var allowedClasses = {};
				// Get classes allowed for all tags
			if (nodeName !== 'all' && Ext.isDefined(this.parsedClasses['all'])) {
				if (this.tags && this.tags[nodeName] && this.tags[nodeName].allowedClasses) {
					var allowed = this.tags[nodeName].allowedClasses;
					Ext.iterate(this.parsedClasses['all'], function (cssClass, value) {
						if (allowed.test(cssClass)) {
							allowedClasses[cssClass] = value;
						}
					});
				} else {
					allowedClasses = this.parsedClasses['all'];
				}
			}
				// Merge classes allowed for nodeName
			if (Ext.isDefined(this.parsedClasses[nodeName])) {
				if (this.tags && this.tags[nodeName] && this.tags[nodeName].allowedClasses) {
					var allowed = this.tags[nodeName].allowedClasses;
					Ext.iterate(this.parsedClasses[nodeName], function (cssClass, value) {
						if (allowed.test(cssClass)) {
							allowedClasses[cssClass] = value;
						}
					});
				} else {
					Ext.iterate(this.parsedClasses[nodeName], function (cssClass, value) {
						allowedClasses[cssClass] = value;
					});
				}
			}
			this.parsedClasses[nodeName] = allowedClasses;
		}, this);
			// If showTagFreeClasses is set and there is no allowedClasses clause on a tag, merge classes allowed for all tags
		if (this.showTagFreeClasses && Ext.isDefined(this.parsedClasses['all'])) {
			Ext.iterate(this.parsedClasses, function (nodeName) {
				if (nodeName !== 'all' && !this.tags[nodeName]) {
					Ext.iterate(this.parsedClasses['all'], function (cssClass, value) {
						this.parsedClasses[nodeName][cssClass] = value;
					}, this);
				}
			}, this);
		}
	},
	/*
	 * This function sorts the class selectors for each nodeName
	 *
	 * @return	void
	 */
	sort: function() {
		Ext.iterate(this.parsedClasses, function (nodeName, value) {
			var classes = [];
			var sortedClasses= {};
				// Collect keys
			Ext.iterate(value, function (cssClass) {
				classes.push(cssClass);
			});
			function compare(a, b) {
				x = value[a];
				y = value[b];
				return ((x < y) ? -1 : ((x > y) ? 1 : 0));
			}
				// Sort keys by comparing texts
			classes = classes.sort(compare);
			for (var i = 0; i < classes.length; ++i) {
				sortedClasses[classes[i]] = value[classes[i]];
			}
			this.parsedClasses[nodeName] = sortedClasses;
		}, this);
	}
});
/***************************************************
 *  TIPS ON FORM FIELDS AND MENU ITEMS
 ***************************************************/
/*
 * Intercept Ext.form.Field.afterRender in order to provide tips on form fields and menu items
 * Adapted from: http://www.extjs.com/forum/showthread.php?t=36642
 */
HTMLArea.util.Tips = function () {
	return {
		tipsOnFormFields: function () {
			if (this.helpText || this.helpTitle) {
				if (!this.helpDisplay) {
					this.helpDisplay = 'both';
				}
				var label = this.label;
					// IE has problems with img inside label tag
				if (label && this.helpIcon && !Ext.isIE) {
					var helpImage = label.insertFirst({
						tag: 'img',
						src: HTMLArea.editorSkin + 'images/system-help-open.png',
						style: 'vertical-align: middle; padding-right: 2px;'
					});
					if (this.helpDisplay == 'image' || this.helpDisplay == 'both'){
						Ext.QuickTips.register({
							target: helpImage,
							title: this.helpTitle,
							text: this.helpText
						});
					}
				}
				if (this.helpDisplay == 'field' || this.helpDisplay == 'both'){
					Ext.QuickTips.register({
						target: this,
						title: this.helpTitle,
						text: this.helpText
					});
				}
			}
		},
		tipsOnMenuItems: function () {
			if (this.helpText || this.helpTitle) {
				Ext.QuickTips.register({
					target: this,
					title: this.helpTitle,
					text: this.helpText
				});
			}
		}
	}
}();
Ext.form.Field.prototype.afterRender = Ext.form.Field.prototype.afterRender.createInterceptor(HTMLArea.util.Tips.tipsOnFormFields);
Ext.menu.BaseItem.prototype.afterRender = Ext.menu.BaseItem.prototype.afterRender.createInterceptor(HTMLArea.util.Tips.tipsOnMenuItems);
/***************************************************
 *  COLOR WIDGETS AND UTILITIES
 ***************************************************/
HTMLArea.util.Color = function () {
	return {
		/*
		 * Returns a rgb-style color from a number
		 */
		colorToRgb: function(v) {
			if (typeof(v) != 'number') {
				return v;
			}
			var r = v & 0xFF;
			var g = (v >> 8) & 0xFF;
			var b = (v >> 16) & 0xFF;
			return 'rgb(' + r + ',' + g + ',' + b + ')';
		},
		/*
		 * Returns hexadecimal color representation from a number or a rgb-style color.
		 */
		colorToHex: function(v) {
			if (typeof(v) === 'undefined' || v == null) {
				return '';
			}
			function hex(d) {
				return (d < 16) ? ('0' + d.toString(16)) : d.toString(16);
			};
			if (typeof(v) == 'number') {
				var b = v & 0xFF;
				var g = (v >> 8) & 0xFF;
				var r = (v >> 16) & 0xFF;
				return '#' + hex(r) + hex(g) + hex(b);
			}
			if (v.substr(0, 3) === 'rgb') {
				var re = /rgb\s*\(\s*([0-9]+)\s*,\s*([0-9]+)\s*,\s*([0-9]+)\s*\)/;
				if (v.match(re)) {
					var r = parseInt(RegExp.$1);
					var g = parseInt(RegExp.$2);
					var b = parseInt(RegExp.$3);
					return ('#' + hex(r) + hex(g) + hex(b)).toUpperCase();
				}
				return '';
			}
			if (v.substr(0, 1) === '#') {
				return v;
			}
			return '';
		},
		/*
		 * Select interceptor to ensure that the color exists in the palette before trying to select
		 */
		checkIfColorInPalette: function (color) {
				// Do not continue if the new color is not in the palette
			if (this.el && !this.el.child('a.color-' + color)) {
					// Remove any previous selection
				this.deSelect();
				return false;
			}
		}
	}
}();
/*
 * Intercept Ext.ColorPalette.prototype.select
 */
Ext.ColorPalette.prototype.select = Ext.ColorPalette.prototype.select.createInterceptor(HTMLArea.util.Color.checkIfColorInPalette);
/*
 * Add deSelect method to Ext.ColorPalette
 */
Ext.override(Ext.ColorPalette, {
	deSelect: function () {
		if (this.el && this.value){
			this.el.child('a.color-' + this.value).removeClass('x-color-palette-sel');
			this.value = null;
		}
	}
});
Ext.ux.menu.HTMLAreaColorMenu = Ext.extend(Ext.menu.Menu, {
	enableScrolling: false,
	hideOnClick: true,
	cls: 'x-color-menu',
	colorPaletteValue: '',
	customColorsValue: '',
	plain: true,
	showSeparator: false,
	initComponent: function () {
		var paletteItems = [];
		var width = 'auto';
		if (this.colorsConfiguration) {
			paletteItems.push({
				xtype: 'container',
				layout: 'anchor',
				width: 160,
				style: { float: 'right' },
				items: {
					xtype: 'colorpalette',
					itemId: 'custom-colors',
					cls: 'htmlarea-custom-colors',
					colors: this.colorsConfiguration,
					value: this.value,
					allowReselect: true,
					tpl: new Ext.XTemplate(
						'<tpl for="."><a href="#" class="color-{1}" hidefocus="on"><em><span style="background:#{1}" unselectable="on">&#160;</span></em><span unselectable="on">{0}</span></a></tpl>'
					)
				}
			});
		}
		if (this.colors.length) {
			paletteItems.push({
				xtype: 'container',
				layout: 'anchor',
				items: {
					xtype: 'colorpalette',
					itemId: 'color-palette',
					cls: 'color-palette',
					colors: this.colors,
					value: this.value,
					allowReselect: true
				}
			});
		}
		if (this.colorsConfiguration && this.colors.length) {
			width = 350;
		}
		Ext.apply(this, {
			layout: 'menu',
			width: width,
			items: paletteItems
		});
		Ext.ux.menu.HTMLAreaColorMenu.superclass.initComponent.call(this);
		this.standardPalette = this.find('itemId', 'color-palette')[0];
		this.customPalette = this.find('itemId', 'custom-colors')[0];
		if (this.standardPalette) {
			this.standardPalette.purgeListeners();
			this.relayEvents(this.standardPalette, ['select']);
		}
		if (this.customPalette) {
			this.customPalette.purgeListeners();
			this.relayEvents(this.customPalette, ['select']);
		}
		this.on('select', this.menuHide, this);
		if (this.handler){
			this.on('select', this.handler, this.scope || this);
		}
	},
	menuHide: function() {
		if (this.hideOnClick){
			this.hide(true);
		}
	}
});
Ext.reg('htmlareacolormenu', Ext.ux.menu.HTMLAreaColorMenu);
/*
 * Color palette trigger field
 * Based on http://www.extjs.com/forum/showthread.php?t=89312
 */
Ext.ux.form.ColorPaletteField = Ext.extend(Ext.form.TriggerField, {
	triggerClass: 'x-form-color-trigger',
	defaultColors: [
		'000000', '222222', '444444', '666666', '999999', 'BBBBBB', 'DDDDDD', 'FFFFFF',
		'660000', '663300', '996633', '003300', '003399', '000066', '330066', '660066',
		'990000', '993300', 'CC9900', '006600', '0033FF', '000099', '660099', '990066',
		'CC0000', 'CC3300', 'FFCC00', '009900', '0066FF', '0000CC', '663399', 'CC0099',
		'FF0000', 'FF3300', 'FFFF00', '00CC00', '0099FF', '0000FF', '9900CC', 'FF0099',
		'CC3333', 'FF6600', 'FFFF33', '00FF00', '00CCFF', '3366FF', '9933FF', 'FF00FF',
		'FF6666', 'FF6633', 'FFFF66', '66FF66', '00FFFF', '3399FF', '9966FF', 'FF66FF',
		'FF9999', 'FF9966', 'FFFF99', '99FF99', '99FFFF', '66CCFF', '9999FF', 'FF99FF',
		'FFCCCC', 'FFCC99', 'FFFFCC', 'CCFFCC', 'CCFFFF', '99CCFF', 'CCCCFF', 'FFCCFF'
	],
		// Whether or not the field background, text, or triggerbackgroud are set to the selected color
	colorizeFieldBackgroud: true,
	colorizeFieldText: true,
	colorizeTrigger: false,
	editable: true,
	initComponent: function () {
		Ext.ux.form.ColorPaletteField.superclass.initComponent.call(this);
		if (!this.colors) {
			this.colors = this.defaultColors;
		}
		this.addEvents(
			'select'
		);
	},
		// private
	validateBlur: function () {
		return !this.menu || !this.menu.isVisible();
	},
	setValue: function (color) {
		if (color) {
			if (this.colorizeFieldBackgroud) {
				this.el.applyStyles('background: #' + color  + ';');
			}
			if (this.colorizeFieldText) {
				this.el.applyStyles('color: #' + this.rgbToHex(this.invert(this.hexToRgb(color)))  + ';');
			}
			if (this.colorizeTrigger) {
				this.trigger.applyStyles('background-color: #' + color  + ';');
			}
		}
		return Ext.ux.form.ColorPaletteField.superclass.setValue.call(this, color);
	},
		// private
	onDestroy: function () {
		Ext.destroy(this.menu);
		Ext.ux.form.ColorPaletteField.superclass.onDestroy.call(this);
	},
		// private
	onTriggerClick: function () {
		if (this.disabled) {
			return;
		}
		if (this.menu == null) {
			this.menu = new Ext.ux.menu.HTMLAreaColorMenu({
				cls: 'htmlarea-color-menu',
				hideOnClick: false,
				colors: this.colors,
				colorsConfiguration: this.colorsConfiguration,
				value: this.getValue()
			});
		}
		this.onFocus();
		this.menu.show(this.el, "tl-bl?");
		this.menuEvents('on');
	},
		//private
	menuEvents: function (method) {
		this.menu[method]('select', this.onSelect, this);
		this.menu[method]('hide', this.onMenuHide, this);
		this.menu[method]('show', this.onFocus, this);
	},
	onSelect: function (m, d) {
		this.setValue(d);
		this.fireEvent('select', this, d);
		this.menu.hide();
	},
	onMenuHide: function () {
		this.focus(false, 60);
		this.menuEvents('un');
	},
	invert: function ( r, g, b ) {
		if( r instanceof Array ) { return this.invert.call( this, r[0], r[1], r[2] ); }
		return [255-r,255-g,255-b];
	},
	hexToRgb: function ( hex ) {
		return [ this.hexToDec( hex.substr(0, 2) ), this.hexToDec( hex.substr(2, 2) ), this.hexToDec( hex.substr(4, 2) ) ];
	},
	hexToDec: function( hex ) {
		var s = hex.split('');
		return ( ( this.getHCharPos( s[0] ) * 16 ) + this.getHCharPos( s[1] ) );
	},
	getHCharPos: function( c ) {
		var HCHARS = '0123456789ABCDEF';
		return HCHARS.indexOf( c.toUpperCase() );
	},
	rgbToHex: function( r, g, b ) {
		if( r instanceof Array ) { return this.rgbToHex.call( this, r[0], r[1], r[2] ); }
		return this.decToHex( r ) + this.decToHex( g ) + this.decToHex( b );
	},
	decToHex: function( n ) {
		var HCHARS = '0123456789ABCDEF';
		n = parseInt(n, 10);
		n = ( !isNaN( n )) ? n : 0;
		n = (n > 255 || n < 0) ? 0 : n;
		return HCHARS.charAt( ( n - n % 16 ) / 16 ) + HCHARS.charAt( n % 16 );
	}
});
Ext.reg('colorpalettefield', Ext.ux.form.ColorPaletteField);
/***************************************************
 * TYPO3-SPECIFIC FUNCTIONS
 ***************************************************/
/*
 * Extending the TYPO3 Lorem Ipsum extension
 */
var lorem_ipsum = function (element, text) {
	if (/^textarea$/i.test(element.nodeName) && element.id && element.id.substr(0,7) === 'RTEarea') {
		var editor = RTEarea[element.id.substr(7, element.id.length)]['editor'];
		editor.getSelection().insertHtml(text);
		editor.updateToolbar();
	}
};
/**
 * HTMLArea.plugin class
 *
 * Every plugin should be a subclass of this class
 *
 */
HTMLArea.Plugin = function (editor, pluginName) {
};
HTMLArea.Plugin = Ext.extend(HTMLArea.Plugin, {
	/**
	 * HTMLArea.Plugin constructor
	 *
	 * @param	object		editor: instance of RTE
	 * @param	string		pluginName: name of the plugin
	 *
	 * @return	boolean		true if the plugin was configured
	 */
	constructor: function (editor, pluginName) {
		this.editor = editor;
		this.editorNumber = editor.editorId;
		this.editorId = editor.editorId;
		this.editorConfiguration = editor.config;
		this.name = pluginName;
		try {
			this.I18N = HTMLArea.I18N[this.name];
		} catch(e) {
			this.I18N = new Object();
		}
		this.configurePlugin(editor);
	},
	/**
	 * Configures the plugin
	 * This function is invoked by the class constructor.
	 * This function should be redefined by the plugin subclass. Normal steps would be:
	 *	- registering plugin ingormation with method registerPluginInformation;
	 *	- registering any buttons with method registerButton;
	 *	- registering any drop-down lists with method registerDropDown.
	 *
	 * @param	object		editor: instance of RTE
	 *
	 * @return	boolean		true if the plugin was configured
	 */
	configurePlugin: function (editor) {
		return false;
	},
	/**
	 * Registers the plugin "About" information
	 *
	 * @param	object		pluginInformation:
	 *					version		: the version,
	 *					developer	: the name of the developer,
	 *					developerUrl	: the url of the developer,
	 *					copyrightOwner	: the name of the copyright owner,
	 *					sponsor		: the name of the sponsor,
	 *					sponsorUrl	: the url of the sponsor,
	 *					license		: the type of license (should be "GPL")
	 *
	 * @return	boolean		true if the information was registered
	 */
	registerPluginInformation: function (pluginInformation) {
		if (typeof(pluginInformation) !== 'object') {
			this.appendToLog('registerPluginInformation', 'Plugin information was not provided', 'warn');
			return false;
		} else {
			this.pluginInformation = pluginInformation;
			this.pluginInformation.name = this.name;
			return true;
		}
	},

	/**
	 * Returns the plugin information
	 *
	 * @return	object		the plugin information object
	 */
	getPluginInformation: function () {
		return this.pluginInformation;
	},

	/**
	 * Returns a plugin object
	 *
	 * @param	string		pluinName: the name of some plugin
	 * @return	object		the plugin object or null
	 */
	getPluginInstance: function (pluginName) {
		return this.editor.getPlugin(pluginName);
	},

	/**
	 * Returns a current editor mode
	 *
	 * @return	string		editor mode
	 */
	getEditorMode: function () {
		return this.editor.getMode();
	},

	/**
	 * Returns true if the button is enabled in the toolbar configuration
	 *
	 * @param	string		buttonId: identification of the button
	 *
	 * @return	boolean		true if the button is enabled in the toolbar configuration
	 */
	isButtonInToolbar: function (buttonId) {
		var index = -1;
		Ext.each(this.editorConfiguration.toolbar, function (row) {
			Ext.each(row, function (group) {
				index = group.indexOf(buttonId);
				return index === -1;
			});
			return index === -1;
		});
		return index !== -1;
	},

	/**
	 * Returns the button object from the toolbar
	 *
	 * @param	string		buttonId: identification of the button
	 *
	 * @return	object		the toolbar button object
	 */
	getButton: function (buttonId) {
		return this.editor.toolbar.getButton(buttonId);
	},
	/**
	 * Registers a button for inclusion in the toolbar
	 *
	 * @param	object		buttonConfiguration: the configuration object of the button:
	 *					id		: unique id for the button
	 *					tooltip		: tooltip for the button
	 *					textMode	: enable in text mode
	 *					action		: name of the function invoked when the button is pressed
	 *					context		: will be disabled if not inside one of listed elements
	 *					hide		: hide in menu and show only in context menu (deprecated, use hidden)
	 *					hidden		: synonym of hide
	 *					selection	: will be disabled if there is no selection
	 *					hotkey		: hotkey character
	 *					dialog		: if true, the button opens a dialogue
	 *					dimensions	: the opening dimensions object of the dialogue window
	 *
	 * @return	boolean		true if the button was successfully registered
	 */
	registerButton: function (buttonConfiguration) {
		if (this.isButtonInToolbar(buttonConfiguration.id)) {
			if (Ext.isString(buttonConfiguration.action) && Ext.isFunction(this[buttonConfiguration.action])) {
				buttonConfiguration.plugins = this;
				if (buttonConfiguration.dialog) {
					if (!buttonConfiguration.dimensions) {
						buttonConfiguration.dimensions = { width: 250, height: 250};
					}
					buttonConfiguration.dimensions.top = buttonConfiguration.dimensions.top ?  buttonConfiguration.dimensions.top : this.editorConfiguration.dialogueWindows.defaultPositionFromTop;
					buttonConfiguration.dimensions.left = buttonConfiguration.dimensions.left ?  buttonConfiguration.dimensions.left : this.editorConfiguration.dialogueWindows.defaultPositionFromLeft;
				}
				buttonConfiguration.hidden = buttonConfiguration.hide;
					// Apply additional ExtJS config properties set in Page TSConfig
					// May not always work for values that must be integers
				if (this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]]) {
					Ext.applyIf(buttonConfiguration, this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]]);
				}
				if (this.editorConfiguration.registerButton(buttonConfiguration)) {
					var hotKey = buttonConfiguration.hotKey ? buttonConfiguration.hotKey :
						((this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]] && this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]].hotKey) ? this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]].hotKey : null);
					if (!hotKey && buttonConfiguration.hotKey == "0") {
						hotKey = "0";
					}
					if (!hotKey && this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]] && this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]].hotKey == "0") {
						hotKey = "0";
					}
					if (hotKey || hotKey == "0") {
						var hotKeyConfiguration = {
							id	: hotKey,
							cmd	: buttonConfiguration.id
						};
						return this.registerHotKey(hotKeyConfiguration);
					}
					return true;
				}
			} else {
				this.appendToLog('registerButton', 'Function ' + buttonConfiguration.action + ' was not defined when registering button ' + buttonConfiguration.id, 'error');
			}
		}
		return false;
	},
	/**
	 * Registers a drop-down list for inclusion in the toolbar
	 *
	 * @param	object		dropDownConfiguration: the configuration object of the drop-down:
	 *					id		: unique id for the drop-down
	 *					tooltip		: tooltip for the drop-down
	 *					action		: name of function to invoke when an option is selected
	 *					textMode	: enable in text mode
	 *
	 * @return	boolean		true if the drop-down list was successfully registered
	 */
	registerDropDown: function (dropDownConfiguration) {
		if (this.isButtonInToolbar(dropDownConfiguration.id)) {
			if (Ext.isString(dropDownConfiguration.action) && Ext.isFunction(this[dropDownConfiguration.action])) {
				dropDownConfiguration.plugins = this;
				dropDownConfiguration.hidden = dropDownConfiguration.hide;
				dropDownConfiguration.xtype = 'htmlareacombo';
					// Apply additional ExtJS config properties set in Page TSConfig
					// May not always work for values that must be integers
				if (this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[dropDownConfiguration.id]]) {
					Ext.applyIf(dropDownConfiguration, this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[dropDownConfiguration.id]]);
				}
				return this.editorConfiguration.registerButton(dropDownConfiguration);
			} else {
				this.appendToLog('registerDropDown', 'Function ' + dropDownConfiguration.action + ' was not defined when registering drop-down ' + dropDownConfiguration.id, 'error');
			}
		}
		return false;
	},
	/**
	 * Registers a text element for inclusion in the toolbar
	 *
	 * @param	object		textConfiguration: the configuration object of the text element:
	 *					id		: unique id for the text item
	 *					text		: the text litteral
	 *					tooltip		: tooltip for the text item
	 *					cls		: a css class to be assigned to the text element
	 *
	 * @return	boolean		true if the drop-down list was successfully registered
	 */
	registerText: function (textConfiguration) {
		if (this.isButtonInToolbar(textConfiguration.id)) {
			textConfiguration.plugins = this;
			textConfiguration.xtype = 'htmlareatoolbartext';
			return this.editorConfiguration.registerButton(textConfiguration);
		}
		return false;
	},

	/**
	 * Returns the drop-down configuration
	 *
	 * @param	string		dropDownId: the unique id of the drop-down
	 *
	 * @return	object		the drop-down configuration object
	 */
	getDropDownConfiguration : function(dropDownId) {
		return this.editorConfiguration.buttonsConfig[dropDownId];
	},

	/**
	 * Registors a hotkey
	 *
	 * @param	object		hotKeyConfiguration: the configuration object of the hotkey:
	 *					id		: the key
	 *					cmd		: name of the button corresponding to the hot key
	 *					element		: value of the record to be selected in the dropDown item
	 *
	 * @return	boolean		true if the hotkey was successfully registered
	 */
	registerHotKey : function (hotKeyConfiguration) {
		return this.editorConfiguration.registerHotKey(hotKeyConfiguration);
	},

	/**
	 * Returns the buttonId corresponding to the hotkey, if any
	 *
	 * @param	string		key: the hotkey
	 *
	 * @return	string		the buttonId or ""
	 */
	translateHotKey : function(key) {
		if (typeof(this.editorConfiguration.hotKeyList[key]) !== "undefined") {
			var buttonId = this.editorConfiguration.hotKeyList[key].cmd;
			if (typeof(buttonId) !== "undefined") {
				return buttonId;
			} else {
				return "";
			}
		}
		return "";
	},

	/**
	 * Returns the hotkey configuration
	 *
	 * @param	string		key: the hotkey
	 *
	 * @return	object		the hotkey configuration object
	 */
	getHotKeyConfiguration: function(key) {
		if (Ext.isDefined(this.editorConfiguration.hotKeyList[key])) {
			return this.editorConfiguration.hotKeyList[key];
		} else {
			return null;
		}
	},
	/**
	 * Initializes the plugin
	 * Is invoked when the toolbar component is created (subclass of Ext.ux.HTMLAreaButton or Ext.ux.form.HTMLAreaCombo)
	 *
	 * @param	object		button: the component
	 *
	 * @return	void
	 */
	init: Ext.emptyFn,
	/**
	 * The toolbar refresh handler of the plugin
	 * This function may be defined by the plugin subclass.
	 * If defined, the function will be invoked whenever the toolbar state is refreshed.
	 *
	 * @return	boolean
	 */
	onUpdateToolbar: Ext.emptyFn,
	/**
	 * The onMode event handler
	 * This function may be redefined by the plugin subclass.
	 * The function is invoked whenever the editor changes mode.
	 *
	 * @param	string		mode: "wysiwyg" or "textmode"
	 *
	 * @return	boolean
	 */
	onMode: function(mode) {
		if (mode === "textmode" && this.dialog && !(this.dialog.buttonId && this.editorConfiguration.buttons[this.dialog.buttonId] && this.editorConfiguration.buttons[this.dialog.buttonId].textMode)) {
			this.dialog.close();
		}
	},
	/**
	 * The onGenerate event handler
	 * This function may be defined by the plugin subclass.
	 * The function is invoked when the editor is initialized
	 *
	 * @return	boolean
	 */
	onGenerate: Ext.emptyFn,
	/**
	 * Localize a string
	 *
	 * @param	string		label: the name of the label to localize
	 *
	 * @return	string		the localization of the label
	 */
	localize: function (label, plural) {
		var i = plural || 0;
		var localized = this.I18N[label];
		if (typeof localized === 'object' && typeof localized[i] !== 'undefined') {
			localized = localized[i]['target'];
		} else {
			localized = HTMLArea.localize(label, plural);
		}
		return localized;
	},
	/**
	 * Get localized label wrapped with contextual help markup when available
	 *
	 * @param	string		fieldName: the name of the field in the CSH file
	 * @param	string		label: the name of the label to localize
	 * @param	string		pluginName: overrides this.name
	 *
	 * @return	string		localized label with CSH markup
	 */
	getHelpTip: function (fieldName, label, pluginName) {
		if (Ext.isDefined(TYPO3.ContextHelp)) {
			var pluginName = Ext.isDefined(pluginName) ? pluginName : this.name;
			if (!Ext.isEmpty(fieldName)) {
				fieldName = fieldName.replace(/-|\s/gi, '_');
			}
			return '<span class="t3-help-link" href="#" data-table="xEXT_rtehtmlarea_' + pluginName + '" data-field="' + fieldName + '"><abbr class="t3-help-teaser">' + (this.localize(label) || label) + '</abbr></span>';
		} else {
			return this.localize(label) || label;
		}
	},
	/**
	 * Load a Javascript file asynchronously
	 *
	 * @param	string		url: url of the file to load
	 * @param	function	callBack: the callBack function
	 *
	 * @return	boolean		true on success of the request submission
	 */
	getJavascriptFile: function (url, callback) {
		return this.editor.ajax.getJavascriptFile(url, callback, this);
	},
	/**
	 * Post data to the server
	 *
	 * @param	string		url: url to post data to
	 * @param	object		data: data to be posted
	 * @param	function	callback: function that will handle the response returned by the server
	 *
	 * @return	boolean		true on success
	 */
	postData: function (url, data, callback) {
	 	return this.editor.ajax.postData(url, data, callback, this);
	},
	/*
	 * Open a window with container iframe
	 *
	 * @param	string		buttonId: the id of the button
	 * @param	string		title: the window title (will be localized here)
	 * @param	object		dimensions: the opening dimensions od the window
	 * @param	string		url: the url to load ino the iframe
	 *
	 * @ return	void
	 */
	openContainerWindow: function (buttonId, title, dimensions, url) {
		this.dialog = new Ext.Window({
			id: this.editor.editorId + buttonId,
			title: this.localize(title) || title,
			cls: 'htmlarea-window',
			width: dimensions.width,
			border: false,
			iconCls: this.getButton(buttonId).iconCls,
			listeners: {
				afterrender: {
					fn: this.onContainerResize
				},
				resize: {
					fn: this.onContainerResize
				},
				close: {
					fn: this.onClose,
					scope: this
				}
			},
			items: {
					// The content iframe
				xtype: 'box',
				height: dimensions.height-20,
				itemId: 'content-iframe',
				autoEl: {
					tag: 'iframe',
					cls: 'content-iframe',
					src: url
				}
			}
		});
		this.show();
	},
	/*
	 * Handler invoked when the container window is rendered or resized in order to resize the content iframe to maximum size
	 */
	onContainerResize: function (panel) {
		var iframe = panel.getComponent('content-iframe');
		if (iframe.rendered) {
			iframe.getEl().setSize(panel.getInnerWidth(), panel.getInnerHeight());
		}
	},
	/*
	 * Get the opening diment=sions of the window
	 *
	 * @param	object		dimensions: default opening width and height set by the plugin
	 * @param	string		buttonId: the id of the button that is triggering the opening of the window
	 *
	 * @return	object		opening width and height of the window
	 */
	getWindowDimensions: function (dimensions, buttonId) {
			// Apply default dimensions
		this.dialogueWindowDimensions = {
			width: 250,
			height: 250
		};
			// Apply default values as per PageTSConfig
		if (this.editorConfiguration.dialogueWindows) {
			Ext.apply(this.dialogueWindowDimensions, this.editorConfiguration.dialogueWindows);
		}
			// Apply dimensions as per button registration
		if (this.editorConfiguration.buttonsConfig[buttonId]) {
			Ext.apply(this.dialogueWindowDimensions, this.editorConfiguration.buttonsConfig[buttonId].dimensions);
		}
			// Apply dimensions as per call
		Ext.apply(this.dialogueWindowDimensions, dimensions);
			// Overrride dimensions as per PageTSConfig
		var buttonConfiguration = this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonId]];
		if (buttonConfiguration && buttonConfiguration.dialogueWindow) {
			Ext.apply(this.dialogueWindowDimensions, buttonConfiguration.dialogueWindow);
		}
		return this.dialogueWindowDimensions;
	},
	/**
	 * Make url from module path
	 *
	 * @param	string		modulePath: module path
	 * @param	string		parameters: additional parameters
	 *
	 * @return	string		the url
	 */
	makeUrlFromModulePath: function(modulePath, parameters) {
		return modulePath + '?' + this.editorConfiguration.RTEtsConfigParams + '&editorNo=' + this.editor.editorId + '&sys_language_content=' + this.editorConfiguration.sys_language_content + '&contentTypo3Language=' + this.editorConfiguration.typo3ContentLanguage + (parameters?parameters:'');
	},
	/**
	 * Append an entry at the end of the troubleshooting log
	 *
	 * @param	string		functionName: the name of the plugin function writing to the log
	 * @param	string		text: the text of the message
	 * @param	string		type: the typeof of message: 'log', 'info', 'warn' or 'error'
	 *
	 * @return	void
	 */
	appendToLog: function (functionName, text, type) {
		this.editor.appendToLog(this.name, functionName, text, type);
	},
	/*
	 * Add a config element to config array if not empty
	 *
	 * @param	object		configElement: the config element
	 * @param	array		configArray: the config array
	 *
	 * @return	void
	 */
	addConfigElement: function (configElement, configArray) {
		if (!Ext.isEmpty(configElement)) {
			configArray.push(configElement);
		}
	},
	/*
	 * Handler for Ext.TabPanel tabchange event
	 * Force window ghost height synchronization
	 * Working around ExtJS 3.1 bug
	 */
	syncHeight: function (tabPanel, tab) {
		var position = this.dialog.getPosition();
		if (position[0] > 0) {
			this.dialog.setPosition(position);
		}
	},
	/*
	 * Show the dialogue window
	 */
	show: function () {
			// Close the window if the editor changes mode
		this.dialog.mon(this.editor, 'HTMLAreaEventModeChange', this.close, this, {single: true });
		this.saveSelection();
		if (typeof(this.dialogueWindowDimensions) !== 'undefined') {
			this.dialog.setPosition(this.dialogueWindowDimensions.positionFromLeft, this.dialogueWindowDimensions.positionFromTop);
		}
		this.dialog.show();
		this.restoreSelection();
	},
	/*
	 * Close the dialogue window (after saving the selection, if IE)
	 */
	close: function () {
		this.saveSelection();
		this.dialog.close();
	},
	/*
	 * Dialogue window onClose handler
	 */
	onClose: function () {
		this.editor.focus();
		this.restoreSelection();
	 	this.editor.updateToolbar();
	},
	/*
	 * Handler for window cancel
	 */
	onCancel: function () {
		this.dialog.close();
		this.editor.focus();
	},
	/*
	 * Save selection
	 * Should be called after processing button other than Cancel
	 */
	saveSelection: function () {
			// If IE, save the current selection
		if (Ext.isIE) {
			this.savedRange = this.editor.getSelection().createRange();
		}
	},
	/*
	 * Restore selection
	 * Should be called before processing dialogue button or result
	 */
	restoreSelection: function () {
			// If IE, restore the selection saved when the window was shown
		if (Ext.isIE && this.savedRange) {
				// Restoring the selection will not work if the inner html was replaced by the plugin
			try {
				this.editor.getSelection().selectRange(this.savedRange);
			} catch (e) {}
		}
	},
	/*
	 * Build the configuration object of a button
	 *
	 * @param	string		button: the text of the button
	 * @param	function	handler: button handler
	 *
	 * @return	object		the button configuration object
	 */
	buildButtonConfig: function (button, handler) {
		return {
			xtype: 'button',
			text: this.localize(button),
			listeners: {
				click: {
					fn: handler,
					scope: this
				}
			}
		};
	}
});
}
