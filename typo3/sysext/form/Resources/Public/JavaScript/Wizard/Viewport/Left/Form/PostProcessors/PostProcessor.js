Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors');

/**
 * The post processor abstract
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.PostProcessor
 * @extends Ext.FormPanel
 */
TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.PostProcessor = Ext.extend(Ext.FormPanel, {
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
	 * @cfg {String} processor
	 *
	 * The name of this processor
	 */
	processor: '',

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
				case 'recipientEmail':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('postprocessor_properties_recipientemail'),
						name: 'recipientEmail',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'senderEmail':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('postprocessor_properties_senderemail'),
						name: 'senderEmail',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'subject':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('postprocessor_properties_subject'),
						name: 'subject',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'destination':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('postprocessor_properties_destination'),
						name: 'destination',
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
			handler: this.removePostProcessor,
			scope: this
		});

		var config = {
			items: [
				{
					xtype: 'fieldset',
					title: TYPO3.l10n.localize('postprocessor_' + this.processor),
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
		TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.PostProcessor.superclass.initComponent.apply(this, arguments);

			// Initialize clientvalidation event
		this.on('clientvalidation', this.validation, this);

			// Strange, but we need to call doLayout() after render
		this.on('afterrender', this.newOrExistingPostProcessor, this);
	},

	/**
	 * Decide whether this is a new or an existing one
	 *
	 * If new, the default configuration has to be added to the processors
	 * of the form, otherwise we can fill the form with the existing configuration
	 */
	newOrExistingPostProcessor: function() {
		this.doLayout();
			// Existing processor
		if (this.element.configuration.postProcessor[this.processor]) {
			this.fillForm();
			// New processor
		} else {
			this.addProcessorToElement();
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
		this.getForm().setValues(this.element.configuration.postProcessor[this.processor]);
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
	 * Add this processor to the element
	 */
	addProcessorToElement: function() {
		var formConfiguration = {postProcessor: {}};
		formConfiguration.postProcessor[this.processor] = this.configuration;

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

			var formConfiguration = {postProcessor: {}};
			formConfiguration.postProcessor[this.processor] = {};
			formConfiguration.postProcessor[this.processor][fieldName] = field.getValue();

			this.element.setConfigurationValue(formConfiguration);
		}
	},

	/**
	 * Remove the processor
	 *
	 * Called when the remove button of this processor has been clicked
	 */
	removePostProcessor: function() {
		this.ownerCt.removePostProcessor(this);
		this.element.removePostProcessor(this.processor);
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
		var processorFields = this.configuration;

		var allowedFields = [];
		try {
			allowedFields = TYPO3.Form.Wizard.Settings.defaults.tabs.form.accordions.postProcessor.postProcessors[this.processor].showProperties.split(/[, ]+/);
		} catch (error) {
			// The object has not been found or constructed wrong
			allowedFields = [
				'recipientEmail',
				'senderEmail'
			];
		}

		Ext.iterate(allowedFields, function(item, index, allItems) {
			fields.push(item);
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
				this.fireEvent('validation', this.processor, valid);
			} else if (!valid && !this.el.hasClass('validation-error')) {
				this.addClass('validation-error');
				this.fireEvent('validation', this.processor, valid);
			}
		}
	}
});

Ext.reg('typo3-form-wizard-viewport-left-form-postprocessors-postprocessor', TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessors.PostProcessor);