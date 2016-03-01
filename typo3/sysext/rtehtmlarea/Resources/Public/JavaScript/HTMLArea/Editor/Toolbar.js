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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/Toolbar
 * The editor toolbar
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Toolbar/Button',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Toolbar/ToolbarText',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Toolbar/Select'],
	function (Util, Dom, Event, Button, ToolbarText, Select) {

	/**
	 * Editor toolbar constructor
	 *
	 * @param {Object} config
	 * @constructor
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/Toolbar
	 */
	var Toolbar = function (config) {
		Util.apply(this, config);

		/**
		 * The toolbar items
		 */
		this.items = {};
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
			Dom.addClass(this.el, 'btn-toolbar');
			this.el.setAttribute('role', 'toolbar');
			if (this.id) {
				this.el.setAttribute('id', this.id);
			}
			if (this.cls) {
				Dom.addClass(this.el, this.cls);
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
					this.addSpacer(this.el, 'space-clear-left');
				}
				firstOnRow = true;
				// Add the groups
				for (j = 0, m = row.length; j < m; j++) {
					group = row[j];
					var groupContainer = this.addGroup();
					firstInGroup = true;
					// Add each item
					for (k = 0, p = group.length; k < p; k++) {
						item = group[k];
						if (item == 'space') {
							this.addSpacer(groupContainer);
						} else {
							// Get the item's config as registered by some plugin
							var itemConfig = editor.config.buttonsConfig[item];
							if (typeof itemConfig === 'object' && itemConfig !== null) {
								itemConfig.id = this.editorId + '-' + itemConfig.id;
								itemConfig.toolbar = this;
								switch (itemConfig.xtype) {
									case 'htmlareabutton':
										this.add(new Button(itemConfig), groupContainer);
										break;
									case 'htmlareaselect':
										this.add(new Select(itemConfig), groupContainer);
										break;
									case 'htmlareatoolbartext':
										this.add(new ToolbarText(itemConfig), groupContainer);
										break;
									default:
										this.add(itemConfig, groupContainer);
								}
								firstInGroup = firstInGroup && itemConfig.hidden;
								firstOnRow = firstOnRow && firstInGroup;
							}
						}
					}
				}
			}
		},

		/**
		 * Add an item to the toolbar
		 *
		 * @param object item: the item to be added (not yet rendered)
		 * @param object container: the element into which to insert the item
		 * @return void
		 */
		add: function (item, container) {
			if (item.itemId) {
				this.items[item.itemId] = item;
			}
			item.render(container);
		},

		/**
		 * Add a group to the toolbar
		 *
		 * @param string cls: a class to be added on the group other than 'btn-group' (default)
		 * @return void
		 */
		addGroup: function (cls) {
			var group = document.createElement('div');
			if (typeof cls === 'string') {
				Dom.addClass(group, cls);
			} else {
				Dom.addClass(group, 'btn-group');
			}
			group.setAttribute('role', 'group');
			group = this.el.appendChild(group);
			return group;
		},

		/**
		 * Add a spacer to the toolbar
		 *
		 * @param object container: the element into which to insert the item
		 * @param string cls: a class to be added on the spacer rather than 'space' (default)
		 * @return void
		 */
		addSpacer: function (container, cls) {
			var spacer = document.createElement('div');
			if (typeof cls === 'string') {
				Dom.addClass(spacer, cls);
			} else {
				Dom.addClass(spacer, 'space');
			}
			container.appendChild(spacer);
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
			this.framework.getStatusBar().onUpdateToolbar(mode, selectionEmpty, ancestors, endPointsInSameBlock);
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
			for (var itemId in this.items) {
				if (typeof this.items[itemId].destroy === 'function') {
					try {
						this.items[itemId].destroy();
					} catch (e) {}
				}
				if (typeof this.items[itemId].onBeforeDestroy === 'function' && this.items[itemId].xtype !== 'htmlareacombo') {
					this.items[itemId].onBeforeDestroy();
				}
			}
			var node;
			while (node = this.el.firstChild) {
				this.el.removeChild(node);
			}
			this.el = null;
		}
	};

	return Toolbar;

});
