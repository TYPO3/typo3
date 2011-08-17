Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Form');

/**
 * The prefix panel in the accordion of the form tab on the left side
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Form.Prefix
 * @extends Ext.Panel
 */
TYPO3.Form.Wizard.Viewport.Left.Form.Prefix = Ext.extend(Ext.FormPanel, {
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
	id: 'formwizard-left-form-prefix',

	/**
	 * @cfg {String} title
	 * The title text to be used as innerHTML (html tags are accepted) to
	 * display in the panel header (defaults to '').
	 */
	title: TYPO3.l10n.localize('form_prefix'),

	/** @cfg {String} defaultType
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
	 * @cfg {Boolean} monitorValid If true, the form monitors its valid state client-side and
	 * regularly fires the clientvalidation event passing that state.
	 * When monitoring valid state, the FormPanel enables/disables any of its configured
	 * buttons which have been configured with formBind: true depending
	 * on whether the form is valid or not. Defaults to false
	 */
	monitorValid: true,

	/**
	 * Constructor
	 *
	 * @param config
	 */
	constructor: function(config){
			// Adds the specified events to the list of events which this Observable may fire.
		this.addEvents({
			'validation': true
		});

			// Call our superclass constructor to complete construction process.
		TYPO3.Form.Wizard.Viewport.Left.Form.Prefix.superclass.constructor.call(this, config);
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
				defaultType: 'textfieldsubmit',
				items: [
					{
						fieldLabel: TYPO3.l10n.localize('prefix_prefix'),
						name: 'prefix',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					}
				]
			}]
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.Form.Prefix.superclass.initComponent.apply(this, arguments);

			// Initialize clientvalidation event
		this.on('clientvalidation', this.validation, this);

			// Fill the form with the configuration values
		this.fillForm();
	},


	/**
	 * Store a changed value from the form in the element
	 *
	 * @param {Object} field The field which has changed
	 */
	storeValue: function(field) {
		if (field.isValid()) {
			var fieldName = field.getName();

			var formConfiguration = {};
			formConfiguration[fieldName] = field.getValue();

			this.element.setConfigurationValue(formConfiguration);
		}
	},

	/**
	 * Fill the form with the configuration of the element
	 *
	 * @param record The current question
	 * @return void
	 */
	fillForm: function() {
		this.getForm().setValues(this.element.configuration);
	},

	/**
	 * Called by the clientvalidation event
	 *
	 * Adds or removes the error class if the form is valid or not
	 *
	 * @param {Object} formPanel This formpanel
	 * @param {Boolean} valid True if the client validation is true
	 */
	validation: function(formPanel, valid) {
		if (this.el) {
			if (valid && this.el.hasClass('validation-error')) {
				this.removeClass('validation-error');
				this.fireEvent('validation', 'prefix', valid);
			} else if (!valid && !this.el.hasClass('validation-error')) {
				this.addClass('validation-error');
				this.fireEvent('validation', 'prefix', valid);
			}
		}
	}
});

Ext.reg('typo3-form-wizard-viewport-left-form-prefix', TYPO3.Form.Wizard.Viewport.Left.Form.Prefix);