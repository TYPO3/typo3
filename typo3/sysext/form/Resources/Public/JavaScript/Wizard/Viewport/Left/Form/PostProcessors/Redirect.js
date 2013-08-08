Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors');

/**
 * The redirect post processor
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.Redirect
 * @extends TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.PostProcessor
 */
TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.Redirect = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.PostProcessor, {
	/**
	 * @cfg {String} processor
	 *
	 * The name of this processor
	 */
	processor: 'redirect',

	/**
	 * Constructor
	 *
	 * Add the configuration object to this component
	 * @param config
	 */
	constructor: function(config) {
		Ext.apply(this, {
			configuration: {
				destination: '',
			}
		});
		TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.Redirect.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-form-postprocessors-redirect', TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.Redirect);
