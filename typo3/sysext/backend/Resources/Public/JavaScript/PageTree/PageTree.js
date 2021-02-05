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
 * Module: TYPO3/CMS/Backend/PageTree/PageTree
 */
define([
    'd3-selection',
    'TYPO3/CMS/Core/Ajax/AjaxRequest',
    'TYPO3/CMS/Backend/Icons',
    'TYPO3/CMS/Backend/PageTree/PageTreeDragHandler',
    'TYPO3/CMS/Backend/SvgTree',
    'TYPO3/CMS/Backend/ContextMenu',
    'TYPO3/CMS/Backend/Storage/Persistent',
    'TYPO3/CMS/Backend/Notification'
  ],
  function(d3selection, AjaxRequest, Icons, PageTreeDragHandler, SvgTree, ContextMenu, Persistent, Notification) {
    'use strict';

    /**
     * @constructor
     * @exports TYPO3/CMS/Backend/PageTree/PageTree
     */
    var PageTree = function() {
      SvgTree.call(this);
      this.originalNodes = [];
      this.settings.defaultProperties = {
        hasChildren: false,
        nameSourceField: 'title',
        prefix: '',
        suffix: '',
        locked: false,
        loaded: false,
        overlayIcon: '',
        selectable: true,
        expanded: false,
        checked: false,
        backgroundColor: '',
        stopPageTree: false,
        class: '',
        readableRootline: '',
        isMountPoint: false,
      };
      this.searchQuery = '';
    };

    PageTree.prototype = Object.create(SvgTree.prototype);

    var _super_ = SvgTree.prototype;

    /**
     * SelectTree initialization
     *
     * @param {String} selector
     * @param {PageTreeDragDrop} dragDrop
     * @param {Object} settings
     */
    PageTree.prototype.initialize = function(selector, dragDrop, settings) {
      var _this = this;

      if (!_super_.initialize.call(_this, selector, settings)) {
        return false;
      }

      _this.settings.isDragAnDrop = settings.allowDragMove;
      _this.dispatch.on('nodeSelectedAfter.pageTree', _this.nodeSelectedAfter);
      _this.dispatch.on('nodeRightClick.pageTree', _this.nodeRightClick);
      _this.dispatch.on('contextmenu.pageTree', _this.contextmenu);
      _this.dispatch.on('updateSvg.pageTree', _this.updateSvg);
      _this.dispatch.on('prepareLoadedNode.pageTree', _this.prepareLoadedNode);
      _this.dragDrop = dragDrop;

      if (_this.settings.temporaryMountPoint) {
        _this.addMountPoint(_this.settings.temporaryMountPoint);
      }

      return this;
    };

    /**
     * Add mount point
     */
    PageTree.prototype.addMountPoint = function(breadcrumb) {
      var _this = this;

      var existingMountPointInfo = _this.wrapper.parentNode.querySelector('.node-mount-point');
      if (existingMountPointInfo) {
        existingMountPointInfo.parentNode.removeChild(existingMountPointInfo);
      }

      _this.wrapper.insertAdjacentHTML('beforebegin',
        '<div class="node-mount-point">' +
        '<div class="node-mount-point__icon" data-tree-icon="actions-document-info"></div>' +
        '<div class="node-mount-point__text"><div>' + breadcrumb + '</div></div>' +
        '<div class="node-mount-point__icon" data-tree-icon="actions-close" title="' + TYPO3.lang['labels.temporaryDBmount'] + '"></div>' +
        '</div>'
      );

      _this.wrapper.parentNode
        .querySelector('[data-tree-icon=actions-close]')
        .addEventListener('click', function() {
          top.TYPO3.Backend.NavigationContainer.PageTree.unsetTemporaryMountPoint();
          _this.wrapper.parentNode.querySelector('.node-mount-point').remove();
        });

      // get icons
      _this.wrapper.parentNode.querySelectorAll('.node-mount-point [data-tree-icon]').forEach(function(iconElement) {
        Icons.getIcon(iconElement.dataset.treeIcon, Icons.sizes.small, null, null, 'inline').then(function(icon) {
          iconElement.insertAdjacentHTML('beforeend', icon);
        });
      });
    };

    /**
     * Displays a notification message and refresh nodes
     *
     * @param error
     */
    PageTree.prototype.errorNotification = function(error) {
      var title = TYPO3.lang.pagetree_networkErrorTitle;
      var desc = TYPO3.lang.pagetree_networkErrorDesc;

      if (error && error.target && (error.target.status || error.target.statusText)) {
        title += ' - ' + (error.target.status || '') + ' ' + (error.target.statusText || '');
      }

      Notification.error(title, desc);
      this.loadData();
    };

    PageTree.prototype.sendChangeCommand = function(data) {
      var _this = this;
      var params = '';

      if (data.target) {
        var targetUid = data.target.identifier;
        if (data.position === 'after') {
          targetUid = -targetUid;
        }
      }

      if (data.command === 'new') {
        params = '&data[pages][NEW_1][pid]=' + targetUid +
          '&data[pages][NEW_1][title]=' + encodeURIComponent(data.name) +
          '&data[pages][NEW_1][doktype]=' + data.type;

      } else if (data.command === 'edit') {
        params = '&data[pages][' + data.uid + '][' + data.nameSourceField + ']=' + encodeURIComponent(data.title);
      } else {
        if (data.command === 'delete') {
          if (data.uid === fsMod.recentIds.web) {
            _this.selectNode(_this.getFirstNode());
          }
          params = '&cmd[pages][' + data.uid + '][delete]=1';
        } else {
          params = 'cmd[pages][' + data.uid + '][' + data.command + ']=' + targetUid;
        }
      }

      _this.nodesAddPlaceholder();

      (new AjaxRequest(top.TYPO3.settings.ajaxUrls.record_process))
        .post(params, {
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        })
        .then(function(response) {
          return response.resolve();
        })
        .then(function(response) {
          if (response && response.hasErrors) {
            if (response.messages) {
              response.messages.forEach(function(message) {
                Notification.error(
                  message.title,
                  message.message
                );
              });
            } else {
              _this.errorNotification();
            }

            _this.nodesContainer.selectAll('.node').remove();
            _this.update();
            _this.nodesRemovePlaceholder();
          } else {
            _this.refreshOrFilterTree();
          }
        })
        .catch(function(error) {
          _this.errorNotification(error);
        });
    };

    PageTree.prototype.getFirstNode = function() {
      return this.nodes[0];
    };

    /**
     * Finds node by its stateIdentifier (e.g. "0_360")
     * @return {Node}
     */
    PageTree.prototype.getNodeByIdentifier = function(identifier) {
      return this.nodes.find(function (node) {
        return node.stateIdentifier === identifier;
      });
    };

    /**
     * Observer for the selectedNode event
     *
     * @param {Node} node
     */
    PageTree.prototype.nodeSelectedAfter = function(node) {
      if (!node.checked) {
        return;
      }
      //remember the selected page in the global state
      fsMod.recentIds.web = node.identifier;
      fsMod.currentBank = node.stateIdentifier.split('_')[0];
      fsMod.navFrameHighlightedID.web = node.stateIdentifier;

      var separator = '?';
      if (currentSubScript.indexOf('?') !== -1) {
        separator = '&';
      }

      TYPO3.Backend.ContentContainer.setUrl(
        currentSubScript + separator + 'id=' + node.identifier
      );
    };

    PageTree.prototype.nodeRightClick = function(selection) {
      var node = selection.closest('svg').querySelector('.nodes .node[data-state-id="' + this.stateIdentifier + '"]');

      if (node) {
        ContextMenu.show(
          node.dataset.table,
          this.identifier,
          node.dataset.context,
          node.dataset.iteminfo,
          node.dataset.parameters,
          node
        );
      }
    };

    PageTree.prototype.contextmenu = function(selection) {
      var node = selection.closest('svg').querySelector('.nodes .node[data-state-id="' + this.stateIdentifier + '"]');

      if (node) {
        ContextMenu.show(
          node.dataset.table,
          this.identifier,
          node.dataset.context,
          node.dataset.iteminfo,
          node.dataset.parameters,
          node
        );
      }
    };

    PageTree.prototype.updateSvg = function(nodeEnter) {
      nodeEnter
        .select('use')
        .attr('data-table', 'pages')
        .attr('data-context', 'tree');
    };

    /**
     * Event listener called for each loaded node,
     * here used to mark node remembered in fsMode as selected
     *
     * @param node
     */
    PageTree.prototype.prepareLoadedNode = function(node) {
      if (node.stateIdentifier === fsMod.navFrameHighlightedID.web) {
        node.checked = true;
      }
    };

    PageTree.prototype.hideChildren = function(node) {
      _super_.hideChildren(node);
      Persistent.set('BackendComponents.States.Pagetree.stateHash.' + node.stateIdentifier, 0);
    };

    PageTree.prototype.showChildren = function(node) {
      this.loadChildrenOfNode(node);
      _super_.showChildren(node);
      Persistent.set('BackendComponents.States.Pagetree.stateHash.' + node.stateIdentifier, 1);
    };

    /**
     * Loads child nodes via Ajax (used when expanding a collapesed node)
     *
     * @param parentNode
     * @return {boolean}
     */
    PageTree.prototype.loadChildrenOfNode = function(parentNode) {
      if (parentNode.loaded) {
        return;
      }
      var _this = this;
      _this.nodesAddPlaceholder();


      (new AjaxRequest(_this.settings.dataUrl + '&pid=' + parentNode.identifier + '&mount=' + parentNode.mountPoint + '&pidDepth=' + parentNode.depth))
        .get({cache: 'no-cache'})
        .then(function(response) {
          return response.resolve();
        })
        .then(function(json) {
          var nodes = Array.isArray(json) ? json : [];
          //first element is a parent
          nodes.shift();
          var index = _this.nodes.indexOf(parentNode) + 1;
          //adding fetched node after parent
          nodes.forEach(function (node, offset) {
            _this.nodes.splice(index + offset, 0, node);
          });

          parentNode.loaded = true;
          _this.setParametersNode();
          _this.prepareDataForVisibleNodes();
          _this.update();
          _this.nodesRemovePlaceholder();

          // Focus node only if it's not currently in edit mode
          if (!_this.nodeIsEdit) {
            _this.switchFocusNode(parentNode);
          }
        })
        .catch(function (error) {
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
        });
    };

    PageTree.prototype.updateNodeBgClass = function(nodeBg) {
      return _super_.updateNodeBgClass.call(this, nodeBg).call(this.initializeDragForNode());
    };

    PageTree.prototype.nodesUpdate = function(nodes) {
      var _this = this;

      nodes = _super_.nodesUpdate.call(this, nodes)
        .call(this.initializeDragForNode())
        .attr('data-table', 'pages')
        .attr('data-context', 'tree')
        .on('contextmenu', function(event, element) {
          event.preventDefault();
          var selection = this;
          _this.dispatch.call('nodeRightClick', element, selection);
        });

      var nodeStop = nodes
        .append('text')
        .text('+')
        .attr('class', 'node-stop')
        .attr('dx', 30)
        .attr('dy', 5)
        .attr('visibility', function(node) {
          return (node.stopPageTree && (node.depth !== 0)) ? 'visible' : 'hidden';
        })
        .on('click', function(event, node) {
          _this.setTemporaryMountPoint(node.identifier);
        });

      return nodes;
    };

    /**
     * Node selection logic (triggered by different events)
     * Page tree supports only one node to be selected at a time
     * so the default function from SvgTree needs to be overridden
     *
     * @param {Node} node
     */
    PageTree.prototype.selectNode = function (node) {
      if (!this.isNodeSelectable(node)) {
        return;
      }

      var _this = this;
      var selectedNodes = this.getSelectedNodes();
      selectedNodes.forEach(function (node) {
        if (node.checked === true) {
          node.checked = false;
          _this.dispatch.call('nodeSelectedAfter', _this, node);
        }
      });

      node.checked = true;

      this.dispatch.call('nodeSelectedAfter', this, node);
      this.update();
    };

    /**
     * Event handler for double click on a node's label
     * Changed text position if there is 'stop page tree' option
     *
     * @param {Node} node
     */
    PageTree.prototype.appendTextElement = function(node) {
      var _this = this;
      var clicks = 0;

      _super_.appendTextElement.call(this, node)
        .attr('dx', function(node) {
          var position = _this.textPosition;
          if (node.stopPageTree && node.depth !== 0) {
            position += 15;
          }

          if (node.locked) {
            position += 15;
          }

          return position;
        })
        .on('click', function(event, node) {
          if (node.identifier !== 0) {
            clicks++;

            if (clicks === 1) {
              setTimeout(function() {
                if (clicks === 1) {
                  _this.clickOnLabel(node, this);
                } else {
                  _this.editNodeLabel(node);
                }

                clicks = 0;
              }, 300);
            }
          } else {
            _this.clickOnLabel(node, this);
          }
        });
    };

    PageTree.prototype.filterTree = function() {
      var _this = this;
      _this.nodesAddPlaceholder();

      (new AjaxRequest(_this.settings.filterUrl + '&q=' + _this.searchQuery))
        .get({cache: 'no-cache'})
        .then(function(response) {
          return response.resolve();
        })
        .then(function(json) {
          var nodes = Array.isArray(json) ? json : [];
          if (nodes.length > 0) {
            if (_this.originalNodes.length === 0) {
              _this.originalNodes = JSON.stringify(_this.nodes);
            }
            _this.replaceData(nodes);
          }
          _this.nodesRemovePlaceholder();
        })
        .catch(function(error) {
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
        });
    };

    PageTree.prototype.refreshOrFilterTree = function() {
      if (this.searchQuery !== '') {
        this.filterTree();
      } else {
        this.refreshTree();
      }
    }

    PageTree.prototype.resetFilter = function() {
      this.searchQuery = '';
      if (this.originalNodes.length > 0) {
        var currentlySelected = this.getSelectedNodes()[0];
        if (typeof currentlySelected === 'undefined') {
          this.refreshTree();
          return;
        }

        this.nodes = JSON.parse(this.originalNodes);
        this.originalNodes = '';
        var currentlySelectedNode = this.getNodeByIdentifier(currentlySelected.stateIdentifier);

        if (currentlySelectedNode) {
          this.selectNode(currentlySelectedNode);
        } else {
          this.refreshTree();
        }
      } else {
        this.refreshTree();
      }
    };

    PageTree.prototype.setTemporaryMountPoint = function(pid) {
      var params = 'pid=' + pid;
      var _this = this;

      (new AjaxRequest(top.TYPO3.settings.ajaxUrls.page_tree_set_temporary_mount_point))
        .post(params, {
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        })
        .then(function(response) {
          return response.resolve();
        })
        .then(function(response) {
          if (response && response.hasErrors) {
            if (response.messages) {
              response.messages.forEach(function(message) {
                Notification.error(
                  message.title,
                  message.message
                );
              });
            } else {
              _this.errorNotification();
            }

            _this.update();
          } else {
            _this.addMountPoint(response.mountPointPath);
            _this.refreshOrFilterTree();
          }
        })
        .catch(function(error) {
          _this.errorNotification(error);
        });
    };

    PageTree.prototype.unsetTemporaryMountPoint = function() {
      var _this = this;
      Persistent.unset('pageTree_temporaryMountPoint').then(function() {
        _this.refreshTree();
      });
    };

    PageTree.prototype.sendEditNodeLabelCommand = function(node) {
      var _this = this;

      var params = '&data[pages][' + node.identifier + '][' + node.nameSourceField + ']=' + encodeURIComponent(node.newName);

      //remove old node from svg tree
      _this.nodesAddPlaceholder(node);

      (new AjaxRequest(top.TYPO3.settings.ajaxUrls.record_process))
        .post(params, {
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        })
        .then(function(response) {
          return response.resolve();
        })
        .then(function(response) {
          if (response && response.hasErrors) {
            if (response.messages) {
              response.messages.forEach(function(message) {
                Notification.error(
                  message.title,
                  message.message
                );
              });
            } else {
              _this.errorNotification();
            }

            _this.nodesAddPlaceholder();
            _this.refreshOrFilterTree();
          } else {
            node.name = node.newName;
            _this.svg.select('.node-placeholder[data-uid="' + node.stateIdentifier + '"]').remove();
            _this.refreshOrFilterTree();
            _this.nodesRemovePlaceholder();
          }
        })
        .catch(function(error) {
          _this.errorNotification(error);
        });
    };

    PageTree.prototype.editNodeLabel = function(node) {
      var _this = this;

      if (!node.allowEdit) {
        return;
      }

      _this.removeEditedText();
      _this.nodeIsEdit = true;

      d3selection.select(_this.svg.node().parentNode)
        .append('input')
        .attr('class', 'node-edit')
        .style('top', function() {
          var top = node.y + _this.settings.marginTop;
          return top + 'px';
        })
        .style('left', (node.x + _this.textPosition + 5) + 'px')
        .style('width', _this.settings.width - (node.x + _this.textPosition + 20) + 'px')
        .style('height', _this.settings.nodeHeight + 'px')
        .attr('type', 'text')
        .attr('value', node.name)
        .on('keydown', function(event) {
          var code = event.keyCode;

          if (code === 13 || code === 9) { //enter || tab
            var newName = this.value.trim();

            if (newName.length && (newName !== node.name)) {
              _this.nodeIsEdit = false;
              _this.removeEditedText();
              node.nameSourceField = node.nameSourceField || 'title';
              node.newName = newName;
              _this.sendEditNodeLabelCommand(node);
            } else {
              _this.nodeIsEdit = false;
              _this.removeEditedText();
            }
          } else if (code === 27) { //esc
            _this.nodeIsEdit = false;
            _this.removeEditedText();
          }
        })
        .on('blur', function() {
          if (_this.nodeIsEdit) {
            var newName = this.value.trim();

            if (newName.length && (newName !== node.name)) {
              node.nameSourceField = node.nameSourceField || 'title';
              node.newName = newName;

              _this.sendEditNodeLabelCommand(node);
            }

            _this.removeEditedText();
          }
        })
        .node()
        .select();
    };

    PageTree.prototype.removeEditedText = function() {
      var _this = this;
      var inputWrapper = d3selection.selectAll('.node-edit');

      if (inputWrapper.size()) {
        try {
          inputWrapper.remove();
          _this.nodeIsEdit = false;
        } catch (e) {

        }
      }
    };

    /**
     * Drag & Drop related code
     */

    /**
     * Initializes a drag&drop when called on the page tree. Should be moved somewhere else at some point
     * @returns {*}
     */
    PageTree.prototype.initializeDragForNode = function() {
      return this.dragDrop.connectDragHandler(new PageTreeDragHandler.PageTreeNodeDragHandler(this, this.dragDrop))
    };

    /**
     * Add new node to the tree (used in drag+drop)
     *
     * @type {Object} options
     * @private
     */
    PageTree.prototype.addNewNode = function(options) {
      var target = options.target;
      var index = this.nodes.indexOf(target);
      var newNode = {};
      newNode.command = 'new';
      newNode.type = options.type;
      newNode.identifier = -1;
      newNode.target = target;
      newNode.parents = target.parents;
      newNode.parentsStateIdentifier = target.parentsStateIdentifier;
      newNode.depth = target.depth;
      newNode.position = options.position;
      newNode.name = (typeof options.title !== 'undefined') ? options.title : TYPO3.lang['tree.defaultPageTitle'];
      newNode.y = newNode.y || newNode.target.y;
      newNode.x = newNode.x || newNode.target.x;

      this.nodeIsEdit = true;

      if (options.position === 'in') {
        newNode.depth++;
        newNode.parents.unshift(index);
        newNode.parentsStateIdentifier.unshift(this.nodes[index].stateIdentifier);
        this.nodes[index].hasChildren = true;
        this.showChildren(this.nodes[index]);
      }

      if (options.position === 'in' || options.position === 'after') {
        index++;
      }

      if (options.icon) {
        newNode.icon = options.icon;
      }

      if (newNode.position === 'before') {
        var positionAndTarget = this.dragDrop.setNodePositionAndTarget(index);
        newNode.position = positionAndTarget[0];
        newNode.target = positionAndTarget[1];
      }

      this.nodes.splice(index, 0, newNode);
      this.setParametersNode();
      this.prepareDataForVisibleNodes();
      this.update();
      this.removeEditedText();

      d3selection.select(this.svg.node().parentNode)
        .append('input')
        .attr('class', 'node-edit')
        .style('top', newNode.y + this.settings.marginTop + 'px')
        .style('left', newNode.x + this.textPosition + 5 + 'px')
        .style('width', this.settings.width - (newNode.x + this.textPosition + 20) + 'px')
        .style('height', this.settings.nodeHeight + 'px')
        .attr('text', 'text')
        .attr('value', newNode.name)
        .on('keydown', function(event) {
          var target = event.target;
          var code = event.keyCode;
          if (code === 13 || code === 9) { // enter || tab
            this.nodeIsEdit = false;
            var newName = target.value.trim();
            if (newName.length) {
              newNode.name = newName;
              this.removeEditedText();
              this.sendChangeCommand(newNode);
            } else {
              this.removeNode(newNode);
            }
          } else if (code === 27) { // esc
            this.nodeIsEdit = false;
            this.removeNode(newNode);
          }
        }.bind(this))
        .on('blur', function(event) {
          if (this.nodeIsEdit && (this.nodes.indexOf(newNode) > -1)) {
            var target = event.target;
            var newName = target.value.trim();
            if (newName.length) {
              newNode.name = newName;
              this.removeEditedText();
              this.sendChangeCommand(newNode);
            } else {
              this.removeNode(newNode);
            }
          }
        }.bind(this))
        .node()
        .select();
    }

    PageTree.prototype.removeNode = function(newNode) {
      var index = this.nodes.indexOf(newNode);
      // if newNode is only one child
      if (this.nodes[index - 1].depth != newNode.depth
        && (!this.nodes[index + 1] || this.nodes[index + 1].depth != newNode.depth)) {
        this.nodes[index - 1].hasChildren = false;
      }
      this.nodes.splice(index, 1);
      this.setParametersNode();
      this.prepareDataForVisibleNodes();
      this.update();
      this.removeEditedText();
    };


    return PageTree;
  });
