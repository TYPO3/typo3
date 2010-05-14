/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Stefan Galinski <stefan.galinski@gmail.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

Ext.ns('TYPO3');

/**
 * Debug panel based upon the widget tab panel
 *
 * If you want to add a new tab, you can use the addTab or addTabWidget methods. The first one
 * creates the widget itself. If you need the latter one, you must create the widget yourself.
 *
 * The drag&drop functionality introduced a new attribute for the widget that should be added
 * as a tab. It's called "draggableTab" and needs to be set to true, if you want activated
 * drag&drop for the new tab.
 *
 * Additional Features:
 * - Drag&Drop
 * - Close tabs with a simple wheel/middle click
 * - utilization of the tabCloseMenu context menu (several closing options)
 * - Grouping of tabs (Only one nested level allowed!)
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.DebugPanel = Ext.extend(Ext.TabPanel, {
	/**
	 * Tab Groups
	 * 
	 * @var Ext.util.MixedCollection
	 */
	tabGroups: new Ext.util.MixedCollection(),

	/**
	 * Initializes the widget and merges our defaults with the user-defined ones. The
	 * user-defined settings are preferred.
	 *
	 * @return void
	 */
	initComponent: function(config) {
		config = config || {};
		Ext.apply(this, config, {
				// activate general tab navigation with mouse wheel support
			enableTabScroll: true,
			defaults: {
				autoScroll: true
			},

				// add the context menu actions
			plugins: new Ext.ux.TabCloseMenu({
				closeTabText: TYPO3.LLL.core.tabs_close,
				closeOtherTabsText: TYPO3.LLL.core.tabs_closeOther,
				closeAllTabsText: TYPO3.LLL.core.tabs_closeAll
			})
		});

			// create a drop arrow indicator
		this.on('render', function() {
			this.arrow = Ext.DomHelper.append(
				Ext.getBody(),
				'<div class="typo3-debugPanel-dragDropArrowDown">&nbsp;</div>',
				true
			);
			this.arrow.hide();
		}, this);

		TYPO3.DebugPanel.superclass.initComponent.call(this);
	},

	/**
	 * Cleanup
	 *
	 * @return void
	 */
	onDestroy: function() {
		Ext.destroy(this.arrow);
		TYPO3.DebugPanel.superclass.onDestroy.call(this);
	},

	/**
	 * Adds a new tab
	 *
	 * If you need more possibilites, you should use the addTabWidget method.
	 *
	 * @see addTabWidget()
	 * @param tabContent String content of the new tab
	 * @param header String tab header
	 * @param group String tab group
	 * @param position Integer position of the new tab
	 * @return void
	 */
	addTab: function(tabContent, header, group, position) {
		var tabWidget = new Ext.Panel({
			title: header,
			html: tabContent,
			border: false,
			autoScroll: true,
			closable: true,
			draggableTab: true
		});

		this.addTabWidget(tabWidget, group, position);
	},

	/**
	 * Adds a new tab to the widget
	 *
	 * You can inject any Ext component, but you need to create it yourself. If you just
	 * want to add some text into a new tab, you should use the addTab function.
	 *
	 * @see addTab()
	 * @param tabWidget Component the component that should be added as a new tab
	 * @param group String tab group
	 * @param position Integer position of the new tab
	 * @return void
	 */
	addTabWidget: function(tabWidget, group, position) {
		if (this.hidden) {
			this.show();
		} else if (this.collapsed) {
			this.expand();
		}

			// Move the widget into a tab group?
		var tabGroup = this;
		if (typeof group !== 'undefined' && group != '' && !this.isTabChildren) {
			if (this.tabGroups.indexOfKey(group) === -1) {
				tabGroup = new TYPO3.DebugPanel({
					border: false,
					title: group,
					autoScroll: true,
					closable: true,
					isTabChildren: true,
					tabParent: this,
					draggableTab: true
				});
				this.addTabWidget(tabGroup);

				this.tabGroups.add(group, tabGroup);
			} else {
				tabGroup = this.tabGroups.key(group);
			}
		}

			// recalculate position if necessary
		if (typeof position === 'undefined') {
			position = tabGroup.items.getCount();
		}

			// hide the debug panel if the last element is closed
		tabWidget.on('destroy', function(element) {
			if (this.isTabChildren) {
				if (!this.items.getCount()) {
					this.tabParent.remove(this.tabParent.tabGroups.key(this.title));
				}
			} else {
				if (!this.items.getCount()) {
					this.hide();
					this.fireEvent('resize');
				}
				this.tabGroups.removeKey(element.title);
			}
		}, tabGroup);

			// add drag&drop and the wheel click functionality
		tabWidget.on('afterlayout', function(element) {
			Ext.get(this.id + '__' + element.id).on('mousedown', function(event) {
				if (!Ext.isIE6 && !Ext.isIE7) {
					if ((Ext.isIE && event.button === 1) ||
						(!Ext.isIE && event.browserEvent.button === 1)
					) {
						event.stopEvent();
						this.remove(tabWidget);
						return false;
					}
				}
				return true;
			}, this);

			if (tabWidget.draggableTab) {
				this.initDragAndDropForTab(tabWidget);
			}
		}, tabGroup);

			// add the widget as a new tab
		tabGroup.insert(position, tabWidget).show();
		tabGroup.ownerCt.doLayout();
	},

	/**
	 * Extends the tab item with drag&drop functionality.
	 *
	 * @param item Component the tab widget
	 * @return void
	 */
	initDragAndDropForTab: function(item) {
		item.tabDragZone = new Ext.dd.DragZone(this.id + '__' + item.id, {
			ddGroup: this.id,

			/**
			 * Reintroduces the simple click event on a tab element.
			 *
			 * @return void
			 */
			b4MouseDown : function() {
				item.show();
				Ext.dd.DragZone.superclass.b4MouseDown.apply(this, arguments);
			},

			/**
			 * On receipt of a mousedown event, see if it is within a draggable element.
			 * Return a drag data object if so. The data object can contain arbitrary application
			 * data, but it should also contain a DOM element in the ddel property to provide
			 * a proxy to drag.
			 *
			 * @param event Ext.EventObject
			 * @return drag data
			 */
			getDragData: function(event) {
				var sourceElement = event.getTarget(item.itemSelector, 10);
				if (sourceElement) {
					var dragComponent = sourceElement.cloneNode(true);
					dragComponent.id = Ext.id();
					item.dragData = {
						ddel: dragComponent,
						sourceEl: sourceElement,
						repairXY: Ext.fly(sourceElement).getXY()
					};
					return item.dragData;
				}

				return false;
			},

			/**
			 * Provide coordinates for the proxy to slide back to on failed drag.
			 * This	is the original XY coordinates of the draggable element.
			 *
			 * @return x,y coordinations of the original component position
			 */
			getRepairXY: function() {
				return this.dragData.repairXY;
			}
		});

		item.tabDropZone = new Ext.dd.DropZone(this.id + '__' + item.id, {
			debugPanel: this,
			ddGroup: this.id,

			/**
			 * If the mouse is over a tab element, return that node. This is
			 * provided as the "target" parameter in all "onNodeXXXX" node event
			 * handling functions
			 *
			 * @param event Ext.EventObject
			 * @return the tab element or boolean false
			 */
			getTargetFromEvent: function(event) {
				var tabElement = Ext.get(event.getTarget()).findParentNode('li');
				if (tabElement !== null) {
					return tabElement;
				}

				return false;
			},

			/**
			 * On entry into a target node, highlight that node.
			 *
			 * @param target string id of the target element
			 * @return void
			 */
			onNodeEnter : function(target) {
				Ext.get(target).addClass('typo3-debugPanel-dragDropOver');
			},

			/**
			 * On exit from a target node, unhighlight that node.
			 *
			 * @param target string id of the target element
			 * @return void
			 */
			onNodeOut : function(target) {
				Ext.get(target).removeClass('typo3-debugPanel-dragDropOver');
				this.debugPanel.arrow.hide();
			},

			/**
			 * While over a target node, return the default drop allowed class which
			 * places a "tick" icon into the drag proxy. Also the arrow position is
			 * recalculated.
			 *
			 * @param target string id of the target element
			 * @param proxy Ext.dd.DDProxy proxy element
			 * @param event Ext.EventObject
			 * @return default dropAllowed class or a boolean false
			 */
			onNodeOver : function(target, proxy, event) {
					// set arrow position
				var element = Ext.get(target);
				var left = 0;
				var tabLeft = element.getX();
				var tabMiddle = tabLeft + element.dom.clientWidth / 2;
				var tabRight = tabLeft + element.dom.clientWidth;
				if (event.getPageX() <= tabMiddle) {
					left = tabLeft;
				} else {
					left = tabRight;
				}
				this.debugPanel.arrow.setTop(this.el.getY() - 8).setLeft(left - 9).show();

					// drop allowed?
				if (proxy.handleElId !== target.id) {
					return Ext.dd.DropZone.prototype.dropAllowed;
				}

				return false;
			},

			/**
			 * On node drop we move the dragged tab element at the position of
			 * the dropped element.
			 *
			 * @param target string id of the target element
			 * @param proxy Ext.dd.DDProxy proxy element
			 * @param event Ext.EventObject
			 * @return true or false
			 */
			onNodeDrop : function(target, proxy, event) {
				if (proxy.handleElId === target.id) {
					return false;
				}

				var dropPanelId = target.id.substring(this.debugPanel.id.length + 2);
				var dragPanelId = proxy.handleElId.substring(this.debugPanel.id.length + 2);

				var dropPanelPosition = this.debugPanel.items.indexOfKey(dropPanelId);
				var dragPanelPosition = this.debugPanel.items.indexOfKey(dragPanelId);

				if (dropPanelPosition !== undefined &&
					dropPanelPosition !== -1 &&
					dropPanelPosition <= this.debugPanel.items.getCount()
				) {
						// calculate arrow position to decide if the elements needs
						// to be inserted on the right or left
					var element = Ext.get(target);
					var tabMiddle = element.getX() + element.dom.clientWidth / 2;
					if (dragPanelPosition > dropPanelPosition) {
						if (event.getPageX() > tabMiddle) {
							dropPanelPosition += 1;
						}
					} else {
						if (event.getPageX() <= tabMiddle) {
							dropPanelPosition -= 1;
						}
					}

					var dropEl = this.debugPanel.remove(dragPanelId, false);
					this.debugPanel.addTabWidget(dropEl, '', dropPanelPosition);
				}

				this.debugPanel.arrow.hide();
				return true;
			}
		});
	}
});

Ext.reg('typo3DebugPanel', TYPO3.DebugPanel);
