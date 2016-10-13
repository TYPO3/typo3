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
 * @AMD-Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin
 * HTMLArea.plugin class
 *
 * Every plugin should be a subclass of this class
 *
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'jquery',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Severity'
], function (UserAgent, Util, Event, $, Modal, Severity) {

	/**
	 * Constructor method
	 *
	 * @param {Object} editor: a reference to the parent object, instance of RTE
	 * @param {String} pluginName: the name of the plugin
	 * @constructor
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin
	 */
	var Plugin = function (editor, pluginName) {
		this.editor = editor;
		this.editorNumber = editor.editorId;
		this.editorId = editor.editorId;
		this.editorConfiguration = editor.config;
		this.name = pluginName;
		this.I18N = {};
		if (typeof HTMLArea.I18N !== 'undefined' && typeof HTMLArea.I18N[this.name] !== 'undefined') {
			this.I18N = HTMLArea.I18N[this.name];
		}
		this.configurePlugin(editor);
	};

	Plugin.prototype = {

		/**
		 * Configures the plugin
		 * This function is invoked by the class constructor.
		 * This function should be redefined by the plugin subclass. Normal steps would be:
		 *	- registering plugin ingormation with method registerPluginInformation;
		 *	- registering any buttons with method registerButton;
		 *	- registering any drop-down lists with method registerDropDown.
		 *
		 * @param {Object} editor Instance of RTE
		 *
		 * @return {Boolean} True if the plugin was configured
		 */
		configurePlugin: function (editor) {
			return false;
		},

		/**
		 * Registers the plugin "About" information
		 *
		 * @param {Object} pluginInformation
		 *                     version: the version,
		 *                     developer: the name of the developer,
		 *                     developerUrl: the url of the developer,
		 *                     copyrightOwner: the name of the copyright owner,
		 *                     sponsor: the name of the sponsor,
		 *                     sponsorUrl: the url of the sponsor,
		 *                     license: the type of license (should be "GPL")
		 *
		 * @return {Boolean} True if the information was registered
		 */
		registerPluginInformation: function (pluginInformation) {
			if (typeof pluginInformation !== 'object' || pluginInformation === null) {
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
		 * @return {Object} The plugin information object
		 */
		getPluginInformation: function () {
			return this.pluginInformation;
		},

		/**
		 * Returns a plugin object
		 *
		 * @param {String} pluginName The name of some plugin
		 * @return {Object} The plugin object or null
		 */
		getPluginInstance: function (pluginName) {
			return this.editor.getPlugin(pluginName);
		},

		/**
		 * Returns a current editor mode
		 *
		 * @return {String} Editor mode
		 */
		getEditorMode: function () {
			return this.editor.getMode();
		},

		/**
		 * Returns true if the button is enabled in the toolbar configuration
		 *
		 * @param {String} buttonId Identification of the button
		 *
		 * @return {Boolean} True if the button is enabled in the toolbar configuration
		 */
		isButtonInToolbar: function (buttonId) {
			var index = -1;
			var i, j, n, m;
			for (i = 0, n = this.editorConfiguration.toolbar.length; i < n; i++) {
				var row = this.editorConfiguration.toolbar[i];
				for (j = 0, m = row.length; j < m; j++) {
					var group = row[j];
					index = group.indexOf(buttonId);
					if (index !== -1) {
						break;
					}
				}
				if (index !== -1) {
					break;
				}
			}
			return index !== -1;
		},

		/**
		 * Returns the button object from the toolbar
		 *
		 * @param {String} buttonId Identification of the button
		 *
		 * @return {Object} The toolbar button object
		 */
		getButton: function (buttonId) {
			return this.editor.toolbar.getButton(buttonId);
		},

		/**
		 * Registers a button for inclusion in the toolbar
		 *
		 * @param {Object} buttonConfiguration The configuration object of the button:
		 *                     id: unique id for the button
		 *                     tooltip: tooltip for the button
		 *                     textMode : enable in text mode
		 *                     action: name of the function invoked when the button is pressed
		 *                     context: will be disabled if not inside one of listed elements
		 *                     hide: hide in menu and show only in context menu (deprecated, use hidden)
		 *                     hidden: synonym of hide
		 *                     selection: will be disabled if there is no selection
		 *                     hotkey: hotkey character
		 *                     dialog: if true, the button opens a dialogue
		 *                     dimensions: the opening dimensions object of the dialogue window
		 *
		 * @return {Boolean} True if the button was successfully registered
		 */
		registerButton: function (buttonConfiguration) {
			if (this.isButtonInToolbar(buttonConfiguration.id)) {
				if (typeof buttonConfiguration.action === 'string' && buttonConfiguration.action.length > 0 && typeof this[buttonConfiguration.action] === 'function') {
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
					Util.applyIf(buttonConfiguration, this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]]);
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
		 * @param {Object} dropDownConfiguration: the configuration object of the drop-down:
		 *                     id: unique id for the drop-down
		 *                     tooltip: tooltip for the drop-down
		 *                     action: name of function to invoke when an option is selected
		 *                     textMode: enable in text mode
		 *
		 * @return {Boolean} True if the drop-down list was successfully registered
		 */
		registerDropDown: function (dropDownConfiguration) {
			if (this.isButtonInToolbar(dropDownConfiguration.id)) {
				if (typeof dropDownConfiguration.action === 'string' && dropDownConfiguration.action.length > 0 && typeof this[dropDownConfiguration.action] === 'function') {
					dropDownConfiguration.plugins = this;
					dropDownConfiguration.hidden = dropDownConfiguration.hide;
					dropDownConfiguration.xtype = 'htmlareaselect';
					// Apply additional config properties set in Page TSConfig
					// May not always work for values that must be integers
					Util.applyIf(dropDownConfiguration, this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[dropDownConfiguration.id]]);
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
		 * @param {Object} textConfiguration: the configuration object of the text element:
		 *                     id: unique id for the text item
		 *                     text: the text litteral
		 *                     tooltip: tooltip for the text item
		 *                     cls: a css class to be assigned to the text element
		 *
		 * @return {Boolean} true if the drop-down list was successfully registered
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
		 * @param {String} dropDownId The unique id of the drop-down
		 *
		 * @return {Object} The drop-down configuration object
		 */
		getDropDownConfiguration: function(dropDownId) {
			return this.editorConfiguration.buttonsConfig[dropDownId];
		},

		/**
		 * Registers a hotkey
		 *
		 * @param {Object} hotKeyConfiguration The configuration object of the hotkey:
		 *                     id: the key
		 *                     cmd: name of the button corresponding to the hot key
		 *                     element: value of the record to be selected in the dropDown item
		 *
		 * @return {Boolean} True if the hotkey was successfully registered
		 */
		registerHotKey: function (hotKeyConfiguration) {
			return this.editorConfiguration.registerHotKey(hotKeyConfiguration);
		},

		/**
		 * Returns the buttonId corresponding to the hotkey, if any
		 *
		 * @param {String} key The hotkey
		 *
		 * @return {string} The buttonId or ""
		 */
		translateHotKey: function(key) {
			var returnValue = '';
			if (typeof this.editorConfiguration.hotKeyList[key] !== 'undefined') {
				var buttonId = this.editorConfiguration.hotKeyList[key].cmd;
				if (typeof buttonId !== 'undefined') {
					returnValue = buttonId;
				}
			}
			return returnValue;
		},

		/**
		 * Returns the hotkey configuration
		 *
		 * @param {String} key The hotkey
		 *
		 * @return {Object} The hotkey configuration object
		 */
		getHotKeyConfiguration: function(key) {
			if (typeof this.editorConfiguration.hotKeyList[key] !== 'undefined') {
				return this.editorConfiguration.hotKeyList[key];
			}
			return null;
		},

		/**
		 * Initializes the plugin
		 * Is invoked when the toolbar component is created (subclass of Ext.ux.HTMLAreaButton or Ext.ux.form.HTMLAreaCombo)
		 *
		 * @param {Object} button The component
		 */
		init: Util.emptyFunction,

		/**
		 * The toolbar refresh handler of the plugin
		 * This function may be defined by the plugin subclass.
		 * If defined, the function will be invoked whenever the toolbar state is refreshed.
		 *
		 * @return {Boolean}
		 */
		onUpdateToolbar: Util.emptyFunction,

		/**
		 * The onMode event handler
		 * This function may be redefined by the plugin subclass.
		 * The function is invoked whenever the editor changes mode.
		 *
		 * @param {String} mode "wysiwyg" or "textmode"
		 *
		 * @return {Boolean}
		 */
		onMode: function(mode) {
			if (mode === "textmode" && this.dialog && !(this.dialog.buttonId && this.editorConfiguration.buttons[this.dialog.buttonId] && this.editorConfiguration.buttons[this.dialog.buttonId].textMode)) {
				if (typeof Modal.currentModal !== 'undefined') {
					Modal.currentModal.trigger('modal-dismiss');
				}
			}
		},

		/**
		 * The onGenerate event handler
		 * This function may be defined by the plugin subclass.
		 * The function is invoked when the editor is initialized
		 *
		 * @return {Boolean}
		 */
		onGenerate: Util.emptyFunction,

		/**
		 * Localize a string
		 *
		 * @param {String} label The name of the label to localize
		 * @param {Integer} plural
		 *
		 * @return {String} The localization of the label
		 */
		localize: function (label, plural) {
			var i = plural || 0;
			var localized = this.I18N[label];
			if (typeof localized === 'object' && localized !== null && typeof localized[i] !== 'undefined') {
				localized = localized[i]['target'];
			} else {
				localized = HTMLArea.localize(label, plural);
			}
			return localized;
		},

		/**
		 * Get localized label wrapped with contextual help markup when available
		 *
		 * @param {String} fieldName The name of the field in the CSH file
		 * @param {String} label The name of the label to localize
		 * @param {String} pluginName Overrides this.name
		 *
		 * @return {String} Localized label with CSH markup
		 */
		getHelpTip: function (fieldName, label, pluginName) {
			if (typeof TYPO3.ContextHelp !== 'undefined' && typeof fieldName === 'string') {
				pluginName = typeof pluginName !== 'undefined' ? pluginName : this.name;
				if (fieldName.length > 0) {
					fieldName = fieldName.replace(/-|\s/gi, '_');
				}
				return '<span class="t3-help-link" href="#" data-table="xEXT_rtehtmlarea_' + pluginName + '" data-field="' + fieldName + '"><abbr class="t3-help-teaser">' + (this.localize(label) || label) + '</abbr></span>';
			}
			return this.localize(label) || label;
		},

		/**
		 * Load a Javascript file asynchronously
		 *
		 * @param {String} url URL of the file to load
		 * @param {Function} callback The callBack function
		 *
		 * @return {Boolean} True on success of the request submission
		 */
		getJavascriptFile: function (url, callback) {
			return this.editor.ajax.getJavascriptFile(url, callback, this);
		},

		/**
		 * Post data to the server
		 *
		 * @param {String} url URL to post data to
		 * @param {Object} data Data to be posted
		 * @param {Function} callback Function that will handle the response returned by the server
		 *
		 * @return {Boolean} True on success
		 */
		postData: function (url, data, callback) {
			return this.editor.ajax.postData(url, data, callback, this);
		},

		/**
		 * Open a window with container iframe
		 *
		 * @param {String} buttonId The id of the button
		 * @param {String} title The window title (will be localized here)
		 * @param {Integer} height The height of the containing iframe
		 * @param {String} url The url to load ino the iframe
		 */
		openContainerWindow: function (buttonId, title, height, url) {
			var $iframe = $('<iframe />', {src: url, 'class': 'content-iframe', style: 'border: 0; width: 100%; height: ' + height * 1 + 'px;'}),
				$content = $('<div />', {'class': 'htmlarea-window', id: this.editor.editorId + buttonId}).append($iframe);

			this.dialog = Modal.show(this.localize(title) || title, $content, Severity.notice);

			// TODO: dirty CSS hack - provide an API instead?
			this.dialog.find('.modal-body').css('padding', 0);
		},

		/**
		 * Make url from module path
		 *
		 * @param {String} modulePath Module path
		 * @param {String} parameters Additional parameters
		 *
		 * @return {String} The url
		 */
		makeUrlFromModulePath: function (modulePath, parameters) {
			return modulePath + (modulePath.indexOf("?") === -1 ? "?" : "&") + this.editorConfiguration.RTEtsConfigParams + '&editorNo=' + this.editor.editorId + '&sys_language_content=' + this.editorConfiguration.sys_language_content + '&contentTypo3Language=' + this.editorConfiguration.typo3ContentLanguage + (parameters?parameters:'');
		},

		/**
		 * Append an entry at the end of the troubleshooting log
		 *
		 * @param {String} functionName The name of the plugin function writing to the log
		 * @param {String} text The text of the message
		 * @param {String} type The typeof of message: 'log', 'info', 'warn' or 'error'
		 */
		appendToLog: function (functionName, text, type) {
			this.editor.appendToLog(this.name, functionName, text, type);
		},

		/**
		 * Add a config element to config array if not empty
		 *
		 * @param {Object} configElement The config element
		 * @param {Array} configArray The config array
		 */
		addConfigElement: function (configElement, configArray) {
			if (typeof configElement === 'object'  && configElement !== null) {
				configArray.push(configElement);
			}
		},

		/**
		 * Show the dialogue window
		 */
		show: function () {
			// Close the window if the editor changes mode
			var self = this;
			Event.one(this.editor, 'HTMLAreaEventModeChange', function (event) { self.close(); });
			this.saveSelection();
			if (typeof this.dialogueWindowDimensions !== 'undefined') {
				this.dialog.setPosition(this.dialogueWindowDimensions.positionFromLeft, this.dialogueWindowDimensions.positionFromTop);
			}
			this.dialog.show();
			this.restoreSelection();
		},

		/**
		 * Remove listeners
		 * This function may be defined by the plugin subclass.
		 * The function is invoked when a plugin dialog is closed
		 */
		removeListeners: Util.emptyFunction,

		/**
		 * Close the dialogue window (after saving the selection, if IE)
		 */
		close: function () {
			this.removeListeners();
			this.saveSelection();
			if (typeof Modal.currentModal !== 'undefined') {
				Modal.currentModal.trigger('modal-dismiss');
			}
		},

		/**
		 * Dialogue window onClose handler
		 */
		onClose: function () {
			this.removeListeners();
			this.editor.focus();
			this.restoreSelection();
			this.editor.updateToolbar();
		},

		/**
		 * Handler for window cancel
		 */
		onCancel: function () {
			Modal.currentModal.trigger('modal-dismiss');

			this.removeListeners();
			this.editor.focus();
		},

		/**
		 * Save selection
		 * Should be called after processing button other than Cancel
		 */
		saveSelection: function () {
			// If IE, save the current selection
			if (UserAgent.isIE) {
				this.savedRange = this.editor.getSelection().createRange();
			}
		},

		/**
		 * Restore selection
		 * Should be called before processing dialogue button or result
		 */
		restoreSelection: function () {
			// If IE, restore the selection saved when the window was shown
			if (UserAgent.isIE && this.savedRange) {
					// Restoring the selection will not work if the inner html was replaced by the plugin
				try {
					this.editor.getSelection().selectRange(this.savedRange);
				} catch (e) {}
			}
		},

		/**
		 * Build the configuration object of a button
		 *
		 * @param {String} button The text of the button
		 * @param {Function} handler Button handler
		 * @param {Boolean} active Whether the button should be active or not
		 * @param {Integer} severity The severity the button is representing
		 *
		 * @return {Object} The button configuration object
		 */
		buildButtonConfig: function (button, handler, active, severity) {
			return {
				text: this.localize(button),
				active: active,
				btnClass: 'btn-' + (typeof severity !== 'undefined' ? Severity.getCssClass(severity) : 'default'),
				trigger: handler
			};
		},

		/**
		 * Helper method to generate the select boxes with predefined values
		 *
		 * @param {Object} $fieldset The jQuery object of the current fieldset
		 * @param {String} fieldLabel The label of the form field
		 * @param {String} selectName The name of the select field
		 * @param {Array} availableOptions Nested options array used for the select field. 0: label, 1: value
		 * @param {String} selectedValue The selected value. Used to set the `selected` property
		 * @param {Function} onChangeHandler Callback function triggered on change
		 * @param {Boolean} isDisabled Whether the field should be disabled or not
		 * @return {Object}
		 */
		attachSelectMarkup: function($fieldset, fieldLabel, selectName, availableOptions, selectedValue, onChangeHandler, isDisabled) {
			var $select = $('<select />', {'class': 'form-control', name: selectName}),
				attributeConfiguration = {}

			for (var i = 0; i < availableOptions.length; ++i) {
				attributeConfiguration = {
					value: availableOptions[i][1]
				};

				if (selectedValue && availableOptions[i][1] === selectedValue) {
					attributeConfiguration.selected = 'selected';
				}

				$select.append(
					$('<option />', attributeConfiguration).text(availableOptions[i][0])
				);
			}

			if (onChangeHandler && typeof onChangeHandler === 'function') {
				$select.on('change', onChangeHandler);
			}

			if (typeof isDisabled === 'boolean' && isDisabled) {
				$select.prop('disabled', isDisabled);
			}

			$fieldset.append(
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(fieldLabel),
					$('<div />', {'class': 'col-sm-10'}).append($select)
				)
			);

			return $fieldset;
		},

		/**
		 * Helper method that creates the necessary markup for a new tab
		 *
		 * @param {Object} $tabs
		 * @param {Object} $container
		 * @param {String} identifier
		 * @param {Object} elements
		 * @param {String} label
		 */
		buildTabMarkup: function($tabs, $container, identifier, elements, label) {
			var $newTabPanel = $('<div />', {role: 'tabpanel', 'class': 'tab-pane', id: identifier});
			$tabs.append(
				$('<li />').append(
					$('<a />', {href: '#' + identifier, 'aria-controls': identifier, role: 'tab', 'data-toggle': 'tab'}).text(label)
				)
			);
			for (var item in elements) {
				if (elements.hasOwnProperty(item)) {
					$newTabPanel.append(
						$('<div />', {'class': 'form-section'}).append(elements[item])
					);
				}
			}
			$container.append($newTabPanel);
		},
	};

	return Plugin;

});
