Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Elements');

/**
 * The predefined elements in the elements tab on the left side
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Elements.Predefined
 * @extends TYPO3.Form.Wizard.Viewport.Left.Elements.ButtonGroup
 */
TYPO3.Form.Wizard.Viewport.Left.Elements.Predefined = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Elements.ButtonGroup, {
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
	id: 'formwizard-left-elements-predefined',

	/**
	 * @cfg {String} title
	 * The title text to be used as innerHTML (html tags are accepted) to
	 * display in the panel header (defaults to '').
	 */
	title: TYPO3.l10n.localize('left_elements_predefined'),

	/**
	 * Constructor
	 *
	 * Add the buttons to the accordion
	 */
	initComponent: function() {
		var allowedButtons = TYPO3.Form.Wizard.Settings.defaults.tabs.elements.accordions.predefined.showButtons.split(/[, ]+/);
		var buttons = [];

		Ext.each(allowedButtons, function(option, index, length) {
			switch (option) {
				case 'email':
					buttons.push({
						text: TYPO3.l10n.localize('predefined_email'),
						id: 'predefined-email',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-predefined-email',
						scope: this
					});
					break;
				case 'radiogroup':
					buttons.push({
						text: TYPO3.l10n.localize('predefined_radiogroup'),
						id: 'predefined-radiogroup',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-predefined-radiogroup',
						scope: this
					});
					break;
				case 'checkboxgroup':
					buttons.push({
						text: TYPO3.l10n.localize('predefined_checkboxgroup'),
						id: 'predefined-checkboxgroup',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-predefined-checkboxgroup',
						scope: this
					});
					break;
				case 'name':
					buttons.push({
						text: TYPO3.l10n.localize('predefined_name'),
						id: 'predefined-name',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-predefined-name',
						scope: this
					});
					break;
			}
		}, this);

		var config = {
			items: buttons
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.Elements.Predefined.superclass.initComponent.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-elements-predefined', TYPO3.Form.Wizard.Viewport.Left.Elements.Predefined);