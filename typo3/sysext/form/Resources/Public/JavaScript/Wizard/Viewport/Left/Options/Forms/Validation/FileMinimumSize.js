Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.FileMinimumSize');

/**
 * The minimum file size rule
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileMinimumSize
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileMinimumSize = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule, {
	/**
	 * @cfg {String} rule
	 *
	 * The name of this rule
	 */
	rule: 'fileminimumsize',

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
				message: TYPO3.l10n.localize('tx_form_system_validate_fileminimumsize.message'),
				error: TYPO3.l10n.localize('tx_form_system_validate_fileminimumsize.error'),
				minimum: 0
			}
		});
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileMinimumSize.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-validation-fileminimumsize', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileMinimumSize);