Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters');

/**
 * The trim filter
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Trim
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Trim = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter, {
	/**
	 * @cfg {String} filter
	 *
	 * The name of this filter
	 */
	filter: 'trim',

	/**
	 * Constructor
	 *
	 * Add the configuration object to this component
	 * @param config
	 */
	constructor: function(config) {
		Ext.apply(this, {
			configuration: {
				characterList: ''
			}
		});
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Trim.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters-trim', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Trim);