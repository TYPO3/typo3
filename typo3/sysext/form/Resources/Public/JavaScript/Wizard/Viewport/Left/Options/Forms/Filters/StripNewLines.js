Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters');

/**
 * The strip new lines filter
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.StripNewLines
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.StripNewLines = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter, {
	/**
	 * @cfg {String} filter
	 *
	 * The name of this filter
	 */
	filter: 'stripnewlines'
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters-stripnewlines', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.StripNewLines);