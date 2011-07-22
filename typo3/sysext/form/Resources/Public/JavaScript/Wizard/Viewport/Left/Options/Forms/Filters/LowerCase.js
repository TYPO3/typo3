Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters');

/**
 * The lower case filter
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.LowerCase
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.LowerCase = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter, {
	/**
	 * @cfg {String} filter
	 *
	 * The name of this filter
	 */
	filter: 'lowercase'
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters-lowercase', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.LowerCase);