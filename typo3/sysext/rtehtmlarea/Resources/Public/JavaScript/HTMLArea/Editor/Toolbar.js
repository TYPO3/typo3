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
 * HTMLArea.Toolbar extends Ext.Container
 */
define('TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/Toolbar',
	['TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ux/Combo',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ux/Button',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ux/ToolbarText'],
	function (Event, Combo, Button, ToolbarText) {

	var Toolbar = Ext.extend(Ext.Container, {

		/**
		 * Constructor
		 */
		initComponent: function () {
			Toolbar.superclass.initComponent.call(this);
			// Add the toolbar items
			this.addItems();
			this.addListener({
				afterrender: {
					fn: this.initEventListeners,
					single: true
				}
			});
		},

		/**
		 * Initialize listeners
		 */
		initEventListeners: function () {
			// Monitor editor becoming ready
			var self = this;
			Event.one(this.getEditor(), 'HtmlAreaEventEditorReady', function (event) { Event.stopEvent(event); self.onEditorReady(); return false; });
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
					this.add({
						xtype: 'tbspacer',
						cls: 'x-form-clear-left'
					});
				}
				firstOnRow = true;
				// Add the groups
				for (j = 0, m = row.length; j < m; j++) {
					group = row[j];
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
					for (k = 0, p = group.length; k < p; k++) {
						item = group[k];
						if (item == 'space') {
							this.add({
								xtype: 'tbspacer',
								cls: 'space'
							});
						} else {
							// Get the item's config as registered by some plugin
							var itemConfig = editor.config.buttonsConfig[item];
							if (typeof itemConfig === 'object' && itemConfig !== null) {
								itemConfig.id = this.editorId + '-' + itemConfig.id;
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
			this.add({
				xtype: 'tbspacer',
				cls: 'x-form-clear-left'
			});
		},

		/**
		 * Retrieve a toolbar item by itemId
		 */
		getButton: function (buttonId) {
			return this.find('itemId', buttonId)[0];
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
		 * When the editor becomes ready
		 */
		onEditorReady: function () {
			var self = this;
			// Monitor editor being unloaded
			Event.one(this.framework.iframe.getIframeWindow(), 'unload', function (event) { return self.onBeforeDestroy(); });
			this.update();
		},

		/**
		 * Cleanup
		 */
		onBeforeDestroy: function () {
			Event.off(this);
			this.removeAll(true);
			return true;
		}
	});

	return Toolbar;

});
