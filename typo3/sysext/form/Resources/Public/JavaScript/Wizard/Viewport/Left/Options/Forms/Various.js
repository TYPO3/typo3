Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms');

/**
 * The various properties of the element
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Various
 * @extends Ext.FormPanel
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Various = Ext.extend(Ext.FormPanel, {
	/**
	 * @cfg {String} title
	 * The title text to be used as innerHTML (html tags are accepted) to
	 * display in the panel header (defaults to '').
	 */
	title: TYPO3.l10n.localize('options_various'),

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
	 * Constructor
	 *
	 * Add the form elements to the accordion
	 */
	initComponent: function() {
		var various = this.element.configuration.various;
		var formItems = new Array();

			// Adds the specified events to the list of events which this Observable may fire.
		this.addEvents({
			'validation': true
		});

		Ext.iterate(various, function(key, value) {
			switch(key) {
				case 'name':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('various_properties_name'),
						name: 'name',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'content':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('various_properties_content'),
						xtype: 'textarea',
						name: 'content',
						allowBlank: false,
						listeners: {
							'triggerclick': {
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
				case 'text':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('various_properties_text'),
						xtype: 'textarea',
						name: 'text',
						allowBlank: false,
						listeners: {
							'triggerclick': {
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
				case 'headingSize':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('various_properties_headingsize'),
						name: 'headingSize',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'headingSize',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: 'H1', value: 'h1'},
								{label: 'H2', value: 'h2'},
								{label: 'H3', value: 'h3'},
								{label: 'H4', value: 'h4'},
								{label: 'H5', value: 'h5'}
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
				case 'prefix':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('various_properties_prefix'),
						name: 'prefix',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'prefix',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('yes'), value: true},
								{label: TYPO3.l10n.localize('no'), value: false}
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
				case 'suffix':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('various_properties_suffix'),
						name: 'suffix',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'suffix',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('yes'), value: true},
								{label: TYPO3.l10n.localize('no'), value: false}
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
				case 'middleName':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('various_properties_middlename'),
						name: 'middleName',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'middleName',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('yes'), value: true},
								{label: TYPO3.l10n.localize('no'), value: false}
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
			}
		}, this);

		var config = {
			items: [{
				xtype: 'fieldset',
				title: '',
				autoHeight: true,
				border: false,
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
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Various.superclass.initComponent.apply(this, arguments);

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

			var formConfiguration = {various: {}};
			formConfiguration.various[fieldName] = field.getValue();

			this.element.setConfigurationValue(formConfiguration);
		}
	},

	/**
	 * Fill the form with the configuration of the element
	 *
	 * @return void
	 */
	fillForm: function() {
		this.getForm().setValues(this.element.configuration.various);
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
				this.fireEvent('validation', 'various', valid);
			} else if (!valid && !this.el.hasClass('validation-error')) {
				this.addClass('validation-error');
				this.fireEvent('validation', 'various', valid);
			}
		}
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-various', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Various);