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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Toolbar/ToolbarText
 * A text item in the toolbar
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event'],
	function (Dom, Util, Event) {

	/**
	 * Toolbar text item constructor
	 *
	 * @param {Object} config
	 * @constructor
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Toolbar/ToolbarText
	 */
	var ToolbarText = function (config) {
		Util.apply(this, config);
	};

	ToolbarText.prototype = {

		/**
		 * Render the text item (called by the toolbar)
		 *
		 * @param object container: the container of the toolbarText (the toolbar object)
		 * @return void
		 */
		render: function (container) {
			this.el = document.createElement('div');
			Dom.addClass(this.el, 'btn');
			Dom.addClass(this.el, 'btn-sm');
			Dom.addClass(this.el, 'btn-default');
			Dom.addClass(this.el, 'toolbar-text');
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
				this.el.setAttribute('aria-label', this.tooltip);
			}
			container.appendChild(this.el);
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
			// Monitor toolbar updates in order to refresh the state of the text item
			var self = this;
			Event.on(this.getToolbar(), 'HTMLAreaEventToolbarUpdate', function (event, mode, selectionEmpty, ancestors, endPointsInSameBlock) { Event.stopEvent(event); self.onUpdateToolbar(mode, selectionEmpty, ancestors, endPointsInSameBlock); return false; });
		},

		/**
		 * Get a reference to the toolbar
		 */
		getToolbar: function() {
			return this.toolbar;
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
		 * Setting disabled/enabled by boolean.
		 * @param boolean disabled
		 * @return void
		 */
		setDisabled: function(disabled){
			this.disabled = disabled;
			if (disabled) {
				this.el.setAttribute('disabled', 'disabled');
			} else {
				this.el.removeAttribute('disabled');
			}
		},

		/**
		 * Cleanup (called by toolbar onBeforeDestroy)
		 */
		onBeforeDestroy: function () {
			if (this.el) {
				var node;
				while (node = this.el.firstChild) {
					this.el.removeChild(node);
				}
				this.el = null;
			}
		}
	};

	return ToolbarText;

});
