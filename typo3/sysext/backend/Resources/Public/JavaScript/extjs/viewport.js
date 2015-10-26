/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

Ext.ns('TYPO3');

	// override splitregion to fit the splitbars in our design
Ext.override(Ext.layout.BorderLayout.SplitRegion, {
	render : function(ct, p) {
		Ext.layout.BorderLayout.SplitRegion.superclass.render.call(this, ct, p);

		var ps = this.position;

		this.splitEl = ct.createChild({
			cls: "x-layout-split x-layout-split-" + ps, html: " ",
			id: this.panel.id + '-xsplit'
		});

		if (this.enableChildSplit) {
			this.splitChildEl = this.splitEl.createChild({
				cls: 'x-layout-mini-wrapper'
			});

		}
		if (this.collapseMode == 'mini') {
			this.miniSplitEl = this.splitEl.createChild({
				cls: "x-layout-mini x-layout-mini-" + ps, html: " "
			});
			this.miniSplitEl.addClassOnOver('x-layout-mini-over');
			this.miniSplitEl.on('click', this.onCollapseClick, this, {stopEvent:true});
			this.miniSplitEl.dom.title = this.collapsible ? this.collapsibleSplitClickTip : this.splitTip;
		}

		var s = this.splitSettings[ps];

		if (this.enableChildSplit) {
			this.split = new Ext.SplitBar(this.splitChildEl.dom, p.el, s.orientation);
		} else {
			this.split = new Ext.SplitBar(this.splitEl.dom, p.el, s.orientation);
		}
		this.split.tickSize = this.tickSize;
		this.split.placement = s.placement;
		this.split.getMaximumSize = this[s.maxFn].createDelegate(this);
		this.split.minSize = this.minSize || this[s.minProp];
		this.split.on("beforeapply", this.onSplitMove, this);
		this.split.useShim = this.useShim === true;
		this.maxSize = this.maxSize || this[s.maxProp];

		if (p.hidden) {
			this.splitEl.hide();
		}

		if (this.useSplitTips) {
			this.splitEl.dom.title = this.collapsible ? this.collapsibleSplitTip : this.splitTip;
		}
		if (this.collapsible) {
			this.splitEl.on("dblclick", this.onCollapseClick, this);
		}
	}
});
/**
 * Extends the viewport with some functionality for TYPO3.
 */
TYPO3.Viewport = Ext.extend(Ext.Viewport, {
	/**
	 * Contains the navigation widgets in a simple array and identified by an unique idea
	 *
	 * @see registerNavigationWidget()
	 * @var object
	 */
	navigationWidgetContainer: {},

	/**
	 * Contains the meta informations of the navigation widgets
	 *
	 * @see registerNavigationWidget()
	 * @var object
	 */
	navigationWidgetMetaData: {},

	/**
	 * The topbar area
	 *
	 * @var Ext.Panel
	 */
	Topbar: null,

	/**
	 * The content area
	 *
	 * @var Ext.Panel
	 */
	ContentContainer: null,

	/**
	 * The navigation frame area
	 *
	 * @var Ext.Panel
	 */
	NavigationContainer: null,

	/**
	 * The iframe navigation component
	 *
	 * @var TYPO3.iframePanel
	 */
	NavigationIframe: null,

	/**
	 * The module menu area
	 *
	 * @var Ext.Panel
	 */
	ModuleMenuContainer: null,

	/**
	 * Initializes the ExtJS viewport with the given configuration.
	 *
	 * @return void
	 */
	initComponent: function() {
		// adjust the module menu and the height of the topbar
		this.initialConfig.items[0].height = TYPO3.configuration.topBarHeight;

		var moduleMenu = this.initialConfig.items[1];
		moduleMenu.width = TYPO3.configuration.moduleMenuWidth;

		// call parent constructor
		TYPO3.Viewport.superclass.initComponent.apply(this, arguments);

		this.ContentContainer = Ext.getCmp('typo3-contentContainer');
		this.NavigationContainer = Ext.getCmp('typo3-navigationContainer');
		this.NavigationIframe = Ext.getCmp('typo3-navigationIframe');
		this.Topbar = Ext.getCmp('typo3-topbar');
		this.ModuleMenuContainer = Ext.getCmp('typo3-module-menu');
	}
});

Ext.reg('typo3Viewport', TYPO3.Viewport);
