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
Ext.namespace('TYPO3.Components.PageTree');

/**
 * @class TYPO3.Components.PageTree.TopPanel
 *
 * Top Panel
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.Panel
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.Components.PageTree.TopPanel = Ext.extend(Ext.Panel, {
	/**
	 * Component Id
	 *
	 * @type {String}
	 */
	id: 'typo3-pagetree-topPanel',

	/**
	 * Border
	 *
	 * @type {Boolean}
	 */
	border: false,

	/**
	 * Toolbar Object
	 *
	 * @type {Ext.Toolbar}
	 */
	tbar: new Ext.Toolbar(),

	/**
	 * Currently Clicked Toolbar Button
	 *
	 * @type {Ext.Button}
	 */
	currentlyClickedButton: null,

	/**
	 * Currently Shown Panel
	 *
	 * @type {Ext.Component}
	 */
	currentlyShownPanel: null,

	/**
	 * Filtering Indicator Item
	 *
	 * @type {Ext.Panel}
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
	 * Language Tree
	 *
	 * @cfg {TYPO3.Components.PageTree.LanguageTree}
	 */
	languageTree: null,

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
	 * Panel topPanelItems Standard Height
	 *
	 * @cfg {Integer}
	 */
	topPanelItemsHeight: 49,

	/**
	 * Language Panel Single Item Height
	 *
	 * @cfg {Integer}
	 */
	languagePanelItemHeight: 22,

	/**
	 * Language Panel All Items Height
	 *
	 * @cfg {Integer}
	 */
	languagePanelAllItemsHeight: 0,

	/**
	 * Indicator Bar Height
	 *
	 * @cfg {Integer}
	 */
	indicatorBarHeight: 35,

	/**
	 * Initializes the component
	 *
	 * @return {void}
	 */
	initComponent: function() {
		this.currentlyShownPanel = new Ext.Panel({
			id: this.id + '-defaultPanel',
			cls: this.id + '-item'
		});
		this.items = [this.currentlyShownPanel];

		TYPO3.Components.PageTree.TopPanel.superclass.initComponent.apply(this, arguments);

		this.addDragDropNodeInsertionFeature();

		if (!TYPO3.Components.PageTree.Configuration.hideFilter
			|| TYPO3.Components.PageTree.Configuration.hideFilter === '0'
		) {
			this.addFilterFeature();
		}

		if (!TYPO3.Components.PageTree.Configuration.hideLanguageSelection
			|| TYPO3.Components.PageTree.Configuration.hideLanguageSelection === '0'
		) {
			this.addLanguageSelection();
		}

		this.getTopToolbar().addItem({xtype: 'tbfill'});

		this.addRefreshTreeFeature();
	},

	/**
	 * Returns a custom button template to fix some nasty webkit issues
	 * by removing some useless wrapping html code
	 *
	 * @return {void}
	 */
	getButtonTemplate: function() {
		return new Ext.Template(
			'<div id="{4}" class="x-btn {3}"><button type="{0}">&nbsp;</button></div>'
		);
	},

	/**
	 * Returns a custom button template for the language button to fix some nasty webkit issues
	 * by removing some useless wrapping html code
	 *
	 * @return {void}
	 */
	getLanguageButtonTemplate: function(languageLabel) {
		return new Ext.Template(
			'<div id="{4}" class="x-btn {3}"><button type="{0}">&nbsp;</button>' + languageLabel + '</div>'
		);
	},

	/**
	 * Adds a button to the components toolbar with a related component
	 *
	 * @param {Object} button
	 * @param {Object} connectedWidget
	 * @return {void}
	 */
	addButton: function(button, connectedWidget) {
		button.template = this.getButtonTemplate();
		if (!button.hasListener('click')) {
			button.on('click', this.topbarButtonCallback);
		}

		if (connectedWidget) {
			connectedWidget.hidden = true;
			button.connectedWidget = connectedWidget;
			this.add(connectedWidget);
		}

		this.getTopToolbar().addItem(button);
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
		var topPanelItems = Ext.getCmp('typo3-pagetree-topPanelItems');
		var temporaryMountPointInfoIndicator = Ext.getCmp('typo3-pagetree-indicatorBar-temporaryMountPoint');
		var filterButton = Ext.getCmp('typo3-pagetree-topPanel-button-filter');

		var indicatorBarHeight = 0;
		if (topPanel.filteringIndicator) {
			indicatorBarHeight += topPanel.filteringIndicator.getHeight();
		}
		if (temporaryMountPointInfoIndicator) {
			indicatorBarHeight += temporaryMountPointInfoIndicator.getHeight();
		}

		topPanel.currentlyShownPanel.hide();
		if (topPanel.currentlyClickedButton) {
			topPanel.currentlyClickedButton.toggle(false);
		}

		if (topPanel.currentlyClickedButton === this) {
			topPanel.currentlyClickedButton = null;
			topPanelItems.setHeight(topPanel.topPanelItemsHeight + indicatorBarHeight);
			topPanel.currentlyShownPanel = topPanel.get(topPanel.id + '-defaultPanel');
		} else {
			this.toggle(true);
			topPanel.currentlyClickedButton = this;
			topPanel.currentlyShownPanel = this.connectedWidget;
			if (this.connectedWidget.id === topPanel.id + '-languageWrap') {
				topPanelItems.setHeight(topPanel.topPanelItemsHeight - topPanel.languagePanelItemHeight + topPanel.languagePanelAllItemsHeight + indicatorBarHeight);
			} else {
				topPanelItems.setHeight(topPanel.topPanelItemsHeight + indicatorBarHeight);
			}
		}

		topPanel.currentlyShownPanel.show();
	},

	/**
	 * Loads the filtering tree nodes with the given search word
	 *
	 * @param {Ext.form.TextField} textField
	 * @return {void}
	 */
	createFilterTree: function(textField) {
		var searchWord = textField.getValue();
		var isNumber = TYPO3.Utility.isNumber(searchWord);
		var hasMinLength = (searchWord.length > 2 || searchWord.length <= 0);
		if ((!hasMinLength && !isNumber) || searchWord === this.filteringTree.searchWord) {
			return;
		}

		this.filteringTree.searchWord = searchWord;
		if (this.filteringTree.searchWord === '') {
			if (this.filteringTree.language == '0' || !this.languageTree) {
				this.app.activeTree = this.tree;
			} else {
				var selectedNode = this.app.getSelected();
				this.languageTree.language = this.filteringTree.language;
				this.app.activeTree = this.languageTree
			}

			textField.setHideTrigger(true);
			this.filteringTree.hide();
			if (this.filteringTree.language == '0' || !this.languageTree) {
				this.tree.show().refreshTree(function() {
					textField.focus(false, 500);
				}, this);
			} else {
				this.app.ownerCt.getEl().mask('', 'x-mask-loading-message');
				this.app.ownerCt.getEl().addClass('t3-mask-loading');
				this.languageTree.show().refreshTree(function() {
					if (selectedNode) {
						this.app.select(selectedNode.attributes.nodeData.id, false);
					}
					textField.focus(false, 500);
					this.app.ownerCt.getEl().unmask();
				}, this);
			}

			if (this.filteringIndicator) {
				this.app.removeIndicator(this.filteringIndicator);
				this.filteringIndicator = null;
			}
		} else {
			var selectedNode = this.app.getSelected();
			this.app.activeTree = this.filteringTree;

			if (!this.filteringIndicator) {
				this.filteringIndicator = this.app.addIndicator(
					this.createIndicatorItem(textField)
				);
			}

			textField.setHideTrigger(false);
			this.tree.hide();
			this.app.ownerCt.getEl().mask('', 'x-mask-loading-message');
			this.app.ownerCt.getEl().addClass('t3-mask-loading');
			this.filteringTree.show().refreshTree(function() {
				if (selectedNode) {
					this.app.select(selectedNode.attributes.nodeData.id, false);
				}
				textField.focus();
				this.app.ownerCt.getEl().unmask();
			}, this);
		}

		this.doLayout();
	},

	/**
	 * Adds an indicator item to the page tree application for the filtering feature
	 *
	 * @param {Ext.form.TextField} textField
	 * @return {void}
	 */
	createIndicatorItem: function(textField) {
		return {
			border: false,
			id: this.app.id + '-indicatorBar-filter',
			cls: this.app.id + '-indicatorBar-item',
			html: '<p>' +
					'<span id="' + this.app.id + '-indicatorBar-filter-info' + '" ' +
						'class="' + this.app.id + '-indicatorBar-item-leftIcon ' +
							TYPO3.Components.PageTree.Sprites.Info + '">&nbsp;' +
					'</span>' +
					'<span id="' + this.app.id + '-indicatorBar-filter-clear' + '" ' +
						'class="' + this.app.id + '-indicatorBar-item-rightIcon ' + '">X' +
					'</span>' +
					TYPO3.Components.PageTree.LLL.activeFilterMode +
				'</p>',
			filteringTree: this.filteringTree,

			listeners: {
				afterrender: {
					scope: this,
					fn: function() {
						var element = Ext.fly(this.app.id + '-indicatorBar-filter-clear');
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
		var topPanelButton = new Ext.Button({
			id: this.id + '-button-filter',
			cls: this.id + '-button',
			iconCls: TYPO3.Components.PageTree.Sprites.Filter,
			tooltip: TYPO3.Components.PageTree.LLL.buttonFilter
		});

		var textField = new Ext.form.TriggerField({
			id: this.id + '-filter',
			enableKeyEvents: true,
			triggerClass: TYPO3.Components.PageTree.Sprites.InputClear,
			value: TYPO3.Components.PageTree.LLL.searchTermInfo,

			listeners: {
				blur: {
					scope: this,
					fn:function(textField) {
						if (textField.getValue() === '') {
							textField.setValue(TYPO3.Components.PageTree.LLL.searchTermInfo);
							textField.addClass(this.id + '-filter-defaultText');
						}
					}
				},

				focus: {
					scope: this,
					fn: function(textField) {
						if (textField.getValue() === TYPO3.Components.PageTree.LLL.searchTermInfo) {
							textField.setValue('');
							textField.removeClass(this.id + '-filter-defaultText');
						}
					}
				},

				keydown: {
					fn: this.createFilterTree,
					scope: this,
					buffer: 1000
				}
			}
		});

		textField.setHideTrigger(true);
		textField.onTriggerClick = function() {
			textField.setValue('');
			this.createFilterTree(textField);
		}.createDelegate(this);

		var topPanelWidget = new Ext.Panel({
			border: false,
			id: this.id + '-filterWrap',
			cls: this.id + '-item',
			items: [textField],

			listeners: {
				show: {
					scope: this,
					fn: function(panel) {
						panel.get(this.id + '-filter').focus();
					}
				}
			}
		});

		this.addButton(topPanelButton, topPanelWidget);
	},

	/**
	 * Creates the entries for the new node drag zone toolbar
	 *
	 * @return {void}
	 */
	createNewNodeToolbar: function() {
		this.dragZone = new Ext.dd.DragZone(this.getEl(), {
			ddGroup: this.ownerCt.ddGroup,
			topPanel: this.ownerCt,

			endDrag: function() {
				this.topPanel.app.activeTree.dontSetOverClass = false;
			},

			getDragData: function(event) {
				this.proxyElement = document.createElement('div');

				var node = Ext.getCmp(event.getTarget('.x-btn').id);
				node.shouldCreateNewNode = true;

				return {
					ddel: this.proxyElement,
					item: node
				}
			},

			onInitDrag: function() {
				this.topPanel.app.activeTree.dontSetOverClass = true;
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

			// listens on the escape key to stop the dragging
		(new Ext.KeyMap(document, {
			key: Ext.EventObject.ESC,
			scope: this,
			buffer: 250,
			fn: function(event) {
				if (this.dragZone.dragging) {
					Ext.dd.DragDropMgr.stopDrag(event);
					this.dragZone.onInvalidDrop(event);
				}
			}
		}, 'keydown'));
	},

	/**
	 * Creates the necessary components for new node drag and drop feature
	 *
	 * @return {void}
	 */
	addDragDropNodeInsertionFeature: function() {
		var newNodeToolbar = new Ext.Toolbar({
			border: false,
			id: this.id + '-item-newNode',
			cls: this.id + '-item',

			listeners: {
				render: {
					fn: this.createNewNodeToolbar
				}
			}
		});

		this.dataProvider.getNodeTypes(function(response) {
			for (var i = 0; i < response.length; ++i) {
				response[i].template = this.getButtonTemplate();
				newNodeToolbar.addItem(response[i]);
			}
			newNodeToolbar.doLayout();
		}, this);

		var topPanelButton = new Ext.Button({
			id: this.id + '-button-newNode',
			cls: this.id + '-button',
			iconCls: TYPO3.Components.PageTree.Sprites.NewNode,
			tooltip: TYPO3.Components.PageTree.LLL.buttonNewNode
		});

		this.addButton(topPanelButton, newNodeToolbar);
	},

	/**
	 * Adds a button to the toolbar for the refreshing feature
	 *
	 * @return {void}
	 */
	addRefreshTreeFeature: function() {
		var topPanelButton = new Ext.Button({
			id: this.id + '-button-refresh',
			cls: this.id + '-button',
			iconCls: TYPO3.Components.PageTree.Sprites.Refresh,
			tooltip: TYPO3.Components.PageTree.LLL.buttonRefresh,

			listeners: {
				click: {
					scope: this,
					fn: function() {
						this.app.activeTree.refreshTree();
					}
				}
			}
		});

		this.addButton(topPanelButton);
	},

	/**
	 * Adds buttons to the toolbar for language selection
	 *
	 * @return {void}
	 */
	addLanguageSelection: function() {
		this.topPanelLanguageButton = new Ext.Button({
			id: this.id + '-button-language-top',
			cls: this.id + '-button',
			iconCls: 't3-icon-flags-multiple',
			tooltip: TYPO3.Components.PageTree.LLL.buttonLanguage,
		}).hide();

		var topPanelWidget = new Ext.Panel({
			border: false,
			id: this.id + '-languageWrap',
			cls: this.id + '-item-languageWrap'
		});

		this.addButton(this.topPanelLanguageButton, topPanelWidget);

		this.dataProvider.getLanguages(function(response) {
			languages = Ext.util.JSON.decode(response, true);
			var i = 0;

			languages.each(function(record) {
				if (i === 0) {
					Ext.getCmp(this.id + '-button-language-top').setIconClass(record['iconCls']);
					Ext.getCmp(this.id + '-button-language-top').setTooltip(TYPO3.Components.PageTree.LLL.activeLanguage + ' ' + record['languageLabel']);
				}
				var topPanelButton = new Ext.Button({
					id: this.id + '-button-language-'+record['icon'],
					cls: this.id + '-button',
					iconCls: record['iconCls'],
					tooltip: TYPO3.Components.PageTree.LLL.buttonLanguage + ' ' + record['languageLabel'],
					width:'98%',
					template: this.getLanguageButtonTemplate(record['languageLabel']),

					listeners: {
						click: {
							scope: this,
							fn: function() {
								Ext.getCmp(this.id + '-button-language-top').fireEvent('click');
								Ext.getCmp(this.id + '-button-language-top').setIconClass(record['iconCls']);
								Ext.getCmp(this.id + '-button-language-top').setTooltip(TYPO3.Components.PageTree.LLL.activeLanguage + ' ' + record['languageLabel']);
								var selectedLanguage = record['lid'];
								this.filteringTree.language = selectedLanguage;
								if (selectedLanguage == 0) {
									if (this.filteringIndicator) {
										this.app.ownerCt.getEl().mask('', 'x-mask-loading-message');
										this.app.ownerCt.getEl().addClass('t3-mask-loading');
										this.filteringTree.show().refreshTree(function() {
											if (selectedNode) {
												this.app.select(selectedNode.attributes.nodeData.id, false);
											}
											this.app.ownerCt.getEl().unmask();
										}, this);
									} else {
										this.app.activeTree = this.tree;
										this.languageTree.hide();
										this.tree.show().refreshTree(function() {
											//textField.focus(false, 500);
										}, this);
									}
								} else {
									if (this.filteringIndicator) {
										this.app.ownerCt.getEl().mask('', 'x-mask-loading-message');
										this.app.ownerCt.getEl().addClass('t3-mask-loading');
										this.filteringTree.show().refreshTree(function() {
											if (selectedNode) {
												this.app.select(selectedNode.attributes.nodeData.id, false);
											}
											this.app.ownerCt.getEl().unmask();
										}, this);
									} else {
										this.tree.hide();
										this.languageTree.language = selectedLanguage;
										var selectedNode = this.app.getSelected();
										this.app.activeTree = this.languageTree;
										this.app.ownerCt.getEl().mask('', 'x-mask-loading-message');
										this.app.ownerCt.getEl().addClass('t3-mask-loading');
										this.languageTree.show().refreshTree(function() {
											if (selectedNode) {
												this.app.select(selectedNode.attributes.nodeData.id, false);
											}
											this.app.ownerCt.getEl().unmask();
										}, this);
									}
								}
							}
						}
					}
				});

				Ext.getCmp(this.id + '-languageWrap').add(topPanelButton);
				Ext.getCmp(this.id + '-languageWrap').doLayout();
				i++;
			}, this);

			this.languagePanelAllItemsHeight = this.languagePanelItemHeight * languages.length;
			Ext.getCmp(this.id + '-languageWrap').setHeight(this.languagePanelAllItemsHeight);
			Ext.getCmp(this.id + '-languageWrap').doLayout();

			if (languages.length > 1) {
				Ext.getCmp(this.id + '-button-language-top').show();
			} else {
				if (languages[0]['lid'] != 0) {
					this.tree.hide();
					this.languageTree.language = languages[0]['lid'];
					this.filteringTree.language = languages[0]['lid'];
					var selectedNode = this.app.getSelected();
					this.app.activeTree = this.languageTree;
					this.app.ownerCt.getEl().mask('', 'x-mask-loading-message');
					this.app.ownerCt.getEl().addClass('t3-mask-loading');
					this.languageTree.show().refreshTree(function() {
						if (selectedNode) {
							this.app.select(selectedNode.attributes.nodeData.id, false);
						}
						this.app.ownerCt.getEl().unmask();
					}, this);
				}
			}
		}, this);
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.TopPanel', TYPO3.Components.PageTree.TopPanel);
