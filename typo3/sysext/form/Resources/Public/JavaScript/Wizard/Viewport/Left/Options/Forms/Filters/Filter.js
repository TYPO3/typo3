Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters');

/**
 * The filter abstract
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter
 * @extends Ext.FormPanel
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter = Ext.extend(Ext.FormPanel, {
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
	 * @cfg {Boolean} monitorValid If true, the form monitors its valid state client-side and
	 * regularly fires the clientvalidation event passing that state.
	 * When monitoring valid state, the FormPanel enables/disables any of its configured
	 * buttons which have been configured with formBind: true depending
	 * on whether the form is valid or not. Defaults to false
	 */
	monitorValid: true,

	/**
	 * @cfg {Object} Default filter configuration
	 */
	configuration: {},

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
				case 'allowWhiteSpace':
					formItems.push({
						xtype: 'checkbox',
						fieldLabel: TYPO3.l10n.localize('filters_properties_allowwhitespace'),
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
				case 'decimalPoint':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('filters_properties_decimalpoint'),
						name: 'decimalPoint',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'thousandSeparator':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('filters_properties_thousandseparator'),
						name: 'thousandSeparator',
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
						fieldLabel: TYPO3.l10n.localize('filters_properties_expression'),
						name: 'expression',
						allowBlank: false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'characterList':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('filters_properties_characterlist'),
						name: 'characterList',
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

		if (Ext.isEmpty(formItems)) {
			formItems.push({
				xtype: 'box',
				autoEl: {
					tag: 'div'
				},
				width: 256,
				cls: 'typo3-message message-information',
				data: [{
					title: TYPO3.l10n.localize('filters_properties_none_title'),
					description: TYPO3.l10n.localize('filters_properties_none')
				}],
				tpl: new Ext.XTemplate(
					'<tpl for=".">',
						'<p><strong>{title}</strong></p>',
						'<p>{description}</p>',
					'</tpl>'
				)

			});
		}

		formItems.push({
			xtype: 'button',
			text: TYPO3.l10n.localize('button_remove'),
			handler: this.removeFilter,
			scope: this
		});

		var config = {
			items: [
				{
					xtype: 'fieldset',
					title: this.filter,
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
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter.superclass.initComponent.apply(this, arguments);

			// Initialize clientvalidation event
		this.on('clientvalidation', this.validation, this);

			// Strange, but we need to call doLayout() after render
		this.on('afterrender', this.newOrExistingFilter, this);
	},

	/**
	 * Decide whether this is a new or an existing one
	 *
	 * If new, the default configuration has to be added to the filters
	 * of the element, otherwise we can fill the form with the existing configuration
	 */
	newOrExistingFilter: function() {
		this.doLayout();
			// Existing filter
		if (this.element.configuration.filters[this.filter]) {
			this.fillForm();
			// New filter
		} else {
			this.addFilterToElement();
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
		this.getForm().setValues(this.element.configuration.filters[this.filter]);
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
	 * Add this filter to the element
	 */
	addFilterToElement: function() {
		var formConfiguration = {filters: {}};
		formConfiguration.filters[this.filter] = this.configuration;

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

			var formConfiguration = {filters: {}};
			formConfiguration.filters[this.filter] = {};
			formConfiguration.filters[this.filter][fieldName] = field.getValue();

			this.element.setConfigurationValue(formConfiguration);
		}
	},

	/**
	 * Remove the filter
	 *
	 * Called when the remove button of this filter has been clicked
	 */
	removeFilter: function() {
		this.ownerCt.removeFilter(this);
		this.element.removeFilter(this.filter);
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
		var filterFields = this.configuration;
		var elementType = this.element.xtype.split('-').pop();

		var allowedGeneralFields = [];
		try {
			allowedGeneralFields = TYPO3.Form.Wizard.Settings.defaults.tabs.options.accordions.filtering.filters[this.filter].showProperties.split(/[, ]+/);
		} catch (error) {
			// The object has not been found or constructed wrong
			allowedGeneralFields = [
				'allowWhiteSpace',
				'decimalPoint',
				'thousandSeparator',
				'expression',
				'characterList'
			];
		}

		var allowedElementFields = [];
		try {
			allowedElementFields = TYPO3.Form.Wizard.Settings.elements[elementType].accordions.filtering.filters[this.filter].showProperties.split(/[, ]+/);
		} catch (error) {
			// The object has not been found or constructed wrong
			allowedElementFields = allowedGeneralFields;
		}

		Ext.iterate(allowedElementFields, function(item, index, allItems) {
			if (allowedGeneralFields.indexOf(item) > -1 && Ext.isDefined(filterFields[item])) {
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
				this.fireEvent('validation', this.filter, valid);
			} else if (!valid && !this.el.hasClass('validation-error')) {
				this.addClass('validation-error');
				this.fireEvent('validation', this.filter, valid);
			}
		}
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters-filter', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.Filter);