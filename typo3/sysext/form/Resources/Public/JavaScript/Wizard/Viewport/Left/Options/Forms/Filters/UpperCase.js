Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters');

/**
 * The upper case filter
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.UpperCase
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.UpperCase = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter, {
	/**
	 * @cfg {String} filter
	 *
	 * The name of this filter
	 */
	filter: 'uppercase'
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters-uppercase', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.UpperCase);