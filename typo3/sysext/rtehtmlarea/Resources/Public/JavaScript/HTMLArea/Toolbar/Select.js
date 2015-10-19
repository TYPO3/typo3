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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Toolbar/Select
 * A select field in the toolbar
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/String'],
	function (UserAgent, Dom, Util, Event, UtilString) {

	/**
	 * Select constructor
	 *
	 * @param {Object} config
	 * @constructor
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Toolbar/Select
	 */
	var Select = function (config) {
		Util.apply(this, config);
	};

	Select.prototype = {

		/**
		 * Render the select item (called by the toolbar)
		 *
		 * @param object container: the container of the select (the toolbar object)
		 * @return void
		 */
		render: function (container) {
			this.el = document.createElement('div');
			Dom.addClass(this.el, 'btn');
			Dom.addClass(this.el, 'form-group');
			this.selectElement = document.createElement('select');
			Dom.addClass(this.selectElement, 'form-control');
			if (this.id) {
				this.selectElement.setAttribute('id', this.id);
			}
			if (typeof this.cls === 'string') {
				Dom.addClass(this.selectElement, this.cls);
			}
			if (typeof this.tooltip === 'string') {
				this.selectElement.setAttribute('title', this.tooltip);
			}
			if (this.width) {
				Dom.setStyle(this.selectElement, { width: this.width + 'px' } );
			} else {
				Dom.setStyle(this.selectElement, { width: '200px' } );
			}
			if (this.maxHeight) {
				Dom.setStyle(this.selectElement, { maxHeight: this.maxHeight + 'px' } );
			}
			if (this.options) {
				for (var i = 0, n = this.options.length; i < n; i++) {
					this.addOption(this.options[i][0], this.options[i][1], this.options[i][1], this.options[i][2]);
				}
			}
			this.selectElement = this.el.appendChild(this.selectElement);
			this.el = container.appendChild(this.el);
			if (this.fieldLabel) {
				var label = document.createElement('label');
				label.innerHTML = this.fieldLabel;
				Dom.addClass(label, 'form-label');
				label.setAttribute('for', this.selectElement.id);
				this.el.insertBefore(label, this.selectElement);
			} else if (typeof this.tooltip === 'string') {
				this.selectElement.setAttribute('aria-label', this.tooltip);
			}
			this.selectedElementWidth = Dom.getSize(this.selectElement).width;
			this.initEventListeners();
		},

		/**
		 * Get the element to which the item is rendered
		 */
		getEl: function () {
			return this.el;
		},

		/**
		 * Initialize listeners
		 */
		initEventListeners: function () {
			var self = this;
			Event.on(this, 'HTMLAreaEventHotkey', function (event, key, keyEvent) { return self.onHotKey(key, keyEvent); });
			Event.on(this.selectElement, 'change', function (event) { return self.onChange(self, event); });
			// Handlers to change the selected option when the select is collapsed/expanded
			if (!UserAgent.isIE) {
				Event.on(this.selectElement, 'click', function (event) { self.onTrigger(event); });
				Event.on(window, 'mouseup', function (event) { if (event.target !== self.selectElement && !self.collapsed) { self.onTrigger(event); event.stopPropagation();}});
				Event.on(this.selectElement, 'blur', function (event) { if (!self.collapsed) { self.onTrigger(event); event.stopPropagation();}});
				Event.on(this.selectElement, 'keyup', function (event) { self.onEscape(event); });
			}
			// Monitor toolbar updates in order to refresh the state of the select
			Event.on(this.getToolbar(), 'HTMLAreaEventToolbarUpdate', function (event, mode, selectionEmpty, ancestors, endPointsInSameBlock) { Event.stopEvent(event); self.onUpdateToolbar(mode, selectionEmpty, ancestors, endPointsInSameBlock); return false; });
		},

		/**
		 * Get a reference to the editor
		 */
		getEditor: function() {
			return this.getToolbar().getEditor();
		},

		/**
		 * Get a reference to the toolbar
		 */
		getToolbar: function() {
			return this.toolbar;
		},

		/**
		 * Handler invoked when an item is selected in the dropdown list
		 */
		onChange: function (select, event) {
			if (!select.disabled) {
				var editor = this.getEditor();
				// Invoke the plugin onChange handler
				this.plugins[this.action](editor, select);
				if (UserAgent.isOpera) {
					editor.focus();
				}
				this.getToolbar().update();
			}
			return false;
		},

		/**
		 * State of the select dropdwon list
		 */
		collapsed: true,

		/**
		 * Handler for a click on the select
		 */
		onTrigger: function (event) {
			this.collapsed = !this.collapsed;
			this.setSelectedOptionText();
		},

		/**
		 * Handler for an escape while focused on the select
		 */
		onEscape: function (event) {
			if (Event.getKey(event) === Event.ESC && !this.collapsed) {
				this.onTrigger();
			}
		},

		/**
		 * Get the current value
		 *
		 * @return string the value attribute of the currently selected option
		 */
		getValue: function () {
			return this.selectElement.options[this.selectElement.selectedIndex].value;
		},

		/**
		 * Set the current value
		 *
		 * @param string value: the value to be selected
		 * @return void
		 */
		setValue: function (value) {
			var options = this.selectElement.options;
			for (var i = 0, n = options.length; i < n; i++) {
				if (options[i].value == value) {
					this.selectElement.selectedIndex = i;
					this.collapsed = true;
					this.setSelectedOptionText();
					break;
			  	}
			}
		},

		/**
		 * Set the current value based on index
		 *
		 * @param int index: the index of the value to be selected
		 * @return void
		 */
		setValueByIndex: function (index) {
			this.selectElement.selectedIndex = index >= 0 ? index : 0;
			this.collapsed = true;
			this.setSelectedOptionText();
		},

		/**
		 * Find the index of the value
		 *
		 * @param string value: the value to be looked up
		 * @return int the index or -1
		 */
		findValue: function (value) {
			var index = -1;
			var options = this.selectElement.options;
			for (var i = 0, n = options.length; i < n; i++) {
				if (options[i].value == value) {
					index = i;
					break;
			  	}
			}
			return index;
		},

		/**
		 * Get the value of the option specified by the index
		 *
		 * @param int index: the index of the option
		 * @return string the value of the option
		 */
		getOptionValue: function (index) {
			var value = '';
			var option = this.selectElement.options[index];
			if (option) {
				value = option.value;
			}
			return value;
		},

		/**
		 * Set the text of the selected option
		 *
		 * @return void
		 */
		setSelectedOptionText: function () {
			var option = this.selectElement.options[this.selectElement.selectedIndex];
			if (this.collapsed && !UserAgent.isIE) {
				option.innerHTML = option.getAttribute('data-htmlarea-text').ellipsis(this.selectedElementWidth - 20);
			} else {
				option.innerHTML = option.getAttribute('data-htmlarea-text');
			}
		},

		/**
		 * Set the first option of the select
		 *
		 * @param string text: the text of the option
		 * @param string value: the value of the option
		 * @param string title: the title of the option, if different from the value
		 * @return object the option
		 */
		setFirstOption: function (text, value, title) {
			var option = this.selectElement.firstChild;
			if (!option) {
				var option = this.addOption(text, value, title);
			} else {
				option.innerHTML = text;
				option.setAttribute('value', value);
				if (typeof title !== 'undefined') {
					option.setAttribute('title', title);
				} else {
					option.setAttribute('title', value);
				}
			}
			return option;
		},

		/**
		 * Add an option to the select
		 *
		 * @param string text: the text of the option
		 * @param string value: the value of the option
		 * @param string title: the title of the option
		 * @param string style: the style of the option
		 * @return object the option
		 */
		addOption: function (text, value, title, style) {
			var option = document.createElement('option');
			option.innerHTML = text;
			option.setAttribute('data-htmlarea-text', text);
			option.setAttribute('value', value);
			if (typeof title !== 'undefined') {
				option.setAttribute('title', title);
			} else {
				option.setAttribute('title', value);
			}
			if (typeof style === 'string' && style.length > 0) {
				option.style.cssText = style;
			}
			if (this.listWidth) {
				Dom.setStyle(option, { width: this.listWidth + 'px' } );
			}
			this.selectElement.add(option);
			return option;
		},

		/**
		 * Get the current options of the select element
		 *
		 * @return array the options of the select element
		 */
		getOptions: function () {
			return this.selectElement.options;
		},

		/**
		 * Get the current count of options
		 *
		 * @return int the count
		 */
		getCount: function () {
			return this.getOptions().length;
		},

		/**
		 * Remove the option at the specified index
		 *
		 * @param int index: the index of the option to be removed
		 * @return void
		 */
		removeAt: function (index) {
			this.selectElement.remove(index);
		},

		/**
		 * Delete all options of the select element
		 *
		 * @return void
		 */
		removeAll: function () {
			var index, options = this.getOptions();
			while (index = options.length) {
				this.selectElement.remove(0);
			}
		},

		/**
		 * Setting disabled/enabled by boolean.
		 *
		 * @param boolean disabled
		 * @return void
		 */
		setDisabled: function(disabled){
			this.disabled = disabled;
			if (disabled) {
				this.selectElement.setAttribute('disabled', 'true');
			} else {
				this.selectElement.removeAttribute('disabled');
			}
		},

		/**
		 * Handler invoked when a hot key configured for this dropdown list is pressed
		 */
		onHotKey: function (key) {
			if (!this.disabled) {
				this.plugins.onHotKey(this.getEditor(), key);
				if (UserAgent.isOpera) {
					this.getEditor().focus();
				}
				this.getToolbar().update();
			}
			return false;
		},

		/**
		 * Handler invoked when the toolbar is updated
		 */
		onUpdateToolbar: function (mode, selectionEmpty, ancestors, endPointsInSameBlock) {
			this.setDisabled(mode === 'textmode' && !this.textMode);
			if (!this.disabled) {
				this.plugins['onUpdateToolbar'](this, mode, selectionEmpty, ancestors, endPointsInSameBlock);
			}
		},

		/**
		 * Cleanup (called by toolbar onBeforeDestroy)
		 */
		onBeforeDestroy: function () {
			Event.off(this);
			Event.off(this.selectElement);
			if (this.selectElement) {
				this.removeAll();
				this.selectElement = null;
			}
			if (this.el) {
				var node;
				while (node = this.el.firstChild) {
					this.el.removeChild(node);
				}
				this.el = null;
			}
		}
	};

	return Select;

});
