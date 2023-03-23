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

import { html, LitElement, TemplateResult } from 'lit';
import { customElement, query } from 'lit/decorators';
import '@typo3/backend/element/icon-element';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import { TreeNodeSelection, Toolbar } from '@typo3/backend/svg-tree';
import { TreeNode } from '@typo3/backend/tree/tree-node';
import { FileStorageTree } from '@typo3/backend/tree/file-storage-tree';
import { DragDropHandler, DragDrop, DraggablePositionEnum, DragDropTargetPosition } from '@typo3/backend/tree/drag-drop';
import ContextMenu from '@typo3/backend/context-menu';
import Notification from '@typo3/backend/notification';
import Persistent from '@typo3/backend/storage/persistent';
import { ModuleStateStorage } from '@typo3/backend/storage/module-state-storage';
import { ModuleUtility } from '@typo3/backend/module';
import { FileListDragDropDetail, FileListDragDropEvent } from '@typo3/filelist/file-list-dragdrop';
import { Resource, ResourceInterface } from '@typo3/backend/resource/resource';

export const navigationComponentName: string = 'typo3-backend-navigation-component-filestoragetree';

/**
 * FileStorageTree which allows for drag+drop, and in-place editing, as well as
 * tree highlighting from the outside
 */
@customElement('typo3-backend-navigation-component-filestorage-tree')
export class EditableFileStorageTree extends FileStorageTree {
  private readonly actionHandler: FileStorageTreeActions;

  public constructor() {
    super();
    this.actionHandler = new FileStorageTreeActions(this);
  }

  public connectedCallback(): void {
    super.connectedCallback();
    document.addEventListener('dragover', this.handleDragOver);
    document.addEventListener('drop', this.handleDrop);
  }

  public disconnectedCallback(): void {
    super.disconnectedCallback();
    document.removeEventListener('dragover', this.handleDragOver);
    document.removeEventListener('drop', this.handleDrop);
  }

  public updateNodeBgClass(nodesBg: TreeNodeSelection): TreeNodeSelection {
    return super.updateNodeBgClass.call(this, nodesBg).call(this.initializeDragForNode());
  }

  protected nodesUpdate(nodes: TreeNodeSelection): TreeNodeSelection {
    return super.nodesUpdate.call(this, nodes).call(this.initializeDragForNode());
  }

  private handleDragOver = (e: DragEvent): void => {
    const target = e.target as SVGElement;
    const node = this.getNodeFromElement(target);
    if (node) {
      if (this.hoveredNode && node.stateIdentifier !== this.hoveredNode.stateIdentifier) {
        this.onMouseOutOfNode(this.hoveredNode);
      }
      if (!node.isOver) {
        this.onMouseOverNode(node);
      }
    }

    e.preventDefault();
  };

  private handleDrop = (event: DragEvent): void => {
    const target = event.target as Element;
    const element = target.closest('[data-state-id]') as SVGElement;
    const node = this.getNodeFromElement(element);

    if (node) {
      const targetResource = FileResource.fromTreeNode(node);
      const fileOperationCollection = FileOperationCollection.fromDataTransfer(event.dataTransfer, targetResource);
      const operationConflicts = fileOperationCollection.getConflictingOperationsForTreeNode(node);
      if (operationConflicts.length > 0) {
        operationConflicts.forEach((operation: FileOperation) => {
          Notification.showMessage(
            TYPO3.lang['drop.conflict'],
            TYPO3.lang['mess.drop.conflict']
              .replace('%s', operation.resource.name)
              .replace('%s', decodeURIComponent(node.identifier)),
            SeverityEnum.error
          );
        });
        return;
      }

      this.actionHandler.initiateDropAction(fileOperationCollection);
    }
    event.preventDefault();
  };

  /**
   * Initializes a drag&drop when called on the tree.
   */
  private initializeDragForNode() {
    return this.actionHandler.connectDragHandler(new FileStorageTreeNodeDragHandler(this, this.actionHandler));
  }
}

/**
 * Responsible for setting up the viewport for the Navigation Component for the File Tree
 */
@customElement('typo3-backend-navigation-component-filestoragetree')
export class FileStorageTreeNavigationComponent extends LitElement {
  @query('.svg-tree-wrapper') tree: EditableFileStorageTree;
  @query('typo3-backend-tree-toolbar') toolbar: Toolbar;

  public connectedCallback(): void {
    super.connectedCallback();
    document.addEventListener('typo3:filestoragetree:refresh', this.refresh);
    document.addEventListener('typo3:filestoragetree:selectFirstNode', this.selectFirstNode);
    // event listener updating current tree state, this can be removed in TYPO3 v12
    document.addEventListener('typo3:filelist:treeUpdateRequested', this.treeUpdateRequested);
  }

