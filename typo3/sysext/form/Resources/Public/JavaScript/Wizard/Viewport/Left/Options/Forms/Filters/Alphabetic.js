Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters');

/**
 * The alphabetic filter
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Alphabetic
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Alphabetic = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter, {
	/**
	 * @cfg {String} filter
	 *
	 * The name of this filter
	 */
	filter: 'alphabetic',

	/**
	 * Constructor
	 *
	 * Add the configuration object to this component
	 * @param config
	 */
	constructor: function(config) {
		Ext.apply(this, {
			configuration: {
				allowWhiteSpace: 0
			}
		});
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Alphabetic.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters-alphabetic', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Alphabetic);