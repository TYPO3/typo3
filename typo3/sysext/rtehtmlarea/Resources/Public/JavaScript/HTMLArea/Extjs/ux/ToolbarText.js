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
 * Ext.ux.Toolbar.HTMLAreaToolbarText extends Ext.Toolbar.TextItem
 */
define('TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ux/ToolbarText',
	['TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event'],
	function (Event) {

	var ToolbarText = Ext.extend(Ext.Toolbar.TextItem, {

		/**
		 * Constructor
		 */
		initComponent: function () {
			ToolbarText.superclass.initComponent.call(this);
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
			// Monitor toolbar updates in order to refresh the state of the button
			var self = this;
			Event.on(this.getToolbar(), 'HTMLAreaEventToolbarUpdate', function (event, mode, selectionEmpty, ancestors, endPointsInSameBlock) { Event.stopEvent(event); self.onUpdateToolbar(mode, selectionEmpty, ancestors, endPointsInSameBlock); return false; });
		},

		/**
		 * Get a reference to the editor
		 */
		getEditor: function() {
			return RTEarea[this.ownerCt.editorId].editor;
		},

		/**
		 * Get a reference to the toolbar
		 */
		getToolbar: function() {
			return this.ownerCt;
		},

		/**
		 * Handler invoked when the toolbar is updated
		 */
		onUpdateToolbar: function (mode, selectionEmpty, ancestors, endPointsInSameBlock) {
			this.setDisabled(mode === 'textmode' && !this.textMode);
			if (!this.disabled) {
				this.plugins['onUpdateToolbar'](this, mode, selectionEmpty, ancestors, endPointsInSameBlock);
			}
		}
	});

	return ToolbarText;

});