  public disconnectedCallback(): void {
    document.removeEventListener('typo3:filestoragetree:refresh', this.refresh);
    document.removeEventListener('typo3:filestoragetree:selectFirstNode', this.selectFirstNode);
    document.removeEventListener('typo3:filelist:treeUpdateRequested', this.treeUpdateRequested);
    super.disconnectedCallback();
  }

  // disable shadow dom for now
  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected render(): TemplateResult {
    const treeSetup = {
      dataUrl: top.TYPO3.settings.ajaxUrls.filestorage_tree_data,
      filterUrl: top.TYPO3.settings.ajaxUrls.filestorage_tree_filter,
      showIcons: true
    };

    return html`
      <div id="typo3-filestoragetree" class="svg-tree">
        <div>
          <typo3-backend-tree-toolbar .tree="${this.tree}" id="filestoragetree-toolbar" class="svg-toolbar"></typo3-backend-tree-toolbar>
          <div class="navigation-tree-container">
            <typo3-backend-navigation-component-filestorage-tree id="typo3-filestoragetree-tree" class="svg-tree-wrapper" .setup=${treeSetup}></typo3-backend-navigation-component-filestorage-tree>
          </div>
        </div>
        <div class="svg-tree-loader">
          <typo3-backend-icon identifier="spinner-circle-light" size="large"></typo3-backend-icon>
        </div>
      </div>
    `;
  }

  protected firstUpdated() {
    this.toolbar.tree = this.tree;
    this.tree.addEventListener('typo3:svg-tree:expand-toggle', this.toggleExpandState);
    this.tree.addEventListener('typo3:svg-tree:node-selected', this.loadContent);
    this.tree.addEventListener('typo3:svg-tree:node-context', this.showContextMenu);
    this.tree.addEventListener('typo3:svg-tree:nodes-prepared', this.selectActiveNode);
  }

  private refresh = (): void => {
    this.tree.refreshOrFilterTree();
  };

  private selectFirstNode = (): void => {
    const node = this.tree.nodes[0];
    if (node) {
      this.tree.selectNode(node, true);
    }
  };

  // event listener updating current tree state, this can be removed in TYPO3 v12
  private treeUpdateRequested = (evt: CustomEvent): void => {
    const identifier = encodeURIComponent(evt.detail.payload.identifier);
    const nodeToSelect = this.tree.nodes.filter((node: TreeNode) => { return node.identifier === identifier; })[0];
    if (nodeToSelect && this.tree.getSelectedNodes().filter((selectedNode: TreeNode) => { return selectedNode.identifier === nodeToSelect.identifier; }).length === 0) {
      this.tree.selectNode(nodeToSelect, false);
    }
  };

  private toggleExpandState = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNode;
    if (node) {
      Persistent.set('BackendComponents.States.FileStorageTree.stateHash.' + node.stateIdentifier, (node.expanded ? '1' : '0'));
    }
  };

  private loadContent = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNode;
    if (!node?.checked) {
      return;
    }

    // remember the selected folder in the global state
    ModuleStateStorage.update('file', node.identifier, true);

    if (evt.detail.propagate === false) {
      return;
    }

    // Load the currently selected module with the updated URL
    const moduleMenu = top.TYPO3.ModuleMenu.App;
    let contentUrl = ModuleUtility.getFromName(moduleMenu.getCurrentModule()).link;
    contentUrl += contentUrl.includes('?') ? '&' : '?';
    top.TYPO3.Backend.ContentContainer.setUrl(contentUrl + 'id=' + node.identifier);
  };

  private showContextMenu = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNode;
    if (!node) {
      return;
    }
    ContextMenu.show(
      node.itemType,
      decodeURIComponent(node.identifier),
      'tree',
      '',
      '',
      this.tree.getElementFromNode(node)
    );
  };

  /**
   * Event listener called for each loaded node,
   * here used to mark node remembered in ModuleStateStorage as selected
   */
  private selectActiveNode = (evt: CustomEvent): void => {
    const selectedNodeIdentifier = ModuleStateStorage.current('file').selection;
    const nodes = evt.detail.nodes as Array<TreeNode>;
    evt.detail.nodes = nodes.map((node: TreeNode) => {
      if (node.identifier === selectedNodeIdentifier) {
        node.checked = true;
      }
      return node;
    });
  };
}

interface NodePositionOptions {
  node: TreeNode,
  target: TreeNode,
  identifier: string,
  position: DraggablePositionEnum
}

/**
 * Extends Drag&Drop functionality for File Storage Tree positioning when dropping
 * Works with and without d3.drag.
 */
class FileStorageTreeActions extends DragDrop {

