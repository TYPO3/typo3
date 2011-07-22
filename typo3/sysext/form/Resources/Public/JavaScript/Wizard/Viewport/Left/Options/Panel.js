Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options');

/**
 * The options panel
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Panel
 * @extends Ext.Panel
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Panel = Ext.extend(Ext.Panel, {
	/**
	 * @cfg {Object} element
	 * The element for the options form
	 */
	element: null,

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
		autoHeight: true,
		border: false,
		padding: 0
	},

	/**
	 * Constructor
	 *
	 * Add the form elements to the tab
	 */
	initComponent: function() {
		var accordions = this.getAccordionsBySettings();
		var accordionItems = new Array();

		// Adds the specified events to the list of events which this Observable may fire.
		this.addEvents({
			'validation': true
		});

		Ext.iterate(accordions, function(item, index, allItems) {
			var accordionXtype = 'typo3-form-wizard-viewport-left-options-forms-' + item;
			accordionItems.push({
				xtype: accordionXtype,
				element: this.element,
				listeners: {
					'validation': {
						fn: this.validation,
						scope: this
					}
				}
			});
		}, this);

		var config = {
			items: [{
				xtype: 'panel',
				layout: 'accordion',
				ref: 'accordion',
				defaults: {
					autoHeight: true,
					cls: 'x-panel-accordion'
				},
				items: accordionItems
			}]
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.Options.Panel.superclass.initComponent.apply(this, arguments);
	},

	/**
	 * Adds the accordions depending on the TSconfig settings
	 *
	 * It will first look at showAccordions for the tab, then it will filter it
	 * down with the accordions allowed for the element.
	 *
	 * @returns {Array}
	 */
	getAccordionsBySettings: function() {
		var accordions = [];
		if (this.element) {
			var elementType = this.element.xtype.split('-').pop();

			var allowedDefaultAccordions = [];
			try {
				allowedDefaultAccordions = TYPO3.Form.Wizard.Settings.defaults.tabs.options.showAccordions.split(/[, ]+/);
			} catch (error) {
				// The object has not been found
				allowedDefaultAccordions = [
					'legend',
					'label',
					'attributes',
					'options',
					'validation',
					'filters',
					'various'
				];
			}

			var allowedElementAccordions = [];
			try {
				allowedElementAccordions = TYPO3.Form.Wizard.Settings.elements[elementType].showAccordions.split(/[, ]+/);
			} catch (error) {
				// The object has not been found
				allowedElementAccordions = allowedDefaultAccordions;
			}

			Ext.iterate(allowedElementAccordions, function(item, index, allItems) {
				var accordionXtype = 'typo3-form-wizard-viewport-left-options-forms-' + item;
				if (
					Ext.isDefined(this.element.configuration[item]) &&
					allowedElementAccordions.indexOf(item) > -1 &&
					Ext.ComponentMgr.isRegistered(accordionXtype)
				) {
					accordions.push(item);
				}
			}, this);
		}

		return accordions;
	},

	/**
	 * Fire the validation event
	 *
	 * This is only a pass-through for the accordion validation events
	 *
	 * @param accordion
	 * @param valid
	 */
	validation: function(accordion, valid) {
		this.fireEvent('validation', accordion, valid);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-panel', TYPO3.Form.Wizard.Viewport.Left.Options.Panel);