Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters');

/**
 * The remove XSS filter
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.RemoveXSS
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.RemoveXSS = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter, {
	/**
	 * @cfg {String} filter
	 *
	 * The name of this filter
	 */
	filter: 'removexss'
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters-removexss', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.RemoveXSS);