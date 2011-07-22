Ext.namespace('TYPO3.Form.Wizard.Helpers');

TYPO3.Form.Wizard.Helpers.History = Ext.extend(Ext.util.Observable, {
	/**
	 * @cfg {Integer} maximum
	 * Maximum steps to go back or forward in history
	 */
	maximum: 20,

	/**
	 * @cfg {Integer} marker
	 * The current step in the history
	 */
	marker: 0,

	/**
	 * @cfg {Array} history
	 * Holds the configuration for each step in history
	 */
	history: [],

	/**
	 * #cfg {String} undoButtonId
	 * The id of the undo button
	 */
	undoButtonId: 'formwizard-history-undo',

	/**
	 * #cfg {String} redoButtonId
	 * The id of the redo button
	 */
	redoButtonId: 'formwizard-history-redo',

	/**
	 * Constructor
	 *
	 * @param config
	 */
	constructor: function(config){
			// Call our superclass constructor to complete construction process.
		TYPO3.Form.Wizard.Helpers.History.superclass.constructor.call(this, config);
	},

	/**
	 * Called when a component is added to a container or there was a change in
	 * one of the form components
	 *
	 * Gets the configuration of all (nested) components, starting at
	 * viewport-right, and adds this configuration to the history
	 *
	 * @returns {void}
	 */
	setHistory: function() {
		var configuration = Ext.getCmp('formwizard-right').getConfiguration();
		this.addToHistory(configuration);
	},

	/**
	 * Add a snapshot to the history
	 *
	 * @param {Object} configuration The form configuration snapshot
	 * @return {void}
	 */
	addToHistory: function(configuration) {
		while (this.history.length > this.marker) {
			this.history.pop();
		}
		this.history.push(Ext.encode(configuration));
		while (this.history.length > this.maximum) {
			this.history.shift();
		}
		this.marker = this.history.length;
		this.buttons();
	},

	/**
	 * Get the current snapshot from the history
	 *
	 * @return {Object} The current snapshot
	 */
	refresh: function() {
		var refreshObject = Ext.decode(this.history[this.marker-1]);
		Ext.getCmp('formwizard-right').loadConfiguration(refreshObject);
	},

	/**
	 * Get the previous snapshot from the history if available
	 *
	 * Unsets the active element, because this element will not be available anymore
	 *
	 * @return {Object} The previous snapshot
	 */
	undo: function() {
		if (this.marker >= 1) {
			this.marker--;
			var undoObject = Ext.decode(this.history[this.marker-1]);
			this.buttons();
			Ext.getCmp('formwizard-right').loadConfiguration(undoObject);
			TYPO3.Form.Wizard.Helpers.Element.unsetActive();
		}
	},

	/**
	 * Get the next snapshot from the history if available
	 *
	 * Unsets the active element, because this element will not be available anymore
	 *
	 * @return {Object} The next snapshot
	 */
	redo: function() {
		if (this.history.length > this.marker) {
			this.marker++;
			var redoObject = Ext.decode(this.history[this.marker-1]);
			this.buttons();
			Ext.getCmp('formwizard-right').loadConfiguration(redoObject);
			TYPO3.Form.Wizard.Helpers.Element.unsetActive();
		}
	},

	/**
	 * Turn the undo/redo buttons on or off
	 * according to marker in the history
	 *
	 * @return {void}
	 */
	buttons: function() {
		var undoButton = Ext.get(this.undoButtonId);
		var redoButton = Ext.get(this.redoButtonId);
		if (this.marker > 1) {
			undoButton.show();
		} else {
			undoButton.hide();
		}
		if (this.history.length > this.marker) {
			redoButton.show();
		} else {
			redoButton.hide();
		}
	}
});

TYPO3.Form.Wizard.Helpers.History = new TYPO3.Form.Wizard.Helpers.History();