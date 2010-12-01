Ext.namespace('TYPO3.Components.PageTree');

TYPO3.Components.PageTree.FeaturePanel = Ext.extend(Ext.Panel, {
	id: 'topPanel',
	border: false,
	height: 60,

	currentlyClickedButton: null,
	currentlyShownPanel: null,

	tbar: new Ext.Toolbar(),

	pageTree: null,
	filterTree: null,

	initComponent: function() {
		this.filterTree = this.addFilterFeature();
		this.addDragDropNodeInsertionFeature();
		this.addRefreshTreeFeature();
		this.addLanguageSelection();

		TYPO3.Components.PageTree.FeaturePanel.superclass.initComponent.apply(this, arguments);
	},

	// This is the callback method which toggles the sub-menu if you click
	// on a top-bar item.
	topbarButtonCallback: function() {
		if (this.ownerCt.ownerCt.currentlyClickedButton === null) {
			// first click, nothing selected yet
			this.toggle(true);
			this.connectedWidget.show();

			this.ownerCt.ownerCt.currentlyClickedButton = this;
			this.ownerCt.ownerCt.currentlyShownPanel = this.connectedWidget;
		} else {
			if (this.ownerCt.ownerCt.currentlyClickedButton === this) {
				// second click onto currently clicked button
				this.ownerCt.ownerCt.currentlyClickedButton.toggle(false);
				this.ownerCt.ownerCt.currentlyShownPanel.hide();
				this.ownerCt.ownerCt.currentlyClickedButton = null;
				this.ownerCt.ownerCt.currentlyShownPanel = null;
			} else {
				// toggling a view
				this.ownerCt.ownerCt.currentlyClickedButton.toggle(false);
				this.ownerCt.ownerCt.currentlyShownPanel.hide();

				this.toggle(true);
				this.connectedWidget.show();
				this.ownerCt.ownerCt.currentlyClickedButton = this;
				this.ownerCt.ownerCt.currentlyShownPanel = this.connectedWidget;
			}
		}
	},

	addWidget: function(button, connectedWidget) {
		button.connectedWidget = connectedWidget;
		if (!button.hasListener('click')) {
			button.addListener('click', this.topbarButtonCallback);
		}

		this.getTopToolbar().addItem(button);
		this.add(connectedWidget);
		this.doLayout();
	},

	/**
	 * Add the "Filter" feature to the top panel and the panel.
	 */
	addFilterFeature: function() {
		// Callback method displaying the results
		var filterCallback = function(textField) {
			var filterString = textField.getValue();
			if (filterString != '') {
				this.pageTree.dataProvider.getFilteredTree(filterString, function(results) {
					this.filterTree.setRootNode({
						id: 'root',
						children: results
					});
					this.pageTree.tree.hide();
					this.filterTree.expandAll();
					this.filterTree.show();
					this.doLayout();
				}.createDelegate(this));
			} else {
				this.filterTree.hide();
				this.pageTree.tree.show();
				this.doLayout();
			}
		};

		this.pageTree.dataProvider.getSpriteIconClasses('actions-system-tree-search-open', function(result) {
			// Top Panel
			var topPanelButton = new Ext.Button({
				//text: 'filter',
				cls: 'topPanel-button ' + result
			});

			var topPanelWidget = new Ext.Panel({
				border: false,
				hidden: true,
				cls: 'typo3-pagetree-topbar-item',
				items: [
					new Ext.form.TextField({
						id: 'typo3-pagetree-topPanel-filter',
						enableKeyEvents: true,
						listeners: {
							keypress: {
								fn: filterCallback,
								scope: this,
								buffer: 250
							}
						}
					})
				]
			});
			this.addWidget(topPanelButton, topPanelWidget);
		}, this);

		// Tree initialization
		return new Ext.tree.TreePanel({
			anchor: '100% 100%',
			border: false,
			autoScroll: true,
			animate: false,
			id: 'typo3-pagetree-filterTree',
			rootVisible: false,
			hidden:true,
			root: {
				id: 'root',
				text: 'Root',
				expanded: true
			}
		});
	},

	/**
	 * Add drag and drop node insertion.
	 * @internal
	 */
	addDragDropNodeInsertionFeature: function() {
		// Initialization of the "new node" toolbar, via a dataProvider.
		var newNodeToolbar = new Ext.Toolbar({
			border: false,
			id: 'typo3-pagetree-topbar-new',
			cls: 'typo3-pagetree-topbar-item',
			hidden: true,
			anchor: '100% 100%',
			autoWidth: true,
			listeners: {
				render: function() {
					new Ext.dd.DragZone(newNodeToolbar.getEl(), {
						ddGroup: 'TreeDD',
						getDragData: function(e) {
							var clickedButton = Ext.ComponentMgr.get(e.getTarget('.x-btn').id);
							clickedButton.shouldCreateNewNode = true;

							this.ddel = document.createElement('div');
							return {ddel: this.ddel, item: clickedButton}
						},
						onInitDrag: function() {
							var clickedButton = this.dragData.item;
							this.ddel.innerHTML = '<span class="' + clickedButton.initialConfig.cls + '"></span>' + clickedButton.title;
							this.ddel.style.width = '150px';
							this.proxy.update(this.ddel);
						}
					});
				}
			}
		});

		// Load data from server
		if (this.pageTree.dataProvider.getNodeTypes) {
			// Only call the server if the server implements getNodeTypes();
			this.pageTree.dataProvider.getNodeTypes(function(response) {
				var length = response.length;
				var item = null;
				for (var i = 0; i < length; ++i) {
					item = new Ext.Toolbar.Button(response[i]);
					newNodeToolbar.addItem(item);
				}
				newNodeToolbar.doLayout();
			});
		}

		this.pageTree.dataProvider.getSpriteIconClasses('actions-page-new', function(result) {
			var topPanelButton = new Ext.Button({
				cls: 'topPanel-button ' + result
			});

			this.addWidget(topPanelButton, newNodeToolbar);
		}, this);
	},

	/**
	 * Adds a language selection menu to the top bar
	 * @internal
	 */
	addLanguageSelection: function() {
		// Initialization of the "new node" toolbar, via a dataProvider.
		(new Ext.Toolbar({
			border: false,
			id: this.id + '-topbar-languageSelection',
			cls: this.id + '-topbar-item',
			hidden: true,
			anchor: '100% 100%',
			autoWidth: true
		}));


	},

	/**
	 * Add the "Refresh Tree" feature to the top panel
	 */
	addRefreshTreeFeature: function() {
		this.pageTree.dataProvider.getSpriteIconClasses('actions-system-refresh', function(result) {
			// Top Panel
			var topPanelButton = new Ext.Button({
				cls: 'topPanel-button ' + result,
				listeners: {
					scope: this.pageTree.tree,
					'click': {
						fn: this.pageTree.tree.refreshTree
					}
				}
			});

			this.getTopToolbar().addItem(topPanelButton);
		}, this);
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.FeaturePanel', TYPO3.Components.PageTree.FeaturePanel);