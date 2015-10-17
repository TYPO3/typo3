Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation');

/**
 * The alphabetic validation rule
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Alphabetic
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Alphabetic = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule, {
	/**
	 * @cfg {String} rule
	 *
	 * The name of this rule
	 */
	rule: 'alphabetic',

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
				message: TYPO3.l10n.localize('tx_form_system_validate_alphabetic.message'),
				error: TYPO3.l10n.localize('tx_form_system_validate_alphabetic.error'),
				allowWhiteSpace: 0
			}
		});
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Alphabetic.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-validation-alphabetic', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Alphabetic);