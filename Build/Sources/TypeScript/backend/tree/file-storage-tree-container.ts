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
import '@typo3/backend/tree/tree-toolbar';
import type { TreeToolbar } from '@typo3/backend/tree/tree-toolbar';
import { TreeNodeInterface, TreeNodePositionEnum } from '@typo3/backend/tree/tree-node';
import { FileStorageTree } from '@typo3/backend/tree/file-storage-tree';
import ContextMenu from '@typo3/backend/context-menu';
import Notification from '@typo3/backend/notification';
import { ModuleStateStorage } from '@typo3/backend/storage/module-state-storage';
import { ModuleUtility } from '@typo3/backend/module';
import { FileListDragDropDetail, FileListDragDropEvent } from '@typo3/filelist/file-list-dragdrop';
import { Resource, ResourceInterface } from '@typo3/backend/resource/resource';
import { DataTransferTypes } from '@typo3/backend/enum/data-transfer-types';

export const navigationComponentName: string = 'typo3-backend-navigation-component-filestoragetree';

interface DragDropTargetPosition {
  target: TreeNodeInterface,
  position: TreeNodePositionEnum
}

/**
 * FileStorageTree which allows for drag+drop, and in-place editing, as well as
 * tree highlighting from the outside
 */
@customElement('typo3-backend-navigation-component-filestorage-tree')
export class EditableFileStorageTree extends FileStorageTree {
  protected allowNodeDrag: boolean = true;

  protected handleNodeMove(node: TreeNodeInterface, target: TreeNodeInterface, position: TreeNodePositionEnum) {
    if (!this.isDropAllowed(target, node)) {
      return;
    }
    const options = this.getDropCommandDetails(target, node, position);
    if (options === null) {
      return;
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
      return;
    }
    this.initiateDropAction(fileOperationCollection);
  }

  protected createDataTransferItemsFromNode(node: TreeNodeInterface) {
    return [
      {
        type: DataTransferTypes.treenode,
        data: this.getNodeTreeIdentifier(node),
      },
      {
        type: DataTransferTypes.falResources,
        data: JSON.stringify([
          FileResource.fromTreeNode(node),
        ]),
      },
    ];
  }

  protected handleNodeDragOver(event: DragEvent): boolean {
    // @todo incorporate isDropAllowed
    if (super.handleNodeDragOver(event)) {
      return true;
    }

    // @TODO Unity with parent
    if (event.dataTransfer.types.includes(DataTransferTypes.falResources)) {

      // Find the current hovered node
      // Exit when no node was hovered
      const targetNode = this.getNodeFromDragEvent(event);
      if (targetNode === null) {
        return false;
      }

      this.cleanDrag();

      // Add hover styling to the current hovered node
      // element, during the drag the default mouse over
      // is disabled by the browser
      const hoverElement = this.getElementFromNode(targetNode);
      hoverElement.classList.add('node-hover');

      // Open node with children while holding the
      // node/element over this node for 1 second
      if (targetNode.hasChildren && !targetNode.__expanded) {
        if (this.openNodeTimeout.targetNode != targetNode) {
          this.openNodeTimeout.targetNode = targetNode;
          clearTimeout(this.openNodeTimeout.timeout);
          this.openNodeTimeout.timeout = setTimeout(() => {
            this.showChildren(this.openNodeTimeout.targetNode);
            this.openNodeTimeout.targetNode = null;
            this.openNodeTimeout.timeout = null;
          }, 1000);
        }
      } else {
        clearTimeout(this.openNodeTimeout.timeout);
        this.openNodeTimeout.targetNode = null;
        this.openNodeTimeout.timeout = null;
      }

      // allow drop
      event.preventDefault();
      return true;
    }

    return false;
  }

  protected getTooltipDescription(node: TreeNodeInterface): string {
    return decodeURIComponent(node.identifier);
  }

