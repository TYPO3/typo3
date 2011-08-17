Ext.namespace('TYPO3.Form.Wizard.Elements.Predefined');

/**
 * The predefined EMAIL element
 *
 * @class TYPO3.Form.Wizard.Elements.Predefined.Email
 * @extends TYPO3.Form.Wizard.Elements.Basic.Textline
 */
TYPO3.Form.Wizard.Elements.Predefined.Email = Ext.extend(TYPO3.Form.Wizard.Elements.Basic.Textline, {
	/**
	 * Initialize the component
	 */
	initComponent: function() {
		var config = {
			configuration: {
				attributes: {
					name: 'email'
				},
				label: {
					value: TYPO3.l10n.localize('elements_label_email')
				},
				validation: {
					required: {
						breakOnError: 0,
						showMessage: 1,
						message: TYPO3.l10n.localize('tx_form_system_validate_required.message'),
						error: TYPO3.l10n.localize('tx_form_system_validate_required.error')
					},
					email: {
						breakOnError: 0,
						showMessage: 1,
						message: TYPO3.l10n.localize('tx_form_system_validate_email.message'),
						error: TYPO3.l10n.localize('tx_form_system_validate_email.error')
					}
				}
			}
		};

			// MERGE config
		Ext.merge(this, config);

			// call parent
		TYPO3.Form.Wizard.Elements.Predefined.Email.superclass.initComponent.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-elements-predefined-email', TYPO3.Form.Wizard.Elements.Predefined.Email);