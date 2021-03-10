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

import AjaxRequest from 'TYPO3/CMS/Core/Ajax/AjaxRequest';
import {SvgTree, SvgTreeSettings, TreeNodeSelection} from '../SvgTree';
import {TreeNode} from '../Tree/TreeNode';
import ContextMenu = require('../ContextMenu');
import Persistent from '../Storage/Persistent';
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {FileStorageTreeNodeDragHandler, FileStorageTreeActions} from './FileStorageTreeActions';

export class FileStorageTree extends SvgTree {
  public settings: SvgTreeSettings;
  public searchQuery: string = '';
  protected networkErrorTitle: string = TYPO3.lang.tree_networkError;
  protected networkErrorMessage: string = TYPO3.lang.tree_networkErrorDescription;

  private originalNodes: string = '';
  private actionHandler: FileStorageTreeActions;

  public constructor() {
    super();
    this.settings.defaultProperties = {
      hasChildren: false,
      nameSourceField: 'title',
      itemType: 'sys_file',
      prefix: '',
      suffix: '',
      locked: false,
      loaded: false,
      overlayIcon: '',
      selectable: true,
      expanded: false,
      checked: false,
      backgroundColor: '',
      class: '',
      readableRootline: ''
    };
  }

  public initialize(selector: HTMLElement, settings: any, actionHandler?: FileStorageTreeActions): boolean {
    if (!super.initialize(selector, settings)) {
      return false;
    }

    this.dispatch.on('nodeSelectedAfter.fileStorageTree', (node: TreeNode) => this.nodeSelectedAfter(node));
    this.dispatch.on('nodeRightClick.fileStorageTree', (node: TreeNode) => this.nodeRightClick(node));
    this.dispatch.on('prepareLoadedNode.fileStorageTree', (node: TreeNode) => this.prepareLoadedNode(node));
    this.actionHandler = actionHandler;
    return true;
  }

  public hideChildren(node: TreeNode): void {
    super.hideChildren(node);
    Persistent.set('BackendComponents.States.FileStorageTree.stateHash.' + node.stateIdentifier, '0');
  }

  public showChildren(node: TreeNode): void {
    this.loadChildrenOfNode(node);
    super.showChildren(node);
    Persistent.set('BackendComponents.States.FileStorageTree.stateHash.' + node.stateIdentifier, '1');
  }

  public updateNodeBgClass(nodeBg: TreeNodeSelection) {
    return super.updateNodeBgClass.call(this, nodeBg).call(this.initializeDragForNode());
  }

  public nodesUpdate(nodes: TreeNodeSelection) {
    return super.nodesUpdate.call(this, nodes).call(this.initializeDragForNode());
  }

  /**
   * Node selection logic (triggered by different events)
   * Folder tree supports only one node to be selected at a time
   * so the default function from SvgTree needs to be overridden
   */
  public selectNode(node: TreeNode): void {
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
  }

  public selectNodeByIdentifier(identifier: string): void {
    identifier = encodeURIComponent(identifier);
    let nodeToSelect = this.nodes.filter((node: TreeNode) => { return node.identifier === identifier})[0];
    if (nodeToSelect && this.getSelectedNodes().filter((selectedNode: TreeNode) => { return selectedNode.identifier === nodeToSelect.identifier; }).length === 0) {
      this.selectNode(nodeToSelect);
    }
  }

  public filterTree() {
    this.nodesAddPlaceholder();
    (new AjaxRequest(this.settings.filterUrl + '&q=' + this.searchQuery))
      .get({cache: 'no-cache'})
      .then((response) => {
        return response.resolve();
      })
      .then((json) => {
        let nodes = Array.isArray(json) ? json : [];
        if (nodes.length) {
          if (this.originalNodes === '') {
            this.originalNodes = JSON.stringify(this.nodes);
          }
        }
        this.replaceData(nodes);
        this.nodesRemovePlaceholder();
      })
      .catch((error: any) => {
        this.errorNotification(error);
        this.nodesRemovePlaceholder();
        throw error;
      });
  }

  public refreshOrFilterTree(): void {
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

  /**
   * Initializes a drag&drop when called on the tree. Should be moved somewhere else at some point
   */
  protected initializeDragForNode() {
    return this.actionHandler.connectDragHandler(new FileStorageTreeNodeDragHandler(this, this.actionHandler))
  }

  protected getNodeTitle(node: TreeNode): string {
    return decodeURIComponent(node.name);
  }

  /**
   * Loads child nodes via Ajax (used when expanding a collapsed node)
   */
  private loadChildrenOfNode(parentNode: TreeNode): void {
    if (parentNode.loaded) {
      this.prepareDataForVisibleNodes();
      this.update();
      return;
    }
    this.nodesAddPlaceholder();

    (new AjaxRequest(this.settings.dataUrl + '&parent=' + parentNode.identifier + '&currentDepth=' + parentNode.depth))
      .get({cache: 'no-cache'})
      .then((response: AjaxResponse) => response.resolve())
      .then((json: any) => {
        let nodes = Array.isArray(json) ? json : [];
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
        this.switchFocusNode(parentNode);
      })
      .catch((error: any) => {
        this.errorNotification(error, false);
        this.nodesRemovePlaceholder();
        throw error;
      });
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
  private nodeSelectedAfter(node: TreeNode): void {
    if (!node.checked) {
      return;
    }
    // remember the selected page in the global state
    window.fsMod.recentIds.file = node.identifier;
    window.fsMod.navFrameHighlightedID.file = node.stateIdentifier;

    const separator = (window.currentSubScript.indexOf('?') !== -1) ? '&' : '?';
    TYPO3.Backend.ContentContainer.setUrl(
      window.currentSubScript + separator + 'id=' + node.identifier
    );
  };

  private nodeRightClick(node: TreeNode): void {
    ContextMenu.show(
      node.itemType,
      decodeURIComponent(node.identifier),
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
  private prepareLoadedNode(node: TreeNode): void {
    if (node.stateIdentifier === window.fsMod.navFrameHighlightedID.file) {
      node.checked = true;
    }
  }
}
