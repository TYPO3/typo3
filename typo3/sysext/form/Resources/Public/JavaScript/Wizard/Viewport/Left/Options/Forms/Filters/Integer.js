Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters');

/**
 * The integer filter
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Integer
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Integer = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter, {
	/**
	 * @cfg {String} filter
	 *
	 * The name of this filter
	 */
	filter: 'integer'
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters-integer', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Integer);