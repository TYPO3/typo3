Ext.namespace('TYPO3.Form.Wizard.Helpers');

TYPO3.Form.Wizard.Helpers.Element = Ext.extend(Ext.util.Observable, {
	/**
	 * @cfg {Object} active
	 * The current active form element
	 */
	active: null,

	/**
	 * Constructor
	 *
	 * @param config
	 */
	constructor: function(config){
			// Adds the specified events to the list of events which this Observable may fire.
		this.addEvents({
			'setactive': true
		});

			// Call our superclass constructor to complete construction process.
		TYPO3.Form.Wizard.Helpers.Element.superclass.constructor.call(this, config);
	},

	/**
	 * Fires the setactive event when a component is set as active
	 *
	 * @param component
	 */
	setActive: function(component) {
		var optionsTabIsValid = Ext.getCmp('formwizard-left-options').tabIsValid();

		if (optionsTabIsValid) {
			if (component == this.active) {
				this.active = null;
			} else {
				this.active = component;
			}
			this.fireEvent('setactive', this.active);
		} else {
			Ext.MessageBox.show({
				title: TYPO3.l10n.localize('options_error'),
				msg: TYPO3.l10n.localize('options_error_message'),
				icon: Ext.MessageBox.ERROR,
				buttons: Ext.MessageBox.OK
			});
		}
	},

	/**
	 * Fires the setactive event when a component is unset.
	 *
	 * This means when the element is destroyed or when the form is reloaded
	 * using undo or redo
	 *
	 * @param component
	 */
	unsetActive: function(component) {
		if (
			this.active && (
				(component && component.getId() == this.active.getId()) ||
				!component
			)
		){
			this.active = null;
			this.fireEvent('setactive');
		}
	}
});

TYPO3.Form.Wizard.Helpers.Element = new TYPO3.Form.Wizard.Helpers.Element();