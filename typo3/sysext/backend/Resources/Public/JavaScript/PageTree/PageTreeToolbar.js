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
define(['jquery',
    'TYPO3/CMS/Backend/Icons',
    'd3',
    'TYPO3/CMS/Backend/PageTree/PageTreeDragDrop',
    'TYPO3/CMS/Backend/Tooltip',
    'TYPO3/CMS/Backend/SvgTree'
  ],
  function($, Icons, d3, PageTreeDragDrop) {
    'use strict';

    /**
     * TreeToolbar class
     *
     * @constructor
     * @exports TYPO3/CMS/Backend/FormEngine/Element/TreeToolbar
     */
    var TreeToolbar = function() {
      this.settings = {
        toolbarSelector: 'tree-toolbar',
        searchInput: '.search-input',
        target: '.svg-toolbar'
      };

      /**
       * jQuery object wrapping the SvgTree
       *
       * @type {jQuery}
       */
      this.$treeWrapper = null;

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
      this.template = null;
    };

    /**
     * Toolbar initialization
     *
     * @param {String} treeSelector
     * @param {Object} settings
     */
    TreeToolbar.prototype.initialize = function(treeSelector, settings) {
      var _this = this;
      _this.$treeWrapper = $(treeSelector);

      this.dragDrop = PageTreeDragDrop;
      this.dragDrop.init(this);
      if (!_this.$treeWrapper.data('svgtree-initialized') || typeof _this.$treeWrapper.data('svgtree') !== 'object') {
        //both toolbar and tree are loaded independently through require js,
        //so we don't know which is loaded first
        //in case of toolbar being loaded first, we wait for an event from svgTree
        _this.$treeWrapper.on('svgTree.initialized', _this.render.bind(_this));
        return;
      }

      $.extend(this.settings, settings);
      this.render();
    };

    /**
     * Create toolbar template
     */
    TreeToolbar.prototype.createTemplate = function() {
      var _this = this;

      var $template = $(
        '<div class="' + _this.settings.toolbarSelector + '">' +
        '<div class="svg-toolbar__menu">' +
        '<div class="x-btn btn btn-default btn-sm x-btn-noicon" data-tree-show-submenu="filter">' +
        '<button class="svg-toolbar__btn" data-tree-icon="actions-filter" title="' + TYPO3.lang['tree.buttonFilter'] + '"></button>' +
        '</div>' +
        '<div class="x-btn btn btn-default btn-sm x-btn-noicon js-svg-refresh">' +
        '<button class="svg-toolbar__btn" data-tree-icon="actions-refresh" title="' + TYPO3.lang['labels.refresh'] + '"></button>' +
        '</div>' +
        '</div>' +
        '<div class="svg-toolbar__submenu">' +
        '<div class="svg-toolbar__submenu-item" data-tree-submenu="filter">' +
        '<input type="text" class="form-control search-input" placeholder="' + TYPO3.lang['tree.searchTermInfo'] + '">' +
        '</div>' +
        '<div class="svg-toolbar__submenu-item" data-tree-submenu="page-new">' +
        '</div>' +
        '</div>' +
        '</div>'
      );

      if (this.tree.settings.doktypes && this.tree.settings.doktypes.length) {
        var $buttons = $template.find('[data-tree-submenu=page-new]');
        $template.find('.svg-toolbar__menu').prepend('<div class="x-btn btn btn-default btn-sm x-btn-noicon" data-tree-show-submenu="page-new">' +
          '<button class="svg-toolbar__btn" data-tree-icon="actions-page-new" title="' + TYPO3.lang['tree.buttonNewNode'] + '"></button>' +
          '</div>'
        );

        $.each(this.tree.settings.doktypes, function(id, e) {
          _this.tree.fetchIcon(e.icon, false);
          $buttons.append('<div class="svg-toolbar__drag-node" data-tree-icon="' + e.icon + '" data-node-type="' + e.nodeType + '" title="' + e.title + '" tooltip="' + e.tooltip + '"></div>');
        });
      }

      _this.template = $template;
    };

    /**
     * Renders toolbar
     */
    TreeToolbar.prototype.render = function() {
      var _this = this;
      this.tree = this.$treeWrapper.data('svgtree');

      $.extend(this.settings, this.tree.settings);
      this.createTemplate();

      var $toolbar = $(this.settings.target).append(this.template);

      //get icons
      $toolbar.find('[data-tree-icon]').each(function() {
        var $this = $(this);

        Icons.getIcon($this.attr('data-tree-icon'), Icons.sizes.small).done(function(icon) {
          $this.append(icon);
        });
      });

      //toggle toolbar submenu
      $toolbar.find('[data-tree-show-submenu]').each(function() {
        $(this).click(function() {
          var $this = $(this);
          var name = $this.attr('data-tree-show-submenu');
          var $submenu = $toolbar.find('[data-tree-submenu=' + name + ']');

          $toolbar.find('[data-tree-show-submenu]').not(this).removeClass('active');
          $this.addClass('active');

          $toolbar.find('[data-tree-submenu]').not($submenu).removeClass('active');
          $submenu.addClass('active');
          $submenu.find('input').focus();
        });
      });

      $toolbar.find('.js-svg-refresh').on('click', this.refreshTree.bind(this));

      var d3Toolbar = d3.select('.svg-toolbar');

      $.each(this.tree.settings.doktypes, function(id, e) {
        if (e.icon) {
          d3Toolbar
            .selectAll('[data-tree-icon=' + e.icon + ']')
            .call(_this.dragDrop.dragToolbar());
        } else {
          console.warn('Missing icon definition for doktype: ' + e.nodeType);
        }
      });

      $toolbar.find(this.settings.searchInput).on('input', function() {
        _this.search.call(_this, this);
      });

      $toolbar.find('[data-toggle="tooltip"]').tooltip();

      if ($('[data-tree-show-submenu="page-new"]').length) {
        $('[data-tree-show-submenu="page-new"]').trigger('click');
      } else {
        $('.svg-toolbar__menu :first-child:not(.js-svg-refresh)').trigger('click');
      }
    };

    /**
     * Refresh tree
     */
    TreeToolbar.prototype.refreshTree = function() {
      this.tree.refreshTree();
    };

    /**
     * Find node by name
     *
     * @param {HTMLElement} input
     */
    TreeToolbar.prototype.search = function(input) {
      var _this = this;
      var name = $(input).val().trim();

      this.tree.nodes[0].expanded = false;
      this.tree.nodes.forEach(function(node) {
        var regex = new RegExp(name, 'i');
        if (node.identifier.toString() === name || regex.test(node.name) || regex.test(node.alias)) {
          _this.showParents(node);
          node.expanded = true;
          node.hidden = false;
        } else if (node.depth !== 0) {
          node.hidden = true;
          node.expanded = false;
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
      var _this = this;

      this._hideUncheckedState = !this._hideUncheckedState;

      if (this._hideUncheckedState) {
        this.tree.nodes.forEach(function(node) {
          if (node.checked) {
            _this.showParents(node);
            node.expanded = true;
            node.hidden = false;
          } else {
            node.hidden = true;
            node.expanded = false;
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
      parent.expanded = true;
      this.showParents(parent);
    };

    return TreeToolbar;
  });
