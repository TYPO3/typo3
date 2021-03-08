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

import * as d3selection from 'd3-selection';
import AjaxRequest from 'TYPO3/CMS/Core/Ajax/AjaxRequest';
import {SvgTree, SvgTreeSettings, TreeNodeSelection} from '../SvgTree';
import {TreeNode} from '../Tree/TreeNode';
import {PageTreeDragDrop, PageTreeNodeDragHandler} from './PageTreeDragDrop';
import Icons = require('../Icons');
import ContextMenu = require('../ContextMenu');
import Persistent from '../Storage/Persistent';
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {KeyTypesEnum as KeyTypes} from '../Enum/KeyTypes';

interface PageTreeSettings extends SvgTreeSettings {
  temporaryMountPoint?: string;
}

export class PageTree extends SvgTree
{
  public searchQuery: string = '';
  public settings: PageTreeSettings;
  protected networkErrorTitle: string = TYPO3.lang.pagetree_networkErrorTitle;
  protected networkErrorMessage: string = TYPO3.lang.pagetree_networkErrorDesc;
  private originalNodes: string = '';
  private dragDrop: PageTreeDragDrop;
  private nodeIsEdit: boolean;
  public constructor() {
    super();
    this.settings.defaultProperties = {
      hasChildren: false,
      nameSourceField: 'title',
      itemType: 'pages',
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
  }

  public initialize(selector: HTMLElement, settings: any, dragDrop?: PageTreeDragDrop): boolean {
    if (!super.initialize(selector, settings)) {
      return false;
    }

    this.dispatch.on('nodeSelectedAfter.pageTree', (node: TreeNode) => this.nodeSelectedAfter(node));
    this.dispatch.on('nodeRightClick.pageTree', (node: TreeNode) => this.nodeRightClick(node));
    this.dispatch.on('prepareLoadedNode.pageTree', (node: TreeNode) => this.prepareLoadedNode(node));
    this.dragDrop = dragDrop;

    if (this.settings.temporaryMountPoint) {
      this.addMountPoint(this.settings.temporaryMountPoint);
    }
    return true;
  };

  public sendChangeCommand(data: any): void {
    let params = '';
    let targetUid = 0;

    if (data.target) {
      targetUid = data.target.identifier;
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
        if (data.uid === window.fsMod.recentIds.web) {
          this.selectNode(this.getFirstNode());
        }
        params = '&cmd[pages][' + data.uid + '][delete]=1';
      } else {
        params = 'cmd[pages][' + data.uid + '][' + data.command + ']=' + targetUid;
      }
    }

    this.nodesAddPlaceholder();

    (new AjaxRequest(top.TYPO3.settings.ajaxUrls.record_process))
      .post(params, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
      })
      .then((response) => {
        return response.resolve();
      })
      .then((response) => {
        if (response && response.hasErrors) {
          this.errorNotification(response.messages, false);
          this.nodesContainer.selectAll('.node').remove();
          this.update();
          this.nodesRemovePlaceholder();
        } else {
          this.refreshOrFilterTree();
        }
      })
      .catch((error) => {
        this.errorNotification(error);
      });
  }

  public getFirstNode(): TreeNode {
    return this.nodes[0];
  }

  public nodeRightClick(node: TreeNode): void {
    ContextMenu.show(
      node.itemType,
      parseInt(node.identifier, 10),
      'tree',
      '',
      '',
      this.getNodeElement(node)
    );
  };

  /**
   * Event listener called for each loaded node,
   * here used to mark node remembered in fsMode as selected
   */
  public prepareLoadedNode(node: TreeNode) {
    if (node.stateIdentifier === window.fsMod.navFrameHighlightedID.web) {
      node.checked = true;
    }
  }

  public hideChildren(node: TreeNode) {
    super.hideChildren(node);
    Persistent.set('BackendComponents.States.Pagetree.stateHash.' + node.stateIdentifier, '0');
  }

  public showChildren(node: TreeNode) {
    this.loadChildrenOfNode(node);
    super.showChildren(node);
    Persistent.set('BackendComponents.States.Pagetree.stateHash.' + node.stateIdentifier, '1');
  }

  /**
   * Loads child nodes via Ajax (used when expanding a collapesed node)
   *
   * @param parentNode
   * @return {boolean}
   */
  public loadChildrenOfNode(parentNode: TreeNode) {
    if (parentNode.loaded) {
      return;
    }

    this.nodesAddPlaceholder();
    (new AjaxRequest(this.settings.dataUrl + '&pid=' + parentNode.identifier + '&mount=' + parentNode.mountPoint + '&pidDepth=' + parentNode.depth))
      .get({cache: 'no-cache'})
      .then((response: AjaxResponse) => response.resolve())
      .then((json: any) => {
        let nodes = Array.isArray(json) ? json : [];
        //first element is a parent
        nodes.shift();
        const index = this.nodes.indexOf(parentNode) + 1;
        //adding fetched node after parent
        nodes.forEach((node: TreeNode, offset: number) => {
          this.nodes.splice(index + offset, 0, node);
        });

        parentNode.loaded = true;
        this.setParametersNode();
        this.prepareDataForVisibleNodes();
        this.update();
        this.nodesRemovePlaceholder();

        // Focus node only if it's not currently in edit mode
        if (!this.nodeIsEdit) {
          this.switchFocusNode(parentNode);
        }
      })
      .catch((error: any) => {
        this.errorNotification(error, false)
        this.nodesRemovePlaceholder();
        throw error;
      });
  };

  public updateNodeBgClass(nodeBg: TreeNodeSelection) {
    return super.updateNodeBgClass.call(this, nodeBg).call(this.initializeDragForNode());
  };

  public nodesUpdate(nodes: TreeNodeSelection) {
    nodes = super.nodesUpdate.call(this, nodes).call(this.initializeDragForNode());

    nodes
      .append('text')
      .text('+')
      .attr('class', 'node-stop')
      .attr('dx', 30)
      .attr('dy', 5)
      .attr('visibility', (node: TreeNode) => node.stopPageTree && node.depth !== 0 ? 'visible' : 'hidden')
      .on('click', (evt: MouseEvent, node: TreeNode) => this.setTemporaryMountPoint(parseInt(node.identifier, 10)));

    return nodes;
  };

  /**
   * Node selection logic (triggered by different events)
   * Page tree supports only one node to be selected at a time
   * so the default function from SvgTree needs to be overridden
   */
  public selectNode(node: TreeNode) {
    if (!this.isNodeSelectable(node)) {
      return;
    }

    // Disable already selected nodes
    this.getSelectedNodes().forEach((node: TreeNode) => {
      if (node.checked === true) {
        node.checked = false;
        this.dispatch.call('nodeSelectedAfter', this, node);
      }
    });

    node.checked = true;
    this.dispatch.call('nodeSelectedAfter', this, node);
    this.update();
  };

  public filterTree() {
    this.nodesAddPlaceholder();
    (new AjaxRequest(this.settings.filterUrl + '&q=' + this.searchQuery))
      .get({cache: 'no-cache'})
      .then((response) => {
        return response.resolve();
      })
      .then((json) => {
        let nodes = Array.isArray(json) ? json : [];
        if (nodes.length > 0) {
          if (this.originalNodes === '') {
            this.originalNodes = JSON.stringify(this.nodes);
          }
          this.replaceData(nodes);
        }
        this.nodesRemovePlaceholder();
      })
      .catch((error: any) => {
        this.errorNotification(error, false)
        this.nodesRemovePlaceholder();
        throw error;
      });
  };

  public refreshOrFilterTree() {
    if (this.searchQuery !== '') {
      this.filterTree();
    } else {
      this.refreshTree();
    }
  }

  public resetFilter(): void {
    this.searchQuery = '';
    if (this.originalNodes.length > 0) {
      let currentlySelected = this.getSelectedNodes()[0];
      if (typeof currentlySelected === 'undefined') {
        this.refreshTree();
        return;
      }

      this.nodes = JSON.parse(this.originalNodes);
      this.originalNodes = '';
      let currentlySelectedNode = this.getNodeByIdentifier(currentlySelected.stateIdentifier);
      if (currentlySelectedNode) {
        this.selectNode(currentlySelectedNode);
      } else {
        this.refreshTree();
      }
    } else {
      this.refreshTree();
    }
  }

  public setTemporaryMountPoint(pid: number): void {
    const params = 'pid=' + pid;

    (new AjaxRequest(top.TYPO3.settings.ajaxUrls.page_tree_set_temporary_mount_point))
      .post(params, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
      })
      .then((response) => {
        return response.resolve();
      })
      .then((response) => {
        if (response && response.hasErrors) {
          this.errorNotification(response.message, true);
          this.update();
        } else {
          this.addMountPoint(response.mountPointPath);
          this.refreshOrFilterTree();
        }
      })
      .catch((error) => {
        this.errorNotification(error, true);
      });
  }

  public unsetTemporaryMountPoint() {
    Persistent.unset('pageTree_temporaryMountPoint').then(() => {
      this.refreshTree();
    });
  };

  /**
   * Drag & Drop + Node Title Editing (In-Place Editing) related code
   */

  /**
   * Initializes a drag&drop when called on the page tree. Should be moved somewhere else at some point
   */
  public initializeDragForNode() {
    return this.dragDrop.connectDragHandler(new PageTreeNodeDragHandler(this, this.dragDrop))
  }

  /**
   * Event handler for double click on a node's label
   * Changed text position if there is 'stop page tree' option
   */
  protected appendTextElement(nodes: TreeNodeSelection): TreeNodeSelection {
    let clicks = 0;
    return super.appendTextElement(nodes)
      .attr('dx', (node) => {
        let position = this.textPosition;
        if (node.stopPageTree && node.depth !== 0) {
          position += 15;
        }
        if (node.locked) {
          position += 15;
        }
        return position;
      })
      .on('click', (event, node: TreeNode) => {
        if (node.identifier === '0') {
          this.clickOnLabel(node);
          return;
        }
        if (++clicks === 1) {
          setTimeout(() => {
            if (clicks === 1) {
              this.clickOnLabel(node);
            } else {
              this.editNodeLabel(node);
            }
            clicks = 0;
          }, 300);
        }
      });
  };

  private sendEditNodeLabelCommand(node: TreeNode) {
    const params = '&data[pages][' + node.identifier + '][' + node.nameSourceField + ']=' + encodeURIComponent(node.newName);

    // remove old node from svg tree
    this.nodesAddPlaceholder(node);

    (new AjaxRequest(top.TYPO3.settings.ajaxUrls.record_process))
      .post(params, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
      })
      .then((response) => {
        return response.resolve();
      })
      .then((response) => {
        if (response && response.hasErrors) {
          this.errorNotification(response.messages, false);
        } else {
          node.name = node.newName;
        }
        this.refreshOrFilterTree();
      })
      .catch((error) => {
        this.errorNotification(error, true);
      });
  }

  private editNodeLabel(node: TreeNode) {
    if (!node.allowEdit) {
      return;
    }
    this.removeEditedText();
    this.nodeIsEdit = true;

    d3selection.select(this.svg.node().parentNode as HTMLElement)
      .append('input')
      .attr('class', 'node-edit')
      .style('top', () => {
        const top = node.y + this.settings.marginTop;
        return top + 'px';
      })
      .style('left', (node.x + this.textPosition + 5) + 'px')
      .style('width', this.settings.width - (node.x + this.textPosition + 20) + 'px')
      .style('height', this.settings.nodeHeight + 'px')
      .attr('type', 'text')
      .attr('value', node.name)
      .on('keydown', (event: KeyboardEvent) => {
        // @todo Migrate to `evt.code`, see https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent/code
        const code = event.keyCode;

        if (code === KeyTypes.ENTER || code === KeyTypes.TAB) {
          const target = event.target as HTMLInputElement;
          const newName = target.value.trim();
          this.nodeIsEdit = false;
          this.removeEditedText();
          if (newName.length && (newName !== node.name)) {
            node.nameSourceField = node.nameSourceField || 'title';
            node.newName = newName;
            this.sendEditNodeLabelCommand(node);
          }
        } else if (code === KeyTypes.ESCAPE) {
          this.nodeIsEdit = false;
          this.removeEditedText();
        }
      })
      .on('blur', (evt: FocusEvent) => {
        if (!this.nodeIsEdit) {
          return;
        }
        const target = evt.target as HTMLInputElement;
        const newName = target.value.trim();
        if (newName.length && (newName !== node.name)) {
          node.nameSourceField = node.nameSourceField || 'title';
          node.newName = newName;
          this.sendEditNodeLabelCommand(node);
        }
        this.removeEditedText();
      })
      .node()
      .select();
  }

  private removeEditedText() {
    const inputWrapper = d3selection.selectAll('.node-edit');
    if (inputWrapper.size()) {
      try {
        inputWrapper.remove();
        this.nodeIsEdit = false;
      } catch (e) {
        // ...
      }
    }
  }

  /**
   * Finds node by its stateIdentifier (e.g. "0_360")
   */
  private getNodeByIdentifier(identifier: string): TreeNode|null {
    return this.nodes.find((node: TreeNode) => {
      return node.stateIdentifier === identifier;
    });
  }

  /**
   * Observer for the selectedNode event
   */
  private nodeSelectedAfter(node: TreeNode) {
    if (!node.checked) {
      return;
    }
    //remember the selected page in the global state
    window.fsMod.recentIds.web = node.identifier;
    window.fsMod.currentBank = node.stateIdentifier.split('_')[0];
    window.fsMod.navFrameHighlightedID.web = node.stateIdentifier;

    let separator = '?';
    if (window.currentSubScript.indexOf('?') !== -1) {
      separator = '&';
    }

    TYPO3.Backend.ContentContainer.setUrl(
      window.currentSubScript + separator + 'id=' + node.identifier
    );
  }

  private addMountPoint(breadcrumb: string) {
    let existingMountPointInfo = this.wrapper.parentNode.querySelector('.node-mount-point');
    if (existingMountPointInfo) {
      existingMountPointInfo.parentNode.removeChild(existingMountPointInfo);
    }

    this.wrapper.insertAdjacentHTML('beforebegin',
      '<div class="node-mount-point">' +
      '<div class="node-mount-point__icon" data-tree-icon="actions-document-info"></div>' +
      '<div class="node-mount-point__text"><div>' + breadcrumb + '</div></div>' +
      '<div class="node-mount-point__icon" data-tree-icon="actions-close" title="' + TYPO3.lang['labels.temporaryDBmount'] + '"></div>' +
      '</div>'
    );

    this.wrapper.parentNode
      .querySelector('[data-tree-icon=actions-close]')
      .addEventListener('click', () => {
        this.unsetTemporaryMountPoint();
        this.wrapper.parentNode.querySelector('.node-mount-point').remove();
      });

    // get icons
    this.wrapper.parentNode.querySelectorAll('.node-mount-point [data-tree-icon]').forEach((iconElement: HTMLElement) => {
      Icons.getIcon(iconElement.dataset.treeIcon, Icons.sizes.small, null, null, 'inline' as any).then((icon: string) => {
        iconElement.insertAdjacentHTML('beforeend', icon);
      });
    });
  }
}