  /**
   * returns true if the node that is currently active has the EXACT same parent node.
   * Typical use case: You cannot move a folder to the parent folder.
   */
  public isInSameParentNode(draggingNode: TreeNode, targetNode: TreeNode): boolean {
    return draggingNode.stateIdentifier == targetNode.stateIdentifier
      || draggingNode.parentsStateIdentifier[0] == targetNode.stateIdentifier
      || targetNode.parentsStateIdentifier.includes(draggingNode.stateIdentifier);
  }

  /**
   * Prepares all the details, which node is dropped on which other, if it is inside or before
   * the target node (= droppedNode).
   */
  public getDropCommandDetails(droppedNode: TreeNode, draggingNode: TreeNode): null | NodePositionOptions {
    const nodes = this.tree.nodes;
    const identifier = draggingNode.identifier;
    let position = this.tree.settings.nodeDragPosition;
    let target = droppedNode || draggingNode;

    if (identifier === target.identifier) {
      return null;
    }

    if (position === DraggablePositionEnum.BEFORE) {
      const index = nodes.indexOf(droppedNode);
      const positionAndTarget = this.setNodePositionAndTarget(index);
      if (positionAndTarget === null) {
        return null;
      }
      position = positionAndTarget.position;
      target = positionAndTarget.target;
    }

    return {
      node: draggingNode,
      identifier: identifier, // dragged node id
      target: target, // hovered node
      position: position // before, in, after
    };
  }

  /**
   * Returns position and target node where it should be added
   */
  public setNodePositionAndTarget(index: number): null | DragDropTargetPosition {
    const nodes = this.tree.nodes;
    const nodeOver = nodes[index];
    const nodeOverDepth = nodeOver.depth;
    if (index > 0) {
      index--;
    }
    const nodeBefore = nodes[index];
    const nodeBeforeDepth = nodeBefore.depth;
    const target = this.tree.nodes[index];

    if (nodeBeforeDepth === nodeOverDepth) {
      return { position: DraggablePositionEnum.AFTER, target };
    } else if (nodeBeforeDepth < nodeOverDepth) {
      return { position: DraggablePositionEnum.INSIDE, target };
    } else {
      for (let i = index; i >= 0; i--) {
        if (nodes[i].depth === nodeOverDepth) {
          return { position: DraggablePositionEnum.AFTER, target: this.tree.nodes[i] };
        } else if (nodes[i].depth < nodeOverDepth) {
          return { position: DraggablePositionEnum.AFTER, target: nodes[i] };
        }
      }
    }
    return null;
  }

  /**
   * Checks various conditions and updates the dragging element (CSS class) and the drag position
   * in order to show if a node can be dropped.
   *
   * @param draggingNode
   */
  public updateStateOfHoveredNode(draggingNode: TreeNode | null): void {
    this.tree.settings.nodeDragPosition = false;
    // Mouse is not on a node, deny
    if (!this.tree.hoveredNode) {
      this.addNodeDdClass('nodrop');
      return;
    }
    // Mouse is outside SVG, deny
    if (!this.tree.isOverSvg) {
      this.addNodeDdClass('nodrop');
      return;
    }
    // we are hovering over the currently dragged node, deny
    if (draggingNode.isOver || this.isTheSameNode(this.tree.hoveredNode, draggingNode)) {
      this.addNodeDdClass('nodrop');
      return;
    }
    // File storage specific, does not make sense to move into the same folder (we have it in the same folder)
    if (this.isInSameParentNode(draggingNode, this.tree.hoveredNode)) {
      this.addNodeDdClass('nodrop');
      return;
    }
    // All good
    this.addNodeDdClass('ok-append');
    this.tree.settings.nodeDragPosition = DraggablePositionEnum.INSIDE;
  }

  public isDropAllowed(hoveredNode: TreeNode, draggingNode: TreeNode): boolean {
    if (draggingNode.isOver) {
      return false;
    }
    if (this.isTheSameNode(hoveredNode, draggingNode)) {
      return false;
    }
    if (!this.tree.isOverSvg) {
      return false;
    }
    return true;
  }

  public initiateDropAction(fileOperationCollection: FileOperationCollection): void {
    const detail: FileListDragDropDetail = {
      action: 'transfer',
      resources: fileOperationCollection.getResources(),
      target: fileOperationCollection.target,
    };
    top.document.dispatchEvent(new CustomEvent(FileListDragDropEvent.transfer, { detail: detail }));
  }
}

/**
 * Drag and drop for nodes (copy/move)
 */
class FileStorageTreeNodeDragHandler implements DragDropHandler {
  public dragStarted: boolean = false;
  public startPageX: number = 0;
  public startPageY: number = 0;
  private tree: FileStorageTree;
  private actionHandler: FileStorageTreeActions;

  constructor(tree: FileStorageTree, actionHandler: FileStorageTreeActions) {
    this.tree = tree;
    this.actionHandler = actionHandler;
  }

