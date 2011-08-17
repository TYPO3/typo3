Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.FileMaximumSize');

/**
 * The maximum file size rule
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileMaximumSize
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileMaximumSize = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule, {
	/**
	 * @cfg {String} rule
	 *
	 * The name of this rule
	 */
	rule: 'filemaximumsize',

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
				message: TYPO3.l10n.localize('tx_form_system_validate_filemaximumsize.message'),
				error: TYPO3.l10n.localize('tx_form_system_validate_filemaximumsize.error'),
				maximum: 0
			}
		});
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileMaximumSize.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-validation-filemaximumsize', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileMaximumSize);