Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters');

/**
 * The title case filter
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.TitleCase
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.TitleCase = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter, {
	/**
	 * @cfg {String} filter
	 *
	 * The name of this filter
	 */
	filter: 'titlecase'
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters-titlecase', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.TitleCase);