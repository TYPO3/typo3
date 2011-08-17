Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options');

/**
 * The options panel for a dummy item
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Dummy
 * @extends Ext.Panel
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Dummy = Ext.extend(Ext.Panel, {
	/**
	 * @cfg {Boolean} border
	 * True to display the borders of the panel's body element, false to hide
	 * them (defaults to true). By default, the border is a 2px wide inset
	 * border, but this can be further altered by setting bodyBorder to false.
	 */
	border: false,

	/**
	 * @cfg {String} id
	 * The unique id of this component (defaults to an auto-assigned id).
	 * You should assign an id if you need to be able to access the component
	 * later and you do not have an object reference available
	 * (e.g., using Ext.getCmp).
	 *
	 * Note that this id will also be used as the element id for the containing
	 * HTML element that is rendered to the page for this component.
	 * This allows you to write id-based CSS rules to style the specific
	 * instance of this component uniquely, and also to select sub-elements
	 * using this component's id as the parent.
	 */
	id: 'formwizard-left-options-dummy',

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
		title: TYPO3.l10n.localize('options_dummy_title'),
		description: TYPO3.l10n.localize('options_dummy_description')
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

Ext.reg('typo3-form-wizard-viewport-left-options-dummy', TYPO3.Form.Wizard.Viewport.Left.Options.Dummy);