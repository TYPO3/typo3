Ext.namespace('TYPO3.Components.PageTree');

TYPO3.Components.PageTree.ContextMenu = Ext.extend(Ext.menu.Menu, {
	node: null,
	pageTree: null,
	id: 'typo3-pagetree-contextmenu',

	listeners: {
		itemclick: {
			fn: function (item) {
                this.pageTree.commandProvider[item.callbackAction](
					item.parentMenu.node,
					item.parentMenu.pageTree
				);
			}
		}
	},

	/**
	 * Fill menu with menu items and returns the number of context menu items
	 *
	 * @param node
	 * @param pageTree
	 * @param contextMenuConfiguration
	 * @return int
	 */
	fillWithMenuItems: function(node, pageTree, contextMenuConfiguration) {
		this.node = node;
		this.pageTree = pageTree;

		var components = this.preProcessContextMenuConfiguration(contextMenuConfiguration);

		if (components.length) {
			for (var component in components) {
				if (components[component] === '-') {
					this.addSeparator();
				} else {
					if (typeof components[component] === 'object') {
						this.addItem(components[component]);
					}
				}
			}
		}
		return components.length;
	},

	// Private
	// recursively parses the context menu actions array and generates the
	// components for the context menu including separators/dividers and sub menus
	preProcessContextMenuConfiguration: function(contextMenuConfiguration, level) {
		level = level || 0;
		if (level > 5) {
			return [];
		}

		var components = [];
		var index = 0;

		var modulesInsideGroup = false;
		var subMenus = 0;
		for (var singleAction in contextMenuConfiguration) {
			if (contextMenuConfiguration[singleAction]['type'] === 'submenu') {
				var subMenuComponents = this.preProcessContextMenuConfiguration(
					contextMenuConfiguration[singleAction]['childActions'],
					level + 1
				);

				if (subMenuComponents.length) {
					var subMenu = new TYPO3.Components.PageTree.ContextMenu({
						id: this.id + '-sub' + ++subMenus,
						items: subMenuComponents,
						node: this.node,
						pageTree: this.pageTree
					});

					components[index++] = {
						text: contextMenuConfiguration[singleAction]['label'],
						cls: 'contextMenu-subMenu',
						menu: subMenu
					};
				}
			} else if (contextMenuConfiguration[singleAction]['type'] === 'divider') {
				if (modulesInsideGroup) {
					components[index++] = '-';
					modulesInsideGroup = false;
				}
			} else {
				modulesInsideGroup = true;

				if (typeof contextMenuConfiguration[singleAction] === 'object') {
					var component = {
						'text': contextMenuConfiguration[singleAction]['label'],
						'icon': contextMenuConfiguration[singleAction]['icon'],
						'iconCls': contextMenuConfiguration[singleAction]['class'],
						'callbackAction': contextMenuConfiguration[singleAction]['callbackAction']
					};

					component.itemTpl = Ext.menu.Item.prototype.itemTpl = new Ext.XTemplate(
						'<a id="{id}" class="{cls}" hidefocus="true" unselectable="on" href="{href}">',
							'<span class="{hrefTarget}">',
								'<img src="{icon}" class="x-menu-item-icon {iconCls}" unselectable="on" />',
							'</span>',
							'<span class="x-menu-item-text">{text}</span>',
						'</a>'
					);

					components[index++] = component;
				}
			}
		}

			// remove divider if it's the last item of the context menu
		if (components.last() === '-') {
			components[components.length - 1] = '';
		}

		return components;
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.ContextMenu', TYPO3.Components.PageTree.ContextMenu);