Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation');

/**
 * The equals validation rule
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Email
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Equals = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule, {
	/**
	 * @cfg {String} rule
	 *
	 * The name of this rule
	 */
	rule: 'equals',

	/**
	 * Constructor
	 *
	 * Add the configuration object to this component
	 * @param config
	 */
	constructor: function(config) {
		Ext.apply(this, {
			configuration: {
				showMessage: 1,
				message: TYPO3.l10n.localize('tx_form_system_validate_equals.message'),
				error: TYPO3.l10n.localize('tx_form_system_validate_equals.error'),
				field: ''
			}
		});
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Equals.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-validation-equals', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Equals);