  public onDragStart(event: MouseEvent, draggingNode: TreeNode | null): boolean {
    if (draggingNode.depth === 0) {
      return false;
    }
    this.startPageX = event.pageX;
    this.startPageY = event.pageY;
    this.dragStarted = false;
    return true;
  }

  public onDragOver(event: MouseEvent, draggingNode: TreeNode | null): boolean {
    if (this.actionHandler.isDragNodeDistanceMore(event, this)) {
      this.dragStarted = true;
    } else {
      return false;
    }

    if (draggingNode.depth === 0) {
      return false;
    }

    // Create the draggable = the shadowed element which follows the cursor
    if (!this.actionHandler.getDraggable()) {
      this.actionHandler.createDraggableFromExistingNode(draggingNode);
    }

    this.actionHandler.openNodeTimeout();
    this.actionHandler.updateDraggablePosition(event);

    // Calculate if the draggingNode is allowed to be dropped, and update the currently hovered node / bg for this
    this.actionHandler.updateStateOfHoveredNode(draggingNode);
    return true;
  }

  public onDrop(event: MouseEvent, draggingNode: TreeNode): boolean {
    if (!this.dragStarted || draggingNode.depth === 0) {
      return false;
    }

    this.actionHandler.cleanupDrop();
    if (this.actionHandler.isDropAllowed(this.tree.hoveredNode, draggingNode)) {
      const options = this.actionHandler.getDropCommandDetails(this.tree.hoveredNode, draggingNode);
      if (options === null) {
        return false;
      }
      const fileOperationCollection = FileOperationCollection.fromNodePositionOptions(options);
      const operationConflicts = fileOperationCollection.getConflictingOperationsForTreeNode(options.target);
      if (operationConflicts.length > 0) {
        operationConflicts.forEach((operation: FileOperation) => {
          Notification.showMessage(
            TYPO3.lang['drop.conflict'],
            TYPO3.lang['mess.drop.conflict']
              .replace('%s', operation.resource.name)
              .replace('%s', decodeURIComponent(options.target.identifier)),
            SeverityEnum.error
          );
        });
        return false;
      }
      this.actionHandler.initiateDropAction(fileOperationCollection);
    }
    return true;
  }
}

/**
 * Internal helper class for drag&drop handling
 */
class FileOperation {
  public constructor(
    public readonly resource: ResourceInterface,
    public readonly position: DraggablePositionEnum = DraggablePositionEnum.INSIDE
  ) {
  }

  public hasConflictWithTreeNode(node: TreeNode): boolean {
    return this.resource.type === 'folder' && (
      node.stateIdentifier === this.resource.stateIdentifier
      || node.parentsStateIdentifier[0] == this.resource.stateIdentifier
      || node.parentsStateIdentifier.includes(this.resource.stateIdentifier)
    );
  }
}

class FileResource extends Resource {
  public static fromTreeNode(node: TreeNode): ResourceInterface {
    return new FileResource(
      decodeURIComponent(node.type),
      decodeURIComponent(node.identifier),
      decodeURIComponent(node.stateIdentifier),
      decodeURIComponent(node.name)
    );
  }
}

class FileOperationCollection {
  protected constructor(
    public readonly operations: FileOperation[],
    public readonly target: ResourceInterface,
  ) {
  }

  public static fromDataTransfer(dataTransfer: DataTransfer, target: ResourceInterface): FileOperationCollection {
    return FileOperationCollection.fromArray(JSON.parse(dataTransfer.getData('application/json')), target);
  }

  public static fromArray(items: ResourceInterface[], target: ResourceInterface): FileOperationCollection {
    const operations: FileOperation[] = [];

    for (const item of items) {
      operations.push(new FileOperation(item, DraggablePositionEnum.INSIDE));
    }

    return new FileOperationCollection(operations, target);
  }

  public static fromNodePositionOptions(options: NodePositionOptions): FileOperationCollection {
    const resource = FileResource.fromTreeNode(options.node);
    const targetResource = FileResource.fromTreeNode(options.target);
    const operations: FileOperation[] = [
      new FileOperation(
        resource,
        options.position
      )
    ];

    return new FileOperationCollection(operations, targetResource);
  }

  public getConflictingOperationsForTreeNode(node: TreeNode): FileOperation[] {
    return this.operations.filter((operation: FileOperation) => operation.hasConflictWithTreeNode(node));
  }

  public getResources(): ResourceInterface[] {
    const resources: ResourceInterface[] = [];
    this.operations.forEach((operation: FileOperation) => {
      resources.push(operation.resource);
    });

    return resources;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-navigation-component-filestorage-tree': EditableFileStorageTree;
    'typo3-backend-navigation-component-filestoragetree': FileStorageTreeNavigationComponent;
  }
}
