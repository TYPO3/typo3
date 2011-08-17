Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.FileAllowedTypes');

/**
 * The allowed file types rule
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileAllowedTypes
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileAllowedTypes = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule, {
	/**
	 * @cfg {String} rule
	 *
	 * The name of this rule
	 */
	rule: 'fileallowedtypes',

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
				message: TYPO3.l10n.localize('tx_form_system_validate_fileallowedtypes.message'),
				error: TYPO3.l10n.localize('tx_form_system_validate_fileallowedtypes.error'),
				types: ''
			}
		});
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileAllowedTypes.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-validation-fileallowedtypes', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.FileAllowedTypes);