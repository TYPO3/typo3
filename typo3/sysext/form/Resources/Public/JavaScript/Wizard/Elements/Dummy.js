Ext.namespace('TYPO3.Form.Wizard.Elements');

/**
 * The dummy element
 *
 * This type will be shown when there is no element in a container which will be
 * form or fieldset and will be removed when there is an element added.
 *
 * @class TYPO3.Form.Wizard.Elements.Dummy
 * @extends TYPO3.Form.Wizard.Elements
 */
TYPO3.Form.Wizard.Elements.Dummy = Ext.extend(TYPO3.Form.Wizard.Elements, {
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
	id: 'dummy',

	/**
	 * @cfg {String} cls
	 * An optional extra CSS class that will be added to this component's
	 * Element (defaults to ''). This can be useful for adding customized styles
	 * to the component or any of its children using standard CSS rules.
	 */
	cls: 'dummy typo3-message message-information',

	/**
	 * @cfg {Object} configuration
	 * The configuration of this element.
	 * This object contains the configuration of this component. It will be
	 * copied to the 'data' variable before rendering. 'data' is deleted after
	 * rendering the xtemplate, so we need a copy.
	 */
	configuration: {
		title: TYPO3.l10n.localize('elements_dummy_title'),
		description: TYPO3.l10n.localize('elements_dummy_description')
	},

	/**
	 * @cfg {Mixed} tpl
	 * An Ext.Template, Ext.XTemplate or an array of strings to form an
	 * Ext.XTemplate. Used in conjunction with the data and tplWriteMode
	 * configurations.
	 */
	tpl: new Ext.XTemplate(
		'<p><strong>{title}</strong></p>',
		'<p>{description}</p>'
	),

	/**
	 * @cfg {Boolean} isEditable
	 * Defines whether the element is editable. If the item is editable,
	 * a button group with remove and edit buttons will be added to this element
	 * and when the the element is clicked, an event is triggered to edit the
	 * element. Some elements, like the dummy, don't need this.
	 */
	isEditable: false
});

Ext.reg('typo3-form-wizard-elements-dummy', TYPO3.Form.Wizard.Elements.Dummy);