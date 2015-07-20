Ext.namespace('TYPO3.Form.Wizard.Viewport');

/**
 * The tabpanel on the left side
 *
 * @class TYPO3.Form.Wizard.Viewport.Left
 * @extends Ext.TabPanel
 */
TYPO3.Form.Wizard.Viewport.Left = Ext.extend(Ext.TabPanel, {
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
	id: 'formwizard-left',

	/**
	 * @cfg {Integer} width
	 * The width of this component in pixels (defaults to auto).
	 */
	width: 350,

	/**
	 * @cfg {String/Number} activeTab A string id or the numeric index of the tab that should be initially
	 * activated on render (defaults to undefined).
	 */
	activeTab: 0,

	/**
	 * @cfg {String} region
	 * Note: this config is only used when this BoxComponent is rendered
	 * by a Container which has been configured to use the BorderLayout
	 * layout manager (e.g. specifying layout:'border').
	 */
	region: 'west',

	/**
	 * @cfg {Boolean} autoScroll
	 * true to use overflow:'auto' on the components layout element and show
	 * scroll bars automatically when necessary, false to clip any overflowing
	 * content (defaults to false).
	 */
	autoScroll: true,

	/**
	 * @cfg {Boolean} border
	 * True to display the borders of the panel's body element, false to hide
	 * them (defaults to true). By default, the border is a 2px wide inset border,
	 * but this can be further altered by setting {@link #bodyBorder} to false.
	 */
	border: false,

	/**
	 * @cfg {Object|Function} defaults
	 *
	 * This option is a means of applying default settings to all added items
	 * whether added through the items config or via the add or insert methods.
	 */
	defaults: {
		autoHeight: true,
		autoWidth: true
	},

	/**
	 * Constructor
	 *
	 * Add the tabs to the tabpanel
	 */
	initComponent: function() {
		var allowedTabs = TYPO3.Form.Wizard.Settings.defaults.showTabs.split(/[, ]+/);
		var tabs = [];

		Ext.each(allowedTabs, function(option, index, length) {
			var tabXtype = 'typo3-form-wizard-viewport-left-' + option;
			if (Ext.ComponentMgr.isRegistered(tabXtype)) {
				tabs.push({
					xtype: tabXtype
				});
			}
		}, this);

		var config = {
			items: tabs
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.superclass.initComponent.apply(this, arguments);

			// Set the focus when a tab has changed. We need this to remove focus from forms
		this.on('tabchange', this.setFocus, this);
	},

	/**
	 * Set the focus to a tab
	 *
	 * doLayout is necessary, because the tabs are sometimes emptied and filled
	 * again, for instance by the history. Otherwise after a history undo or redo
	 * the options and form tabs are empty.
	 *
	 * @param tabPanel
	 * @param tab
	 */
	setFocus: function(tabPanel, tab) {
		tabPanel.doLayout();
		tab.el.focus();
	},

	/**
	 * Set the options tab as active tab
	 *
	 * Called by the options panel when an element has been selected
	 */
	setOptionsTab: function() {
		this.setActiveTab('formwizard-left-options');
	}
});

Ext.reg('typo3-form-wizard-viewport-left', TYPO3.Form.Wizard.Viewport.Left);