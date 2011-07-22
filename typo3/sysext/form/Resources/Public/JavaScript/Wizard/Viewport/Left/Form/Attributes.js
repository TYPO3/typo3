Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Form');

/**
 * The attributes panel in the accordion of the form tab on the left side
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Form.Attributes
 * @extends TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Attributes
 */
TYPO3.Form.Wizard.Viewport.Left.Form.Attributes = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Attributes, {
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
	id: 'formwizard-left-form-attributes'
});

Ext.reg('typo3-form-wizard-viewport-left-form-attributes', TYPO3.Form.Wizard.Viewport.Left.Form.Attributes);