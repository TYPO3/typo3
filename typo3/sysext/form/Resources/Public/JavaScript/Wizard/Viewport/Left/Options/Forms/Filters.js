Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms');

/**
 * The filters accordion panel in the element options in the left tabpanel
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters
 * @extends Ext.Panel
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters = Ext.extend(Ext.Panel, {
	/**
	 * @cfg {String} title
	 * The title text to be used as innerHTML (html tags are accepted) to
	 * display in the panel header (defaults to '').
	 */
	title: TYPO3.l10n.localize('options_filters'),

	/**
	 * @cfg {Object} validFilters
	 * Keeps track which filters are valid. Filters contain forms which
	 * do client validation. If there is a validation change in a form in the
	 * filter, a validation event will be fired, which changes one of these
	 * values
	 */
	validFilters: {
		alphabetic: true,
		alphanumeric: true,
		currency: true,
		digit: true,
		integer: true,
		lowercase: true,
		regexp: true,
		removexss: true,
		stripnewlines: true,
		titlecase: true,
		trim: true,
		uppercase: true
	},

	/**
	 * Constructor
	 *
	 * Add the form elements to the accordion
	 */
	initComponent: function() {
		var filters = this.getFiltersBySettings();

			// Adds the specified events to the list of events which this Observable may fire.
		this.addEvents({
			'validation': true
		});

		var config = {
			items: [{
				xtype: 'typo3-form-wizard-viewport-left-options-forms-filters-dummy',
				ref: 'dummy'
			}],
			tbar: [
				{
					xtype: 'combo',
					hideLabel: true,
					name: 'filters',
					ref: 'filters',
					mode: 'local',
					triggerAction: 'all',
					forceSelection: true,
					editable: false,
					hiddenName: 'filters',
					emptyText: TYPO3.l10n.localize('filters_emptytext'),
					width: 150,
					displayField: 'label',
					valueField: 'value',
					store: new Ext.data.JsonStore({
						fields: ['label', 'value'],
						data: filters
					}),
					listeners: {
						'select': {
							scope: this,
							fn: this.addFilter
						}
					}
				}
			]
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters.superclass.initComponent.apply(this, arguments);

			// Initialize the filters when they are available for this element
		this.initFilters();
	},

	/**
	 * Called when constructing the filters accordion
	 *
	 * Checks if the element already has filters and loads these instead of the dummy
	 */
	initFilters: function() {
		var filters = this.element.configuration.filters;
		if (!Ext.isEmptyObject(filters)) {
			this.remove(this.dummy);
			Ext.iterate(filters, function(key, value) {
				this.add({
					xtype: 'typo3-form-wizard-viewport-left-options-forms-filters-' + key,
					element: this.element,
					configuration: value,
					listeners: {
						'validation': {
							fn: this.validation,
							scope: this
						}
					}
				});
			}, this);
		}
	},

	/**
	 * Add a filter to the filters list
	 *
	 * @param comboBox
	 * @param record
	 * @param index
	 */
	addFilter: function(comboBox, record, index) {
		var filter = comboBox.getValue();
		var xtype = 'typo3-form-wizard-viewport-left-options-forms-filters-' + filter;

		if (!Ext.isEmpty(this.findByType(xtype))) {
			Ext.MessageBox.alert(TYPO3.l10n.localize('filters_alert_title'), TYPO3.l10n.localize('filters_alert_description'));
		} else {
			this.remove(this.dummy);

			this.add({
				xtype: xtype,
				element: this.element,
				listeners: {
					'validation': {
						fn: this.validation,
						scope: this
					}
				}
			});
			this.doLayout();
		}
	},

	/**
	 * Remove a filter from the filters list
	 *
	 * Shows dummy when there is no filter for this element
	 *
	 * @param component
	 */
	removeFilter: function(component) {
		this.remove(component);
		this.validation(component.filter, true);
		if (this.items.length == 0) {
			this.add({
				xtype: 'typo3-form-wizard-viewport-left-options-forms-filters-dummy',
				ref: 'dummy'
			});
		}
		this.doLayout();
	},

	/**
	 * Get the allowed filters by the TSconfig settings
	 *
	 * @returns {Array}
	 */
	getFiltersBySettings: function() {
		var filters = [];
		var elementType = this.element.xtype.split('-').pop();

		var allowedDefaultFilters = [];
		try {
			allowedDefaultFilters = TYPO3.Form.Wizard.Settings.defaults.tabs.options.accordions.filtering.showFilters.split(/[, ]+/);
		} catch (error) {
			// The object has not been found
			allowedDefaultFilters = [
				'alphabetic',
				'alphanumeric',
				'currency',
				'digit',
				'integer',
				'lowercase',
				'regexp',
				'removexss',
				'stripnewlines',
				'titlecase',
				'trim',
				'uppercase'
			];
		}

		var allowedElementFilters = [];
		try {
			allowedElementFilters = TYPO3.Form.Wizard.Settings.elements[elementType].accordions.filtering.showFilters.split(/[, ]+/);
		} catch (error) {
			// The object has not been found or constructed wrong
			allowedElementFilters = allowedDefaultFilters;
		}

		Ext.iterate(allowedElementFilters, function(item, index, allItems) {
			if (allowedDefaultFilters.indexOf(item) > -1) {
				filters.push({label: TYPO3.l10n.localize('filters_' + item), value: item});
			}
		}, this);

		return filters;
	},

	/**
	 * Called by the validation listeners of the filters
	 *
	 * Checks if all filters are valid. If not, adds a class to the accordion
	 *
	 * @param {String} filter The filter which fires the event
	 * @param {Boolean} isValid Rule is valid or not
	 */
	validation: function(filter, isValid) {
		this.validFilters[filter] = isValid;
		var accordionIsValid = true;
		Ext.iterate(this.validFilters, function(key, value) {
			if (!value) {
				accordionIsValid = false;
			}
		}, this);
		if (this.el) {
			if (accordionIsValid && this.el.hasClass('validation-error')) {
				this.removeClass('validation-error');
				this.fireEvent('validation', 'filters', isValid);
			} else if (!accordionIsValid && !this.el.hasClass('validation-error')) {
				this.addClass('validation-error');
				this.fireEvent('validation', 'filters', isValid);
			}
		}
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-filters', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Filters);