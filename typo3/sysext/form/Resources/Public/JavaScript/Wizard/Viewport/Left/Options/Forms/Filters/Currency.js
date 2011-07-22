Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters');

/**
 * The currency filter
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Currency
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Currency = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter, {
	/**
	 * @cfg {String} filter
	 *
	 * The name of this filter
	 */
	filter: 'currency',

	/**
	 * Constructor
	 *
	 * Add the configuration object to this component
	 * @param config
	 */
	constructor: function(config) {
		Ext.apply(this, {
			configuration: {
				decimalPoint: '.',
				thousandSeparator: ','
			}
		});
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Currency.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters-currency', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Currency);