Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Form');

/**
 * The behaviour panel in the accordion of the form tab on the left side
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Form.Behaviour
 * @extends Ext.Panel
 */
TYPO3.Form.Wizard.Viewport.Left.Form.Behaviour = Ext.extend(Ext.FormPanel, {
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
	id: 'formwizard-left-form-behaviour',

	/**
	 * @cfg {String} title
	 * The title text to be used as innerHTML (html tags are accepted) to
	 * display in the panel header (defaults to '').
	 */
	title: TYPO3.l10n.localize('form_behaviour'),

	/**
	 * @cfg {String} defaultType
	 *
	 * The default xtype of child Components to create in this Container when
	 * a child item is specified as a raw configuration object,
	 * rather than as an instantiated Component.
	 *
	 * Defaults to 'panel', except Ext.menu.Menu which defaults to 'menuitem',
	 * and Ext.Toolbar and Ext.ButtonGroup which default to 'button'.
	 */
	defaultType: 'textfield',

	/**
	 * @cfg {Object} element
	 * The form component
	 */
	element: null,

	/**
	 * Constructor
	 *
	 * @param config
	 */
	constructor: function(config){
		// Call our superclass constructor to complete construction process.
		TYPO3.Form.Wizard.Viewport.Left.Form.Behaviour.superclass.constructor.call(this, config);
	},

	/**
	 * Constructor
	 *
	 * Add the form elements to the tab
	 */
	initComponent: function() {
		var config = {
			items: [{
				xtype: 'fieldset',
				title: '',
				ref: 'fieldset',
				autoHeight: true,
				border: false,
				defaults: {
					width: 150,
					msgTarget: 'side'
				},
				defaultType: 'checkbox',
				items: [
					{
						fieldLabel: TYPO3.l10n.localize('behaviour_confirmation_page'),
						name: 'confirmation',
						listeners: {
							'check': {
								scope: this,
								fn: this.storeValue
							}
						}
					}
				]
			}]
		};

		// Apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

		// Call parent
		TYPO3.Form.Wizard.Viewport.Left.Form.Behaviour.superclass.initComponent.apply(this, arguments);

		// Fill the form with the configuration values
		this.fillForm();
	},


	/**
	 * Store a changed value from the form in the element
	 *
	 * @param {Object} field The field which has changed
	 */
	storeValue: function(field) {
		var fieldName = field.getName();

		var formConfiguration = {};
		formConfiguration[fieldName] = field.getValue();

		this.element.setConfigurationValue(formConfiguration);
	},

	/**
	 * Fill the form with the configuration of the element
	 *
	 * @return void
	 */
	fillForm: function() {
		this.getForm().setValues(this.element.configuration);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-form-behaviour', TYPO3.Form.Wizard.Viewport.Left.Form.Behaviour);