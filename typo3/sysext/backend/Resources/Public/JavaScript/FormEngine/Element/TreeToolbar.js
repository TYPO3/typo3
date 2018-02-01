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

/**
 * Module: TYPO3/CMS/Backend/FormEngine/Element/TreeToolbar
 */
define(['jquery', 'TYPO3/CMS/Backend/Icons', 'TYPO3/CMS/Backend/Tooltip', 'TYPO3/CMS/Backend/FormEngine/Element/SvgTree'], function($, Icons) {
  'use strict';

  /**
   * TreeToolbar class
   *
   * @constructor
   * @exports TYPO3/CMS/Backend/FormEngine/Element/TreeToolbar
   */
  var TreeToolbar = function() {
    this.settings = {
      toolbarSelector: '.tree-toolbar',
      collapseAllBtn: '.collapse-all-btn',
      expandAllBtn: '.expand-all-btn',
      searchInput: '.search-input',
      toggleHideUnchecked: '.hide-unchecked-btn'
    };

    /**
     * jQuery object wrapping the SvgTree
     *
     * @type {jQuery}
     */
    this.treeWrapper = null;

    /**
     * SvgTree instance
     *
     * @type {SvgTree}
     */
    this.tree = null;

    /**
     * State of the hide unchecked toggle button
     *
     * @type {boolean}
     * @private
     */
    this._hideUncheckedState = false;

    /**
     * Toolbar template
     *
     * @type {jQuery}
     */
    this.template = $(
      '<div class="tree-toolbar btn-toolbar">' +
      '<div class="input-group">' +
      '<span class="input-group-addon input-group-icon filter"></span>' +
      '<input type="text" class="form-control search-input" placeholder="' + TYPO3.lang['tcatree.findItem'] + '">' +
      '</div>' +
      '<div class="btn-group">' +
      '<button type="button" data-toggle="tooltip" class="btn btn-default expand-all-btn" title="' + TYPO3.lang['tcatree.expandAll'] + '"></button>' +
      '<button type="button" data-toggle="tooltip" class="btn btn-default collapse-all-btn" title="' + TYPO3.lang['tcatree.collapseAll'] + '"></button>' +
      '<button type="button" data-toggle="tooltip" class="btn btn-default hide-unchecked-btn" title="' + TYPO3.lang['tcatree.toggleHideUnchecked'] + '"></button>' +
      '</div>' +
      '</div>'
    )
  };

  /**
   * Toolbar initialization
   *
   * @param {String} treeSelector
   * @param {Object} settings
   */
  TreeToolbar.prototype.initialize = function(treeSelector, settings) {
    var me = this;
    this.treeWrapper = $(treeSelector);
    if (!this.treeWrapper.data('svgtree-initialized') || typeof this.treeWrapper.data('svgtree') !== 'object') {
      //both toolbar and tree are loaded independently through require js, so we don't know which is loaded first
      //in case of toolbar being loaded first, we wait for an event from svgTree
      this.treeWrapper.on('svgTree.initialized', this.render.bind(me));
      return;
    }
    $.extend(this.settings, settings);
    this.render();
  };

  /**
   * Renders toolbar
   */
  TreeToolbar.prototype.render = function() {
    var me = this;
    this.tree = this.treeWrapper.data('svgtree');
    var $toolbar = this.template.clone().insertBefore(this.treeWrapper);

    Icons.getIcon('actions-filter', Icons.sizes.small).done(function(icon) {
      $toolbar.find('.filter').append(icon);
    });
    Icons.getIcon('apps-pagetree-category-expand-all', Icons.sizes.small).done(function(icon) {
      $toolbar.find('.expand-all-btn').append(icon);
    });
    Icons.getIcon('apps-pagetree-category-collapse-all', Icons.sizes.small).done(function(icon) {
      $toolbar.find('.collapse-all-btn').append(icon);
    });
    Icons.getIcon('apps-pagetree-category-toggle-hide-checked', Icons.sizes.small).done(function(icon) {
      $toolbar.find('.hide-unchecked-btn').append(icon);
    });

    $toolbar.find(this.settings.collapseAllBtn).on('click', this.collapseAll.bind(this));
    $toolbar.find(this.settings.expandAllBtn).on('click', this.expandAll.bind(this));
    $toolbar.find(this.settings.searchInput).on('input', function() {
      me.search.call(me, this);
    });
    $toolbar.find(this.settings.toggleHideUnchecked).on('click', this.toggleHideUnchecked.bind(this));
    $toolbar.find('[data-toggle="tooltip"]').tooltip();
  };

  /**
   * Collapse children of root node
   */
  TreeToolbar.prototype.collapseAll = function() {
    this.tree.collapseAll();
  };

  /**
   * Expand all nodes
   */
  TreeToolbar.prototype.expandAll = function() {
    this.tree.expandAll();
  };

  /**
   * Find node by name
   *
   * @param {HTMLElement} input
   */
  TreeToolbar.prototype.search = function(input) {
    var me = this,
      name = $(input).val();

    this.tree.nodes[0].open = false;
    this.tree.nodes.forEach(function(node) {
      var regex = new RegExp(name, 'i');
      if (regex.test(node.name)) {
        me.showParents(node);
        node.open = true;
        node.hidden = false;
      } else {
        node.hidden = true;
        node.open = false;
      }
    });
    this.tree.prepareDataForVisibleNodes();
    this.tree.update();
  };

  /**
   * Show only checked items
   *
   * @param {HTMLElement} input
   */
  TreeToolbar.prototype.toggleHideUnchecked = function(input) {
    var me = this;

    this._hideUncheckedState = !this._hideUncheckedState;

    if (this._hideUncheckedState) {
      this.tree.nodes.forEach(function(node) {
        if (node.checked) {
          me.showParents(node);
          node.open = true;
          node.hidden = false;
        } else {
          node.hidden = true;
          node.open = false;
        }
      });
    } else {
      this.tree.nodes.forEach(function(node) {
        node.hidden = false;
      });
    }
    this.tree.prepareDataForVisibleNodes();
    this.tree.update();
  };

  /**
   * Finds and show all parents of node
   *
   * @param {Node} node
   * @returns {Boolean}
   */
  TreeToolbar.prototype.showParents = function(node) {
    if (node.parents.length === 0) {
      return true;
    }

    var parent = this.tree.nodes[node.parents[0]];
    parent.hidden = false;
    //expand parent node
    parent.open = true;
    this.showParents(parent);
  };

  return TreeToolbar;
});
