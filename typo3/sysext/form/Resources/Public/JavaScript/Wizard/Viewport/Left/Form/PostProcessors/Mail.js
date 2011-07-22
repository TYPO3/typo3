Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors');

/**
 * The mail post processor
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.Mail
 * @extends TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.PostProcessor
 */
TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.Mail = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.PostProcessor, {
	/**
	 * @cfg {String} processor
	 *
	 * The name of this processor
	 */
	processor: 'mail',

	/**
	 * Constructor
	 *
	 * Add the configuration object to this component
	 * @param config
	 */
	constructor: function(config) {
		Ext.apply(this, {
			configuration: {
				recipientEmail: '',
				senderEmail: ''
			}
		});
		TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.Mail.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-form-postprocessors-mail', TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.Mail);