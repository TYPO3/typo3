/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Stefan Galinski <stefan.galinski@gmail.com>
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
/**
 * @class TYPO3.Components.PageTree.TopPanel
 *
 * Top Panel
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.panel.Panel
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
Ext.define('TYPO3.Components.PageTree.TopPanel', {
	extend: 'Ext.panel.Panel',

	/**
	 * Component Id
	 *
	 * @type {String}
	 */
	id: 'typo3-pagetree-topPanel',

	/**
	 * Toolbar Object
	 *
	 * @type {Ext.Toolbar}
	 */
	dockedItems: [{
		xtype: 'toolbar',
		dock: 'top',
		itemId: 'topToolbar'
	}],

	/**
	 * Panel CSS
	 *
	 * @type {String}
	 */
	cls: 'typo3-pagetree-topPanel',
	
	/**
	 * Body CSS
	 *
	 * @type {String}
	 */
	bodyCls: 'typo3-pagetree-topPanel-item',
	
	/**
	 * Layout
	 *
	 * @type {String}
	 */
	layout: 'anchor',

	/**
	 * Currently Clicked Toolbar Button
	 *
	 * @type {Ext.Button}
	 */
	currentlyClickedButton: null,

	/**
	 * Filtering Indicator Item
	 *
	 * @type {Ext.panel.Panel}
	 */
	filteringIndicator: null,

	/**
	 * Drag and Drop Group
	 *
	 * @cfg {String}
	 */
	ddGroup: '',

	/**
	 * Data Provider
	 *
	 * @cfg {Object}
	 */
	dataProvider: null,

	/**
	 * Filtering Tree
	 *
	 * @cfg {TYPO3.Components.PageTree.FilteringTree}
	 */
	filteringTree: null,

	/**
	 * Page Tree
	 *
	 * @cfg {TYPO3.Components.PageTree.Tree}
	 */
	tree: null,

	/**
	 * Application Panel
	 *
	 * @cfg {TYPO3.Components.PageTree.App}
	 */
	app: null,

	/**
	 * Initializes the component
	 *
	 * @return {void}
	 */
	initComponent: function() {
		this.callParent();
			// Node insertion feature
		this.addDragDropNodeInsertionFeature();
			// Filter feature
		if (!TYPO3.Components.PageTree.Configuration.hideFilter
			|| TYPO3.Components.PageTree.Configuration.hideFilter === '0'
		) {
			this.addFilterFeature();
		}
			// Refresh feature
		this.getDockedComponent('topToolbar').add({xtype: 'tbfill'});
		this.addRefreshTreeFeature();
	},

	/**
	 * Adds a button to the components toolbar with a related component
	 *
	 * @param {Object} button
	 * @param {Object} connectedWidget
	 * @return {void}
	 */
	addButton: function(button, connectedWidget) {
		if (!button.hasListener('click')) {
			button.on('click', this.topbarButtonCallback);
		}

		if (connectedWidget) {
			connectedWidget.hidden = true;
			button.connectedWidget = connectedWidget;
			this.add(connectedWidget);
		}

		this.getDockedComponent('topToolbar').add(button);
		this.doLayout();
	},

	/**
	 * Usual button callback method that triggers the assigned component of the
	 * clicked toolbar button
	 *
	 * @return {void}
	 */
	topbarButtonCallback: function() {
		var topPanel = this.ownerCt.ownerCt;

		if (topPanel.currentlyClickedButton) {
			topPanel.currentlyClickedButton.toggle(false);
			if (topPanel.currentlyClickedButton.connectedWidget) {
				topPanel.currentlyClickedButton.connectedWidget.hide();
			}
		}

		if (topPanel.currentlyClickedButton === this) {
			topPanel.currentlyClickedButton = null;
			if (this.connectedWidget) {
				this.connectedWidget.hide();
			}
		} else {
			this.toggle(true);
			topPanel.currentlyClickedButton = this;
			if (this.connectedWidget) {
				this.connectedWidget.show();
			}
		}
	},

	/**
	 * Loads the filtering tree nodes with the given search word
	 *
	 * @param {Ext.form.field.Trigger} textField
	 * @return {void}
	 */
	createFilterTree: function (textField) {
		var searchWord = textField.getValue();
		var isNumber = TYPO3.Utility.isNumber(searchWord);
		var hasMinLength = (searchWord.length > 2 || searchWord.length <= 0);
		if ((!hasMinLength && !isNumber) || searchWord === this.filteringTree.searchWord) {
			return;
		}
		this.filteringTree.searchWord = searchWord;
		if (this.filteringTree.searchWord === '') {
			textField.setHideTrigger(true);
			this.app.setTree(this.tree);
			this.tree.refreshTree(function() {
				textField.focus(false, 500);
			}, this);

			if (this.filteringIndicator) {
				this.app.removeIndicator(this.filteringIndicator);
				this.filteringIndicator = null;
			}
		} else {
			var selectedNode = this.app.getSelected();

			if (!this.filteringIndicator) {
				this.filteringIndicator = this.app.addIndicator(
					this.createIndicatorItem(textField)
				);
			}

			this.app.ownerCt.getEl().mask('', 'x-mask-loading-message');
			this.app.ownerCt.getEl().addCls('t3-mask-loading');
			this.app.setTree(this.filteringTree);
			this.filteringTree.refreshTree(function() {
				if (selectedNode) {
					this.app.select(selectedNode.getNodeData('id'));
				}
				this.app.ownerCt.getEl().unmask();
				textField.setHideTrigger(false);
				textField.triggerWrap.setWidth(0);
				this.forceComponentLayout();
				textField.focus();
			}, this);
		}
	},

	/**
	 * Adds an indicator item to the page tree application for the filtering feature
	 *
	 * @param {Ext.form.TextField} textField
	 * @return {void}
	 */
	createIndicatorItem: function(textField) {
		return {
			id: this.app.getId() + '-indicatorBar-filter',
			cls: this.app.getId() + '-indicatorBar-item',
			renderData: {
				appId: this.app.getId(),
				spriteIconCls: TYPO3.Components.PageTree.Sprites.Info,
				label: TYPO3.Components.PageTree.LLL.activeFilterMode
			},
			renderTpl: Ext.create('Ext.XTemplate',
				'<p>',
				'<span id="{appId}-indicatorBar-filter-info" class="{appId}-indicatorBar-item-leftIcon {spriteIconCls}">&nbsp;</span>',
				'&nbsp;{label}&nbsp;',
				'<span id="{appId}-indicatorBar-filter-clear" class="{appId}-indicatorBar-item-rightIcon">X</span>',
				'</p>'
			),
			filteringTree: this.filteringTree,

			listeners: {
				afterrender: {
					scope: this,
					fn: function(component) {
						var element = Ext.get(this.app.getId() + '-indicatorBar-filter-clear');
						element.on('click', function() {
							textField.setValue('');
							this.createFilterTree(textField);
						}, this);
					}
				}
			}
		};
	},

	/**
	 * Adds the necessary functionality and components for the filtering feature
	 *
	 * @return {void}
	 */
	addFilterFeature: function() {
		var topPanelButton = Ext.create('Ext.button.Button', {
			id: this.getId() + '-button-filter',
			cls: this.getId() + '-button',
			iconCls: TYPO3.Components.PageTree.Sprites.Filter,
			tooltip: TYPO3.Components.PageTree.LLL.buttonFilter
		});

		var textField = Ext.create('Ext.form.field.Trigger', {
			id: this.getId() + '-filter',
			border: false,
			enableKeyEvents: true,
			labelWidth: 0,
			triggerCls: TYPO3.Components.PageTree.Sprites.InputClear,
			value: TYPO3.Components.PageTree.LLL.searchTermInfo,

			listeners: {
				blur: {
					fn: function (textField) {
						if (textField.getValue() === '') {
							textField.setValue(TYPO3.Components.PageTree.LLL.searchTermInfo);
							textField.inputEl.addCls(this.getId() + '-filter-defaultText');
						}
					},
					scope: this
				},

				focus: {
					fn: function (textField) {
						if (textField.getValue() === TYPO3.Components.PageTree.LLL.searchTermInfo) {
							textField.setValue('');
							textField.inputEl.removeCls(this.getId() + '-filter-defaultText');
						}
					},
					scope: this
				},

				keydown: {
					fn: this.createFilterTree,
					scope: this,
					buffer: 1000
				},

				show: {
					fn: function () { this.focus(); }
				}
			}
		});

		textField.setHideTrigger(true);
		textField.onTriggerClick = Ext.Function.bind(
			function (textField) {
				textField.setValue('');
				this.createFilterTree(textField);
			},
			this,
			[textField]
		);

		this.addButton(topPanelButton, textField);
	},

	/**
	 * Creates the entries for the new node drag zone toolbar
	 *
	 * @return {void}
	 */
	createNewNodeToolbar: function() {
		this.dragZone = Ext.create('Ext.dd.DragZone', this.getEl(), {
			ddGroup: this.ownerCt.ddGroup,
			topPanel: this.ownerCt,

			endDrag: function() {
				this.topPanel.app.getTree().dontSetOverClass = false;
			},

			getDragData: function (event) {
				this.proxyElement = document.createElement('div');
				var clickedButton = event.getTarget('.x-btn');
				if (clickedButton) {
					var node = Ext.getCmp(clickedButton.id);
				}
				if (node) {
					node.shouldCreateNewNode = true;
				}
				return {
					ddel: this.proxyElement,
					item: node,
					records: [node]
				}
			},

			onInitDrag: function() {
				this.topPanel.app.getTree().dontSetOverClass = true;
				var clickedButton = this.dragData.item;
				var cls = clickedButton.initialConfig.iconCls;

				this.proxyElement.shadow = false;
				this.proxyElement.innerHTML = '<div class="x-dd-drag-ghost-pagetree">' +
					'<span class="x-dd-drag-ghost-pagetree-icon ' + cls + '">&nbsp;</span>' +
					'<span class="x-dd-drag-ghost-pagetree-text">'  + clickedButton.title + '</span>' +
				'</div>';

				this.proxy.update(this.proxyElement);
			}
		});

			// Listens on the escape key to stop the dragging
		Ext.create('Ext.util.KeyMap', document, {
			key: Ext.EventObject.ESC,
			scope: this,
			buffer: 250,
			fn: function(event) {
				if (this.dragZone.dragging) {
					Ext.dd.DragDropManager.stopDrag(event);
					this.dragZone.onInvalidDrop(event);
				}
			}
		}, 'keydown');
	},

	/**
	 * Creates the necessary components for new node drag and drop feature
	 *
	 * @return {void}
	 */
	addDragDropNodeInsertionFeature: function() {
		var topPanelButton = Ext.create('Ext.button.Button', {
			id: this.getId() + '-button-newNode',
			cls: this.getId() + '-button',
			iconCls: TYPO3.Components.PageTree.Sprites.NewNode,
			tooltip: TYPO3.Components.PageTree.LLL.buttonNewNode
		});

		var newNodeToolbar = Ext.create('Ext.toolbar.Toolbar', {
			id: this.getId() + '-item-newNode',
			cls: this.getId() + '-item',
			listeners: {
				render: {
					fn: this.createNewNodeToolbar
				}
			}
		});

		this.dataProvider.getNodeTypes(function(response) {
			newNodeToolbar.add(response);
			newNodeToolbar.doLayout();
		}, this);

		this.addButton(topPanelButton, newNodeToolbar);
	},

	/**
	 * Adds a button to the toolbar for the refreshing feature
	 *
	 * @return {void}
	 */
	addRefreshTreeFeature: function() {
		var topPanelButton = Ext.create('Ext.button.Button', {
			id: this.getId() + '-button-refresh',
			cls: this.getId() + '-button',
			iconCls: TYPO3.Components.PageTree.Sprites.Refresh,
			tooltip: TYPO3.Components.PageTree.LLL.buttonRefresh,

			listeners: {
				click: {
					scope: this,
					fn: function() {
						this.app.getTree().refreshTree();
					}
				}
			}
		});

		this.addButton(topPanelButton);
	}
});
