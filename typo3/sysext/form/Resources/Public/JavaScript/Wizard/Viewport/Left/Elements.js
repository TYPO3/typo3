Ext.namespace('TYPO3.Form.Wizard.Viewport.Left');

/**
 * The elements panel in the elements tab on the left side
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Elements
 * @extends Ext.Panel
 */
TYPO3.Form.Wizard.Viewport.Left.Elements = Ext.extend(Ext.Panel, {
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
	id: 'formwizard-left-elements',

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
	title: TYPO3.l10n.localize('left_elements'),

	/**
	 * Constructor
	 *
	 * Add the form elements to the tab
	 */
	initComponent: function() {
		var allowedAccordions = TYPO3.Form.Wizard.Settings.defaults.tabs.elements.showAccordions.split(/[, ]+/);
		var accordions = [];

		allowedAccordions.each(function(option, index, length) {
			var accordionXtype = 'typo3-form-wizard-viewport-left-elements-' + option;
			if (Ext.ComponentMgr.isRegistered(accordionXtype)) {
				accordions.push({
					xtype: accordionXtype
				});
			}
		}, this);

		var config = {
			items: [
				{
					xtype: 'container',
					id: 'formwizard-left-elements-intro',
					tpl: new Ext.XTemplate(
						'<tpl for=".">',
							'<p><strong>{title}</strong></p>',
							'<p>{description}</p>',
						'</tpl>'
					),
					data: [{
						title: TYPO3.l10n.localize('left_elements_intro_title'),
						description: TYPO3.l10n.localize('left_elements_intro_description')
					}],
					cls: 'formwizard-left-dummy typo3-message message-information'
				}, {
					xtype: 'panel',
					layout: 'accordion',
					border: false,
					padding: 0,
					defaults: {
						autoHeight: true,
						cls: 'x-panel-accordion'
					},
					items: accordions
				}
			]
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.Elements.superclass.initComponent.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-viewport-left-elements', TYPO3.Form.Wizard.Viewport.Left.Elements);