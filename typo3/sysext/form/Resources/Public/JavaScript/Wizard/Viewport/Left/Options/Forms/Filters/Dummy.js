Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters');

/**
 * The dummy item when no filter is defined for an element
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Dummy
 * @extends Ext.Panel
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Dummy = Ext.extend(Ext.Panel, {
	/**
	 * @cfg {Boolean} border
	 * True to display the borders of the panel's body element, false to hide
	 * them (defaults to true). By default, the border is a 2px wide inset
	 * border, but this can be further altered by setting bodyBorder to false.
	 */
	border: false,

	/**
	 * @cfg {Number/String} padding
	 * A shortcut for setting a padding style on the body element. The value can
	 * either be a number to be applied to all sides, or a normal css string
	 * describing padding.
	 */
	padding: 0,

	/**
	 * @cfg {String} cls
	 * An optional extra CSS class that will be added to this component's
	 * Element (defaults to ''). This can be useful for adding customized styles
	 * to the component or any of its children using standard CSS rules.
	 */
	cls: 'formwizard-left-dummy typo3-message message-information',

	/**
	 * @cfg {Mixed} data
	 * The initial set of data to apply to the tpl to update the content area of
	 * the Component.
	 */
	data: [{
		title: TYPO3.l10n.localize('filters_dummy_title'),
		description: TYPO3.l10n.localize('filters_dummy_description')
	}],

	/**
	 * @cfg {Mixed} tpl
	 * An Ext.Template, Ext.XTemplate or an array of strings to form an
	 * Ext.XTemplate. Used in conjunction with the data and tplWriteMode
	 * configurations.
	 */
	tpl: new Ext.XTemplate(
		'<tpl for=".">',
			'<p><strong>{title}</strong></p>',
			'<p>{description}</p>',
		'</tpl>'
	)
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters-dummy', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Dummy);