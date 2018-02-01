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
 * @class TYPO3.Components.PageTree.DeletionDropZone
 *
 * Deletion Drop Zone
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.Panel
 */
TYPO3.Components.PageTree.DeletionDropZone = Ext.extend(Ext.Panel, {
  /**
   * Border
   *
   * @type {Boolean}
   */
  border: true,

  /**
   * Hide the drop zone initially
   *
   * @type {Boolean}
   */
  hidden: true,

  /**
   * Command Provider
   *
   * @cfg {Object}
   */
  commandProvider: null,

  /**
   * Drag and Drop Group
   *
   * @cfg {String}
   */
  ddGroup: '',

  /**
   * Main Application
   *
   * @cfg {TYPO3.Components.PageTree.App}
   */
  app: null,

  /**
   * Removed node had a previous sibling
   *
   * @type {Boolean}
   */
  isPreviousSibling: false,

  /**
   * Removed node
   *
   * @type {Ext.tree.TreeNode}
   */
  previousNode: null,

  /**
   * Click Handler for the recovery text
   *
   * @type {Function}
   */
  textClickHandler: null,

  /**
   * Amount of drops (used to prevent early hiding of the box)
   *
   * @type {int}
   */
  amountOfDrops: 0,

  /**
   * Listeners
   *
   * The "afterrender" event creates the drop zone
   */
  listeners: {
    afterrender: {
      fn: function() {
        this.createDropZone();

        this.getEl().on('mouseout', function(e) {
          if (!e.within(this.getEl(), true)) {
            this.removeClass(this.id + '-activateProxyOver');
            if (!this.app.activeTree.shouldCopyNode) {
              this.app.activeTree.copyHint.show();
            }
          }
        }, this);
      }
    },

    beforehide: {
      fn: function() {
        if (this.textClickHandler) {
          return false;
        }
      }
    }
  },

  /**
   * Initializes the component
   *
   * @return {void}
   */
  initComponent: function() {
    this.html = '<p><span id="' + this.id + '-icon">' + TYPO3.Components.PageTree.Icons.TrashCan + '</span><span id="' + this.id + '-text">' +
      TYPO3.Components.PageTree.LLL.dropToRemove + '</span></p>';

    TYPO3.Components.PageTree.DeletionDropZone.superclass.initComponent.apply(this, arguments);
  },


  /**
   * Creates the drop zone and it's functionality
   *
   * @return {void}
   */
  createDropZone: function() {
    (new Ext.dd.DropZone(this.getEl(), {
      ddGroup: this.ddGroup,

      notifyOver: function(ddProxy, e) {
        ddProxy.setDragElPos(e.xy[0], e.xy[1] - 60);
        return this.id + '-proxyOver';
      }.createDelegate(this),

      notifyEnter: function() {
        this.addClass(this.id + '-activateProxyOver');
        if (!this.app.activeTree.shouldCopyNode) {
          this.app.activeTree.copyHint.hide();
        }

        return this.id + '-proxyOver';
      }.createDelegate(this),

      notifyDrop: function(ddProxy, e, n) {
        var node = n.node;
        if (!node) {
          return;
        }

        var tree = node.ownerTree;
        var nodeHasChildNodes = (node.hasChildNodes() || node.isExpandable());

        var callback = null;
        if (!top.TYPO3.configuration.inWorkspace && !nodeHasChildNodes) {
          callback = this.setRecoverState.createDelegate(this);
        }

        if (nodeHasChildNodes) {
          node.ownerTree.commandProvider.confirmDelete(node, tree, callback, true);
        } else {
          node.ownerTree.commandProvider.deleteNode(node, tree, callback);
        }
      }.createDelegate(this)
    }));
  },

  /**
   * Sets the drop zone into the recovery state
   *
   * @param {Ext.tree.TreeNode} node
   * @param {TYPO3.Components.PageTree.Tree} tree
   * @param {Boolean} succeeded
   * @return {void}
   */
  setRecoverState: function(node, tree, succeeded) {
    if (!succeeded) {
      this.toOriginState();
      return;
    }

    this.show();
    this.setHeight(50);
    this.updateIcon(TYPO3.Components.PageTree.Icons.TrashCanRestore);
    this.updateText(
      node.text + '<br />' +
      '<span class="' + this.id + '-restore">' +
      '<span class="' + this.id + '-restoreText">' +
      TYPO3.Components.PageTree.LLL.dropZoneElementRemoved +
      '</span>' +
      '</span>',
      false
    );
    this.app.doLayout();

    ++this.amountOfDrops;
    (function() {
      if (!--this.amountOfDrops) {
        this.toOriginState();
      }
    }).defer(10000, this);

    this.textClickHandler = this.restoreNode.createDelegate(this, [node, tree]);
    Ext.get(this.id + '-text').on('click', this.textClickHandler);

    this.isPreviousSibling = false;
    this.previousNode = node.parentNode;
    if (node.previousSibling) {
      this.previousNode = node.previousSibling;
      this.isPreviousSibling = true;
    }
  },

  /**
   * Updates the drop zone text label
   *
   * @param {String} text
   * @param {Boolean} animate
   * @return {void}
   */
  updateText: function(text, animate) {
    animate = animate || false;

    var elementText = Ext.get(this.id + '-text');
    if (animate) {
      elementText.animate({opacity: {to: 0}}, 1, function(elementText) {
        elementText.update(text);
        elementText.setStyle('opacity', 1);
      });
    } else {
      elementText.update(text);
    }
  },

  /**
   * Updates the drop zone icon with another icon
   *
   * @param {String} icon
   * @return {void}
   */
  updateIcon: function(icon) {
    Ext.get(this.id + '-icon').update(icon);
  },

  /**
   * Resets the drop zone to the initial state
   *
   * @param {Boolean} hide
   * @return {void}
   */
  toOriginState: function(hide) {
    if (hide !== false) {
      hide = true;
    }

    Ext.get(this.id + '-text').un('click', this.textClickHandler);
    this.previousNode = this.textClickHandler = null;
    this.isPreviousSibling = false;

    if (hide && !this.app.activeTree.dragZone.dragging) {
      this.hide();
    }

    this.setHeight(35);
    this.updateText(TYPO3.Components.PageTree.LLL.dropToRemove, false);
    this.updateIcon(TYPO3.Components.PageTree.Icons.TrashCan);
    this.app.doLayout();
  },

  /**
   * Restores the last removed node
   *
   * @param {Ext.tree.TreeNode} node
   * @param {TYPO3.Components.PageTree.Tree} tree
   * @return {void}
   */
  restoreNode: function(node, tree) {
    if (this.isPreviousSibling) {
      this.commandProvider.restoreNodeAfterDestination(node, tree, this.previousNode);
    } else {
      this.commandProvider.restoreNodeToFirstChildOfDestination(node, tree, this.previousNode);
    }
    this.setHeight(35);
    this.updateText(TYPO3.Components.PageTree.LLL.dropZoneElementRestored);
    this.app.doLayout();

    (function() {
      if (this.textClickHandler) {
        this.toOriginState();
      }
    }).defer(3000, this);
  }
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.DeletionDropZone', TYPO3.Components.PageTree.DeletionDropZone);
