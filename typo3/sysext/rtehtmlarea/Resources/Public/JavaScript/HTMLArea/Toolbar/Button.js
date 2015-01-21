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
 * A button in the toolbar
 */
define('TYPO3/CMS/Rtehtmlarea/HTMLArea/Toolbar/Button',
	['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event'],
	function (UserAgent, Dom, Util, Event) {

	/**
	 * Button constructor
	 */
	var Button = function (config) {
		Util.apply(this, config);		
	};

	Button.prototype = {

		/**
		 * Render the button item (called by the toolbar)
		 *
		 * @param object container: the container of the button (the toolbar object)
		 * @return void
		 */
		render: function (container) {
			this.el = document.createElement('div');
			Dom.addClass(this.el, 'x-form-item');
			Dom.addClass(this.el, 'button');
			Dom.addClass(this.el, 'unselectable');
			this.el.setAttribute('unselectable', 'on');
			if (this.id) {
				this.el.setAttribute('id', this.id);
			}
			if (typeof this.cls === 'string') {
				Dom.addClass(this.el, this.cls);
			}
			if (typeof this.text === 'string') {
				this.el.innerHTML = this.text;
			}
			if (typeof this.tooltip === 'string') {
				this.el.setAttribute('title', this.tooltip);
			}
			if (this.hidden) {
				Dom.setStyle(this.el, { display: 'none' } );
			}
			this.buttonElement = document.createElement('button');
			this.buttonElement.setAttribute('type', 'button');
			Dom.addClass(this.buttonElement, 'btn-text');
			if (typeof this.iconCls === 'string') {
				Dom.addClass(this.buttonElement, this.iconCls);
			}
			this.buttonElement.innerHTML = '&nbsp;';
			this.el.appendChild(this.buttonElement);
			container.getEl().appendChild(this.el);
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
			Event.on(this, 'HTMLAreaEventContextMenu', function (event, button) { return self.onButtonClick(button, event); });
			Event.on(this.el, this.clickEvent, function (event) { return self.onButtonClick(self, event); });
			Event.on(this.el, 'mouseover', function (event) { return self.onMouseOver(event); });
			Event.on(this.el, 'mouseout', function (event) { return self.onMouseOut(event); });
			// Monitor toolbar updates in order to refresh the state of the button
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
		 * Get the itemId of the button
		 */
		getItemId: function() {
			return this.itemId;
		},

		/**
		 * Add properties and function to set button active or not depending on current selection
		 */
		inactive: true,
		activeClass: 'buttonActive',
		setInactive: function (inactive) {
			this.inactive = inactive;
			return inactive ? Dom.removeClass(this.el, this.activeClass) : Dom.addClass(this.el, this.activeClass);
		},

		/**
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
				var i, j, n;
				for (i = 0, n = ancestors.length; i < n; i++) {
					var ancestor = ancestors[i];
					inContext = matchAny || contexts.test(ancestor.nodeName);
					if (inContext) {
						for (j = attributes.length; --j >= 0;) {
							inContext = eval("ancestor." + attributes[j]);
							if (!inContext) {
								break;
							}
						}
					}
					if (inContext) {
						break;
					}
				}
			}
			return inContext && (!this.selection || !selectionEmpty);
		},

		/**
		 * Handler invoked when the button is clicked
		 */
		onButtonClick: function (button, event, key) {
			if (!this.disabled) {
				if (!this.plugins[this.action](this.getEditor(), key || this.itemId) && event) {
					Event.stopEvent(event);
				}
				if (UserAgent.isOpera) {
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

		/**
		 * Handler invoked when the mouse goes over the button
		 */
		onMouseOver: function (event) {
			if (!this.disabled && this.inactive) {
				Dom.addClass(this.el, 'buttonHover');
			}
		},

		/**
		 * Handler invoked when the mouse moves out of the button
		 */
		onMouseOut: function (event) {
			Dom.removeClass(this.el, 'buttonHover');
		},

		/**
		 * Handler invoked when the hotkey configured for this button is pressed
		 */
		onHotKey: function (key, event) {
			return this.onButtonClick(this, event, key);
		},

		/**
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
		},

		/**
		 * Update the tooltip text
		 */
		setTooltip: function (text) {
			this.tooltip = text;
			this.buttonElement.title = text;
		},

		/**
		 * Css class applied when the item is disabled
		 */
		disabledClass: 'buttonDisabled',

		/**
		 * Setting disabled/enabled by boolean.
		 * @param boolean disabled
		 * @return void
		 */
		setDisabled: function(disabled){
			this.disabled = disabled;
			if (disabled) {
				Dom.addClass(this.el, this.disabledClass);
			} else {
				Dom.removeClass(this.el, this.disabledClass);
			}
		},

		/**
		 * Cleanup (called by toolbar onBeforeDestroy)
		 */
		onBeforeDestroy: function () {
			Event.off(this);
			if (this.el) {
				Event.off(this.el);
				var node;
				while (node = this.el.firstChild) {
					this.el.removeChild(node);
				}
				this.el = null;
			}
		}
	};

	return Button;

});
