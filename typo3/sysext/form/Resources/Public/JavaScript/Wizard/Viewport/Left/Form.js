Ext.namespace('TYPO3.Form.Wizard.Viewport.LeftTYPO3.Form.Wizard.Elements');

/**
 * The form tab on the left side
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Form
 * @extends Ext.Panel
 */
TYPO3.Form.Wizard.Viewport.Left.Form = Ext.extend(Ext.Panel, {
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
	id: 'formwizard-left-form',

	/**
	 * @cfg {String} cls
	 * An optional extra CSS class that will be added to this component's
	 * Element (defaults to ''). This can be useful for adding customized styles
	 * to the component or any of its children using standard CSS rules.
	 */
	cls: 'x-tab-panel-body-content',

	/**
	 * @cfg {String} title
	 * The title text to be used as innerHTML (html tags are accepted) to
	 * display in the panel header (defaults to '').
	 */
	title: TYPO3.l10n.localize('left_form'),

	/**
	 * @cfg {Boolean} border
	 * True to display the borders of the panel's body element, false to hide
	 * them (defaults to true). By default, the border is a 2px wide inset
	 * border, but this can be further altered by setting bodyBorder to false.
	 */
	border: false,

	/**
	 * @cfg {Object|Function} defaults
	 * This option is a means of applying default settings to all added items
	 * whether added through the items config or via the add or insert methods.
	 */
	defaults: {
		//autoHeight: true,
		border: false,
		padding: 0
	},

	/**
	 * @cfg {Object} validAccordions
	 * Keeps track which accordions are valid. Accordions contain forms which
	 * do client validation. If there is a validation change in a form in the
	 * accordion, a validation event will be fired, which changes one of these
	 * values
	 */
	validAccordions: {
		behaviour: true,
		prefix: true,
		attributes: true,
		postProcessor: true
	},

	/**
	 * Constructor
	 *
	 * Add the form elements to the tab
	 */
	initComponent: function() {
		var config = {
			items: [{
				xtype: 'panel',
				layout: 'accordion',
				ref: 'accordion',
				defaults: {
					autoHeight: true,
					cls: 'x-panel-accordion'
				}
			}]
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.Form.superclass.initComponent.apply(this, arguments);
	},

	/**
	 * Called whenever a form has been added to the right container
	 *
	 * Sets element to the form component and calls the function to add the
	 * attribute fields
	 *
	 * @param form
	 */
	setForm: function(form) {
		var allowedAccordions = TYPO3.Form.Wizard.Settings.defaults.tabs.form.showAccordions.split(/[, ]+/);

		this.accordion.removeAll();
		if (form) {
			Ext.each(allowedAccordions, function(option, index, length) {
				switch (option) {
					case 'behaviour':
						this.accordion.add({
							xtype: 'typo3-form-wizard-viewport-left-form-behaviour',
							element: form,
							listeners: {
								'validation': {
									fn: this.validation,
									scope: this
								}
							}
						});
						break;
					case 'prefix':
						this.accordion.add({
							xtype: 'typo3-form-wizard-viewport-left-form-prefix',
							element: form,
							listeners: {
								'validation': {
									fn: this.validation,
									scope: this
								}
							}
						});
						break;
					case 'attributes':
						this.accordion.add({
							xtype: 'typo3-form-wizard-viewport-left-form-attributes',
							element: form,
							listeners: {
								'validation': {
									fn: this.validation,
									scope: this
								}
							}
						});
						break;
					case 'postProcessor':
						this.accordion.add({
							xtype: 'typo3-form-wizard-viewport-left-form-postprocessor',
							element: form,
							listeners: {
								'validation': {
									fn: this.validation,
									scope: this
								}
							}
						});
						break;
				}
			}, this);
		}
		this.doLayout();
	},

	/**
	 * Called by the validation listeners of the accordions
	 *
	 * Checks if all accordions are valid. If not, adds a class to the tab
	 *
	 * @param {String} accordion The accordion which fires the event
	 * @param {Boolean} isValid Accordion is valid or not
	 */
	validation: function(accordion, isValid) {
		this.validAccordions[accordion] = isValid;
		var tabIsValid = true;
		Ext.iterate(this.validAccordions, function(key, value) {
			if (!value) {
				tabIsValid = false;
			}
		}, this);
		if (this.tabEl) {
			var tabEl = Ext.get(this.tabEl);
			if (tabIsValid && tabEl.hasClass('validation-error')) {
				tabEl.removeClass('validation-error');
			} else if (!tabIsValid && !tabEl.hasClass('validation-error')) {
				tabEl.addClass('validation-error');
			}
		}
	}
});

Ext.reg('typo3-form-wizard-viewport-left-form', TYPO3.Form.Wizard.Viewport.Left.Form);