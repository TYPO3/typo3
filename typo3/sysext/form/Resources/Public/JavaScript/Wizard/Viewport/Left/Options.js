Ext.namespace('TYPO3.Form.Wizard.Viewport.LeftTYPO3.Form.Wizard.Elements');

/**
 * The options tab on the left side
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options
 * @extends Ext.Panel
 */
TYPO3.Form.Wizard.Viewport.Left.Options = Ext.extend(Ext.Panel, {
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
	id: 'formwizard-left-options',

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
	title: TYPO3.l10n.localize('left_options'),

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
	 * @cfg {Object} validAccordions
	 * Keeps track which accordions are valid. Accordions contain forms which
	 * do client validation. If there is a validation change in a form in the
	 * accordion, a validation event will be fired, which changes one of these
	 * values
	 */
	validAccordions: {
		attributes: true,
		filters: true,
		label: true,
		legend: true,
		options: true,
		validation: true,
		various: true
	},

	/**
	 * Constructor
	 *
	 * Add the form elements to the tab
	 */
	initComponent: function() {
		var config = {
			items: [{
				xtype: 'typo3-form-wizard-viewport-left-options-dummy'
			}]
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.Options.superclass.initComponent.apply(this, arguments);

			// if the active element changes in helper, this should be reflected here
		TYPO3.Form.Wizard.Helpers.Element.on('setactive', this.toggleActive, this);
	},

	/**
	 * Load options form according to element type
	 *
	 * This will be called whenever the current element changes
	 *
	 * @param component The current element
	 * @return void
	 */
	toggleActive: function(component) {
		if (component) {
			this.removeAll();
			this.add({
				xtype: 'typo3-form-wizard-viewport-left-options-panel',
				element: component,
				listeners: {
					'validation': {
						fn: this.validation,
						scope: this
					}
				}
			});
			this.ownerCt.setOptionsTab();
		} else {
			this.removeAll();
			this.add({
				xtype: 'typo3-form-wizard-viewport-left-options-dummy'
			});
		}
		Ext.get(this.tabEl).removeClass('validation-error');
		Ext.iterate(this.validAccordions, function(key, value) {
			this.validAccordions[key] = true;
		}, this);
		this.doLayout();
	},

	/**
	 * Checks if a tab is valid by iterating all accordions on validity
	 *
	 * @returns {Boolean}
	 */
	tabIsValid: function() {
		var valid = true;

		Ext.iterate(this.validAccordions, function(key, value) {
			if (!value) {
				valid = false;
			}
		}, this);

		return valid;
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
		var tabIsValid = this.tabIsValid();

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

Ext.reg('typo3-form-wizard-viewport-left-options', TYPO3.Form.Wizard.Viewport.Left.Options);