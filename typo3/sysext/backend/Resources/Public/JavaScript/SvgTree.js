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
 * Module: TYPO3/CMS/Backend/SvgTree
 */
define(
  [
    'jquery',
    'd3',
    'TYPO3/CMS/Backend/ContextMenu',
    'TYPO3/CMS/Backend/Modal',
    'TYPO3/CMS/Backend/Severity',
    'TYPO3/CMS/Backend/Notification',
    'TYPO3/CMS/Backend/Icons',
    'TYPO3/CMS/Backend/Tooltip',
  ],
  function($, d3, ContextMenu, Modal, Severity, Notification, Icons, Tooltip) {
    'use strict';

    /**
     * @constructor
     * @exports SvgTree
     */
    var SvgTree = function() {
      this.settings = {
        showCheckboxes: false,
        showIcons: false,
        allowRecursiveDelete: false,
        marginTop: 15,
        nodeHeight: 20,
        indentWidth: 16,
        width: 300,
        duration: 400,
        dataUrl: '',
        nodeOver: {},
        validation: {
          maxItems: Number.MAX_VALUE
        },
        defaultProperties: {},
        unselectableElements: [],
        expandUpToLevel: null,
        readOnlyMode: false,
        /**
         * List node identifiers which can not be selected together with any other node
         */
        exclusiveNodesIdentifiers: ''
      };

      /**
       * Check if cursor is over svg
       *
       * @type {boolean}
       */
      this.isOverSvg = false;

      /**
       * Root <svg> element
       *
       * @type {Selection}
       */
      this.svg = null;

      /**
       * Wrapper of svg element
       *
       * @type {Selection}
       */
      this.d3wrapper = null;

      /**
       * SVG <g> container wrapping all .nodes, .links, .nodes-bg  elements
       *
       * @type {Selection}
       */
      this.container = null;

      /**
       * SVG <g> container wrapping all .node elements
       *
       * @type {Selection}
       */
      this.nodesContainer = null;

      /**
       * SVG <g> container wrapping all .nodes-bg elements
       *
       * @type {Selection}
       */
      this.nodesBgContainer = null;

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
        var _this = this;
        this.wrapper = $wrapper;
        this.setWrapperHeight();
        this.dispatch = d3.dispatch(
          'updateNodes',
          'updateSvg',
          'loadDataAfter',
          'prepareLoadedNode',
          'nodeSelectedAfter',
          'nodeRightClick',
          'contextmenu'
        );

        /**
         * Create element:
         *
         * <svg version="1.1" width="100%">
         *   <g class="nodes-wrapper">
         *     <g class="nodes-bg"><rect class="node-bg"></rect></g>
         *     <g class="links"><path class="link"></path></g>
         *     <g class="nodes"><g class="node"></g></g>
         *   </g>
         * </svg>
         */
        this.d3wrapper = d3
          .select($wrapper[0]);
        this.svg = this.d3wrapper.append('svg')
          .attr('version', '1.1')
          .attr('width', '100%')
          .on('mouseover', function() {
            _this.isOverSvg = true;
          })
          .on('mouseout', function() {
            _this.isOverSvg = false;
          });

        this.container = this.svg
          .append('g')
          .attr('class', 'nodes-wrapper')
          .attr('transform', 'translate(' + (this.settings.indentWidth / 2) + ',' + (this.settings.nodeHeight / 2) + ')');
        this.nodesBgContainer = this.container.append('g')
          .attr('class', 'nodes-bg');
        this.linksContainer = this.container.append('g')
          .attr('class', 'links');
        this.nodesContainer = this.container.append('g')
          .attr('class', 'nodes');
        if (this.settings.showIcons) {
          this.iconsContainer = this.svg.append('defs');
          this.data.icons = {};
        }

        this.updateScrollPosition();
        this.loadData();

        this.wrapper.on('resize scroll', function() {
          _this.updateScrollPosition();
          _this.update();
        });

        $('#typo3-pagetree').on('isVisible', function() {
          _this.updateWrapperHeight();
        });

        this.wrapper.data('svgtree', this);
        this.wrapper.data('svgtree-initialized', true);
        this.wrapper.trigger('svgTree.initialized');
        this.resize();
        return true;
      },

      /**
       * Update svg tree after changed window height
       */
      resize: function() {
        var _this = this;
        $(window).resize(function() {
          if ($('#typo3-pagetree').is(':visible')) {
            _this.updateWrapperHeight();
          }
        });
      },

      /**
       * Update svg wrapper height
       */
      updateWrapperHeight: function() {
        var _this = this;

        _this.setWrapperHeight();
        _this.updateScrollPosition();
        _this.update();
      },

      /**
       * Set svg wrapper height
       */
      setWrapperHeight: function() {
        var treeWrapperHeight = ($('body').height() - $('#svg-toolbar').outerHeight() - $('.scaffold-topbar').height());
        $('#typo3-pagetree-tree').height(treeWrapperHeight);
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
        var _this = this;
        _this.nodesAddPlaceholder();

        d3.json(this.settings.dataUrl, function(error, json) {
          if (error) {
            var title = TYPO3.lang.pagetree_networkErrorTitle;
            var desc = TYPO3.lang.pagetree_networkErrorDesc;

            if (error && error.target && (error.target.status || error.target.statusText)) {
              title += ' - ' + (error.target.status || '') + ' ' + (error.target.statusText || '');
            }

            Notification.error(
              title,
              desc);

            _this.nodesRemovePlaceholder();
            throw error;
          }

          var nodes = Array.isArray(json) ? json : [];
          _this.replaceData(nodes);
          _this.nodesRemovePlaceholder();
        });
      },

      /**
       * Delete old tree and create new one
       *
       * @param {Node[]} nodes
       */
      replaceData: function(nodes) {
        var _this = this;

        _this.setParametersNode(nodes);
        _this.dispatch.call('loadDataAfter', _this);
        _this.prepareDataForVisibleNodes();
        _this.nodesContainer.selectAll('.node').remove();
        _this.nodesBgContainer.selectAll('.node-bg').remove();
        _this.linksContainer.selectAll('.link').remove();
        _this.update();
      },

      /**
       * Set parameters like node parents, parentsStateIdentifier, checked
       *
       * @param {Node[]} nodes
       */
      setParametersNode: function(nodes) {
        var _this = this;

        nodes = nodes || this.nodes;
        nodes = nodes.map(function(node, index) {
          if (typeof node.command === 'undefined') {
            node = $.extend({}, _this.settings.defaultProperties, node);
          }
          node.expanded = (_this.settings.expandUpToLevel !== null) ? node.depth < _this.settings.expandUpToLevel : Boolean(node.expanded);
          node.parents = [];
          node.parentsStateIdentifier = [];
          node._isDragged = false;
          if (node.depth > 0) {
            var currentDepth = node.depth;
            for (var i = index; i >= 0; i--) {
              var currentNode = nodes[i];
              if (currentNode.depth < currentDepth) {
                node.parents.push(i);
                node.parentsStateIdentifier.push(nodes[i].stateIdentifier);
                currentDepth = currentNode.depth;
              }
            }
          }

          node.canToggle = node.hasChildren;

          // create stateIdentifier if doesn't exist (for category tree)
          if (!node.stateIdentifier) {
            var parentId = (node.parents.length) ? node.parents[node.parents.length - 1] : node.identifier;
            node.stateIdentifier = parentId + '_' + node.identifier;
          }

          if (typeof node.checked === 'undefined') {
            node.checked = false;
          }
          if (node.selectable === false) {
            _this.settings.unselectableElements.push(node.identifier);
          }

          // dispatch event
          _this.dispatch.call('prepareLoadedNode', _this, node);
          return node;
        });

        // get nodes with depth 0, if there is only 1 then open it and disable toggle
        var nodeDepths = nodes.filter(function(node) {
          return node.depth === 0;
        });

        if (nodeDepths.length === 1) {
          nodes[0].expanded = true;
          nodes[0].canToggle = false;
        }

        _this.nodes = nodes;
      },

      nodesRemovePlaceholder: function() {
        $('.svg-tree').find('.node-loader').hide();
        $('.svg-tree').find('.svg-tree-loader').hide();
      },

      nodesAddPlaceholder: function(node) {
        if (node) {
          $('.svg-tree').find('.node-loader').css({top: node.y + this.settings.marginTop}).show();
        } else {
          $('.svg-tree').find('.svg-tree-loader').show();
        }
      },

      /**
       * Filters out invisible nodes (collapsed) from the full dataset (this.rootNode)
       * and enriches dataset with additional properties
       * Visible dataset is stored in this.data
       */
      prepareDataForVisibleNodes: function() {
        var _this = this;

        var blacklist = {};
        this.nodes.map(function(node, index) {
          if (!node.expanded) {
            blacklist[index] = true;
          }
        });

        this.data.nodes = this.nodes.filter(function(node) {
          return node.hidden !== true && !node.parents.some(function(index) {
            return Boolean(blacklist[index]);
          });
        });

        this.data.links = [];
        var pathAboveMounts = 0;

        this.data.nodes.forEach(function(n, i) {
          // delete n.children;
          n.x = n.depth * _this.settings.indentWidth;

          if (n.readableRootline) {
            pathAboveMounts += _this.settings.nodeHeight;
          }

          n.y = (i * _this.settings.nodeHeight) + pathAboveMounts;
          if (n.parents[0] !== undefined) {
            _this.data.links.push({
              source: _this.nodes[n.parents[0]],
              target: n
            });
          }

          if (_this.settings.showIcons) {
            _this.fetchIcon(n.icon);
            _this.fetchIcon(n.overlayIcon);
            if (n.locked) {
              _this.fetchIcon('warning-in-use');
            }
          }
        });

        this.svg.attr('height', ((this.data.nodes.length * this.settings.nodeHeight) + (this.settings.nodeHeight / 2) + pathAboveMounts));
      },

      /**
       * Fetch icon from Icon API and store it in data.icons
       *
       * @param {String} iconName
       * @param {Boolean} update
       */
      fetchIcon: function(iconName, update) {
        if (!iconName) {
          return;
        }

        if (typeof update === 'undefined') {
          update = true;
        }

        var _this = this;
        if (!(iconName in this.data.icons)) {
          this.data.icons[iconName] = {
            identifier: iconName,
            icon: ''
          };
          Icons.getIcon(iconName, Icons.sizes.small, null, null, 'inline').done(function(icon) {
            var result = icon.match(/<svg[\s\S]*<\/svg>/i);

            if (result) {
              _this.data.icons[iconName].icon = result[0];
            }

            if (update) {
              _this.update();
            }
          });
        }
      },

      /**
       * Renders the subset of the tree nodes fitting the viewport (adding, modifying and removing SVG nodes)
       */
      update: function() {
        var _this = this;
        var visibleRows = Math.ceil(_this.viewportHeight / _this.settings.nodeHeight + 1);
        var position = Math.floor(Math.max(_this.scrollTop, 0) / _this.settings.nodeHeight);

        var visibleNodes = this.data.nodes.slice(position, position + visibleRows);
        var nodes = this.nodesContainer.selectAll('.node').data(visibleNodes, function(d) {
          return d.stateIdentifier;
        });

        var nodesBg = this.nodesBgContainer.selectAll('.node-bg').data(visibleNodes, function(d) {
          return d.stateIdentifier;
        });

        // delete nodes without corresponding data
        nodes
          .exit()
          .remove();

        // delete
        nodesBg
          .exit()
          .remove();

        // update nodes background
        var nodeBgClass = this.updateNodeBgClass(nodesBg);

        nodeBgClass
          .attr('class', function(node, i) {
            return _this.getNodeBgClass(node, i, nodeBgClass);
          })
          .attr('style', function(node) {
            return node.backgroundColor ? 'fill: ' + node.backgroundColor + ';' : '';
          });

        this.updateLinks();
        nodes = this.enterSvgElements(nodes);

        // update nodes
        nodes
          .attr('transform', this.getNodeTransform)
          .select('.node-name')
          .text(this.getNodeLabel.bind(this));

        nodes
          .select('.chevron')
          .attr('transform', this.getChevronTransform)
          .style('fill', this.getChevronColor)
          .attr('class', this.getChevronClass);

        nodes
          .select('.toggle')
          .attr('visibility', this.getToggleVisibility);

        if (this.settings.showIcons) {
          nodes
            .select('use.node-icon')
            .attr('xlink:href', this.getIconId);
          nodes
            .select('use.node-icon-overlay')
            .attr('xlink:href', this.getIconOverlayId);
          nodes
            .select('use.node-icon-locked')
            .attr('xlink:href', function (node) {
              return '#icon-' + (node.locked ? 'warning-in-use' : '');
            });

        }

        // dispatch event
        this.dispatch.call('updateNodes', this, nodes);
      },

      /**
       * @param {Node} nodesBg
       * @returns {Node} nodesBg
       */
      updateNodeBgClass: function(nodesBg) {
        var _this = this;

        return nodesBg.enter()
          .append('rect')
          .merge(nodesBg)
          .attr('width', '100%')
          .attr('height', this.settings.nodeHeight)
          .attr('data-state-id', this.getNodeStateIdentifier)
          .attr('transform', this.getNodeBgTransform)
          .on('mouseover', function(node) {
            _this.nodeBgEvents().mouseOver(node, this);
          })
          .on('mouseout', function(node) {
            _this.nodeBgEvents().mouseOut(node, this);
          })
          .on('click', function(node) {
            _this.selectNode(node);
          })
          .on('contextmenu', function(node) {
            _this.dispatch.call('nodeRightClick', node, this);
          });
      },

      /**
       * node background events
       *
       */
      nodeBgEvents: function() {
        var _this = this;
        var self = {};

        self.mouseOver = function(node, element) {
          var elementNodeBg = _this.svg.select('.nodes-bg .node-bg[data-state-id="' + node.stateIdentifier + '"]');

          node.isOver = true;
          _this.settings.nodeOver.node = node;

          if (elementNodeBg.size()) {
            elementNodeBg
              .classed('node-over', true)
              .attr('rx', '3')
              .attr('ry', '3');
          }
        };

        self.mouseOut = function(node, element) {
          var elementNodeBg = _this.svg.select('.nodes-bg .node-bg[data-state-id="' + node.stateIdentifier + '"]');

          node.isOver = false;
          _this.settings.nodeOver.node = false;

          if (elementNodeBg.size()) {
            elementNodeBg
              .classed('node-over node-alert', false)
              .attr('rx', '0')
              .attr('ry', '0');
          }
        };

        return self;
      },

      /**
       * Renders links(lines) between parent and child nodes
       */
      updateLinks: function() {
        var _this = this;
        var visibleLinks = this.data.links.filter(function(linkData) {
          return linkData.source.y <= _this.scrollBottom && linkData.target.y >= _this.scrollTop;
        });

        var links = this.linksContainer
          .selectAll('.link')
          .data(visibleLinks);

        // delete
        links
          .exit()
          .remove();

        // create
        links.enter()
          .append('path')
          .attr('class', 'link')

          // create + update
          .merge(links)
          .attr('d', this.getLinkPath.bind(_this));
      },

      /**
       * Adds missing SVG nodes
       *
       * @param {Selection} nodes
       * @returns {Selection}
       */
      enterSvgElements: function(nodes) {
        var _this = this;
        this.textPosition = 10;

        if (this.settings.showIcons) {
          var iconsArray = $.map(this.data.icons, function(value) {
            if (value.icon !== '') return value;
          });

          var icons = this.iconsContainer
            .selectAll('.icon-def')
            .data(iconsArray, function(i) {
              return i.identifier;
            });

          icons
            .exit()
            .remove();

          icons
            .enter()
            .append('g')
            .attr('class', 'icon-def')
            .attr('id', function(i) {
              return 'icon-' + i.identifier;
            })
            .append(function(i) {
              // workaround for IE11 where you can't simply call .html(content) on svg
              var parser = new DOMParser();
              var markupText = i.icon.replace('<svg', '<g').replace('/svg>', '/g>');
              markupText = "<svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'>" + markupText + '</svg>';
              var dom = parser.parseFromString(markupText, 'image/svg+xml');
              return dom.documentElement.firstChild;
            });
        }

        // create the node elements
        var nodeEnter = _this.nodesUpdate(nodes);

        // append the chevron element
        var chevron = nodeEnter
          .append('g')
          .attr('class', 'toggle')
          .attr('visibility', this.getToggleVisibility)
          .attr('transform', 'translate(-8, -8)')
          .on('click', function(node) {
            _this.chevronClick(node);
          });

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
          this.textPosition = 30;

          var nodeContainer = nodeEnter
            .append('g')
            .attr('class', 'node-icon-container')
            .attr('title', this.getNodeTitle)
            .attr('data-toggle', 'tooltip')
            .on('click', function(node) {
                _this.clickOnIcon(node, this);
            });

          nodeContainer
            .append('use')
            .attr('class', 'node-icon')
            .attr('data-uid', this.getNodeIdentifier)
            .attr('transform', 'translate(8, -8)');

          nodeContainer
            .append('use')
            .attr('transform', 'translate(8, -3)')
            .attr('class', 'node-icon-overlay');

          nodeContainer
            .append('use')
            .attr('x', 27)
            .attr('y', -7)
            .attr('class', 'node-icon-locked');
        }

        Tooltip.initialize('[data-toggle="tooltip"]', {
            delay: {
                "show": 50,
                "hide": 50
            },
            trigger: 'hover',
            container: 'body',
            placement: 'right',
        });

        this.dispatch.call('updateSvg', this, nodeEnter);

        _this.appendTextElement(nodeEnter);

        return nodes.merge(nodeEnter);
      },

      /**
       * append the text element
       *
       * @param {Node} node
       * @returns {Node} node
       */
      appendTextElement: function(node) {
        var _this = this;

        return node
          .append('text')
          .attr('dx', function (node) {
            return _this.textPosition + (node.locked ? 15 : 0);
          })
          .attr('dy', 5)
          .attr('class', 'node-name')
          .on('click', function(node) {
            _this.clickOnLabel(node, this);
          });
      },

      /**
       * @param {Node} nodes
       * @returns {Node} nodes
       */
      nodesUpdate: function(nodes) {
        var _this = this;

        nodes = nodes
          .enter()
          .append('g')
          .attr('class', this.getNodeClass)
          .attr('transform', this.getNodeTransform)
          .attr('data-state-id', this.getNodeStateIdentifier)
          .attr('title', this.getNodeTitle)
          .on('mouseover', function(node) {
            _this.nodeBgEvents().mouseOver(node, this);
          })
          .on('mouseout', function(node) {
            _this.nodeBgEvents().mouseOut(node, this);
          });

        var nodeStop = nodes
          .append('text')
          .text(function(node) {
            return node.readableRootline;
          })
          .attr('class', 'node-rootline')
          .attr('dx', 0)
          .attr('dy', -15)
          .attr('visibility', function(node) {
            return node.readableRootline ? 'visible' : 'hidden';
          });

        return nodes;
      },

      /**
       * Computes the tree item identifier based on the data
       *
       * @param {Node} node
       * @returns {String}
       */
      getNodeIdentifier: function(node) {
        return node.identifier;
      },

      /**
       * Computes the tree item state identifier based on the data
       *
       * @param {Node} node
       * @returns {String}
       */
      getNodeStateIdentifier: function(node) {
        return node.stateIdentifier;
      },

      /**
       * Computes the tree item label based on the data
       *
       * @param {Node} node
       * @returns {String}
       */
      getNodeLabel: function(node) {
        return (node.prefix || '') + node.name + (node.suffix || '');
      },

      /**
       * Computes the tree node class
       *
       * @param {Node} node
       * @returns {String}
       */
      getNodeClass: function(node) {
        return 'node identifier-' + node.stateIdentifier;
      },

      /**
       * Computes the tree node-bg class
       *
       * @param {Node} node
       * @param {Integer} i
       * @param {Object} nodeBgClass
       * @returns {String}
       */
      getNodeBgClass: function(node, i, nodeBgClass) {
        var bgClass = 'node-bg';
        var prevNode = false;
        var nextNode = false;

        if (typeof nodeBgClass === 'object') {
          prevNode = nodeBgClass.data()[i - 1];
          nextNode = nodeBgClass.data()[i + 1];
        }

        if (node.checked) {
          bgClass += ' node-selected';
        }

        if ((prevNode && (node.depth > prevNode.depth)) || !prevNode) {
          node.firstChild = true;
          bgClass += ' node-firth-child';
        }

        if ((nextNode && (node.depth > nextNode.depth)) || !nextNode) {
          node.lastChild = true;
          bgClass += ' node-last-child';
        }

        if (node.class) {
          bgClass += ' ' + node.class;
        }

        return bgClass;
      },

      /**
       * Computes the tree item label based on the data
       *
       * @param {Node} node
       * @returns {String}
       */
      getNodeTitle: function(node) {
        return node.tip ? node.tip : 'uid=' + node.identifier;
      },

      /**
       * Returns chevron 'transform' attribute value
       *
       * @param {Node} node
       * @returns {String}
       */
      getChevronTransform: function(node) {
        return node.expanded ? 'translate(16,0) rotate(90)' : ' rotate(0)';
      },

      /**
       * Returns chevron class
       *
       * @param {Node} node
       * @returns {String}
       */
      getChevronColor: function(node) {
        return node.expanded ? '#000' : '#8e8e8e';
      },

      /**
       * Computes toggle 'visibility' attribute value
       *
       * @param {Node} node
       * @returns {String}
       */
      getToggleVisibility: function(node) {
        return node.canToggle ? 'visible' : 'hidden';
      },

      /**
       * Computes chevron 'class' attribute value
       *
       * @param {Node} node
       * @returns {String}
       */
      getChevronClass: function(node) {
        return 'chevron ' + (node.expanded ? 'expanded' : 'collapsed');
      },

      /**
       * Returns icon's href attribute value
       *
       * @param {Node} node
       * @returns {String}
       */
      getIconId: function(node) {
        return '#icon-' + node.icon;
      },

      /**
       * Returns icon's href attribute value
       *
       * @param {Node} node
       * @returns {String}
       */
      getIconOverlayId: function(node) {
        return '#icon-' + node.overlayIcon;
      },

      /**
       * Returns a SVG path's 'd' attribute value
       *
       * @param {Object} link
       * @returns {String}
       */
      getLinkPath: function(link) {
        var target = {
          x: link.target._isDragged ? link.target._x : link.target.x,
          y: link.target._isDragged ? link.target._y : link.target.y
        };
        var path = [];
        path.push('M' + link.source.x + ' ' + link.source.y);
        path.push('V' + target.y);
        if (target.hasChildren) {
          path.push('H' + (target.x - 2));
        } else {
          path.push('H' + ((target.x + this.settings.indentWidth / 4) - 2));
        }

        return path.join(' ');
      },

      /**
       * Returns a 'transform' attribute value for the tree element (absolute positioning)
       *
       * @param {Node} node
       */
      getNodeTransform: function(node) {
        return 'translate(' + (node.x || 0) + ',' + (node.y || 0) + ')';
      },

      /**
       * Returns a 'transform' attribute value for the node background element (absolute positioning)
       *
       * @param {Node} node
       */
      getNodeBgTransform: function(node) {
        return 'translate(-8, ' + ((node.y || 0) - 10) + ')';
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
       * If a node is one of the exclusiveNodesIdentifiers list,
       * all other nodes has to be unselected before selecting this node.
       *
       * @param {Node} node
       */
      handleExclusiveNodeSelection: function(node) {
        var exclusiveKeys = this.settings.exclusiveNodesIdentifiers.split(',');
        var _this = this;
        if (this.settings.exclusiveNodesIdentifiers.length && node.checked === false) {
          if (exclusiveKeys.indexOf('' + node.identifier) > -1) {

            // this key is exclusive, so uncheck all others
            this.nodes.forEach(function(node) {
              if (node.checked === true) {
                node.checked = false;
                _this.dispatch.call('nodeSelectedAfter', _this, node);
              }
            });

            this.exclusiveSelectedNode = node;
          } else if (exclusiveKeys.indexOf('' + node.identifier) === -1 && this.exclusiveSelectedNode) {

            // current node is not exclusive, but other exclusive node is already selected
            this.exclusiveSelectedNode.checked = false;
            this.dispatch.call('nodeSelectedAfter', this, this.exclusiveSelectedNode);
            this.exclusiveSelectedNode = null;
          }
        }
      },

      /**
       * Check whether node can be selected.
       * In some cases (e.g. selecting a parent) it should not be possible to select
       * element (as it's own parent).
       *
       * @param {Node} node
       * @returns {Boolean}
       */
      isNodeSelectable: function(node) {
        return !this.settings.readOnlyMode && this.settings.unselectableElements.indexOf(node.identifier) === -1;
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
       * @param {HTMLElement} element
       */
      clickOnIcon: function(node, element) {
        this.dispatch.call('contextmenu', node, element);
      },

      /**
       * Event handler for click on a node's label/text
       *
       * @param {Node} node
       * @param {HTMLElement} element
       */
      clickOnLabel: function(node, element) {
        this.selectNode(node);
      },

      /**
       * Event handler for click on a chevron
       *
       * @param {Node} node
       */
      chevronClick: function(node) {
        if (node.expanded) {
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
        node.expanded = false;
      },

      /**
       * Updates node's data to show/expand children
       *
       * @param {Node} node
       */
      showChildren: function(node) {
        node.expanded = true;
      },

      /**
       * Refresh view with new data
       */
      refreshTree: function() {
        this.loadData();
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
