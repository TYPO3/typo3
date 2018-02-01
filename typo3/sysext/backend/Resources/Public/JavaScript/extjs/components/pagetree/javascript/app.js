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
 * @class TYPO3.Components.PageTree.App
 *
 * Page tree main application that controls setups the components
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.Panel
 */
TYPO3.Components.PageTree.App = Ext.extend(Ext.Panel, {
  /**
   * Panel id
   *
   * @type {String}
   */
  id: 'typo3-pagetree',

  /**
   * Border
   *
   * @type {Boolean}
   */
  border: false,

  /**
   * Layout Type
   *
   * @type {String}
   */
  layout: 'fit',

  /**
   * Monitor resize
   *
   * @type {Boolean}
   */
  monitorResize: true,

  /**
   * Active tree
   *
   * @type {TYPO3.Components.PageTree.Tree}
   */
  activeTree: null,

  /**
   * Main pagetree
   *
   * @type {TYPO3.Components.PageTree.Tree}
   */
  mainTree: null,

  /**
   * Local cache for node paths
   *
   * @type {object}
   */
  nodePaths: {},

  /**
   * Initializes the application
   *
   * Set's the necessary language labels, configuration options and sprite icons by an
   * external call and initializes the needed components.
   *
   * @return {void}
   */
  initComponent: function() {
    TYPO3.Components.PageTree.DataProvider.loadResources(function(response) {
      TYPO3.Components.PageTree.LLL = response['LLL'];
      TYPO3.Components.PageTree.Configuration = response['Configuration'];
      TYPO3.Components.PageTree.Icons = response['Icons'];

      this.mainTree = this.activeTree = new TYPO3.Components.PageTree.Tree({
        id: this.id + '-tree',
        deletionDropZoneId: this.id + '-deletionDropZone',
        ddGroup: this.id,
        stateful: true,
        stateId: 'Pagetree' + TYPO3.Components.PageTree.Configuration.temporaryMountPoint,
        stateEvents: [],
        autoScroll: true,
        autoHeight: true,
        autoWidth: true,
        plugins: [new Ext.ux.state.TreePanel()],
        commandProvider: TYPO3.Components.PageTree.Actions,
        treeDataProvider: TYPO3.Components.PageTree.DataProvider,
        monitorResize: true,
        app: this,
        listeners: {
          resize: {
            fn: function() {
              this.doLayout(true);
              TYPO3.Backend.doLayout();
            },
            scope: this,
            buffer: 250
          }
        }
      });

      var filteringTree = new TYPO3.Components.PageTree.FilteringTree({
        id: this.id + '-filteringTree',
        deletionDropZoneId: this.id + '-deletionDropZone',
        ddGroup: this.id,
        autoScroll: true,
        autoHeight: false,
        commandProvider: TYPO3.Components.PageTree.Actions,
        treeDataProvider: TYPO3.Components.PageTree.DataProvider,
        app: this
      }).hide();

      var topPanel = new TYPO3.Components.PageTree.TopPanel({
        cls: this.id + '-toppanel',
        dataProvider: TYPO3.Components.PageTree.DataProvider,
        filteringTree: filteringTree,
        ddGroup: this.id,
        tree: this.mainTree,
        app: this
      });

      var deletionDropZone = new TYPO3.Components.PageTree.DeletionDropZone({
        id: this.id + '-deletionDropZone',
        commandProvider: TYPO3.Components.PageTree.Actions,
        ddGroup: this.id,
        app: this,
        region: 'south',
        height: 35
      });

      var topPanelItems = new Ext.Panel({
        id: this.id + '-topPanelItems',
        cls: this.id + '-toppanel-items',
        border: false,
        region: 'north',
        height: 65,
        items: [
          topPanel, {
            border: false,
            id: this.id + '-indicatorBar'
          }
        ]
      });

      this.add({
        layout: 'border',
        border: false,
        monitorResize: true,
        items: [
          topPanelItems,
          {
            border: false,
            id: this.id + '-treeContainer',
            region: 'center',
            layout: 'fit',
            monitorResize: true,
            items: [this.mainTree, filteringTree]
          },
          deletionDropZone
        ]
      });

      if (TYPO3.Components.PageTree.Configuration.temporaryMountPoint) {
        topPanelItems.on('afterrender', function() {
          this.addTemporaryMountPointIndicator();
        }, this);
      }

      this.doLayout();
      this.selectRequestedPageId();

    }, this);

    TYPO3.Components.PageTree.App.superclass.initComponent.apply(this, arguments);
  },

  /**
   * Is true once initComponent has been called for rendering
   *
   * @returns {boolean}
   */
  isInitialized: function() {
    return this.mainTree !== null;
  },

  /**
   * Adds the temporary mount point indicator item
   *
   * @return {void}
   */
  addTemporaryMountPointIndicator: function() {
    this.temporaryMountPointInfoIndicator = this.addIndicator({
      border: false,
      id: this.id + '-indicatorBar-temporaryMountPoint',
      cls: this.id + '-indicatorBar-item',
      scope: this,
      listeners: {
        afterrender: {
          fn: function() {
            var element = Ext.fly(this.id + '-indicatorBar-temporaryMountPoint-clear');
            var me = this;
            element.on('click', function() {
              top.TYPO3.Storage.Persistent.unset('pageTree_temporaryMountPoint').done(
                function() {
                  TYPO3.Components.PageTree.Configuration.temporaryMountPoint = null;
                  me.removeIndicator(me.temporaryMountPointInfoIndicator);
                  me.getTree().refreshTree();
                  me.getTree().stateId = 'Pagetree';
                }
              );
            }, this);
          },
          scope: this
        }
      },
      html: '' +
      '<div class="alert alert-info">' +
      '<div class="media">' +
      '<div class="media-left">' +
      TYPO3.Components.PageTree.Icons.Info +
      '</div>' +
      '<div class="media-body">' +
      TYPO3.Components.PageTree.Configuration.temporaryMountPoint +
      '</div>' +
      '<div class="media-right">' +
      '<a href="#" id="' + this.id + '-indicatorBar-temporaryMountPoint-clear">' +
      TYPO3.Components.PageTree.Icons.Close +
      '</a>' +
      '</div>' +
      '</div>' +
      '</div>'
    });
  },

  /**
   * Adds an indicator item
   *
   * @param {Object} component
   * @return {void}
   */
  addIndicator: function(component) {
    if (component.listeners && component.listeners.afterrender) {
      component.listeners.afterrender.fn = component.listeners.afterrender.fn.createSequence(
        this.afterTopPanelItemAdded, this
      );
    } else {
      if (component.listeners) {
        component.listeners = {}
      }

      component.listeners.afterrender = {
        scope: this,
        fn: this.afterTopPanelItemAdded
      }
    }

    var indicator = Ext.getCmp(this.id + '-indicatorBar').add(component);
    this.doLayout();

    return indicator;
  },

  /**
   * Recalculates the top panel items height after an indicator was added
   *
   * @param {Ext.Component} component
   * @return {void}
   */
  afterTopPanelItemAdded: function(component) {
    var topPanelItems = Ext.getCmp(this.id + '-topPanelItems');
    topPanelItems.setHeight(topPanelItems.getHeight() + component.getHeight());
    this.doLayout();
  },

  /**
   * Removes an indicator item from the indicator bar
   *
   * @param {Ext.Component} component
   * @return {void}
   */
  removeIndicator: function(component) {
    var topPanelItems = Ext.getCmp(this.id + '-topPanelItems');
    topPanelItems.setHeight(topPanelItems.getHeight() - component.getHeight());
    Ext.getCmp(this.id + '-indicatorBar').remove(component);
    this.doLayout();
  },

  /**
   * Compatibility method that calls refreshTree()
   *
   * @return {void}
   */
  refresh: function() {
    this.refreshTree();
  },

  /**
   * Another compatibility method that calls refreshTree()
   *
   * @return {void}
   */
  refresh_nav: function() {
    this.refreshTree();
  },

  /**
   * Refreshes the tree and then selects the node defined by fsMod.recentIds['web']
   *
   * @return {void}
   */
  refreshTree: function() {
    this.activeTree.refreshTree(function() {
      this.selectRequestedPageId();
    }, this);
  },

  /**
   * Selects the node defined by fsMod.recentIds['web']
   *
   * @return {void}
   */
  selectRequestedPageId: function() {
    if (!isNaN(fsMod.recentIds['web']) && fsMod.recentIds['web'] !== '') {
      this.select(fsMod.recentIds['web']);
    }
  },

  /**
   * Returns the current active tree
   *
   * @return {TYPO3.Components.PageTree.Tree}
   */
  getTree: function() {
    return this.activeTree;
  },

  /**
   * Selects a node on the main tree defined by the page id.
   *
   * @param {int} pageId
   */
  select: function(pageId) {
    this.selectPageId(pageId);
  },

  selectPageId: function(pageId, allResults) {
    this.invokePageId(pageId, Ext.createDelegate(this.mainTree.selectPath, this.mainTree), allResults);
  },

  expandPageId: function(pageId, allResults) {
    this.invokePageId(pageId, Ext.createDelegate(this.mainTree.expandPath, this.mainTree), allResults);
  },

  /**
   * @param {int} pageId
   * @param {Function} callback
   * @param {Boolean} allResults
   */
  invokePageId: function(pageId, callback, allResults) {
    if (typeof this.nodePaths[pageId] !== 'undefined') {
      this.invokeNodePaths(this.nodePaths[pageId], pageId, callback, allResults);
    } else {
      var handler = function(nodePaths) {
        this.nodePaths[pageId] = nodePaths;
        this.invokeNodePaths(nodePaths, pageId, callback, allResults);
      };
      TYPO3.Components.PageTree.Commands.getNodePaths(pageId, handler, this);
    }
  },

  invokeNodePaths: function(nodePaths, pageId, callback, allResults) {
    if (!nodePaths.length) {
      return;
    }
    if (!allResults) {
      nodePaths = [nodePaths[0]];
    }
    var self = this;
    Ext.each(nodePaths, function(nodePath) {
      callback('/root/' + nodePath.join('/'), 'id', function() {
        self.mainTree.selectNode(self.mainTree.getSelectionModel().getSelectedNode());
      });
    });
  },

  /**
   * Returns the currently selected node of the currently active tree
   *
   * @return {Ext.tree.TreeNode}
   */
  getSelected: function() {
    var node = this.getTree().getSelectionModel().getSelectedNode();
    return node ? node : null;
  }
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.App', TYPO3.Components.PageTree.App);
