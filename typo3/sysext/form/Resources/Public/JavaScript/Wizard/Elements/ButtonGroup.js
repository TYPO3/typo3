Ext.namespace('TYPO3.Form.Wizard');

/**
 * Button group to show on top of the form elements
 *
 * Most elements contain buttons to delete or edit the item. These buttons are
 * grouped in this component
 *
 * @class TYPO3.Form.Wizard.ButtonGroup
 * @extends Ext.Container
 */
TYPO3.Form.Wizard.ButtonGroup = Ext.extend(Ext.Container, {
	/**
	 * @cfg {String} cls
	 * An optional extra CSS class that will be added to this component's
	 * Element (defaults to ''). This can be useful for adding customized styles
	 * to the component or any of its children using standard CSS rules.
	 */
	cls: 'buttongroup',

	/**
	 * @cfg {Object|Function} defaults
	 * This option is a means of applying default settings to all added items
	 * whether added through the items config or via the add or insert methods.
	 */
	defaults: {
		xtype: 'button',
		template: new Ext.Template(
			'<span id="{4}"><button type="{0}" class="{3}"></button></span>'
		),
		tooltipType: 'title'
	},

	/** @cfg {Boolean} forceLayout
	 * If true the container will force a layout initially even if hidden or
	 * collapsed. This option is useful for forcing forms to render in collapsed
	 * or hidden containers. (defaults to false).
	 */
	forceLayout: true,

	/**
	 * Constructor
	 */
	initComponent: function() {
		var config = {
			items: [
				{
					iconCls: 't3-icon t3-icon-actions t3-icon-actions-edit t3-icon-edit-delete',
					tooltip: TYPO3.l10n.localize('elements_button_delete'),
					handler: this.removeElement,
					scope: this
				}, {
					iconCls: 't3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-open',
					tooltip: TYPO3.l10n.localize('elements_button_edit'),
					handler: this.setActive,
					scope: this
				}
			]
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.ButtonGroup.superclass.initComponent.apply(this, arguments);
	},

	/**
	 * Called by the click event of the remove button
	 *
	 * When clicking the remove button a confirmation will be asked by the
	 * container this button group is in.
	 */
	removeElement: function(button, event) {
		event.stopPropagation();
		this.ownerCt.confirmDeleteElement();
	},

	/**
	 * Called by the click event of the edit button
	 *
	 * Tells the element helper that this component is set as the active one
	 */
	setActive: function(button, event) {
		this.ownerCt.setActive(event, event.getTarget());
	}
});

Ext.reg('typo3-form-wizard-buttongroup', TYPO3.Form.Wizard.ButtonGroup);