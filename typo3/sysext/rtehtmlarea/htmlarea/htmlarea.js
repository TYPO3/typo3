/***************************************************************
*  Copyright notice
*
*  (c) 2002-2004 interactivetools.com, inc.
*  (c) 2003-2004 dynarch.com
*  (c) 2004-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 *
 * TYPO3 SVN ID: $Id$
 */
	// Avoid re-initialization on AJax call when HTMLArea object was already initialized
if (typeof(HTMLArea) == 'undefined') {
	// Establish HTMLArea name space
Ext.namespace('HTMLArea.CSS', 'HTMLArea.util.TYPO3', 'HTMLArea.util.Tips', 'HTMLArea.util.Color', 'Ext.ux.form', 'Ext.ux.menu', 'Ext.ux.Toolbar');
Ext.apply(HTMLArea, {
	/*************************************************************************
	 * THESE BROWSER IDENTIFICATION CONSTANTS ARE DEPRECATED AS OF TYPO3 4.4 *
	 *************************************************************************/
		// Browser identification
	is_gecko	: Ext.isGecko || Ext.isOpera || Ext.isWebKit,
	is_ff2		: Ext.isGecko2,
	is_ie		: Ext.isIE,
	is_safari	: Ext.isWebKit,
	is_chrome	: Ext.isChrome,
	is_opera	: Ext.isOpera,
	/***************************************************
	 * COMPILED REGULAR EXPRESSIONS                    *
	 ***************************************************/
	RE_htmlTag		: /<.[^<>]*?>/g,
	RE_tagName		: /(<\/|<)\s*([^ \t\n>]+)/ig,
	RE_head			: /<head>((.|\n)*?)<\/head>/i,
	RE_body			: /<body>((.|\n)*?)<\/body>/i,
	Reg_body		: new RegExp('<\/?(body)[^>]*>', 'gi'),
	reservedClassNames	: /htmlarea/,
	RE_email		: /([0-9a-z]+([a-z0-9_-]*[0-9a-z])*){1}(\.[0-9a-z]+([a-z0-9_-]*[0-9a-z])*)*@([0-9a-z]+([a-z0-9_-]*[0-9a-z])*\.)+[a-z]{2,9}/i,
	RE_url			: /(([^:/?#]+):\/\/)?(([a-z0-9_]+:[a-z0-9_]+@)?[a-z0-9_-]{2,}(\.[a-z0-9_-]{2,})+\.[a-z]{2,5}(:[0-9]+)?(\/\S+)*)/i,
	RE_blockTags		: /^(body|p|h1|h2|h3|h4|h5|h6|ul|ol|pre|dl|dt|dd|div|noscript|blockquote|form|hr|table|caption|fieldset|address|td|tr|th|li|tbody|thead|tfoot|iframe)$/i,
	RE_closingTags		: /^(p|blockquote|a|li|ol|ul|dl|dt|td|th|tr|tbody|thead|tfoot|caption|colgroup|table|div|b|bdo|big|cite|code|del|dfn|em|i|ins|kbd|label|q|samp|small|span|strike|strong|sub|sup|tt|u|var|abbr|acronym|font|center|object|embed|style|script|title|head)$/i,
	RE_noClosingTag		: /^(img|br|hr|col|input|area|base|link|meta|param)$/i,
	RE_numberOrPunctuation	: /[0-9.(),;:!¡?¿%#$'"_+=\\\/-]*/g,
	/***************************************************
	 * TROUBLESHOOTING                                 *
	 ***************************************************/
	_appendToLog: function(str){
		if (HTMLArea.enableDebugMode) {
			var log = document.getElementById('HTMLAreaLog');
			if(log) {
				log.appendChild(document.createTextNode(str));
				log.appendChild(document.createElement('br'));
			}
		}
	},
	appendToLog: function (editorId, objectName, functionName, text) {
		HTMLArea._appendToLog(editorId + '[' + objectName + '::' + functionName + ']: ' + text);
	},
	/***************************************************
	 * LOCALIZATION                                    *
	 ***************************************************/
	localize: function (label) {
		return HTMLArea.I18N.dialogs[label] || HTMLArea.I18N.tooltips[label] || HTMLArea.I18N.msg[label] || label;
	},
	/***************************************************
	 * INITIALIZATION                                  *
	 ***************************************************/
	init: function () {
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
		HTMLArea._appendToLog("[HTMLArea::init]: Editor url set to: " + HTMLArea.editorUrl);
		HTMLArea._appendToLog("[HTMLArea::init]: Editor skin CSS set to: " + HTMLArea.editorCSS);
		HTMLArea._appendToLog("[HTMLArea::init]: Editor content skin CSS set to: " + HTMLArea.editedContentCSS);
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
		// Remove tags (must be a regular expression)
	this.htmlRemoveTags = /none/i;
		// Remove tags and their contents (must be a regular expression)
	this.htmlRemoveTagsAndContents = /none/i;
		// Remove comments
	this.htmlRemoveComments = false;
		// Custom tags (must be a regular expression)
	this.customTags = /none/i;
		// BaseURL to be included in the iframe document
	this.baseURL = document.baseURI || document.URL;
	if (this.baseURL && this.baseURL.match(/(.*\:\/\/.*\/)[^\/]*/)) {
		this.baseURL = RegExp.$1;
	}
		// URL-s
	this.popupURL = "popups/";
		// DocumentType
	this.documentType = '<!DOCTYPE html\r'
			+ '    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"\r'
			+ '    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\r';
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
			HTMLArea._appendToLog('[HTMLArea.Config::registerButton]: A toolbar item with the same Id: ' + config.id + ' already exists and will be overidden.');
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
			HTMLArea._appendToLog('[HTMLArea.Config::registerHotKey]: A hotkey with the same key ' + hotKeyConfiguration.id + ' already exists and will be overidden.');
		}
		if (Ext.isDefined(hotKeyConfiguration.cmd) && !Ext.isEmpty(hotKeyConfiguration.cmd) && Ext.isDefined(this.buttonsConfig[hotKeyConfiguration.cmd])) {
			this.hotKeyList[hotKeyConfiguration.id] = hotKeyConfiguration;
			HTMLArea._appendToLog('[HTMLArea.Config::registerHotKey]: A hotkey with key ' + hotKeyConfiguration.id + ' was registered for toolbar item ' + hotKeyConfiguration.cmd + '.');
			return true;
		} else {
			HTMLArea._appendToLog('[HTMLArea.Config::registerHotKey]: A hotkey with key ' + hotKeyConfiguration.id + ' could not be registered because toolbar item with id ' + hotKeyConfiguration.cmd + ' was not registered.');
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
				editor.focus();
				if (!Ext.isEmpty(this.savedRange)) {
					editor.selectRange(this.savedRange);
					this.savedRange = null;
				}
			}
				// Invoke the plugin onChange handler
			this.plugins[this.action](editor, combo, record, index);
				// In IE, bookmark the updated selection as the editor will be loosing focus
			if (Ext.isIE) { 
				editor.focus();
				this.savedRange = editor._createRange(editor._getSelection());
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
			this.savedRange = editor._createRange(editor._getSelection());
		}
	},
	/*
	 * Handler invoked in IE when the editor gets the focus back
	 */
	restoreSelection: function (event) {
		if (!Ext.isEmpty(this.savedRange) && this.triggered) {
			this.getEditor().selectRange(this.savedRange);
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
			selectionEmpty = true,
			ancestors = null,
			endPointsInSameBlock = true;
		if (editor.getMode() === 'wysiwyg') {
			selectionEmpty = editor._selectionEmpty(editor._getSelection());
			ancestors = editor.getAllAncestors();
			endPointsInSameBlock = editor.endPointsInSameBlock();
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
			removeTagsAndContents: this.config.htmlRemoveTagsAndContents
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
		if (this.isNested  && !Ext.isWebKit) {
			var options = {
				stopEvent: true
			};
			if (Ext.isGecko) {
				options.delay = 50;
			}
			Ext.each(this.nestedParentElements.sorted, function (nested) {
				if (!Ext.isGecko) {
					options.target = Ext.get(nested);
				}
				this.mon(
					Ext.get(nested),
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
			this.createHead();
			this.getStyleSheets();
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
			HTMLArea._appendToLog('[HTMLArea.Iframe::createHead]: Iframe baseURL set to: ' + base.href);
		}
		var link0 = this.document.getElementsByTagName('link')[0];
		if (!link0) {
			link0 = this.document.createElement('link');
			link0.rel = 'stylesheet';
				// Firefox 3.0.1 does not apply the base URL while Firefox 3.6.8 does so. Do not know in what version this was fixed.
				// Therefore, for versions before 3.6.8, we prepend the url with the base, if the url is not absolute
			link0.href = ((Ext.isGecko && navigator.productSub < 2010072200 && !/^http(s?):\/{2}/.test(this.config.editedContentStyle)) ? this.config.baseURL : '') + this.config.editedContentStyle;
			head.appendChild(link0);
			HTMLArea._appendToLog('[HTMLArea.Iframe::createHead]: Skin CSS set to: ' + link0.href);
		}
		if (this.config.defaultPageStyle) {
			var link = this.document.getElementsByTagName('link')[1];
			if (!link) {
				link = this.document.createElement('link');
				link.rel = 'stylesheet';
				link.href = ((Ext.isGecko && navigator.productSub < 2010072200 && !/^https?:\/{2}/.test(this.config.defaultPageStyle)) ? this.config.baseURL : '') + this.config.defaultPageStyle;
				head.appendChild(link);
			}
			HTMLArea._appendToLog('[HTMLArea.Iframe::createHead]: Override CSS set to: ' + link.href);
		}
		if (this.config.pageStyle) {
			var link = this.document.getElementsByTagName('link')[2];
			if (!link) {
				link = this.document.createElement('link');
				link.rel = 'stylesheet';
				link.href = ((Ext.isGecko && navigator.productSub < 2010072200 && !/^https?:\/{2}/.test(this.config.pageStyle)) ? this.config.baseURL : '') + this.config.pageStyle;
				head.appendChild(link);
			}
			HTMLArea._appendToLog('[HTMLArea.Iframe::createHead]: Content CSS set to: ' + link.href);
		}
		HTMLArea._appendToLog('[HTMLArea.Iframe::createHead]: Editor iframe document head successfully built.');
	},
	/*
	 * Fire event 'HTMLAreaEventIframeReady' when the iframe style sheets become accessible
	 */
	getStyleSheets: function () {
		var stylesAreLoaded = true;
		var errorText = '';
		var rules;
		if (Ext.isOpera) {
			if (this.document.readyState != 'complete') {
				stylesAreLoaded = false;
				errorText = 'Document.readyState not complete';
			}
		} else {
				// Test if the styleSheets array is at all accessible
			if (Ext.isIE) {
				try { 
					rules = this.document.styleSheets[0].rules;
				} catch(e) {
					stylesAreLoaded = false;
					errorText = e;
				}
			} else {
				try { 
					this.document.styleSheets && this.document.styleSheets[0] && this.document.styleSheets[0].rules;
				} catch(e) {
					stylesAreLoaded = false;
					errorText = e;
				}
			}
				// Then test if all stylesheets are accessible
			if (stylesAreLoaded) {
				if (this.document.styleSheets.length) {
					Ext.each(this.document.styleSheets, function (styleSheet) {
						if (Ext.isIE) {
							try { rules = styleSheet.rules; } catch(e) { stylesAreLoaded = false; errorText = e; return false; }
							try { rules = styleSheet.imports; } catch(e) { stylesAreLoaded = false; errorText = e; return false; }
						} else {
							try { rules = styleSheet.cssRules; } catch(e) { stylesAreLoaded = false; errorText = e; return false; }
						}
					});
				} else {
					stylesAreLoaded = false;
					errorText = 'Empty stylesheets array';
				}
			}
		}
		if (!stylesAreLoaded) {
			this.getStyleSheets.defer(100, this);
			HTMLArea._appendToLog('[HTMLArea.Iframe::getStyleSheets]: Stylesheets not yet loaded (' + errorText + '). Retrying...');
			if (/Security/i.test(errorText)) {
				HTMLArea._appendToLog('ERROR [HTMLArea.Iframe::getStyleSheets]: A security error occurred. Make sure all stylesheets are accessed from the same domain/subdomain and using the same protocol as the current script.');
			}
		} else {
			HTMLArea._appendToLog('[HTMLArea.Iframe::getStyleSheets]: Stylesheets successfully accessed.');
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
			styleEvent = (event.browserEvent.attrName == 'style');
		} else if (Ext.isIE) {
			styleEvent = (event.browserEvent.propertyName == 'style.display');
		}
		if (styleEvent && this.nestedParentElements.sorted.indexOf(target.id) != -1 && (target.style.display == '' || target.style.display == 'block')) {
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
		/*****************************************************
		 * onKeyPress DEPRECATED AS OF TYPO3 4.4             *
		 *****************************************************/
		if (this.getEditor().hasPluginWithOnKeyPressHandler) {
			var letBubble = true;
			Ext.iterate(this.getEditor().plugins, function (pluginId) {
				var plugin = this.getEditor().getPlugin(pluginId);
				if (Ext.isFunction(plugin.onKeyPress)) {
					if (!plugin.onKeyPress(event.browserEvent)) {
						event.stopEvent();
						letBubble = false;
					}
				}
				return letBubble;
			}, this);
			if (!letBubble) {
				return letBubble;
			}
		}
		this.fireEvent('HTMLAreaEventWordCountChange', 100);
		if (!event.altKey && !event.ctrlKey) {
				// Detect URL in non-IE browsers
			if (!Ext.isIE && (event.getKey() != Ext.EventObject.ENTER || (event.shiftKey && !Ext.isWebKit))) {
				this.getEditor()._detectURL(event);
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
		if (Ext.isWebKit && /^(img)$/i.test(target.nodeName)) {
			this.getEditor().selectNode(target);
		}
		this.getToolbar().updateLater.delay(100);
		return true;
	},
	/*
	 * Handlers for drag and drop operations
	 */
	onDrop: function (event) {
		if (Ext.isWebKit) {
			this.getEditor().cleanAppleStyleSpans.defer(50, this.getEditor(), [this.getEditor().document.body]);
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
		return true;
	},
	/*
	 * Handler for BACKSPACE and DELETE keys
	 */
	onBackSpace: function (key, event) {
		if (this.inhibitKeyboardInput(event)) {
			return false;
		}
		if ((!Ext.isIE && !event.shiftKey) || Ext.isIE) {
			if (this.getEditor()._checkBackspace()) {
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
		this.getEditor()._detectURL(event);
		if (this.getEditor()._checkInsertP()) {
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
			editor._detectURL(event);
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
				editor.selectNode(brNode, false);
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
		this.getEditor().insertHTML('&nbsp;');
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
		this.getEditor().insertHTML('&nbsp;');
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
			html: HTMLArea.I18N.msg['Path'] + ': '
		}, true).setVisibilityMode(Ext.Element.DISPLAY).setVisible(true);
		this.statusBarTextMode = Ext.DomHelper.append(this.getEl(), {
			id: this.editorId + '-statusBarTextMode',
			tag: 'span',
			cls: 'statusBarTextMode',
			html: HTMLArea.I18N.msg['TEXT_MODE']
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
				html: HTMLArea.I18N.msg['Path'] + ': '
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
					'ext:qtitle': HTMLArea.I18N.dialogs['statusBarStyle'],
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
		this.statusBarWordCount.dom.innerHTML = wordCount ? ( wordCount + ' ' + HTMLArea.I18N.dialogs[(wordCount == 1) ? 'word' : 'words']) : '&nbsp;';
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
	setSelection: function(element) {
		this.selected = element ? element : null;
	},
	/*
	 * Select the element that was clicked in the status bar and set the status bar selection
	 */
	selectElement: function (element) {
		var editor = this.getEditor();
		element.blur();
		if (!Ext.isIE) {
			if (/^(img)$/i.test(element.ancestor.nodeName)) {
				editor.selectNode(element.ancestor);
			} else {
				editor.selectNodeContents(element.ancestor);
			}
		} else {
			if (/^(img|table)$/i.test(element.ancestor.nodeName)) {
				var range = editor.document.body.createControlRange();
				range.addElement(element.ancestor);
				range.select();
			} else {
				editor.selectNode(element.ancestor);
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
		}
	},
	/*
	 * Resize the framework components
	 */
	onFrameworkResize: function () {
			// For unknown reason, in Chrome 7, this following is the only way to set the height of the iframe
		if (Ext.isChrome) {
			this.iframe.getResizeEl().dom.setAttribute('style', 'width:' + this.getInnerWidth() + 'px; height:' + this.getInnerHeight() + 'px;');
		} else {
			this.iframe.setSize(this.getInnerWidth(), this.getInnerHeight());
		}
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
				// For unknown reason, in Chrome 7, this following is the only way to set the height of the iframe
			if (Ext.isChrome) {
				this.iframe.getResizeEl().dom.setAttribute('style', 'width:' + this.getInnerWidth() + 'px; height:' + this.getInnerHeight() + 'px;');
			} else {
				this.iframe.setHeight(this.getInnerHeight());
			}
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
						src: (Ext.isGecko || Ext.isChrome) ? 'javascript:void(0);' : HTMLArea.editorUrl + 'popups/blank.html'
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
		HTMLArea._appendToLog('[HTMLArea.Editor::onFrameworkReady]: Editor ready.');
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
					HTMLArea._appendToLog('[HTMLArea.Editor::setMode]: The HTML document is not well-formed.');
					TYPO3.Dialog.ErrorDialog({
						title: 'htmlArea RTE',
						msg: HTMLArea.I18N.msg['HTML-document-not-well-formed']
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
				return this.textArea.getValue();
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
	 * Instantiate the specified plugin and register it with the editor
	 *
	 * @param	string		plugin: the name of the plugin
	 *
	 * @return	boolean		true if the plugin was successfully registered
	 */
	registerPlugin: function (pluginName) {
		var plugin = null;
		if (Ext.isString(pluginName)) {
			/*******************************************************************************
			 * USE OF PLUGIN NAME OUTSIDE HTMLArea NAMESPACE IS DEPRECATED AS OF TYPO3 4.4 *
			 *******************************************************************************/
			try {
				plugin = eval(pluginName);
			} catch (e) {
				try {
					plugin = eval('HTMLArea.' + pluginName);
				} catch (error) {
					HTMLArea._appendToLog('ERROR [HTMLArea.Editor::registerPlugin]: Cannot register invalid plugin: ' + error);
					return false;
				}
			}
		}
		if (!Ext.isFunction(plugin)) {
			HTMLArea._appendToLog('ERROR [HTMLArea.Editor::registerPlugin]: Cannot register undefined plugin.');
			return false;
		}
		var pluginInstance = new plugin(this, pluginName);
		if (pluginInstance) {
			var pluginInformation = pluginInstance.getPluginInformation();
			pluginInformation.instance = pluginInstance;
			this.plugins[pluginName] = pluginInformation;
			HTMLArea._appendToLog('[HTMLArea.Editor::registerPlugin]: Plugin ' + pluginName + ' was successfully registered.');
			return true;
		} else {
			HTMLArea._appendToLog("ERROR [HTMLArea.Editor::registerPlugin]: Can't register plugin " + pluginName + '.');
			return false;
		}
	},
	/*
	 * Generate registered plugins
	 */
	generatePlugins: function () {
		this.hasPluginWithOnKeyPressHandler = false;
		Ext.iterate(this.plugins, function (pluginId) {
			var plugin = this.getPlugin(pluginId);
			plugin.onGenerate();
				// onKeyPress deprecated as of TYPO3 4.4
			if (Ext.isFunction(plugin.onKeyPress)) {
				this.hasPluginWithOnKeyPressHandler = true;
				HTMLArea._appendToLog('[HTMLArea.Editor::generatePlugins]: Deprecated use of onKeyPress function by plugin ' + pluginId + '. Use keyMap instead.');
			}
		}, this);
		HTMLArea._appendToLog('[HTMLArea.Editor::generatePlugins]: All plugins successfully generated.');
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
	 *
	 * @return	void
	 */
	appendToLog: function (objectName, functionName, text) {
		HTMLArea.appendToLog(this.editorId, objectName, functionName, text);
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
			// ExtJS is not releasing any resources when the iframe is unloaded
		this.htmlArea.destroy();
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
		this.editor.appendToLog('HTMLArea.Ajax', 'getJavascriptFile', 'Requesting script ' + url);
		Ext.Ajax.request({
			method: 'GET',
			url: url,
			callback: callback,
			success: function (response) {
				success = true;
			},
			failure: function (response) {
				self.editor.inhibitKeyboardInput = false;
				self.editor.appendToLog('HTMLArea.Ajax', 'getJavascriptFile', 'Unable to get ' + url + ' . Server reported ' + response.status);
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
		this.editor.appendToLog('HTMLArea.Ajax', 'postData', 'Posting to ' + url + '. Data: ' + params);
		Ext.Ajax.request({
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			url: url,
			params: params,
			callback: Ext.isFunction(callback) ? callback: function (options, success, response) {
				if (success) {
					self.editor.appendToLog('HTMLArea.Ajax', 'postData', 'Post request to ' + url + ' successful. Server response: ' + response.responseText);
				} else {
					self.editor.appendToLog('HTMLArea.Ajax', 'postData', 'Post request to ' + url + ' failed. Server reported ' + response.status);
				}
			},
			success: function (response) {
				success = true;
			},
			failure: function (response) {
				self.editor.appendToLog('HTMLArea.Ajax', 'postData', 'Unable to post ' + url + ' . Server reported ' + response.status);
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
					var originalStyles = currentElement.getStyles('visibility', 'position', 'top', 'display');
					currentElement.setStyle({
						visibility: 'hidden',
						position: 'absolute',
						top: '-10000px',
						display: ''
					});
				}
				result = this.accessParentElements(parentElements, callbackFunc, args);
				if (actionRequired) {
					currentElement.setStyle(originalStyles);
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
 * Load a stylesheet file
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.4 *
 ***********************************************
 */
HTMLArea.loadStyle = function(style, plugin, url) {
	if (typeof(url) == "undefined") {
		var url = HTMLArea.editorUrl || '';
		if (typeof(plugin) != "undefined") { url += "plugins/" + plugin + "/"; }
		url += style;
		if (/^\//.test(style)) { url = style; }
	}
	var head = document.getElementsByTagName("head")[0];
	var link = document.createElement("link");
	link.rel = "stylesheet";
	link.href = url;
	head.appendChild(link);
};

/*
 * Get the url of some popup
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.4 *
 ***********************************************
 */
HTMLArea.Editor.prototype.popupURL = function(file) {
	var url = "";
	if(file.match(/^plugin:\/\/(.*?)\/(.*)/)) {
		var pluginId = RegExp.$1;
		var popup = RegExp.$2;
		if(!/\.html$/.test(popup)) popup += ".html";
		if (this.config.pathToPluginDirectory[pluginId]) {
			url = this.config.pathToPluginDirectory[pluginId] + "popups/" + popup;
		} else {
			url = HTMLArea.editorUrl + "plugins/" + pluginId + "/popups/" + popup;
		}
	} else {
		url = HTMLArea.editorUrl + this.config.popupURL + file;
	}
	return url;
};

/***************************************************
 *  EDITOR UTILITIES
 ***************************************************/
HTMLArea.getInnerText = function(el) {
	var txt = '', i;
	if(el.firstChild) {
		for(i=el.firstChild;i;i =i.nextSibling) {
			if(i.nodeType == 3) txt += i.data;
			else if(i.nodeType == 1) txt += HTMLArea.getInnerText(i);
		}
	} else {
		if(el.nodeType == 3) txt = el.data;
	}
	return txt;
};

HTMLArea.Editor.prototype.forceRedraw = function() {
	this.htmlArea.doLayout();
};

/*
 * Focus the editor iframe window or the textarea.
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.4 *
 ***********************************************
 */
HTMLArea.Editor.prototype.focusEditor = function() {
	this.focus();
	return this.document;
};

/*
 * Check if any plugin has an opened window
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.4 *
 ***********************************************
 */
HTMLArea.Editor.prototype.hasOpenedWindow = function () {
	for (var plugin in this.plugins) {
		if (this.plugins.hasOwnProperty(plugin)) {
			if (HTMLArea.Dialog[plugin.name] && HTMLArea.Dialog[plugin.name].hasOpenedWindow && HTMLArea.Dialog[plugin.name].hasOpenedWindow()) {
				return true;
			}
		}
	}
	return false
};
HTMLArea.Editor.prototype.updateToolbar = function(noStatus) {
	this.toolbar.update(noStatus);
};
/***************************************************
 *  DOM TREE MANIPULATION
 ***************************************************/

/*
 * Surround the currently selected HTML source code with the given tags.
 * Delete the selection, if any.
 */
HTMLArea.Editor.prototype.surroundHTML = function(startTag,endTag) {
	this.insertHTML(startTag + this.getSelectedHTML().replace(HTMLArea.Reg_body, "") + endTag);
};

/*
 * Change the tag name of a node.
 */
HTMLArea.Editor.prototype.convertNode = function(el,newTagName) {
	var newel = this.document.createElement(newTagName), p = el.parentNode;
	while (el.firstChild) newel.appendChild(el.firstChild);
	p.insertBefore(newel, el);
	p.removeChild(el);
	return newel;
};

/*
 * Find a parent of an element with a specified tag
 */
HTMLArea.getElementObject = function(el,tagName) {
	var oEl = el;
	while (oEl != null && oEl.nodeName.toLowerCase() != tagName) oEl = oEl.parentNode;
	return oEl;
};

/*
 * This function removes the given markup element
 *
 * @param	object	element: the inline element to be removed, content being preserved
 *
 * @return	void
 */
HTMLArea.Editor.prototype.removeMarkup = function(element) {
	var bookmark = this.getBookmark(this._createRange(this._getSelection()));
	var parent = element.parentNode;
	while (element.firstChild) {
		parent.insertBefore(element.firstChild, element);
	}
	parent.removeChild(element);
	this.selectRange(this.moveToBookmark(bookmark));
};

/*
 * This function verifies if the element has any allowed attributes
 *
 * @param	object	element: the DOM element
 * @param	array	allowedAttributes: array of allowed attribute names
 *
 * @return	boolean	true if the element has one of the allowed attributes
 */
HTMLArea.hasAllowedAttributes = function(element,allowedAttributes) {
	var value;
	for (var i = allowedAttributes.length; --i >= 0;) {
		value = element.getAttribute(allowedAttributes[i]);
		if (value) {
			if (allowedAttributes[i] == "style" && element.style.cssText) {
				return true;
			} else {
				return true;
			}
		}
	}
	return false;
};

/***************************************************
 *  SELECTIONS AND RANGES
 ***************************************************/

/*
 * Return true if we have some selected content
 */
HTMLArea.Editor.prototype.hasSelectedText = function() {
	return this.getSelectedHTML() != "";
};

/*
 * Get an array with all the ancestor nodes of the selection.
 */
HTMLArea.Editor.prototype.getAllAncestors = function() {
	var p = this.getParentElement();
	var a = [];
	while (p && (p.nodeType === 1) && (p.nodeName.toLowerCase() !== "body")) {
		a.push(p);
		p = p.parentNode;
	}
	a.push(this.document.body);
	return a;
};

/*
 * Get the block ancestors of an element within a given block
 */
HTMLArea.Editor.prototype.getBlockAncestors = function(element, withinBlock) {
	var ancestors = new Array();
	var ancestor = element;
	while (ancestor && (ancestor.nodeType === 1) && !/^(body)$/i.test(ancestor.nodeName) && ancestor != withinBlock) {
		if (HTMLArea.isBlockElement(ancestor)) {
			ancestors.unshift(ancestor);
		}
		ancestor = ancestor.parentNode;
	}
	ancestors.unshift(ancestor);
	return ancestors;
};

/*
 * Get the block elements containing the start and the end points of the selection
 */
HTMLArea.Editor.prototype.getEndBlocks = function(selection) {
	var range = this._createRange(selection);
	if (!Ext.isIE) {
		var parentStart = range.startContainer;
		if (/^(body)$/i.test(parentStart.nodeName)) {
			parentStart = parentStart.firstChild;
		}
		var parentEnd = range.endContainer;
		if (/^(body)$/i.test(parentEnd.nodeName)) {
			parentEnd = parentEnd.lastChild;
		}
	} else {
		if (selection.type !== "Control" ) {
			var rangeEnd = range.duplicate();
			range.collapse(true);
			var parentStart = range.parentElement();
			rangeEnd.collapse(false);
			var parentEnd = rangeEnd.parentElement();
		} else {
			var parentStart = range.item(0);
			var parentEnd = parentStart;
		}
	}
	while (parentStart && !HTMLArea.isBlockElement(parentStart)) {
		parentStart = parentStart.parentNode;
	}
	while (parentEnd && !HTMLArea.isBlockElement(parentEnd)) {
		parentEnd = parentEnd.parentNode;
	}
	return {	start	: parentStart,
			end	: parentEnd
	};
};

/*
 * This function determines if the end poins of the current selection are within the same block
 *
 * @return	boolean	true if the end points of the current selection are inside the same block element
 */
HTMLArea.Editor.prototype.endPointsInSameBlock = function() {
	var selection = this._getSelection();
	if (this._selectionEmpty(selection)) {
		return true;
	} else {
		var parent = this.getParentElement(selection);
		var endBlocks = this.getEndBlocks(selection);
		return (endBlocks.start === endBlocks.end && !/^(table|thead|tbody|tfoot|tr)$/i.test(parent.nodeName));
	}
};

/*
 * Get the deepest ancestor of the selection that is of the specified type
 * Borrowed from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
 */
HTMLArea.Editor.prototype._getFirstAncestor = function(sel,types) {
	var prnt = this._activeElement(sel);
	if (prnt == null) {
		try {
			prnt = (Ext.isIE ? this._createRange(sel).parentElement() : this._createRange(sel).commonAncestorContainer);
		} catch(e) {
			return null;
		}
	}
	if (typeof(types) == 'string') types = [types];

	while (prnt) {
		if (prnt.nodeType == 1) {
			if (types == null) return prnt;
			for (var i = 0; i < types.length; i++) {
				if(prnt.tagName.toLowerCase() == types[i]) return prnt;
			}
			if(prnt.tagName.toLowerCase() == 'body') break;
			if(prnt.tagName.toLowerCase() == 'table') break;
		}
		prnt = prnt.parentNode;
	}
	return null;
};
/*
 * Get the node whose contents are currently fully selected
 *
 * @param 	array		selection: the current selection
 * @param 	array		range: the range of the current selection
 * @param 	array		ancestors: the array of ancestors node of the current selection
 *
 * @return	object		the fully selected node, if any, null otherwise
 */
HTMLArea.Editor.prototype.getFullySelectedNode = function (selection, range, ancestors) {
	var node, fullNodeSelected = false;
	if (!selection) {
		var selection = this._getSelection();
	}
	if (!this._selectionEmpty(selection)) {
		if (!range) {
			var range = this._createRange(selection);
		}
		if (!ancestors) {
			var ancestors = this.getAllAncestors();
		}
		Ext.each(ancestors, function (ancestor) {
			if (Ext.isIE) {
				fullNodeSelected = (selection.type !== 'Control' && ancestor.innerText == range.text) || (selection.type === 'Control' && ancestor.innerText == range.item(0).text);
			} else {
				fullNodeSelected = (ancestor.textContent == range.toString());
			}
			if (fullNodeSelected) {
				node = ancestor;
				return false;
			}
		});
			// Working around bug with WebKit selection
		if (Ext.isWebKit && !fullNodeSelected) {
			var statusBarSelection = this.statusBar ? this.statusBar.getSelection() : null;
			if (statusBarSelection && statusBarSelection.textContent == range.toString()) {
				fullNodeSelected = true;
				node = statusBarSelection;
			}
		}
	}
	return fullNodeSelected ? node : null;
};
/***************************************************
 *  Category: EVENT HANDLERS
 ***************************************************/

/*
 * Intercept some native execCommand commands
 */
HTMLArea.Editor.prototype.execCommand = function(cmdID, UI, param) {
	this.focus();
	switch (cmdID) {
		default:
			try {
				this.document.execCommand(cmdID, UI, param);
			} catch(e) {
				HTMLArea._appendToLog('[HTMLArea.Editor::execCommand]: ' + e + 'by execCommand(' + cmdID + ')');
			}
	}
	this.toolbar.update();
	return false;
};

HTMLArea.Editor.prototype.scrollToCaret = function() {
	if (!Ext.isIE) {
		var e = this.getParentElement(),
			w = this._iframe.contentWindow ? this._iframe.contentWindow : window,
			h = w.innerHeight || w.height,
			d = this.document,
			t = d.documentElement.scrollTop || d.body.scrollTop;
		if (e.offsetTop > h+t || e.offsetTop < t) {
			this.getParentElement().scrollIntoView();
		}
	}
};
/***************************************************
 *  UTILITY FUNCTIONS
 ***************************************************/

/*
 * Check if the client agent is supported
 */
HTMLArea.checkSupportedBrowser = function() {
	return Ext.isGecko || Ext.isWebKit || Ext.isOpera || Ext.isIE;
};
/*
 * Remove a class name from the class attribute of an element
 *
 * @param	object		el: the element
 * @param	string		className: the class name to remove
 * @param	boolean		substring: if true, remove the first class name starting with the given string
 * @return	void
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.5 *
 ***********************************************
 */
HTMLArea._removeClass = function(el, className, substring) {
	HTMLArea.DOM.removeClass(el, className, substring);
};
/*
 * Add a class name to the class attribute
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.5 *
 ***********************************************
 */
HTMLArea._addClass = function(el, className) {
	HTMLArea.DOM.addClass(el, className);
};
/*
 * Check if a class name is in the class attribute of an element
 *
 * @param	object		el: the element
 * @param	string		className: the class name to look for
 * @param	boolean		substring: if true, look for a class name starting with the given string
 * @return	boolean		true if the class name was found
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.5 *
 ***********************************************
 */
HTMLArea._hasClass = function(el, className, substring) {
	return HTMLArea.DOM.hasClass(el, className, substring);
};

HTMLArea.isBlockElement = function(el) { return el && el.nodeType == 1 && HTMLArea.RE_blockTags.test(el.nodeName.toLowerCase()); };
HTMLArea.needsClosingTag = function(el) { return el && el.nodeType == 1 && !HTMLArea.RE_noClosingTag.test(el.tagName.toLowerCase()); };

/*
 * Perform HTML encoding of some given string
 * Borrowed in part from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
 */
HTMLArea.htmlDecode = function(str) {
	str = str.replace(/&lt;/g, "<").replace(/&gt;/g, ">");
	str = str.replace(/&nbsp;/g, "\xA0"); // Decimal 160, non-breaking-space
	str = str.replace(/&quot;/g, "\x22");
	str = str.replace(/&#39;/g, "'") ;
	str = str.replace(/&amp;/g, "&");
	return str;
};
HTMLArea.htmlEncode = function(str) {
	if (typeof(str) != 'string') str = str.toString(); // we don't need regexp for that, but.. so be it for now.
	str = str.replace(/&/g, "&amp;");
	str = str.replace(/</g, "&lt;").replace(/>/g, "&gt;");
	str = str.replace(/\xA0/g, "&nbsp;"); // Decimal 160, non-breaking-space
	str = str.replace(/\x22/g, "&quot;"); // \x22 means '"'
	return str;
};
/*
 * Retrieve the HTML code from the given node.
 * This is a replacement for getting innerHTML, using standard DOM calls.
 * Wrapper catches a Mozilla-Exception with non well-formed html source code.
 ***********************************************
 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.5 *
 ***********************************************
 */
HTMLArea.getHTML = function(root, outputRoot, editor){
	try {
		return editor.iframe.htmlRenderer.render(root, outputRoot);
	} catch(e) {
		HTMLArea._appendToLog('[HTMLArea::getHTML]: The HTML document is not well-formed.');
		if (!HTMLArea.enableDebugMode) {
			TYPO3.Dialog.ErrorDialog({
				title: 'htmlArea RTE',
				msg: HTMLArea.I18N.msg['HTML-document-not-well-formed']
			});
			return editor.document.body.innerHTML;
		} else {
			return editor.iframe.htmlRenderer.render(root, outputRoot);
		}
	}
};
HTMLArea.getPrevNode = function(node) {
	if(!node)                return null;
	if(node.previousSibling) return node.previousSibling;
	if(node.parentNode)      return node.parentNode;
	return null;
};

HTMLArea.getNextNode = function(node) {
	if(!node)            return null;
	if(node.nextSibling) return node.nextSibling;
	if(node.parentNode)  return node.parentNode;
	return null;
};

HTMLArea.removeFromParent = function(el) {
	if(!el.parentNode) return;
	var pN = el.parentNode;
	pN.removeChild(el);
	return el;
};
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
						if (Ext.isIE) {
							node.removeAttribute('className');
						}
					} else {
						node.className = '';
					}
				}
			}
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
		removeTrailingBR: true
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
				html += /^(script|style)$/i.test(node.parentNode.nodeName) ? node.data : HTMLArea.htmlEncode(node.data);
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
		if (node.nodeType == HTMLArea.DOM.ELEMENT_NODE) {
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
		var filterededAttributes = {};
		var attribute, attributeName, attributeValue;
		for (var i = attributes.length; --i >= 0 ;) {
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
					// IE fails to put style in attributes list.
				if (attributeName === 'style') {
					attributeValue = node.style.cssText;
					// May need to strip the base url
				} else if (attributeName === 'href' || attributeName === 'src') {
					attributeValue = this.stripBaseURL(attributeValue);
					// Ignore value="0" reported by IE on all li elements
				} else if (attributeName === 'value' && /^li$/i.test(node.nodeName) && attributeValue == 0) {
					continue;
				}
				// Ignore special values reported by Mozilla
			} else if (Ext.isGecko && /(_moz|^$)/.test(attributeValue)) {
				continue;
			}
				// Ignore id attributes generated by ExtJS
			if (attributeName === 'id' && /^ext-gen/.test(attributeValue)) {
				continue;
			}
			filterededAttributes[attributeName] = attributeValue;
		}
		return filterededAttributes;
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
			} else if (this.removeTrailingBR && !node.nextSibling && HTMLArea.isBlockElement(node.parentNode) && (!node.previousSibling || !/^br$/i.test(node.previousSibling.nodeName))) {
						// If an empty paragraph with a class attribute, insert a non-breaking space so that RTE transform does not clean it away
					if (!node.previousSibling && node.parentNode && /^p$/i.test(node.parentNode.nodeName) && node.parentNode.className) {
						html += "&nbsp;";
					}
				return html;
			}
		}
			// Normal node
		var attributes = this.getAttributes(node);
		for (var attributeName in attributes) {
			html +=  ' ' + attributeName + '="' + HTMLArea.htmlEncode(attributes[attributeName]) + '"';
		}
		html = '<' + node.nodeName.toLowerCase() + html + (HTMLArea.RE_noClosingTag.test(node.nodeName) ? ' />' : '>');
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
		var html = HTMLArea.RE_noClosingTag.test(node.nodeName) ? '' : '</' + node.nodeName.toLowerCase() + '>';
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
 *  HTMLArea.CSS.Parser: CSS Parser
 ***************************************************/
HTMLArea.CSS.Parser = Ext.extend(Ext.util.Observable, {
	/*
	 * HTMLArea.CSS.Parser constructor
	 */
	constructor: function (config) {
		HTMLArea.CSS.Parser.superclass.constructor.call(this, {});
		var configDefaults = {
			parseAttemptsMaximumNumber: 17,
			prefixLabelWithClassName: false,
			postfixLabelWithClassName: false,
			showTagFreeClasses: false,
			tags: null,
			editor: null
		};
		Ext.apply(this, config, configDefaults);
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
							this.editor.appendToLog('HTMLArea.CSS.Parser', 'initiateParsing', 'Javascript file successfully evaluated: ' + this.editor.config.classesUrl);
						}
					} catch(e) {
						this.editor.appendToLog('HTMLArea.CSS.Parser', 'initiateParsing', 'Error evaluating contents of Javascript file: ' + this.editor.config.classesUrl);
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
				if (this.parseAttemptsCounter < this.parseAttemptsMaximumNumber) {
					this.attemptTimeout = this.parse.defer(200, this);
					this.parseAttemptsCounter++;
				} else {
					this.editor.appendToLog('HTMLArea.CSS.Parser', 'parse', 'The stylesheets could not be parsed. Reported error: ' + this.error);
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
		for (var i = 0; i < this.editor.document.styleSheets.length; i++) {
			if (!Ext.isIE) {
				try {
					this.parseRules(this.editor.document.styleSheets[i].cssRules);
				} catch (e) {
					this.error = e;
					this.cssLoaded = false;
					this.parsedClasses = {};
				}
			} else {
				try{
					if (this.editor.document.styleSheets[i].imports) {
						this.parseIeRules(this.editor.document.styleSheets[i].imports);
					}
					if (this.editor.document.styleSheets[i].rules) {
						this.parseRules(this.editor.document.styleSheets[i].rules);
					}
				} catch (e) {
					this.error = e;
					this.cssLoaded = false;
					this.parsedClasses = {};
				}
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
				if (cssRules[rule].styleSheet) {
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
			if (!v) {
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
				return null;
			}
			if (v.substr(0, 1) === '#') {
				return v;
			}
			return null;
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
 * Interim backward compatibility
 */
HTMLArea._makeColor = HTMLArea.util.Color.colorToRgb;
HTMLArea._colorToRgb = HTMLArea.util.Color.colorToHex;
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
						'<tpl for="."><a href="#" class="color-{1}" hidefocus="on"><em><span style="background:#{1}" unselectable="on">&#160;</span></em><span unselectable="on">{0}<span></a></tpl>'
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
/**
 * Internet Explorer returns an item having the _name_ equal to the given id, even if it's not having any id.
 * This way it can return a different form field even if it's not a textarea.  This works around the problem by
 * specifically looking to search only elements having a certain tag name.
 */
HTMLArea.getElementById = function(tag, id) {
	var el, i, objs = document.getElementsByTagName(tag);
	for (i = objs.length; --i >= 0 && (el = objs[i]);) {
		if (el.id == id) return el;
	}
	return null;
};

/***************************************************
 * TYPO3-SPECIFIC FUNCTIONS
 ***************************************************/
/*
 * Extending the TYPO3 Lorem Ipsum extension
 */
var lorem_ipsum = function(element,text) {
	if (element.tagName.toLowerCase() == "textarea" && element.id && element.id.substr(0,7) == "RTEarea") {
		var editor = RTEarea[element.id.substr(7, element.id.length)]["editor"];
		editor.insertHTML(text);
		editor.toolbar.update();
	}
};
/*
 * Create the editor when HTMLArea is loaded and when Ext is ready
 */
HTMLArea.initEditor = function(editorNumber) {
	if (document.getElementById('pleasewait' + editorNumber)) {
		if (HTMLArea.checkSupportedBrowser()) {
			document.getElementById('pleasewait' + editorNumber).style.display = 'block';
			document.getElementById('editorWrap' + editorNumber).style.visibility = 'hidden';
			if (!HTMLArea.isReady) {
				HTMLArea.initEditor.defer(150, null, [editorNumber]);
			} else {
					// Create an editor for the textarea
				HTMLArea._appendToLog("[HTMLArea::initEditor]: Initializing editor with editor Id: " + editorNumber + ".");
				var editor = new HTMLArea.Editor(Ext.apply(new HTMLArea.Config(editorNumber), RTEarea[editorNumber]));
				editor.generate();
				return false;
			}
		} else {
			document.getElementById('pleasewait' + editorNumber).style.display = 'none';
			document.getElementById('editorWrap' + editorNumber).style.visibility = 'visible';
		}
	}
};

/**
 *	Base, version 1.0.2
 *	Copyright 2006, Dean Edwards
 *	License: http://creativecommons.org/licenses/LGPL/2.1/
 */

HTMLArea.Base = function() {
	if (arguments.length) {
		if (this == window) { // cast an object to this class
			HTMLArea.Base.prototype.extend.call(arguments[0], arguments.callee.prototype);
		} else {
			this.extend(arguments[0]);
		}
	}
};

HTMLArea.Base.version = "1.0.2";

HTMLArea.Base.prototype = {
	extend: function(source, value) {
		var extend = HTMLArea.Base.prototype.extend;
		if (arguments.length == 2) {
			var ancestor = this[source];
			// overriding?
			if ((ancestor instanceof Function) && (value instanceof Function) &&
				ancestor.valueOf() != value.valueOf() && /\bbase\b/.test(value)) {
				var method = value;
			//	var _prototype = this.constructor.prototype;
			//	var fromPrototype = !Base._prototyping && _prototype[source] == ancestor;
				value = function() {
					var previous = this.base;
				//	this.base = fromPrototype ? _prototype[source] : ancestor;
					this.base = ancestor;
					var returnValue = method.apply(this, arguments);
					this.base = previous;
					return returnValue;
				};
				// point to the underlying method
				value.valueOf = function() {
					return method;
				};
				value.toString = function() {
					return String(method);
				};
			}
			return this[source] = value;
		} else if (source) {
			var _prototype = {toSource: null};
			// do the "toString" and other methods manually
			var _protected = ["toString", "valueOf"];
			// if we are prototyping then include the constructor
			if (HTMLArea.Base._prototyping) _protected[2] = "constructor";
			for (var i = 0; (name = _protected[i]); i++) {
				if (source[name] != _prototype[name]) {
					extend.call(this, name, source[name]);
				}
			}
			// copy each of the source object's properties to this object
			for (var name in source) {
				if (!_prototype[name]) {
					extend.call(this, name, source[name]);
				}
			}
		}
		return this;
	},

	base: function() {
		// call this method from any other method to invoke that method's ancestor
	}
};

HTMLArea.Base.extend = function(_instance, _static) {
	var extend = HTMLArea.Base.prototype.extend;
	if (!_instance) _instance = {};
	// build the prototype
	HTMLArea.Base._prototyping = true;
	var _prototype = new this;
	extend.call(_prototype, _instance);
	var constructor = _prototype.constructor;
	_prototype.constructor = this;
	delete HTMLArea.Base._prototyping;
	// create the wrapper for the constructor function
	var klass = function() {
		if (!HTMLArea.Base._prototyping) constructor.apply(this, arguments);
		this.constructor = klass;
	};
	klass.prototype = _prototype;
	// build the class interface
	klass.extend = this.extend;
	klass.implement = this.implement;
	klass.toString = function() {
		return String(constructor);
	};
	extend.call(klass, _static);
	// single instance
	var object = constructor ? klass : _prototype;
	// class initialisation
	//if (object.init instanceof Function) object.init();
	return object;
};

HTMLArea.Base.implement = function(_interface) {
	if (_interface instanceof Function) _interface = _interface.prototype;
	this.prototype.extend(_interface);
};

/**
 * HTMLArea.plugin class
 *
 * Every plugin should be a subclass of this class
 *
 */
HTMLArea.Plugin = HTMLArea.Base.extend({

	/**
	 * HTMLArea.plugin constructor
	 *
	 * @param	object		editor: instance of RTE
	 * @param	string		pluginName: name of the plugin
	 *
	 * @return	boolean		true if the plugin was configured
	 */
	constructor : function(editor, pluginName) {
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
		return this.configurePlugin(editor);
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
	configurePlugin : function(editor) {
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
	registerPluginInformation : function(pluginInformation) {
		if (typeof(pluginInformation) !== "object") {
			this.appendToLog("registerPluginInformation", "Plugin information was not provided");
			return false;
		} else {
			this.pluginInformation = pluginInformation;
			this.pluginInformation.name = this.name;
				/* Ensure backwards compatibility */
			this.pluginInformation.developer_url = this.pluginInformation.developerUrl;
			this.pluginInformation.c_owner = this.pluginInformation.copyrightOwner;
			this.pluginInformation.sponsor_url = this.pluginInformation.sponsorUrl;
			return true;
		}
	},

	/**
	 * Returns the plugin information
	 *
	 * @return	object		the plugin information object
	 */
	getPluginInformation : function() {
		return this.pluginInformation;
	},

	/**
	 * Returns a plugin object
	 *
	 * @param	string		pluinName: the name of some plugin
	 * @return	object		the plugin object or null
	 */
	getPluginInstance : function(pluginName) {
		return this.editor.getPlugin(pluginName);
	},

	/**
	 * Returns a current editor mode
	 *
	 * @return	string		editor mode
	 */
	getEditorMode : function() {
		return this.editor.getMode();
	},

	/**
	 * Returns true if the button is enabled in the toolbar configuration
	 *
	 * @param	string		buttonId: identification of the button
	 *
	 * @return	boolean		true if the button is enabled in the toolbar configuration
	 */
	isButtonInToolbar : function(buttonId) {
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
	getButton: function(buttonId) {
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
				this.appendToLog("registerButton", "Function " + buttonConfiguration.action + " was not defined when registering button " + buttonConfiguration.id);
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
				this.appendToLog('registerDropDown', 'Function ' + dropDownConfiguration.action + ' was not defined when registering drop-down ' + dropDownConfiguration.id);
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
	 ***********************************************
	 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.4 *
	 ***********************************************
	 * Register the key handler to the editor keyMap in onGenerate function
	 * The keyPress event handler
	 * This function may be defined by the plugin subclass.
	 * If defined, the function is invoked whenever a key is pressed.
	 *
	 * @param	event		keyEvent: the event that was triggered when a key was pressed
	 *
	 * @return	boolean
	 */
	onKeyPress: null,
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
		if (mode === "textmode" && this.dialog && HTMLArea.Dialog[this.name] == this.dialog && !(this.dialog.buttonId && this.editorConfiguration.buttons[this.dialog.buttonId] && this.editorConfiguration.buttons[this.dialog.buttonId].textMode)) {
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
	 * Make function reference in order to avoid memory leakage in IE
	 ***********************************************
	 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.4 *
	 ***********************************************
	 *
	 * @param	string		functionName: the name of the plugin function to be invoked
	 *
	 * @return	function	function definition invoking the specified function of the plugin
	 */
	makeFunctionReference: function (functionName) {
		var self = this;
		return (function(arg1, arg2, arg3) {
			return (self[functionName](arg1, arg2, arg3));});
	},
	/**
	 * Localize a string
	 *
	 * @param	string		label: the name of the label to localize
	 *
	 * @return	string		the localization of the label
	 */
	localize: function (label) {
		return this.I18N[label] || HTMLArea.localize(label);
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
			return '<a class="t3-help-link" href="#" data-table="xEXT_rtehtmlarea_' + pluginName + '" data-field="' + fieldName + '"><abbr class="t3-help-teaser">' + this.localize(label) + '</abbr></a>';
		} else {
			return this.localize(label);
		}
	},
	/**
	 * Initiate context help listening on the dialogue window
	 * This is normally specified as render handler of the window
	 *
	 * @return	void
	 */
	enableContextHelp: function () {
		if (Ext.isDefined(TYPO3.ContextHelp) && Ext.isFunction(TYPO3.ContextHelp.openHelpWindow)) {
			Ext.select('div').on('click', TYPO3.ContextHelp.openHelpWindow, TYPO3.ContextHelp, {delegate: 'a.t3-help-link'});
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
		this.appendToLog('getJavascriptFile', 'Requesting script ' + url);
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
	 	this.appendToLog('postData', 'Posting to ' + url + '.');
	 	return this.editor.ajax.postData(url, data, callback, this);
	},
	/**
	 ***********************************************
	 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.4 *
	 ***********************************************
	 * Open a dialog window or bring focus to it if is already opened
	 *
	 * @param	string		buttonId: buttonId requesting the opening of the dialog
	 * @param	string		url: name, without extension, of the html file to be loaded into the dialog window
	 * @param	string		action: name of the plugin function to be invoked when the dialog ends
	 * @param	object		arguments: object of variable type to be passed to the dialog
	 * @param	object		dimensions: object giving the width and height of the dialog window
	 * @param	string		showScrollbars: specifies by "yes" or "no" whether or not the dialog window should have scrollbars
	 * @param	object		dialogOpener: reference to the opener window
	 *
	 * @return	object		the dialogue object
	 */
	openDialog : function (buttonId, url, action, arguments, dimensions, showScrollbars, dialogOpener) {
		if (this.dialog && this.dialog.hasOpenedWindow() && this.dialog.buttonId === buttonId) {
			this.dialog.focus();
			return this.dialog;
		} else {
			var actionFunctionReference = action;
			if (typeof(action) === "string") {
				if (typeof(this[action]) === "function") {
					var actionFunctionReference = this.makeFunctionReference(action);
				} else {
					this.appendToLog("openDialog", "Function " + action + " was not defined when opening dialog for " + buttonId);
				}
			}
			return new HTMLArea.Dialog(
					this,
					buttonId,
					url,
					actionFunctionReference,
					arguments,
					this.getWindowDimensions(dimensions, buttonId),
					(showScrollbars?showScrollbars:"no"),
					dialogOpener
				);
		}
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
			height: dimensions.height,
			border: false,
				// As of ExtJS 3.1, JS error with IE when the window is resizable
			//resizable: !Ext.isIE,
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
		var dialogueWindowDimensions = {
			width: 250,
			height: 250,
			top: this.editorConfiguration.dialogueWindows.defaultPositionFromTop,
			left: this.editorConfiguration.dialogueWindows.defaultPositionFromLeft
		};
			// Apply dimensions as per button registration
		if (this.editorConfiguration.buttonsConfig[buttonId]) {
			Ext.apply(dialogueWindowDimensions, this.editorConfiguration.buttonsConfig[buttonId].dimensions);
		}
			// Apply dimensions as per call
		Ext.apply(dialogueWindowDimensions, dimensions);
			// Overrride dimensions as per PageTSConfig
		var buttonConfiguration = this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonId]];
		if (buttonConfiguration && buttonConfiguration.dialogueWindow) {
			Ext.apply(dialogueWindowDimensions, buttonConfiguration.dialogueWindow);
			if (buttonConfiguration.dialogueWindow.top) {
				dialogueWindowDimensions.top = buttonConfiguration.dialogueWindow.positionFromTop;
			}
			if (buttonConfiguration.dialogueWindow.left) {
				dialogueWindowDimensions.left = buttonConfiguration.dialogueWindow.positionFromLeft;
			}
		}
		return dialogueWindowDimensions;
	},
	/**
	 ***********************************************
	 * THIS FUNCTION IS DEPRECATED AS OF TYPO3 4.4 *
	 ***********************************************
	 * Make url from the name of a popup of the plugin
	 *
	 * @param	string		popupName: name, without extension, of the html file to be loaded into the dialog window
	 *
	 * @return	string		the url
	 */
	makeUrlFromPopupName: function(popupName) {
		return (popupName ? this.editor.popupURL("plugin://" + this.name + "/" + popupName) : this.editor.popupURL("blank.html"));
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
		return modulePath + '?' + this.editorConfiguration.RTEtsConfigParams + '&editorNo=' + this.editor.editorId + '&sys_language_content=' + this.editorConfiguration.sys_language_content + '&contentTypo3Language=' + this.editorConfiguration.typo3ContentLanguage + '&contentTypo3Charset=' + encodeURIComponent(this.editorConfiguration.typo3ContentCharset) + (parameters?parameters:'');
	},
	/**
	 * Append an entry at the end of the troubleshooting log
	 *
	 * @param	string		functionName: the name of the plugin function writing to the log
	 * @param	string		text: the text of the message
	 *
	 * @return	void
	 */
	appendToLog: function (functionName, text) {
		this.editor.appendToLog(this.name, functionName, text);
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
			this.savedRange = this.editor._createRange(this.editor._getSelection());
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
				this.editor.selectRange(this.savedRange);
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

/**
 * HTMLArea.Dialog class
 *********************************************
 * THIS OBJECT IS DEPRECATED AS OF TYPO3 4.4 *
 *********************************************
 */
HTMLArea.Dialog = HTMLArea.Base.extend({

	/**
	 * HTMLArea.Dialog constructor
	 *
	 * @param	object		plugin: reference to the invoking plugin
	 * @param	string		buttonId: buttonId triggering the opening of the dialog
	 * @param	string		url: url of the html document to load into the dialog window
	 * @param	function	action: function to be executed when the the dialog ends
	 * @param	object		arguments: object of variable type to be passed to the dialog
	 * @param	object		dimensions: object giving the width and height of the dialog window
	 * @param	string		showScrollbars: specifies by "yes" or "no" whether or not the dialog window should have scrollbars
	 * @param	object		dialogOpener: reference to the opener window
	 *
	 * @return	boolean		true if the dialog window was opened
	 */
	constructor : function (plugin, buttonId, url, action, arguments, dimensions, showScrollbars, dialogOpener) {
		this.window = window.window ? window.window : window.self;
		this.plugin = plugin;
		this.buttonId = buttonId;
		this.action = action;
		if (typeof(arguments) !== "undefined") {
			this.arguments = arguments;
		}
		this.plugin.dialog = this;

		if (HTMLArea.Dialog[this.plugin.name] && HTMLArea.Dialog[this.plugin.name].hasOpenedWindow() && HTMLArea.Dialog[this.plugin.name].plugin != this.plugin) {
			HTMLArea.Dialog[this.plugin.name].close();
		}
		HTMLArea.Dialog[this.plugin.name] = this;
		this.dialogWindow = window.open(url, this.plugin.name + "Dialog", "toolbar=no,location=no,directories=no,menubar=no,resizable=yes,top=" + dimensions.top + ",left=" + dimensions.left + ",dependent=yes,dialog=yes,chrome=no,width=" + dimensions.width + ",height=" + dimensions.height + ",scrollbars=" + showScrollbars);
		if (!this.dialogWindow) {
			this.plugin.appendToLog("openDialog", "Dialog window could not be opened with url " + url);
			return false;
		}

		if (typeof(dialogOpener) !== "undefined") {
			this.dialogWindow.opener = dialogOpener;
			this.dialogWindow.opener.openedDialog = this;
		}
		if (!this.dialogWindow.opener) {
			this.dialogWindow.opener = this.window;
		}
		return true;
	},
	/**
	 * Adds OK and Cancel buttons to the dialogue window
	 *
	 * @return	void
	 */
	addButtons : function() {
		var self = this;
		var div = this.document.createElement("div");
		this.content.appendChild(div);
		div.className = "buttons";
		for (var i = 0; i < arguments.length; ++i) {
			var btn = arguments[i];
			var button = this.document.createElement("button");
			div.appendChild(button);
			switch (btn) {
				case "ok":
					button.innerHTML = this.plugin.localize("OK");
					button.onclick = function() {
						try {
							self.callFormInputHandler();
						} catch(e) { };
						return false;
					};
					break;
				case "cancel":
					button.innerHTML = this.plugin.localize("Cancel");
					button.onclick = function() {
						self.close();
						return false;
					};
					break;
			}
		}
	},

	/**
	 * Call the form input handler
	 *
	 * @return	boolean		false
	 */
	callFormInputHandler : function() {
		var tags = ["input", "textarea", "select"];
		var params = new Object();
		for (var ti = tags.length; --ti >= 0;) {
			var tag = tags[ti];
			var els = this.content.getElementsByTagName(tag);
			for (var j = 0; j < els.length; ++j) {
				var el = els[j];
				var val = el.value;
				if (el.nodeName.toLowerCase() == "input") {
					if (el.type == "checkbox") {
						val = el.checked;
					}
				}
				params[el.name] = val;
			}
		}
		this.action(this, params);
		return false;
	},

	/**
	 * Cheks if the dialogue has an open dialogue window
	 *
	 * @return	boolean		true if the dialogue has an open window
	 */
	hasOpenedWindow : function () {
		return this.dialogWindow && !this.dialogWindow.closed;
	},

	/**
	 * Initialize the dialog window: load the stylesheets, localize labels, resize if required, etc.
	 * This function MUST be invoked from the dialog window in the onLoad event handler
	 *
	 * @param	boolean		noResize: if true the window in not resized, but may be centered
	 *
	 * @return	void
	 */
	initialize : function (noLocalize, noResize, noStyle) {
		this.dialogWindow.HTMLArea = HTMLArea;
		this.dialogWindow.dialog = this;
			// Capture unload and escape events
		this.captureEvents();
			// Get stylesheets for the dialog window
		if (!noStyle) this.loadStyle();
			// Localize the labels of the popup window
		if (!noLocalize) this.localize();
			// Resize the dialog window to its contents
		if (!noResize) this.resize(noResize);
	},
	/**
	 * Load the stylesheets in the dialog window
	 *
	 * @return	void
	 */
	loadStyle : function () {
		var head = this.dialogWindow.document.getElementsByTagName("head")[0];
		var link = this.dialogWindow.document.createElement("link");
		link.rel = "stylesheet";
		link.type = "text/css";
		link.href = HTMLArea.editorCSS;
		if (link.href.indexOf("http") == -1 && !Ext.isIE) link.href = HTMLArea.hostUrl + link.href;
		head.appendChild(link);
	},

	/**
	 * Localize the labels contained in the dialog window
	 *
	 * @return	void
	 */
	localize : function () {
		var label;
		var types = ["input", "label", "option", "select", "legend", "span", "td", "button", "div", "h1", "h2", "a"];
		for (var type = 0; type < types.length; ++type) {
			var elements = this.dialogWindow.document.getElementsByTagName(types[type]);
			for (var i = elements.length; --i >= 0;) {
				var element = elements[i];
				if (element.firstChild && element.firstChild.data) {
					label = this.plugin.localize(element.firstChild.data);
					if (label) element.firstChild.data = label;
				}
				if (element.title) {
					label = this.plugin.localize(element.title);
					if (label) element.title = label;
				}
					// resetting the selected option for Mozilla
				if (types[type] == "option" && element.selected ) {
					element.selected = false;
					element.selected = true;
				}
			}
		}
		label = this.plugin.localize(this.dialogWindow.document.title);
		if (label) this.dialogWindow.document.title = label;
	},

	/**
	 * Resize the dialog window to its contents
	 *
	 * @param	boolean		noResize: if true the window in not resized, but may be centered
	 *
	 * @return	void
	 */
	resize : function (noResize) {
		var buttonConfiguration = this.plugin.editorConfiguration.buttons[this.plugin.editorConfiguration.convertButtonId[this.buttonId]];
		if (!this.plugin.editorConfiguration.dialogueWindows.doNotResize
				&& (!buttonConfiguration  || !buttonConfiguration.dialogueWindow || !buttonConfiguration.dialogueWindow.doNotResize)) {
				// Resize if allowed
			var dialogWindow = this.dialogWindow;
			var doc = dialogWindow.document;
			var content = doc.getElementById("content");
				// As of Google Chrome build 1798, window resizeTo and resizeBy are completely erratic: do nothing
			if (Ext.isGecko || ((Ext.isIE || Ext.isOpera || (Ext.isWebKit && !Ext.isChrome)) && content)) {
				var self = this;
				setTimeout( function() {
					if (!noResize) {
						if (content) {
							self.resizeToContent(content);
						} else if (dialogWindow.sizeToContent) {
							dialogWindow.sizeToContent();
						}
					}
					self.centerOnParent();
				}, 75);
			} else if (!noResize) {
				var body = doc.body;
				if (Ext.isIE) {
					var innerX = (doc.documentElement && doc.documentElement.clientWidth) ? doc.documentElement.clientWidth : body.clientWidth;
					var innerY = (doc.documentElement && doc.documentElement.clientHeight) ? doc.documentElement.clientHeight : body.clientHeight;
					var pageY = Math.max(body.scrollHeight, body.offsetHeight);
					if (innerY == pageY) {
						dialogWindow.resizeTo(body.scrollWidth, body.scrollHeight + 80);
					} else {
						dialogWindow.resizeBy((innerX < body.scrollWidth) ? (Math.max(body.scrollWidth, body.offsetWidth) - innerX) : 0, (body.scrollHeight - body.offsetHeight));
					}
					// As of Google Chrome build 1798, window resizeTo and resizeBy are completely erratic: do nothing
				} else if (Ext.isSafari || Ext.isOpera) {
					dialogWindow.resizeTo(dialogWindow.innerWidth, body.offsetHeight + 10);
					if (dialogWindow.innerHeight < body.scrollHeight) {
						dialogWindow.resizeBy(0, (body.scrollHeight - dialogWindow.innerHeight) + 10);
					}
				}
				this.centerOnParent();
			} else {
				this.centerOnParent();
			}
		} else {
			this.centerOnParent();
		}
	},

	/**
	 * Resize the Opera dialog window to its contents, based on size of content div
	 *
	 * @param	object		content: reference to the div (may also be form) section containing the contents of the dialog window
	 *
	 * @return	void
	 */
	resizeToContent : function(content) {
		var dialogWindow = this.dialogWindow;
		var doc = dialogWindow.document;
		var docElement = doc.documentElement;
		var body = doc.body;
		var width = 0, height = 0;

		var contentWidth = content.offsetWidth;
		var contentHeight = content.offsetHeight;
		if (Ext.isGecko || Ext.isWebKit) {
			dialogWindow.resizeTo(contentWidth, contentHeight + (Ext.isWebKit ? 40 : (Ext.isGecko2 ? 75 : 95)));
		} else {
			dialogWindow.resizeTo(contentWidth + 200, contentHeight + 200);
			if (dialogWindow.innerWidth) {
				width = dialogWindow.innerWidth;
				height = dialogWindow.innerHeight;
			} else if (docElement && docElement.clientWidth) {
				width = docElement.clientWidth;
				height = docElement.clientHeight;
			} else if (body && body.clientWidth) {
				width = body.clientWidth;
				height = body.clientHeight;
			}
			dialogWindow.resizeTo(contentWidth + ((contentWidth + 200 ) - width), contentHeight + ((contentHeight + 200) - (height - 16)));
		}
	},

	/**
	 * Center the dialogue window on the parent window
	 *
	 * @return	void
	 */
	centerOnParent : function () {
		var buttonConfiguration = this.plugin.editorConfiguration.buttons[this.plugin.editorConfiguration.convertButtonId[this.buttonId]];
		if (!this.plugin.editorConfiguration.dialogueWindows.doNotCenter && (!buttonConfiguration  || !buttonConfiguration.dialogueWindow || !buttonConfiguration.dialogueWindow.doNotCenter)) {
			var dialogWindow = this.dialogWindow;
			var doc = dialogWindow.document;
			var body = doc.body;
				// Center on parent if allowed
			if (!Ext.isIE) {
				var x = dialogWindow.opener.screenX + (dialogWindow.opener.outerWidth - dialogWindow.outerWidth) / 2;
				var y = dialogWindow.opener.screenY + (dialogWindow.opener.outerHeight - dialogWindow.outerHeight) / 2;
			} else {
				var W = body.offsetWidth;
				var H = body.offsetHeight;
				var x = (screen.availWidth - W) / 2;
				var y = (screen.availHeight - H) / 2;
			}
				// As of build 1798, Google Chrome moveTo breaks the window dimensions: do nothing
			if (!Ext.isChrome) {
				try {
					dialogWindow.moveTo(x, y);
				} catch(e) { }
			}
		}
	},

	/**
	 * Perform the action function when the dialog end
	 *
	 * @return	void
	 */
	performAction : function (val) {
		if (val && this.action) {
			this.action(val);
		}
	},

	/**
	 * Bring the focus on the dialog window
	 *
	 * @return	void
	 */
	focus : function () {
		if (this.hasOpenedWindow()) {
			this.dialogWindow.focus();
		}
	},
	/**
	 * Close the dialog window
	 *
	 * @return	void
	 */
	close : function () {
		if (this.dialogWindow) {
			try {
				if (this.dialogWindow.openedDialog) {
					this.dialogWindow.openedDialog.close();
				}
			} catch(e) { }
			HTMLArea.Dialog[this.plugin.name] = null;
			if (!this.dialogWindow.closed) {
				this.dialogWindow.dialog = null;
				if (Ext.isWebKit || Ext.isIE) {
					this.dialogWindow.blur();
				}
				this.dialogWindow.close();
					// Safari 3.1.2 does not set the closed flag
				if (!this.dialogWindow.closed) {
					this.dialogWindow = null;
				}
			}
				// Opera unload event may be triggered after the editor iframe is gone
			if (this.plugin.editor._iframe) {
				this.plugin.editor.toolbar.update();
			}
		}
		return false;
	},

	/**
	 * Make function reference in order to avoid memory leakage in IE
	 *
	 * @param	string		functionName: the name of the dialog function to be invoked
	 *
	 * @return	function	function definition invoking the specified function of the dialog
	 */
	makeFunctionReference : function (functionName) {
		var self = this;
		return (function(arg1, arg2) {
			self[functionName](arg1, arg2);});
	},

	/**
	 * Escape event handler
	 *
	 * @param	object		ev: the event
	 *
	 * @return	boolean		false if the event was handled
	 */
	closeOnEscape : function(event) {
		var ev = event.browserEvent;
		if (ev.keyCode == 27) {
			if (!Ext.isIE) {
				var parentWindow = ev.currentTarget.defaultView;
			} else {
				var parentWindow = ev.srcElement.parentNode.parentNode.parentWindow;
			}
			if (parentWindow && parentWindow.dialog) {
					// If the dialogue window as an onEscape function, invoke it
				if (typeof(parentWindow.onEscape) == "function") {
					parentWindow.onEscape(ev);
				}
				if (parentWindow.dialog) {
					parentWindow.dialog.close();
				}
				return false;
			}
		}
		return true;
	},
	/**
	 * Capture unload and escape events
	 *
	 * @return	void
	 */
	captureEvents : function (skipUnload) {
			// Capture unload events on the dialogue window and the editor frame
		if (!Ext.isIE && this.plugin.editor._iframe.contentWindow) {
			Ext.EventManager.on(this.plugin.editor._iframe.contentWindow, 'unload', this.close, this, {single: true});
		}
		if (!skipUnload) {
			Ext.EventManager.on(this.dialogWindow, 'unload', this.close, this, {single: true});
		}
			// Capture escape key on the dialogue window
		Ext.EventManager.on(this.dialogWindow.document, 'keypress', this.closeOnEscape, this, {single: true});
	 }
});
}