  protected handleNodeDrop(event: DragEvent): boolean {
    if (super.handleNodeDrop(event)) {
      return true;
    }
    if (event.dataTransfer.types.includes(DataTransferTypes.falResources)) {
      const node = this.getNodeFromDragEvent(event);
      if (node === null) {
        return false;
      }

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
          return false;
        }
        // allow drop
        event.preventDefault();
        this.initiateDropAction(fileOperationCollection);
        return true;
      }
    }
    return false;
  }

  /**
   * Prepares all the details, which node is dropped on which other, if it is inside or before
   * the target node (= droppedNode).
   */
  private getDropCommandDetails(droppedNode: TreeNodeInterface, draggingNode: TreeNodeInterface, position: TreeNodePositionEnum): null | NodePositionOptions {
    const nodes = this.nodes;
    const identifier = draggingNode.identifier;
    let target = droppedNode/* || draggingNode*/;

    if (identifier === target.identifier) {
      return null;
    }

    if (position === TreeNodePositionEnum.BEFORE) {
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
  private setNodePositionAndTarget(index: number): null | DragDropTargetPosition {
    const nodes = this.nodes;
    const nodeOver = nodes[index];
    const nodeOverDepth = nodeOver.depth;
    if (index > 0) {
      index--;
    }
    const nodeBefore = nodes[index];
    const nodeBeforeDepth = nodeBefore.depth;
    const target = this.nodes[index];

    if (nodeBeforeDepth === nodeOverDepth) {
      return { position: TreeNodePositionEnum.AFTER, target };
    } else if (nodeBeforeDepth < nodeOverDepth) {
      return { position: TreeNodePositionEnum.INSIDE, target };
    } else {
      for (let i = index; i >= 0; i--) {
        if (nodes[i].depth === nodeOverDepth) {
          return { position: TreeNodePositionEnum.AFTER, target: this.nodes[i] };
        } else if (nodes[i].depth < nodeOverDepth) {
          return { position: TreeNodePositionEnum.AFTER, target: nodes[i] };
        }
      }
    }
    return null;
  }

  private isDropAllowed(target: TreeNodeInterface, draggingNode: TreeNodeInterface): boolean {
    if (target === draggingNode) {
      return false;
    }
    // @todo: why needed?
    if (!this.isOverRoot) {
      return false;
    }
    return true;
  }

  private initiateDropAction(fileOperationCollection: FileOperationCollection): void {
    const detail: FileListDragDropDetail = {
      action: 'transfer',
      resources: fileOperationCollection.getResources(),
      target: fileOperationCollection.target,
    };
    top.document.dispatchEvent(new CustomEvent(FileListDragDropEvent.transfer, { detail: detail }));
  }
}

/**
 * Responsible for setting up the viewport for the Navigation Component for the File Tree
 */
@customElement('typo3-backend-navigation-component-filestoragetree')
export class FileStorageTreeNavigationComponent extends LitElement {
  @query('.tree-wrapper') tree: EditableFileStorageTree;
  @query('typo3-backend-tree-toolbar') toolbar: TreeToolbar;

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
      <div id="typo3-filestoragetree" class="tree">
        <typo3-backend-tree-toolbar .tree="${this.tree}" id="filestoragetree-toolbar"></typo3-backend-tree-toolbar>
        <div class="navigation-tree-container">
          <typo3-backend-navigation-component-filestorage-tree id="typo3-filestoragetree-tree" class="tree-wrapper" .setup=${treeSetup}></typo3-backend-navigation-component-filestorage-tree>
        </div>
      </div>
    `;
  }

  protected firstUpdated() {
    this.toolbar.tree = this.tree;
    this.tree.addEventListener('typo3:tree:node-selected', this.loadContent);
    this.tree.addEventListener('typo3:tree:node-context', this.showContextMenu);
    this.tree.addEventListener('typo3:tree:nodes-prepared', this.selectActiveNode);
  }

  private readonly refresh = (): void => {
    this.tree.refreshOrFilterTree();
  };

  private readonly selectFirstNode = (): void => {
    const node = this.tree.nodes[0];
    if (node) {
      this.tree.selectNode(node, true);
    }
  };

  // event listener updating current tree state, this can be removed in TYPO3 v12
  private readonly treeUpdateRequested = (evt: CustomEvent): void => {
    const identifier = encodeURIComponent(evt.detail.payload.identifier);
    const nodeToSelect = this.tree.nodes.filter((node: TreeNodeInterface) => { return node.identifier === identifier; })[0];
    if (nodeToSelect && this.tree.getSelectedNodes().filter((selectedNode: TreeNodeInterface) => { return selectedNode.identifier === nodeToSelect.identifier; }).length === 0) {
      this.tree.selectNode(nodeToSelect, false);
    }
  };

  private readonly loadContent = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNodeInterface;
    if (!node?.checked) {
      return;
    }

    // remember the selected folder in the global state
    ModuleStateStorage.update('media', node.identifier, true);

    if (evt.detail.propagate === false) {
      return;
    }

    // Load the currently selected module with the updated URL
    const moduleMenu = top.TYPO3.ModuleMenu.App;
    let contentUrl = ModuleUtility.getFromName(moduleMenu.getCurrentModule()).link;
    contentUrl += contentUrl.includes('?') ? '&' : '?';
    top.TYPO3.Backend.ContentContainer.setUrl(contentUrl + 'id=' + node.identifier);
  };

  private readonly showContextMenu = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNodeInterface;
    if (!node) {
      return;
    }
    ContextMenu.show(
      node.recordType,
      decodeURIComponent(node.identifier),
      'tree',
      '',
      '',
      this.tree.getElementFromNode(node),
      evt.detail.originalEvent as PointerEvent
    );
  };

  /**
   * Event listener called for each loaded node,
   * here used to mark node remembered in ModuleStateStorage as selected
   */
  private readonly selectActiveNode = (evt: CustomEvent): void => {
    const selectedNodeIdentifier = ModuleStateStorage.current('file').selection;
    const nodes = evt.detail.nodes as Array<TreeNodeInterface>;
    evt.detail.nodes = nodes.map((node: TreeNodeInterface) => {
      if (node.identifier === selectedNodeIdentifier) {
        node.checked = true;
      }
      return node;
    });
  };
}

interface NodePositionOptions {
  node: TreeNodeInterface,
  target: TreeNodeInterface,
  identifier: string,
  position: TreeNodePositionEnum
}


/**
 * Internal helper class for drag&drop handling
 */
class FileOperation {
  public constructor(
    public readonly resource: ResourceInterface,
    public readonly position: TreeNodePositionEnum = TreeNodePositionEnum.INSIDE
  ) {
  }

  public hasConflictWithTreeNode(node: TreeNodeInterface): boolean {
    return this.resource.type === 'folder' && (
      node.identifier === this.resource.identifier
      || node.__parents[0] == this.resource.identifier
      || node.__parents.includes(this.resource.identifier)
    );
  }
}

class FileResource extends Resource {
  public static fromTreeNode(node: TreeNodeInterface): ResourceInterface {
    return new FileResource(
      decodeURIComponent(node.resourceType),
      decodeURIComponent(node.identifier),
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
    return FileOperationCollection.fromArray(JSON.parse(dataTransfer.getData(DataTransferTypes.falResources)), target);
  }

  public static fromArray(items: ResourceInterface[], target: ResourceInterface): FileOperationCollection {
    const operations: FileOperation[] = [];

    for (const item of items) {
      operations.push(new FileOperation(item, TreeNodePositionEnum.INSIDE));
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

  public getConflictingOperationsForTreeNode(node: TreeNodeInterface): FileOperation[] {
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
