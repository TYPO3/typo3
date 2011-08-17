Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms');

/**
 * The validation accordion panel in the element options in the left tabpanel
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation
 * @extends Ext.Panel
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation = Ext.extend(Ext.Panel, {
	/**
	 * @cfg {String} title
	 * The title text to be used as innerHTML (html tags are accepted) to
	 * display in the panel header (defaults to '').
	 */
	title: TYPO3.l10n.localize('options_validation'),

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
	id: 'formwizard-left-options-validation',

	/**
	 * @cfg {Object} validRules
	 * Keeps track which rules are valid. Rules contain forms which
	 * do client validation. If there is a validation change in a form in the
	 * rule, a validation event will be fired, which changes one of these
	 * values
	 */
	validRules: {
		alphabetic: true,
		alphanumeric: true,
		between: true,
		date: true,
		digit: true,
		email: true,
		equals: true,
		fileallowedtypes: true,
		float: true,
		greaterthan: true,
		inarray: true,
		integer: true,
		ip: true,
		length: true,
		lessthan: true,
		regexp: true,
		required: true,
		uri: true
	},

	/**
	 * Constructor
	 *
	 * Add the form elements to the accordion
	 */
	initComponent: function() {
		var rules = this.getRulesBySettings();

			// Adds the specified events to the list of events which this Observable may fire.
		this.addEvents({
			'validation': true
		});

		var config = {
			items: [{
				xtype: 'typo3-form-wizard-viewport-left-options-forms-validation-dummy',
				ref: 'dummy'
			}],
			tbar: [
				{
					xtype: 'combo',
					hideLabel: true,
					name: 'rules',
					ref: 'rules',
					mode: 'local',
					triggerAction: 'all',
					forceSelection: true,
					editable: false,
					hiddenName: 'rules',
					emptyText: TYPO3.l10n.localize('validation_emptytext'),
					width: 150,
					displayField: 'label',
					valueField: 'value',
					store: new Ext.data.JsonStore({
						fields: ['label', 'value'],
						data: rules
					}),
					listeners: {
						'select': {
							scope: this,
							fn: this.addRule
						}
					}
				}
			]
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation.superclass.initComponent.apply(this, arguments);

			// Initialize the rules when they are available for this element
		this.initRules();
	},

	/**
	 * Called when constructing the validation accordion
	 *
	 * Checks if the element already has rules and loads these instead of the dummy
	 */
	initRules: function() {
		var rules = this.element.configuration.validation;
		if (!Ext.isEmptyObject(rules)) {
			this.remove(this.dummy);
			Ext.iterate(rules, function(key, value) {
				var xtype = 'typo3-form-wizard-viewport-left-options-forms-validation-' + key;
				if (Ext.ComponentMgr.isRegistered(xtype)) {
					this.add({
						xtype: xtype,
						element: this.element,
						configuration: value,
						listeners: {
							'validation': {
								fn: this.validation,
								scope: this
							}
						}
					});
				}
			}, this);
		}
	},

	/**
	 * Add a rule to the validation list
	 *
	 * @param comboBox
	 * @param record
	 * @param index
	 */
	addRule: function(comboBox, record, index) {
		var rule = comboBox.getValue();
		var xtype = 'typo3-form-wizard-viewport-left-options-forms-validation-' + rule;

		if (!Ext.isEmpty(this.findByType(xtype))) {
			Ext.MessageBox.alert(TYPO3.l10n.localize('validation_alert_title'), TYPO3.l10n.localize('validation_alert_description'));
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
	 * Remove a rule from the validation list
	 *
	 * Shows dummy when there is no validation rule for this element
	 *
	 * @param component
	 */
	removeRule: function(component) {
		this.remove(component);
		this.validation(component.rule, true);
		if (this.items.length == 0) {
			this.add({
				xtype: 'typo3-form-wizard-viewport-left-options-forms-validation-dummy',
				ref: 'dummy'
			});
		}
		this.doLayout();
	},

	/**
	 * Get the rules by the TSconfig settings
	 *
	 * @returns {Array}
	 */
	getRulesBySettings: function() {
		var rules = [];
		var elementType = this.element.xtype.split('-').pop();

		var allowedDefaultRules = [];
		try {
			allowedDefaultRules = TYPO3.Form.Wizard.Settings.defaults.tabs.options.accordions.validation.showRules.split(/[, ]+/);
		} catch (error) {
			// The object has not been found
			allowedDefaultRules = [
				'alphabetic',
				'alphanumeric',
				'between',
				'date',
				'digit',
				'email',
				'equals',
				'fileallowedtypes',
				'float',
				'greaterthan',
				'inarray',
				'integer',
				'ip',
				'length',
				'lessthan',
				'regexp',
				'required',
				'uri'
			];
		}

		var allowedElementRules = [];
		try {
			allowedElementRules = TYPO3.Form.Wizard.Settings.elements[elementType].accordions.validation.showRules.split(/[, ]+/);
		} catch (error) {
			// The object has not been found or constructed wrong
			allowedElementRules = allowedDefaultRules;
		}

		Ext.iterate(allowedElementRules, function(item, index, allItems) {
			if (allowedDefaultRules.indexOf(item) > -1) {
				rules.push({label: TYPO3.l10n.localize('validation_' + item), value: item});
			}
		}, this);

		return rules;
	},

	/**
	 * Called by the validation listeners of the rules
	 *
	 * Checks if all rules are valid. If not, adds a class to the accordion
	 *
	 * @param {String} rule The rule which fires the event
	 * @param {Boolean} isValid Rule is valid or not
	 */
	validation: function(rule, isValid) {
		this.validRules[rule] = isValid;
		var accordionIsValid = true;
		Ext.iterate(this.validRules, function(key, value) {
			if (!value) {
				accordionIsValid = false;
			}
		}, this);
		if (this.el) {
			if (accordionIsValid && this.el.hasClass('validation-error')) {
				this.removeClass('validation-error');
				this.fireEvent('validation', 'validation', isValid);
			} else if (!accordionIsValid && !this.el.hasClass('validation-error')) {
				this.addClass('validation-error');
				this.fireEvent('validation', 'validation', isValid);
			}
		}
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-validation', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Validation);