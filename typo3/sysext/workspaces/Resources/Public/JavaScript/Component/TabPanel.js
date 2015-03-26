Ext.ns('TYPO3.Workspaces.Component');

TYPO3.Workspaces.Component.TabPanel = Ext.extend(Ext.TabPanel, {
	menuRight: null,
	tabMenu: null,

	menuItems: [],
	menuItemTemplate: null,

	listeners: {
		beforetabchange: function(panel, newTab, currentTab) {
			if (typeof currentTab !== 'undefined' && newTab.triggerUrl) {
				this.handleTriggerUrl(newTab);
			}
		},
		afterrender: function() {
			this.createMenu();
			this.arrangeTabsAfterRender();
			this.updateMenu();
		}
	},

	initComponent: function() {
		TYPO3.Workspaces.Component.TabPanel.superclass.initComponent.call(this);
		Ext.EventManager.onWindowResize(this.handleResize, this);

		this.menuItemTemplate = new Ext.XTemplate(
			'<a id="{id}" class="{cls} x-unselectable" hidefocus="true" unselectable="on" href="{href}"',
				'<tpl if="hrefTarget">',
					' target="{hrefTarget}"',
				'</tpl>',
			'>',
				'<span class="x-menu-item-text">{text}</span>',
			'</a>'
		);
	},

	getParentPanel: function() {
		return this.findParentByType('panel');
	},

	createMenu : function() {
		var position = this.tabPosition=='bottom' ? this.footer : this.header;
		var h = this.stripWrap.dom.offsetHeight;
		var menuRight = position.insertFirst({
			cls:'x-tab-menu-right'
		});
		menuRight.hide();
		menuRight.setHeight(h);
		menuRight.addClassOnOver('x-tab-menu-right-over');
		menuRight.on('click', this.showMenu, this);
		this.menuRight = menuRight;
	},

	updateMenu: function() {
		if (this.menuItems.length) {
			this.menuRight.show();
		} else {
			this.menuRight.hide();
		}
	},

	showMenu: function(event) {
		if (this.tabMenu) {
			this.tabMenu.destroy();
			this.un('destroy', this.tabMenu.destroy, this.tabMenu);
			this.tabMenu = null;
		}

		this.tabMenu =  new Ext.menu.Menu({
			cls: 'typo3-workspaces-menu'
		});
		this.on('destroy', this.tabMenu.destroy, this.tabMenu);

		this.addMenuItems();

		var target = Ext.get(event.getTarget());
		var xy = target.getXY();
		xy[1] += this.menuRight.getHeight() - 1;

		this.tabMenu.showAt(xy);
	},

	addMenuItems: function() {
		Ext.each(this.menuItems, function(cmp) {
			menuItem = new Ext.menu.Item({
				itemTpl: this.menuItemTemplate,
				text      : cmp.title,
				handler   : this.handleTriggerUrl,
				scope     : this,
				triggerUrl: cmp.triggerUrl
				//iconCls   : item.iconCls
			});
			this.tabMenu.add(menuItem);
		}, this);
	},

	handleTriggerUrl: function(item) {
		location.href = item.triggerUrl;
	},

	handleResize: function(width, height) {
		this.setWidth(width);
		this.arrangeTabsAfterResize();
		this.updateMenu();
	},

	arrangeTabsAfterRender: function() {
		var i, cmp, moveItems = [], width = 0;
		var lastIndex = this.items.items.length;
		var tabPanelWidth = this.getParentPanel().getWidth();

		for (i = 0; i < lastIndex; i++) {
			cmp = this.getComponent(i);
			width += Ext.get(cmp.tabEl).getWidth() + this.tabMargin;
			if (width > tabPanelWidth - this.menuRight.getWidth()) {
				moveItems.push(cmp);
			}
		}

		Ext.each(moveItems, function(cmp) {
			this.remove(cmp);
			this.menuItems.push(cmp);
		}, this);
	},

	arrangeTabsAfterResize: function() {
		var i, cmp, moveItems = [], width = 0;
		var lastIndex = this.items.items.length;
		var tabPanelWidth = this.getParentPanel().getWidth();

		for (i = 0; i < lastIndex; i++) {
			cmp = this.getComponent(i);
			width += Ext.get(cmp.tabEl).getWidth() + this.tabMargin;
			if (width > tabPanelWidth - this.menuRight.getWidth()) {
				moveItems.unshift(cmp);
			}
		}

		if (moveItems.length) {
			Ext.each(moveItems, function(cmp) {
				this.remove(cmp);
				this.menuItems.unshift(cmp);
			}, this);
		} else {
			while (this.menuItems.length) {
				cmp = this.menuItems[0];
				this.add(cmp);
				width += Ext.get(cmp.tabEl).getWidth() + this.tabMargin;
				if (width > tabPanelWidth - this.menuRight.getWidth()) {
					this.remove(cmp);
					break;
				}
				this.menuItems.shift();
			}
		}
	}
});
Ext.reg('WorkspacesTabPanel', TYPO3.Workspaces.Component.TabPanel);
