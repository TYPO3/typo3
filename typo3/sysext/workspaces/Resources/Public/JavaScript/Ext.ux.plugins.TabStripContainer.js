/**
 * Ext.ux.plugins.TabStripContainer
 *
 * @author  Steffen Kamper
 * @date	December 19, 2010
 *
 * @class Ext.ux.plugins.TabStripContainer
 * @extends Object
 */

Ext.ns('Ext.ux.plugins');

Ext.ux.plugins.TabStripContainer = Ext.extend(Object, {

	/**
	 * @hide	private
	 *
	 * Tab panel we are plugged in.
	 */
	tabPanel : null,

	/**
	 * @hide	private
	 *
	 * items for the panel
	 */
	items: [],

	/**
	 * @hide	private
	 *
	 * Cached tab panel's strip wrap element container, i.e. panel's header or footer element.
	 */
	headerFooterEl : null,


	/**
	 * @constructor
	 */
	constructor : function(config) {
		Ext.apply(this, config);
	},

	/**
	 * Initializes plugin
	 */
	init : function(tabPanel) {
		this.tabPanel = tabPanel;
		tabPanel.on(
			'afterrender',
			this.onTabPanelAfterRender,
			this,
			{
				delay: 10
			}
		);
	},

	/**
	 * Adds the panel to the tab header/footer
	 *
	 * @param tabPanel
	 */
	onTabPanelAfterRender: function(tabPanel) {
		var height, panelDiv, stripTarget, config;
		// Getting and caching strip wrap element parent, i.e. tab panel footer or header.
		this.headerFooterEl =
				this.tabPanel.tabPosition == 'bottom'
					? this.tabPanel.footer
					: this.tabPanel.header;
		height = this.headerFooterEl.getComputedHeight();
		stripTarget = tabPanel[tabPanel.stripTarget];
		stripTarget.applyStyles('position: relative;');

		panelDiv = this.headerFooterEl.createChild({
			tag : 'div',
			id: this.id || Ext.id(),
			style : {
				position : 'absolute',
				right: 0,
				top: '1px'
			}
		});
		panelDiv.setSize(this.width, height, false);
		config = Ext.applyIf({
			layout: 'hbox',
			height: height,
			width: this.width,
			renderTo: panelDiv
		}, this.panelConfig);
		this.panelContainer = new Ext.Panel(config);
		this.panelContainer.add(this.items);
		this.panelContainer.doLayout();
	},

	doLayout: function () {
		this.panelContainer.doLayout();
	}

});
Ext.preg('Ext.ux.plugins.TabStripContainer', Ext.ux.plugins.TabStripContainer);
