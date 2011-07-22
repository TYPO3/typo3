Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters');

/**
 * The digit filter
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Digit
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Digit = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter, {
	/**
	 * @cfg {String} filter
	 *
	 * The name of this filter
	 */
	filter: 'digit'
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters-digit', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Digit);