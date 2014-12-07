/**
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
 * Ext.ux.form.HTMLAreaCombo extends Ext.form.ComboBox
 */
define('TYPO3/CMS/Rtehtmlarea/HTMLArea/Extjs/ux/Combo',
	['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event'],
	function (UserAgent, Event) {

	var Combo = Ext.extend(Ext.form.ComboBox, {

		/**
		 * Constructor
		 */
		initComponent: function () {
			Ext.ux.form.HTMLAreaCombo.superclass.initComponent.call(this);
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
			var self = this;
			Event.on(this, 'HTMLAreaEventHotkey', function (event, key, event) { return self.onHotKey(key); });
			this.addListener({
				select: {
					fn: this.onComboSelect
				},
				specialkey: {
					fn: this.onSpecialKey
				},
				beforedestroy: {
					fn: this.onBeforeDestroy,
					single: true
				}
			});
			// Monitor toolbar updates in order to refresh the state of the combo
			Event.on(this.getToolbar(), 'HTMLAreaEventToolbarUpdate', function (event, mode, selectionEmpty, ancestors, endPointsInSameBlock) { Event.stopEvent(event); self.onUpdateToolbar(mode, selectionEmpty, ancestors, endPointsInSameBlock); return false; });
			// Monitor framework becoming ready
			Event.one(this.getToolbar().ownerCt, 'HTMLAreaEventFrameworkReady', function (event) { Event.stopEvent(event); self.onFrameworkReady(); return false; });
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
		 * Handler invoked when an item is selected in the dropdown list
		 */
		onComboSelect: function (combo, record, index) {
			if (!combo.disabled) {
				var editor = this.getEditor();
					// In IE, reclaim lost focus on the editor iframe and restore the bookmarked selection
				if (UserAgent.isIE) {
					if (typeof this.savedRange === 'object' && this.savedRange !== null) {
						editor.getSelection().selectRange(this.savedRange);
						this.savedRange = null;
					}
				}
					// Invoke the plugin onChange handler
				this.plugins[this.action](editor, combo, record, index);
					// In IE, bookmark the updated selection as the editor will be loosing focus
				if (UserAgent.isIE) {
					this.savedRange = editor.getSelection().createRange();
					this.triggered = true;
				}
				if (UserAgent.isOpera) {
					editor.focus();
				}
				this.getToolbar().update();
			}
			return false;
		},

		/**
		 * Handler invoked when the trigger element is clicked
		 * In IE, need to reclaim lost focus for the editor in order to restore the selection
		 */
		onTriggerClick: function () {
			Ext.ux.form.HTMLAreaCombo.superclass.onTriggerClick.call(this);
			// In IE, avoid focus being stolen and selection being lost
			if (UserAgent.isIE) {
				this.triggered = true;
				this.getEditor().focus();
			}
		},

		/**
		 * Handler invoked when the list of options is clicked in
		 */
		onViewClick: function (doFocus) {
			// Avoid stealing focus from the editor
			Ext.ux.form.HTMLAreaCombo.superclass.onViewClick.call(this, false);
		},

		/**
		 * Handler invoked in IE when the mouse moves out of the editor iframe
		 */
		saveSelection: function (event) {
			var editor = this.getEditor();
			if (editor.document.hasFocus()) {
				this.savedRange = editor.getSelection().createRange();
			}
		},

		/**
		 * Handler invoked in IE when the editor gets the focus back
		 */
		restoreSelection: function (event) {
			if (typeof this.savedRange === 'object' && this.savedRange !== null && this.triggered) {
				this.getEditor().getSelection().selectRange(this.savedRange);
				this.triggered = false;
			}
		},

		/**
		 * Handler invoked when the enter key is pressed while the combo has focus
		 */
		onSpecialKey: function (combo, event) {
			if (event.getKey() == event.ENTER) {
				event.stopEvent();
			}
			return false;
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
		 * The iframe must have been rendered
		 */
		onFrameworkReady: function () {
			var iframe = this.getEditor().iframe;
			// Close the combo on a click in the iframe
			// Note: ExtJS is monitoring events only on the parent window
			var self = this;
			Event.on(iframe.document.documentElement, 'click', function (event) { self.collapse(); return true; });
			// Special handling for combo stealing focus in IE
			if (UserAgent.isIE) {
				// Take a bookmark in case the editor looses focus by activation of this combo
				Event.on(iframe.getEl().dom, 'mouseleave', function (event) { self.saveSelection(event); return true; });
				// Restore the selection if combo was triggered
				Event.on(iframe.getEl().dom, 'focus', function (event) { self.restoreSelection(event); return true; });
			}
		},

		/**
		 * Cleanup
		 */
		onBeforeDestroy: function () {
			this.savedRange = null;
			this.getStore().removeAll();
			this.getStore().destroy();
		}
	});

	Ext.reg('htmlareacombo', Combo);
	Ext.ux.form.HTMLAreaCombo = Combo;
	return Combo;

});
