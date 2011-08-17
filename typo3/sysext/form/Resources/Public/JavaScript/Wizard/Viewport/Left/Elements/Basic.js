Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Elements');

/**
 * The basic elements in the elements tab on the left side
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Elements.Basic
 * @extends TYPO3.Form.Wizard.Viewport.Left.Elements.ButtonGroup
 */
TYPO3.Form.Wizard.Viewport.Left.Elements.Basic = Ext.extend(TYPO3.Form.Wizard.Viewport.Left.Elements.ButtonGroup, {
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
	id: 'formwizard-left-elements-basic',

	/**
	 * @cfg {String} title
	 * The title text to be used as innerHTML (html tags are accepted) to
	 * display in the panel header (defaults to '').
	 */
	title: TYPO3.l10n.localize('left_elements_basic'),

	/**
	 * Constructor
	 *
	 * Add the buttons to the accordion
	 */
	initComponent: function() {
		var allowedButtons = TYPO3.Form.Wizard.Settings.defaults.tabs.elements.accordions.basic.showButtons.split(/[, ]+/);
		var buttons = [];

		allowedButtons.each(function(option, index, length) {
			switch (option) {
				case 'button':
					buttons.push({
						text: TYPO3.l10n.localize('basic_button'),
						id: 'basic-button',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-basic-button',
						scope: this
					});
					break;
				case 'checkbox':
					buttons.push({
						text: TYPO3.l10n.localize('basic_checkbox'),
						id: 'basic-checkbox',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-basic-checkbox',
						scope: this
					});
					break;
				case 'fieldset':
					buttons.push({
						text: TYPO3.l10n.localize('basic_fieldset'),
						id: 'basic-fieldset',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-basic-fieldset',
						scope: this
					});
					break;
				case 'fileupload':
					buttons.push({
						text: TYPO3.l10n.localize('basic_fileupload'),
						id: 'basic-fileupload',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-basic-fileupload',
						scope: this
					});
					break;
				case 'hidden':
					buttons.push({
						text: TYPO3.l10n.localize('basic_hidden'),
						id: 'basic-hidden',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-basic-hidden',
						scope: this
					});
					break;
				case 'password':
					buttons.push({
						text: TYPO3.l10n.localize('basic_password'),
						id: 'basic-password',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-basic-password',
						scope: this
					});
					break;
				case 'radio':
					buttons.push({
						text: TYPO3.l10n.localize('basic_radio'),
						id: 'basic-radio',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-basic-radio',
						scope: this
					});
					break;
				case 'reset':
					buttons.push({
						text: TYPO3.l10n.localize('basic_reset'),
						id: 'basic-reset',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-basic-reset',
						scope: this
					});
					break;
				case 'select':
					buttons.push({
						text: TYPO3.l10n.localize('basic_select'),
						id: 'basic-select',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-basic-select',
						scope: this
					});
					break;
				case 'submit':
					buttons.push({
						text: TYPO3.l10n.localize('basic_submit'),
						id: 'basic-submit',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-basic-submit',
						scope: this
					});
					break;
				case 'textarea':
					buttons.push({
						text: TYPO3.l10n.localize('basic_textarea'),
						id: 'basic-textarea',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-basic-textarea',
						scope: this
					});
					break;
				case 'textline':
					buttons.push({
						text: TYPO3.l10n.localize('basic_textline'),
						id: 'basic-textline',
						clickEvent: 'dblclick',
						handler: this.onDoubleClick,
						iconCls: 'formwizard-left-elements-basic-textline',
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
		TYPO3.Form.Wizard.Viewport.Left.Elements.Basic.superclass.initComponent.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-elements-basic', TYPO3.Form.Wizard.Viewport.Left.Elements.Basic);