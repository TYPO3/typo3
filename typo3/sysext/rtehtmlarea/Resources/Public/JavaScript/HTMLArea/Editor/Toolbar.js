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
 * The editor toolbar
 */
define('TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/Toolbar',
	['TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ux/Combo',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ux/Button',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ux/ToolbarText'],
	function (Util, Dom, Event, Combo, Button, ToolbarText) {

	/**
	 * Editor toolbar constructor
	 */
	var Toolbar = function (config) {
		Util.apply(this, config);
	};

	Toolbar.prototype = {

		/**
		 * Render the toolbar (called by framework rendering)
		 *
		 * @param object container: the container into which to insert the toolbar (that is the framework)
		 * @return void
		 */
		render: function (container) {
			this.el = document.createElement('div');
			if (this.id) {
				this.el.setAttribute('id', this.id);
			}
			if (this.cls) {
				this.el.setAttribute('class', this.cls);
			}
			this.el = container.appendChild(this.el);
			this.addItems();
			this.rendered = true;
		},

		/**
		 * Get the element to which the toolbar is rendered
		 */
		getEl: function () {
			return this.el;
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
		 * The toolbar items
		 */
		items: {},

		/**
		 * Create the toolbar items based on editor toolbar configuration
		 */
		addItems: function () {
			var editor = this.getEditor();
			// Walk through the editor toolbar configuration nested arrays: [ toolbar [ row [ group ] ] ]
			var firstOnRow = true;
			var firstInGroup = true;
			var i, j, k, n, m, p, row, group, item;
			for (i = 0, n = editor.config.toolbar.length; i < n; i++) {
				row = editor.config.toolbar[i];
				if (!firstOnRow) {
					// If a visible item was added to the previous line
					this.addSpacer('space-clear-left');
				}
				firstOnRow = true;
				// Add the groups
				for (j = 0, m = row.length; j < m; j++) {
					group = row[j];
					// To do: this.config.keepButtonGroupTogether ...
					if (!firstOnRow && !firstInGroup) {
						// If a visible item was added to the line
						this.addSeparator();
					}
					firstInGroup = true;
					// Add each item
					for (k = 0, p = group.length; k < p; k++) {
						item = group[k];
						if (item == 'space') {
							this.addSpacer();
						} else {
							// Get the item's config as registered by some plugin
							var itemConfig = editor.config.buttonsConfig[item];
							if (typeof itemConfig === 'object' && itemConfig !== null) {
								itemConfig.id = this.editorId + '-' + itemConfig.id;
								itemConfig.toolbar = this;
								switch (itemConfig.xtype) {
									case 'htmlareabutton':
										this.add(new Button(itemConfig));
										break;
									case 'htmlareacombo':
										this.add(new Combo(itemConfig));
										break;
									case 'htmlareatoolbartext':
										this.add(new ToolbarText(itemConfig));
										break;
									default:
										this.add(itemConfig);
								}
								firstInGroup = firstInGroup && itemConfig.hidden;
								firstOnRow = firstOnRow && firstInGroup;
							}
						}
					}
				}
			}
			this.addSpacer('space-clear-left');
		},

		/**
		 * Add an item to the toolbar
		 *
		 * @param object item: the item to be added (not yet rendered)
		 * @return void
		 */
		add: function (item) {
			if (item.xtype === 'htmlareacombo') {
				var wrapDiv = document.createElement('div');
				Dom.addClass(wrapDiv, 'x-form-item');
				wrapDiv = this.el.appendChild(wrapDiv);
				item.render(wrapDiv);
				if (item.helpTitle) {
					item.getEl().dom.setAttribute('title', item.helpTitle);
				}
				wrapDiv.appendChild(item.getEl().dom);
				if (item.fieldLabel) {
					var textDiv = document.createElement('div');
					Dom.addClass(textDiv, 'x-form-item');
					Dom.addClass(textDiv, 'toolbar-text');
					var text = document.createElement('label');
					text.innerHTML = item.fieldLabel;
					Dom.addClass(text, 'x-form-item-label');
					text.setAttribute('for', item.getEl().dom.id);
					textDiv.appendChild(text);
					this.el.insertBefore(textDiv, wrapDiv);
				}
			} else {
				item.render(this.el);
				var itemDiv = this.el.appendChild(item.getEl().dom);
				Dom.addClass(item.getEl().dom, 'x-form-item');
			}
			if (item.xtype === 'htmlareatoolbartext') {
				Dom.addClass(item.getEl().dom, 'x-form-item-label');
			}
			if (item.itemId) {
				this.items[item.itemId] = item;
			}
		},

		/**
		 * Add a spacer to the toolbar
		 *
		 * @param string cls: a class to be added on the spacer
		 * @return void
		 */
		addSpacer: function (cls) {
			var spacer = document.createElement('div');
			Dom.addClass(spacer, 'space');
			if (typeof cls === 'string') {
				Dom.addClass(spacer, cls);
			}
			this.el.appendChild(spacer);
		},

		/**
		 * Add a separator to the toolbar
		 *
		 * @param string cls: a class to be added on the separator
		 * @return void
		 */
		addSeparator: function (cls) {
			var spacer = document.createElement('div');
			Dom.addClass(spacer, 'separator');
			if (typeof cls === 'string') {
				Dom.addClass(spacer, cls);
			}
			this.el.appendChild(spacer);
		},

		/**
		 * Remove a button from the toolbar
		 *
		 * @param string buttonId: the itemId of the item to remove
		 * @return void
		 */
		remove: function (buttonId) {
			var item = this.items[buttonId];
			if (item) {
				if (item.getEl()) {
					Dom.removeFromParent(item.getEl().dom);
				}
				this.items[item.itemId] = null;
			}
		},

		/**
		 * Retrieve a toolbar item by itemId
		 */
		getButton: function (buttonId) {
			return this.items[buttonId];
		},

		/**
		 * Get the current height of the toolbar
		 */
		getHeight: function () {
			return Dom.getSize(this.el).height;
		},

		/**
		 * Update the toolbar after some delay
		 */
		updateLater: function (delay) {
			if (this.updateToolbarLater) {
				window.clearTimeout(this.updateToolbarLater);
			}
			if (delay) {
				var self = this;
				this.updateToolbarLater = window.setTimeout(function () {
					self.update();
				}, delay);
			} else {
				this.update();
			}
		},

		/**
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
			/**
			 * @event HTMLAreaEventToolbarUpdate
			 * Fires when the toolbar is updated
			 */
			Event.trigger(this, 'HTMLAreaEventToolbarUpdate', [mode, selectionEmpty, ancestors, endPointsInSameBlock]);
		},

		/**
		 * Cleanup (called by framework onBeforeDestroy)
		 */
		onBeforeDestroy: function () {
			Event.off(this);
			while (node = this.el.firstChild) {
				this.el.removeChild(node);
			}
			for (var itemId in this.items) {
				if (typeof this.items[itemId].destroy === 'function') {
					try {
						this.items[itemId].destroy();
					} catch (e) {}
				}
			}
		}
	};

	return Toolbar;

});
