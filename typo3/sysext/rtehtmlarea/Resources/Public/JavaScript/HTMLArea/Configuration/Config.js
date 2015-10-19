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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Configuration/Config
 * Configuration of af an Editor of TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (UserAgent, Util) {

	/**
	 * Constructor: Sets editor configuration defaults
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Configuration/Config
	 */
	var Config = function (editorId) {
		this.editorId = editorId;
			// for Mozilla
		this.useCSS = false;
		this.enableMozillaExtension = true;
		this.disableEnterParagraphs = false;
		this.disableObjectResizing = false;
		this.removeTrailingBR = true;
			// style included in the iframe document
		this.editedContentStyle = HTMLArea.editedContentCSS;
			// Array of content styles
		this.pageStyle = [];
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
		this.popupURL = "Resources/Public/Html/";
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
				textMode: false,
				selection: false,
				dialog: false,
				hidden: false
			}
		};
	};

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
	Config.prototype.registerButton = function (config) {
		config.itemId = config.id;
		if (typeof this.buttonsConfig[config.id] !== 'undefined' && this.buttonsConfig[config.id] !== null) {
			HTMLArea.appendToLog('', 'HTMLArea.Config', 'registerButton', 'A toolbar item with the same Id: ' + config.id + ' already exists and will be overidden.', 'warn');
		}
		// Apply defaults
		Util.applyIf(config, this.configDefaults['all']);
		Util.applyIf(config, this.configDefaults[config.xtype]);
		// Set some additional properties
		switch (config.xtype) {
			case 'htmlareaselect':
				config.hideLabel = typeof config.fieldLabel !== 'string' || !config.fieldLabel.length || UserAgent.isIE6;
				config.helpTitle = config.tooltip;
				break;
			default:
				if (!config.iconCls) {
					config.iconCls = config.id;
				}
				break;
		}
		config.cmd = config.id;
		config.tooltipType = 'title';
		this.buttonsConfig[config.id] = config;
		return true;
	};

	/**
	 * Register a hotkey with the editor configuration.
	 */
	Config.prototype.registerHotKey = function (hotKeyConfiguration) {
		if (typeof this.hotKeyList[hotKeyConfiguration.id] !== 'undefined') {
			HTMLArea.appendToLog('', 'HTMLArea.Config', 'registerHotKey', 'A hotkey with the same key ' + hotKeyConfiguration.id + ' already exists and will be overidden.', 'warn');
		}
		if (typeof hotKeyConfiguration.cmd === 'string' && hotKeyConfiguration.cmd.length > 0 && typeof this.buttonsConfig[hotKeyConfiguration.cmd] !== 'undefined') {
			this.hotKeyList[hotKeyConfiguration.id] = hotKeyConfiguration;
			return true;
		} else {
			HTMLArea.appendToLog('', 'HTMLArea.Config', 'registerHotKey', 'A hotkey with key ' + hotKeyConfiguration.id + ' could not be registered because toolbar item with id ' + hotKeyConfiguration.cmd + ' was not registered.', 'warn');
			return false;
		}
	};

	/**
	 * Get the configured document type for dialogue windows
	 */
	Config.prototype.getDocumentType = function () {
		return this.documentType;
	};

	return Config;

});
