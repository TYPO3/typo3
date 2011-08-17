Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation');

/**
 * The greater than validation rule
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.GreaterThan
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.GreaterThan = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule, {
	/**
	 * @cfg {String} rule
	 *
	 * The name of this rule
	 */
	rule: 'greaterthan',

	/**
	 * Constructor
	 *
	 * Add the configuration object to this component
	 * @param config
	 */
	constructor: function(config) {
		Ext.apply(this, {
			configuration: {
				breakOnError: 0,
				showMessage: 1,
				message: TYPO3.l10n.localize('tx_form_system_validate_greaterthan.message'),
				error: TYPO3.l10n.localize('tx_form_system_validate_greaterthan.error'),
				minimum: 0
			}
		});
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.GreaterThan.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-validation-greaterthan', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.GreaterThan);