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
 * Module: TYPO3/CMS/Backend/FormEngine/Element/SvgTree
 */
define(['jquery', 'd3'], function($, d3) {
  'use strict';

  /**
   * @constructor
   * @exports SvgTree
   */
  var SvgTree = function() {
    this.settings = {
      showCheckboxes: false,
      showIcons: false,
      nodeHeight: 20,
      indentWidth: 16,
      duration: 400,
      dataUrl: 'tree-configuration.json',
      validation: {
        maxItems: Number.MAX_VALUE
      },
      unselectableElements: [],
      expandUpToLevel: null,
      readOnlyMode: false,
      /**
       * List node identifiers which can not be selected together with any other node
       */
      exclusiveNodesIdentifiers: ''
    };

    /**
     * Root <svg> element
     *
     * @type {Selection}
     */
    this.svg = null;

    /**
     * SVG <g> container wrapping all .node elements
     *
     * @type {Selection}
     */
    this.nodesContainer = null;

    /**
     * SVG <defs> container wrapping all icon definitions
     *
     * @type {Selection}
     */
    this.iconsContainer = null;

    /**
     * SVG <g> container wrapping all links (lines between parent and child)
     *
     * @type {Selection}
     */
    this.linksContainer = null;

    /**
     *
     * @type {{nodes: Node[], links: Object, icons: Object}}
     */
    this.data = {};

    /**
     * D3 event dispatcher
     *
     * @type {Object}
     */
    this.dispatch = null;

    /**
     * jQuery object of wrapper holding the SVG
     * Height of this wrapper is important (we only render as many nodes as fit in the wrapper
     *
     * @type {jQuery}
     */
    this.wrapper = null;
    this.viewportHeight = 0;
    this.scrollTop = 0;
    this.scrollBottom = 0;
    this.position = 0;

    /**
     * Exclusive node which is currently selected
     *
     * @type {Node}
     */
    this.exclusiveSelectedNode = null;
  };

  SvgTree.prototype = {
    constructor: SvgTree,

    /**
     * Initializes the tree component - created basic markup, loads and renders data
     *
     * @param {String} selector
     * @param {Object} settings
     */
    initialize: function(selector, settings) {
      var $wrapper = $(selector);
      // Do nothing if already initialized
      if ($wrapper.data('svgtree-initialized')) {
        return false;
      }

      $.extend(this.settings, settings);
      var me = this;
      this.wrapper = $wrapper;
      this.dispatch = d3.dispatch('updateNodes', 'updateSvg', 'loadDataAfter', 'prepareLoadedNode', 'nodeSelectedAfter');
      this.svg = d3
        .select($wrapper[0])
        .append('svg')
        .attr('version', '1.1')
        .attr('width', '100%');
      var container = this.svg
        .append('g')
        .attr('transform', 'translate(' + (this.settings.indentWidth / 2) + ',' + (this.settings.nodeHeight / 2) + ')');
      this.linksContainer = container.append('g')
        .attr('class', 'links');
      this.nodesContainer = container.append('g')
        .attr('class', 'nodes');
      if (this.settings.showIcons) {
        this.iconsContainer = this.svg.append('defs');
      }

      this.updateScrollPosition();
      this.loadData();

      this.wrapper.on('resize scroll', function() {
        me.updateScrollPosition();
        me.update();
      });
      this.wrapper.data('svgtree', this);
      this.wrapper.data('svgtree-initialized', true);
      this.wrapper.trigger('svgTree.initialized');
      return true;
    },

    /**
     * Updates variables used for visible nodes calculation
     */
    updateScrollPosition: function() {
      this.viewportHeight = this.wrapper.height();
      this.scrollTop = this.wrapper.scrollTop();
      this.scrollBottom = this.scrollTop + this.viewportHeight + (this.viewportHeight / 2);
    },

    /**
     * Loads tree data (json) from configured url
     */
    loadData: function() {
      var me = this;
      d3.json(this.settings.dataUrl, function(error, json) {
        if (error) throw error;
        var nodes = Array.isArray(json) ? json : [];
        nodes = nodes.map(function(node, index) {
          node.open = (me.settings.expandUpToLevel !== null) ? node.depth < me.settings.expandUpToLevel : Boolean(node.expanded);
          node.parents = [];
          node._isDragged = false;
          if (node.depth > 0) {
            var currentDepth = node.depth;
            for (var i = index; i >= 0; i--) {
              var currentNode = nodes[i];
              if (currentNode.depth < currentDepth) {
                node.parents.push(i);
                currentDepth = currentNode.depth;
              }
            }
          }
          if (typeof node.checked == 'undefined') {
            node.checked = false;
            me.settings.unselectableElements.push(node.identifier);
          }
          //dispatch event
          me.dispatch.call('prepareLoadedNode', me, node);
          return node;
        });

        me.nodes = nodes;
        me.dispatch.call('loadDataAfter', me);
        me.prepareDataForVisibleNodes();
        me.update();
      });
    },

    /**
     * Filters out invisible nodes (collapsed) from the full dataset (this.rootNode)
     * and enriches dataset with additional properties
     * Visible dataset is stored in this.data
     */
    prepareDataForVisibleNodes: function() {
      var me = this;

      var blacklist = {};
      this.nodes.map(function(node, index) {
        if (!node.open) {
          blacklist[index] = true;
        }
      });

      this.data.nodes = this.nodes.filter(function(node) {
        return node.hidden != true && !node.parents.some(function(index) {
          return Boolean(blacklist[index]);
        });
      });

      var iconHashes = [];
      this.data.links = [];
      this.data.icons = [];
      this.data.nodes.forEach(function(n, i) {
        //delete n.children;
        n.x = n.depth * me.settings.indentWidth;
        n.y = i * me.settings.nodeHeight;
        if (n.parents[0] !== undefined) {
          me.data.links.push({
            source: me.nodes[n.parents[0]],
            target: n
          });
        }
        if (!n.iconHash && me.settings.showIcons && n.icon) {
          n.iconHash = Math.abs(me.hashCode(n.icon));
          if (iconHashes.indexOf(n.iconHash) === -1) {
            iconHashes.push(n.iconHash);
            me.data.icons.push({
              identifier: n.iconHash,
              icon: n.icon
            });
          }
          delete n.icon;
        }
        if (!n.iconOverlayHash && me.settings.showIcons && n.overlayIcon) {
          n.iconOverlayHash = Math.abs(me.hashCode(n.overlayIcon));
          if (iconHashes.indexOf(n.iconOverlayHash) === -1) {
            iconHashes.push(n.iconOverlayHash);
            me.data.icons.push({
              identifier: n.iconOverlayHash,
              icon: n.overlayIcon
            });
          }
          delete n.overlayIcon;
        }
      });
      this.svg.attr('height', this.data.nodes.length * this.settings.nodeHeight);
    },

    /**
     * Renders the subset of the tree nodes fitting the viewport (adding, modifying and removing SVG nodes)
     */
    update: function() {
      var me = this;
      var visibleRows = Math.ceil(this.viewportHeight / this.settings.nodeHeight + 1);
      var position = Math.floor(Math.max(this.scrollTop, 0) / this.settings.nodeHeight);

      var visibleNodes = this.data.nodes.slice(position, position + visibleRows);
      var nodes = this.nodesContainer.selectAll('.node').data(visibleNodes, function(d) {
        return d.identifier;
      });

      // delete nodes without corresponding data
      nodes
        .exit()
        .remove();

      nodes = this.enterSvgElements(nodes);

      this.updateLinks();
      // update
      nodes
        .attr('transform', this.getNodeTransform)
        .select('text')
        .text(this.getNodeLabel.bind(me));

      nodes
        .select('.chevron')
        .attr('transform', this.getChevronTransform)
        .attr('visibility', this.getChevronVisibility);

      if (this.settings.showIcons) {
        nodes
          .select('use.node-icon')
          .attr('xlink:href', this.getIconId);
        nodes
          .select('use.node-icon-overlay')
          .attr('xlink:href', this.getIconOverlayId);
      }

      //dispatch event
      this.dispatch.call('updateNodes', me, nodes);
    },

    /**
     * Renders links(lines) between parent and child nodes
     */
    updateLinks: function() {
      var me = this;
      var visibleLinks = this.data.links.filter(function(linkData) {
        return linkData.source.y <= me.scrollBottom && linkData.target.y >= me.scrollTop;
      });

      var links = this.linksContainer
        .selectAll('.link')
        .data(visibleLinks);
      // delete
      links
        .exit()
        .remove();

      //create
      links.enter().append('path')
        .attr('class', 'link')
        //create + update
        .merge(links)
        .attr('d', this.getLinkPath.bind(me));
    },

    /**
     * Adds missing SVG nodes
     *
     * @param {Selection} nodes
     * @returns {Selection}
     */
    enterSvgElements: function(nodes) {
      var me = this;
      me.textPosition = 10;

      if (me.settings.showIcons) {
        var icons = this.iconsContainer
          .selectAll('.icon-def')
          .data(this.data.icons, function(i) {
            return i.identifier;
          });
        icons
          .enter()
          .append('g')
          .attr('class', 'icon-def')
          .attr('id', function(i) {
            return 'icon-' + i.identifier;
          })
          .append(function(i) {
            //workaround for IE11 where you can't simply call .html(content) on svg
            var parser = new DOMParser();
            var markupText = i.icon.replace('<svg', '<g').replace('/svg>', '/g>');
            markupText = "<svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'>" + markupText + "</svg>";
            var dom = parser.parseFromString(markupText, "image/svg+xml");
            return dom.documentElement.firstChild;
          });
      }

      // create the node elements
      var nodeEnter = nodes
        .enter()
        .append('g')
        .attr('class', this.getNodeClass)
        .attr('transform', this.getNodeTransform);

      // append the chevron element
      var chevron = nodeEnter
        .append('g')
        .attr('class', 'toggle')
        .on('click', this.chevronClick.bind(me));

      // improve usability by making the click area a 16px square
      chevron
        .append('path')
        .style('opacity', 0)
        .attr('d', 'M 0 0 L 16 0 L 16 16 L 0 16 Z');
      chevron
        .append('path')
        .attr('class', 'chevron')
        .attr('d', 'M 4 3 L 13 8 L 4 13 Z');

      // append the icon element
      if (this.settings.showIcons) {
        me.textPosition = 30;
        nodeEnter
          .append('use')
          .attr('x', 8)
          .attr('y', -8)
          .attr('class', 'node-icon')
          .on('click', this.clickOnIcon.bind(me));
        nodeEnter
          .append('use')
          .attr('x', 8)
          .attr('y', -3)
          .attr('class', 'node-icon-overlay')
          .on('click', this.clickOnIcon.bind(me));
      }

      this.dispatch.call('updateSvg', me, nodeEnter);

      // append the text element
      nodeEnter
        .append('text')
        .attr('dx', me.textPosition)
        .attr('dy', 5)
        .on('click', this.clickOnLabel.bind(me))
        .on('dblclick', this.dblClickOnLabel.bind(me));

      nodeEnter
        .append('title')
        .text(this.getNodeTitle.bind(me));

      return nodes.merge(nodeEnter);
    },

    /**
     * Computes the tree item label based on the data
     *
     * @param {Node} node
     * @returns {String}
     */
    getNodeLabel: function(node) {
      return node.name;
    },

    /**
     * Computes the tree node class
     *
     * @param {Node} node
     * @returns {String}
     */
    getNodeClass: function(node) {
      return 'node identifier-' + node.identifier;
    },

    /**
     * Computes the tree item label based on the data
     *
     * @param {Node} node
     * @returns {String}
     */
    getNodeTitle: function(node) {
      return 'uid=' + node.identifier;
    },

    /**
     * Returns chevron 'transform' attribute value
     *
     * @param {Node} node
     * @returns {String}
     */
    getChevronTransform: function(node) {
      return node.open ? 'translate(8 -8) rotate(90)' : 'translate(-8 -8) rotate(0)';
    },

    /**
     * Computes chevron 'visibility' attribute value
     *
     * @param {Node} node
     * @returns {String}
     */
    getChevronVisibility: function(node) {
      return node.hasChildren ? 'visible' : 'hidden';
    },

    /**
     * Returns icon's href attribute value
     *
     * @param {Node} node
     * @returns {String}
     */
    getIconId: function(node) {
      return '#icon-' + node.iconHash;
    },
    /**
     * Returns icon's href attribute value
     *
     * @param {Node} node
     * @returns {String}
     */
    getIconOverlayId: function(node) {
      return '#icon-' + node.iconOverlayHash;
    },

    /**
     * Returns a SVG path's 'd' attribute value
     *
     * @param {Object} link
     * @returns {String}
     */
    getLinkPath: function(link) {
      var me = this;

      var target = {
        x: link.target._isDragged ? link.target._x : link.target.x,
        y: link.target._isDragged ? link.target._y : link.target.y
      };
      var path = [];
      path.push('M' + link.source.x + ' ' + link.source.y);
      path.push('V' + target.y);
      if (target.hasChildren) {
        path.push('H' + target.x);
      } else {
        path.push('H' + (target.x + me.settings.indentWidth / 4));
      }
      return path.join(' ');
    },

    /**
     * Returns a 'transform' attribute value for the tree element (absolute positioning)
     *
     * @param {Node} node
     */
    getNodeTransform: function(node) {
      return 'translate(' + node.x + ',' + node.y + ')';
    },

    /**
     * Simple hash function used to create icon href's
     *
     * @param {String} s
     * @returns {String}
     */
    hashCode: function(s) {
      return s.split('')
        .reduce(function(a, b) {
          a = ((a << 5) - a) + b.charCodeAt(0);
          return a & a
        }, 0);
    },

    /**
     * Node selection logic (triggered by different events)
     *
     * @param {Node} node
     */
    selectNode: function(node) {
      if (!this.isNodeSelectable(node)) {
        return;
      }
      var checked = node.checked;
      this.handleExclusiveNodeSelection(node);

      if (this.settings.validation && this.settings.validation.maxItems) {
        var selectedNodes = this.getSelectedNodes();
        if (!checked && selectedNodes.length >= this.settings.validation.maxItems) {
          return;
        }
      }
      node.checked = !checked;

      this.dispatch.call('nodeSelectedAfter', this, node);
      this.update();
    },

    /**
     * Handle exclusive nodes functionality
     * If a node is one of the exclusiveNodesIdentifiers list, all other nodes has to be unselected before selecting this node.
     *
     * @param {Node} node
     */
    handleExclusiveNodeSelection: function(node) {
      var exclusiveKeys = this.settings.exclusiveNodesIdentifiers.split(','),
        me = this;
      if (this.settings.exclusiveNodesIdentifiers.length && node.checked === false) {
        if (exclusiveKeys.indexOf('' + node.identifier) > -1) {
          // this key is exclusive, so uncheck all others
          this.nodes.forEach(function(node) {
            if (node.checked === true) {
              node.checked = false;
              me.dispatch.call('nodeSelectedAfter', me, node);
            }
          });
          this.exclusiveSelectedNode = node;
        } else if (exclusiveKeys.indexOf('' + node.identifier) === -1 && this.exclusiveSelectedNode) {
          //current node is not exclusive, but other exclusive node is already selected
          this.exclusiveSelectedNode.checked = false;
          this.dispatch.call('nodeSelectedAfter', this, this.exclusiveSelectedNode);
          this.exclusiveSelectedNode = null;
        }
      }
    },

    /**
     * Check whether node can be selected, in some cases like parent selector it should not be possible to select
     * element as it's own parent
     *
     * @param {Node} node
     * @returns {Boolean}
     */
    isNodeSelectable: function(node) {
      return !this.settings.readOnlyMode && this.settings.unselectableElements.indexOf(node.identifier) == -1;
    },

    /**
     * Returns an array of selected nodes
     *
     * @returns {Node[]}
     */
    getSelectedNodes: function() {
      return this.nodes.filter(function(node) {
        return node.checked;
      });
    },

    /**
     * Event handler for clicking on a node's icon
     *
     * @param {Node} node
     */
    clickOnIcon: function(node) {
    },

    /**
     * Event handler for click on a node's label/text
     *
     * @param {Node} node
     */
    clickOnLabel: function(node) {
      this.selectNode(node);
    },

    /**
     * Event handler for double click on a node's label
     *
     * @param {Node} node
     */
    dblClickOnLabel: function(node) {
    },

    /**
     * Event handler for click on a chevron
     *
     * @param {Node} node
     */
    chevronClick: function(node) {
      if (node.open) {
        this.hideChildren(node);
      } else {
        this.showChildren(node);
      }
      this.prepareDataForVisibleNodes();
      this.update();
    },

    /**
     * Updates node's data to hide/collapse children
     *
     * @param {Node} node
     */
    hideChildren: function(node) {
      node.open = false;
    },

    /**
     * Updates node's data to show/expand children
     *
     * @param {Node} node
     */
    showChildren: function(node) {
      node.open = true;
    },

    /**
     * Expand all nodes and refresh view
     */
    expandAll: function() {
      this.nodes.forEach(this.showChildren.bind(this));
      this.prepareDataForVisibleNodes();
      this.update();
    },

    /**
     * Collapse all nodes recursively and refresh view
     */
    collapseAll: function() {
      this.nodes.forEach(this.hideChildren.bind(this));
      this.prepareDataForVisibleNodes();
      this.update();
    }
  };

  return SvgTree;
});
