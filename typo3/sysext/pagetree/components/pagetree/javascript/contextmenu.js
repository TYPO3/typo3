Ext.namespace('TYPO3.Components.PageTree');

TYPO3.Components.PageTree.ContextMenu = Ext.extend(Ext.menu.Menu, {
	dataRecord: null,
	id: 'typo3-pagetree-contextmenu',
	listeners: {
		itemclick: {
			scope: this,
			fn: function (item) {
				if (item.callbackAction != undefined) {
					eval(item.callbackAction + '(item.parentMenu.dataRecord, item.attributes)');
				}
			}
		}
	},


	/**
	 * Fill menu with menu items.
	 *
	 * @param dataRecord
	 * The data record to bind to the menu.
	 * MUST contain "attributes.actions" as an array defining the allowed actions for the current item.
	 *
	 * @param contextMenuConfiguration
	 * Context menu configuration. See Ext.MenuItem for properties.
	 * Additionally, the following two properties have to exist:
	 * - callback: Callback method to be called when the click happens. Gets two parameters: the dataRecord, and item.attributes.
	 * - action: The name of the action
	 *
	 * @return the number of menu items in the first level.
	 */
	fillWithMenuItems: function(dataRecord, contextMenuConfiguration) {
		this.dataRecord = dataRecord;
		this.dataRecord.attributes.actions = Ext.toArray(this.dataRecord.attributes.actions);
		//
		var components = this.preProcessContextMenuConfiguration(contextMenuConfiguration);

		if (components.length) {
			for (var component in components) {
				if (components[component] == '-') {
					this.addSeparator();
				} else {
					if (typeof components[component] == 'object') {
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
			if (singleAction.indexOf('--submenu') != -1) {
				var subMenuComponents = this.preProcessContextMenuConfiguration(
						contextMenuConfiguration[singleAction],
						level + 1
						);

				if (subMenuComponents.length) {
					var subMenu = new TYPO3.Components.PageTree.ContextMenu({
						id: this.id + '-sub' + ++subMenus,
						items: subMenuComponents,
						dataRecord: this.dataRecord
					});

					components[index++] = {
						text: contextMenuConfiguration[singleAction]['text'],
						cls: 'contextMenu-subMenu',
						menu: subMenu
					};
				}
			} else {
				if (singleAction.indexOf('--divider') != -1) {
					if (modulesInsideGroup) {
						components[index++] = '-';
						modulesInsideGroup = false;
					}
				} else {
					modulesInsideGroup = true;

					if (typeof contextMenuConfiguration[singleAction] == 'object') {
						var component = contextMenuConfiguration[singleAction];
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
		}

		return components;
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.ContextMenu', TYPO3.Components.PageTree.ContextMenu);