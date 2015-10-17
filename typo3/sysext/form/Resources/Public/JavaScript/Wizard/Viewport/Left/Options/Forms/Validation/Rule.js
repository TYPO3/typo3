Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation');

/**
 * The validation rules abstract
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule
 * @extends Ext.FormPanel
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule = Ext.extend(Ext.FormPanel, {
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
	 * @cfg {String} rule
	 *
	 * The name of this rule
	 */
	rule: '',

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
				case 'message':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('validation_properties_message'),
						name: 'message',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'error':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('validation_properties_error'),
						name: 'error',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'showMessage':
					formItems.push({
						xtype: 'checkbox',
						fieldLabel: TYPO3.l10n.localize('validation_properties_showmessage'),
						name: 'showMessage',
						inputValue: '1',
						listeners: {
							'check': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'allowWhiteSpace':
					formItems.push({
						xtype: 'checkbox',
						fieldLabel: TYPO3.l10n.localize('validation_properties_allowwhitespace'),
						name: 'allowWhiteSpace',
						inputValue: '1',
						listeners: {
							'check': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'minimum':
					formItems.push({
						xtype: 'spinnerfield',
						fieldLabel: TYPO3.l10n.localize('validation_properties_minimum'),
						name: 'minimum',
						minValue: 0,
						accelerate: true,
						listeners: {
							'spin': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'maximum':
					formItems.push({
						xtype: 'spinnerfield',
						fieldLabel: TYPO3.l10n.localize('validation_properties_maximum'),
						name: 'maximum',
						minValue: 0,
						accelerate: true,
						listeners: {
							'spin': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'inclusive':
					formItems.push({
						xtype: 'checkbox',
						fieldLabel: TYPO3.l10n.localize('validation_properties_inclusive'),
						name: 'inclusive',
						inputValue: '1',
						listeners: {
							'check': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'format':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('validation_properties_format'),
						name: 'format',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'field':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('validation_properties_field'),
						name: 'field',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'array':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('validation_properties_array'),
						name: 'array',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'expression':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('validation_properties_expression'),
						name: 'expression',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
				case 'types':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('validation_properties_types'),
						name: 'types',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
			}
		}, this);

		formItems.push({
			xtype: 'button',
			text: TYPO3.l10n.localize('button_remove'),
			handler: this.removeRule,
			scope: this
		});

		var config = {
			items: [
				{
					xtype: 'fieldset',
					title: TYPO3.l10n.localize('validation_' + this.rule),
					autoHeight: true,
					defaults: {
						width: 128,
						msgTarget: 'side'
					},
					defaultType: 'textfieldsubmit',
					items: formItems
				}
			]
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule.superclass.initComponent.apply(this, arguments);

			// Initialize clientvalidation event
		this.on('clientvalidation', this.validation, this);

			// Strange, but we need to call doLayout() after render
		this.on('afterrender', this.newOrExistingRule, this);
	},

	/**
	 * Decide whether this is a new or an existing one
	 *
	 * If new, the default configuration has to be added to the validation rules
	 * of the element, otherwise we can fill the form with the existing configuration
	 */
	newOrExistingRule: function() {
		this.doLayout();
			// Existing rule
		if (this.element.configuration.validation[this.rule]) {
			this.fillForm();
			// New rule
		} else {
			this.addRuleToElement();
		}
	},

	/**
	 * Fill the form with the configuration of the element
	 *
	 * When filling, the events of all form elements should be suspended,
	 * otherwise the values are written back to the element, for instance on a
	 * check event on a checkbox.
	 */
	fillForm: function() {
		this.suspendEventsBeforeFilling();
		this.getForm().setValues(this.element.configuration.validation[this.rule]);
		this.resumeEventsAfterFilling();
	},

	/**
	 * Suspend the events on all items within this component
	 */
	suspendEventsBeforeFilling: function() {
		this.cascade(function(item) {
			item.suspendEvents();
		});
	},

	/**
	 * Resume the events on all items within this component
	 */
	resumeEventsAfterFilling: function() {
		this.cascade(function(item) {
			item.resumeEvents();
		});
	},

	/**
	 * Add this rule to the element
	 */
	addRuleToElement: function() {
		var formConfiguration = {validation: {}};
		formConfiguration.validation[this.rule] = this.configuration;

		this.element.setConfigurationValue(formConfiguration);

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

			var formConfiguration = {validation: {}};
			formConfiguration.validation[this.rule] = {};
			formConfiguration.validation[this.rule][fieldName] = field.getValue();

			this.element.setConfigurationValue(formConfiguration);
		}
	},

	/**
	 * Remove the rule
	 *
	 * Called when the remove button of this rule has been clicked
	 */
	removeRule: function() {
		this.ownerCt.removeRule(this);
		this.element.removeValidationRule(this.rule);
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
		var ruleFields = this.configuration;
		var elementType = this.element.xtype.split('-').pop();

		var allowedGeneralFields = [];
		try {
			allowedGeneralFields = TYPO3.Form.Wizard.Settings.defaults.tabs.options.accordions.validation.rules[this.rule].showProperties.split(/[, ]+/);
		} catch (error) {
			// The object has not been found or constructed wrong
			allowedGeneralFields = [
				'message',
				'error',
				'showMessage',
				'allowWhiteSpace',
				'minimum',
				'maximum',
				'inclusive',
				'format',
				'field',
				'array',
				'strict',
				'expression'
			];
		}

		var allowedElementFields = [];
		try {
			allowedElementFields = TYPO3.Form.Wizard.Settings.elements[elementType].accordions.validation.rules[this.rule].showProperties.split(/[, ]+/);
		} catch (error) {
			// The object has not been found or constructed wrong
			allowedElementFields = allowedGeneralFields;
		}

		Ext.iterate(allowedElementFields, function(item, index, allItems) {
			if (allowedGeneralFields.indexOf(item) > -1 && Ext.isDefined(ruleFields[item])) {
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
				this.fireEvent('validation', this.rule, valid);
			} else if (!valid && !this.el.hasClass('validation-error')) {
				this.addClass('validation-error');
				this.fireEvent('validation', this.rule, valid);
			}
		}
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-validation-rule', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.Rule);