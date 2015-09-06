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
Ext.namespace('TYPO3.Components.PageTree');

/**
 * @class TYPO3.Components.PageTree.ContextMenu
 *
 * Context menu implementation
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.menu.Menu
 */
TYPO3.Components.PageTree.ContextMenu = Ext.extend(Ext.menu.Menu, {
	/**
	 * Context menu node
	 *
	 * @cfg {Ext.tree.TreeNode}
	 */
	node: null,

	/**
	 * Page Tree
	 *
	 * @cfg {TYPO3.Components.PageTree.Tree}
	 */
	pageTree: null,

	/**
	 * Component Id
	 *
	 * @type {String}
	 */
	id: 'typo3-pagetree-contextmenu',

	/**
	 * Parent clicks should be ignored
	 *
	 * @type {Boolean}
	 */
	ignoreParentClicks: true,

	/**
	 * Listeners
	 *
	 * The itemclick event triggers the configured single click action
	 */
	listeners: {
		itemclick: {
			fn: function (item) {
				if (this.pageTree.commandProvider[item.callbackAction]) {
					if (item.parentMenu.pageTree.stateHash) {
						fsMod.recentIds['web'] = item.parentMenu.node.attributes.nodeData.id;
						item.parentMenu.pageTree.stateHash['lastSelectedNode'] = item.parentMenu.node.id;
					}

					this.pageTree.commandProvider[item.callbackAction](
						item.parentMenu.node,
						item.parentMenu.pageTree,
						item
					);
				}
			}
		}
	},

	/**
	 * Fills the menu with the actions
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} pageTree
	 * @param {Object} contextMenuConfiguration
	 * @return {void}
	 */
	fill: function(node, pageTree, contextMenuConfiguration) {
		this.node = node;
		this.pageTree = pageTree;

		var components = this.preProcessContextMenuConfiguration(contextMenuConfiguration);
		if (components.length) {
			for (var component in components) {
				if (components[component] === '-') {
					this.addSeparator();
				} else if (typeof components[component] === 'object') {
					this.addItem(components[component]);
				}
			}
		}
	},

	/**
	 * Parses the context menu actions array recursively and generates the
	 * components for the context menu including separators/dividers and sub menus
	 *
	 * @param {Object} contextMenuConfiguration
	 * @param {int} level
	 * @return {Object}
	 */
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
						menu: subMenu,
						icon: contextMenuConfiguration[singleAction]['icon']
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
						'callbackAction': contextMenuConfiguration[singleAction]['callbackAction'],
						'customAttributes': contextMenuConfiguration[singleAction]['customAttributes']
					};
					component.itemTpl = Ext.menu.Item.prototype.itemTpl = new Ext.XTemplate(
						'<a id="{id}" class="{cls}" hidefocus="true" unselectable="on" href="{href}">',
							'<span class="x-menu-item-icon" unselectable="on">{icon}</span>',
							'<span class="x-menu-item-text">{text}</span>',
						'</a>'
					);

					components[index++] = component;
				}
			}
		}

			// remove divider if it's the last item of the context menu
		if (components[components.length - 1] === '-') {
			components[components.length - 1] = '';
		}

		return components;
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.ContextMenu', TYPO3.Components.PageTree.ContextMenu);
