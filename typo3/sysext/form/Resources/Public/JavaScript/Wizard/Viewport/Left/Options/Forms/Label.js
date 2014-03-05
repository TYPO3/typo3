Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms');

/**
 * The label properties and the layout of the element
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Label
 * @extends Ext.FormPanel
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Label = Ext.extend(Ext.FormPanel, {
	/**
	 * @cfg {String} title
	 * The title text to be used as innerHTML (html tags are accepted) to
	 * display in the panel header (defaults to '').
	 */
	title: TYPO3.l10n.localize('options_label'),

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
	 * @cfg {Boolean} monitorValid If true, the form monitors its valid state client-side and
	 * regularly fires the clientvalidation event passing that state.
	 * When monitoring valid state, the FormPanel enables/disables any of its configured
	 * buttons which have been configured with formBind: true depending
	 * on whether the form is valid or not. Defaults to false
	 */
	monitorValid: true,

	/**
	 * @cfg {String} cls
	 * An optional extra CSS class that will be added to this component's
	 * Element (defaults to ''). This can be useful for adding customized styles
	 * to the component or any of its children using standard CSS rules.
	 */
	cls: 'x-panel-accordion',

	/**
	 * Constructor
	 *
	 * Add the form elements to the accordion
	 */
	initComponent: function() {
		var fields = this.getFieldsBySettings();
		var formItems = new Array();

			// Adds the specified events to the list of events which this Observable may fire.
		this.addEvents({
			'validation': true
		});

		Ext.iterate(fields, function(item, index, allItems) {
			switch(item) {
				case 'label':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('label_label'),
						name: 'label',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'layout':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('label_layout'),
						name: 'layout',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'layout',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('label_layout_front'), value: 'front'},
								{label: TYPO3.l10n.localize('label_layout_back'), value: 'back'}
							]
						}),
						listeners: {
							'select': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				default:
			}
		}, this);

		var config = {
			items: [{
				xtype: 'fieldset',
				title: '',
				border: false,
				autoHeight: true,
				defaults: {
					width: 150,
					msgTarget: 'side'
				},
				defaultType: 'textfieldsubmit',
				items: formItems
			}]
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Label.superclass.initComponent.apply(this, arguments);

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

			if (fieldName == 'label') {
				var formConfiguration = {
					label: {
						value: field.getValue()
					}
				};
			} else {
				var formConfiguration = {};
				formConfiguration[fieldName] = field.getValue();
			}
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
		this.getForm().setValues({
			label: this.element.configuration.label.value,
			layout: this.element.configuration.layout
		});
	},

	/**
	 * Get the fields for the element
	 *
	 * Based on the TSconfig general allowed fields
	 * and the TSconfig allowed fields for this type of element
	 *
	 * @returns object
	 */
	getFieldsBySettings: function() {
		var fields = [];
		var elementType = this.element.xtype.split('-').pop();

		var allowedGeneralFields = [];
		try {
			allowedGeneralFields = TYPO3.Form.Wizard.Settings.defaults.tabs.options.accordions.label.showProperties.split(/[, ]+/);
		} catch (error) {
			// The object has not been found or constructed wrong
			allowedGeneralFields = [
				'label',
				'layout'
			];
		}

		var allowedElementFields = [];
		try {
			allowedElementFields = TYPO3.Form.Wizard.Settings.elements[elementType].accordions.label.showProperties.split(/[, ]+/);
		} catch (error) {
			// The object has not been found or constructed wrong
			allowedElementFields = allowedGeneralFields;
		}

		Ext.iterate(allowedElementFields, function(item, index, allItems) {
			if (allowedGeneralFields.indexOf(item) > -1) {
				fields.push(item);
			}
		}, this);

		return fields;
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
				this.fireEvent('validation', 'label', valid);
			} else if (!valid && !this.el.hasClass('validation-error')) {
				this.addClass('validation-error');
				this.fireEvent('validation', 'label', valid);
			}
		}
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-label', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Label);