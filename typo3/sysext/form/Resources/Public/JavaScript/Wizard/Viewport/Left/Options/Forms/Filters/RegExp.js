Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters');

/**
 * The regular expression filter
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.RegExp
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.RegExp = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter, {
	/**
	 * @cfg {String} filter
	 *
	 * The name of this filter
	 */
	filter: 'regexp',

	/**
	 * Constructor
	 *
	 * Add the configuration object to this component
	 * @param config
	 */
	constructor: function(config) {
		Ext.apply(this, {
			configuration: {
				expression: ''
			}
		});
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.RegExp.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters-regexp', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.RegExp);