Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Elements');

/**
 * The button group abstract for the elements tab on the left side
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Elements.ButtonGroup
 * @extends Ext.ButtonGroup
 */
TYPO3.Form.Wizard.Viewport.Left.Elements.ButtonGroup = Ext.extend(Ext.Panel, {
	/**
	 * @cfg {Object|Function} defaults
	 * This option is a means of applying default settings to all added items
	 * whether added through the items config or via the add or insert methods.
	 */
	defaults: {
		xtype: 'button',
		scale: 'small',
		width: 140,
		iconAlign: 'left',
		cls: 'formwizard-element'
	},

	cls: 'formwizard-buttongroup',

	/**
	 * @cfg {Boolean} autoHeight
	 * true to use height:'auto', false to use fixed height (defaults to false).
	 * Note: Setting autoHeight: true means that the browser will manage the panel's height
	 * based on its contents, and that Ext will not manage it at all. If the panel is within a layout that
	 * manages dimensions (fit, border, etc.) then setting autoHeight: true
	 * can cause issues with scrolling and will not generally work as expected since the panel will take
	 * on the height of its contents rather than the height required by the Ext layout.
	 */
	autoHeight: true,

	/**
	 * @cfg {Number/String} padding
	 * A shortcut for setting a padding style on the body element. The value can
	 * either be a number to be applied to all sides, or a normal css string
	 * describing padding.
	 */
	padding: 0,

	/**
	 * @cfg {String} layout
	 * In order for child items to be correctly sized and positioned, typically
	 * a layout manager must be specified through the layout configuration option.
	 *
	 * The sizing and positioning of child items is the responsibility of the
	 * Container's layout manager which creates and manages the type of layout
	 * you have in mind.
	 */
	layout: 'table',

	/**
	 * @cfg {Object} layoutConfig
	 * This is a config object containing properties specific to the chosen
	 * layout if layout has been specified as a string.
	 */
	layoutConfig: {
		columns: 2
	},

	/**
	 * Constructor
	 *
	 * Add the buttons to the accordion
	 */
	initComponent: function() {
		var config = {};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.Elements.ButtonGroup.superclass.initComponent.apply(this, arguments);

			// Initialize the dragzone after rendering
		this.on('render', this.initializeDrag, this);
	},

	/**
	 * Initialize the drag zone.
	 *
	 * @param buttonGroup
	 */
	initializeDrag: function(buttonGroup) {
		buttonGroup.dragZone = new Ext.dd.DragZone(buttonGroup.getEl(), {
			getDragData: function(element) {
				var sourceElement = element.getTarget('.formwizard-element');
				if (sourceElement) {
					clonedElement = sourceElement.cloneNode(true);
					clonedElement.id = Ext.id();
					return buttonGroup.dragData = {
						sourceEl: sourceElement,
						repairXY: Ext.fly(sourceElement).getXY(),
						ddel: clonedElement
					};
				}
			},
			getRepairXY: function() {
				return buttonGroup.dragData.repairXY;
			}
		});
	},

	/**
	 * Called when a button has been double clicked
	 *
	 * Tells the form in the right container to add a new element, according to
	 * the button which has been clicked.
	 *
	 * @param button
	 * @param event
	 */
	onDoubleClick: function(button, event) {
		var formContainer = Ext.getCmp('formwizard-right').get(0).containerComponent;
		formContainer.dropElement(button, 'container');
	}
});

Ext.reg('typo3-form-wizard-viewport-left-elements-buttongroup', TYPO3.Form.Wizard.Viewport.Left.Elements.ButtonGroup);