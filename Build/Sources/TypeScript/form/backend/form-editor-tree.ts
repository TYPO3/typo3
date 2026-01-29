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

/* eslint-disable @typescript-eslint/member-ordering */
// Member ordering disabled: override methods are grouped by functionality for better readability

import { customElement } from 'lit/decorators.js';
import { Tree, type DataTransferStringItem } from '@typo3/backend/tree/tree';
import type { TreeNodeInterface } from '@typo3/backend/tree/tree-node';
import { TreeNodePositionEnum } from '@typo3/backend/tree/tree-node';
import { DataTransferTypes } from '@typo3/backend/enum/data-transfer-types';
import { FORM_EDITOR_TREE_EVENTS } from './form-editor-tree-events';

/**
 * Interface for FormEditor tree nodes
 */
export interface FormEditorTreeNode {
  identifier: string;
  identifierPath: string;
  label: string;
  type: string;
  iconIdentifier: string;
  isComposite: boolean;
  isTopLevel: boolean;
  enabled: boolean;
  children?: FormEditorTreeNode[];
  expanded?: boolean;
}

/**
 * Metadata stored for each node to support FormEditor-specific validation
 */
interface NodeMetadata {
  isComposite: boolean;
  isTopLevel: boolean;
  originalOverlayIcon: string;
}

/**
 * Validation state for a node
 */
interface NodeValidationState {
  hasError: boolean;
  childHasError: boolean;
}

const ROOT_DEPTH = 0;
const TOP_LEVEL_DEPTH = 1;

const VALIDATION_ERROR_CLASS = 'node-validation-error';
const VALIDATION_CHILD_ERROR_CLASS = 'node-validation-error';

const VALIDATION_ERROR_ICON = 'overlay-missing';

/**
 * FormEditor Tree Component
 *
 * Custom tree implementation for the TYPO3 Form Editor that handles
 * form element hierarchy with drag & drop support and FormEditor-specific
 * validation rules.
 */
@customElement('typo3-backend-navigation-component-formeditor-tree')
export class FormEditorTree extends Tree {
  private readonly nodeMetadata: Map<string, NodeMetadata> = new Map();
  private readonly nodeValidationState: Map<string, NodeValidationState> = new Map();

  /**
   * Constructor - Initialize tree settings
   */
  public constructor() {
    super();
    this.settings.showIcons = true;
    this.settings.defaultProperties = {
      hasChildren: false,
      nameSourceField: 'label',
      type: 'form_element',
      prefix: '',
      suffix: '',
      locked: false,
      loaded: true,
      overlayIcon: '',
      selectable: true,
      expanded: false,
      checked: false,
    };
    this.settings.dataUrl = '';
    this.settings.filterUrl = '';
    this.allowNodeEdit = false;
    this.allowNodeDrag = true;
    this.allowNodeSorting = true;
  }

  /**
   * Set tree nodes from FormEditor data structure
   *
   * @param nodes - Array of FormEditorTreeNode objects
   */
  public setNodes(nodes: FormEditorTreeNode[]): void {
    this.syncExpandedStates();
    this.nodeMetadata.clear();

    // Convert FormEditor nodes to basic TreeNodeInterface format
    const basicNodes = this.convertToTreeNodes(nodes);

    // Let the base Tree class enhance nodes with internal __ properties
    // Cast is safe because enhanceNodes() adds all missing properties
    this.nodes = this.enhanceNodes(basicNodes as TreeNodeInterface[]);

    this.requestUpdate();
  }

  /**
   * Set the currently selected node
   *
   * @param identifierPath - Full identifier path of the node to select
   */
  public setSelectedNode(identifierPath: string): void {
    const node = this.nodes.find(n => n.identifier === identifierPath);
    if (node) {
      this.selectNode(node, false);
    }
  }

  /**
   * Set validation error state for a node
   *
   * Marks a node as having a validation error. This will add the appropriate
   * CSS class to highlight the node in red and show an error overlay icon.
   *
   * @param identifierPath - Full identifier path of the node
   * @param hasError - Whether the node has a direct validation error
   */
  public setNodeValidationError(identifierPath: string, hasError: boolean = true): void {
    const currentState = this.nodeValidationState.get(identifierPath) || { hasError: false, childHasError: false };
    currentState.hasError = hasError;
    this.nodeValidationState.set(identifierPath, currentState);

    // Update the node's overlayIcon to show error state
    const node = this.nodes.find(n => n.identifier === identifierPath);
    if (node) {
      this.updateNodeOverlayIcon(node, hasError);
    }

    this.requestUpdate();
  }

  /**
   * Set child-has-error state for a node
   *
   * Marks a node as having a child with a validation error. This will add
   * a CSS class to indicate the error state in a subtler way (e.g., red text).
   *
   * @param identifierPath - Full identifier path of the node
   * @param childHasError - Whether a child node has a validation error
   */
  public setNodeChildHasError(identifierPath: string, childHasError: boolean = true): void {
    const currentState = this.nodeValidationState.get(identifierPath) || { hasError: false, childHasError: false };
    currentState.childHasError = childHasError;
    this.nodeValidationState.set(identifierPath, currentState);
    this.requestUpdate();
  }

  /**
   * Clear all validation error states
   *
   * Removes all validation error markers from the tree.
   * Typically called before re-validating the form.
   */
  public clearAllValidationErrors(): void {
    // Clear overlay icons on all nodes that had errors
    this.nodeValidationState.forEach((state, identifierPath) => {
      if (state.hasError) {
        const node = this.nodes.find(n => n.identifier === identifierPath);
        if (node) {
          this.updateNodeOverlayIcon(node, false);
        }
      }
    });

    this.nodeValidationState.clear();
    this.requestUpdate();
  }

  /**
   * Update a node's overlay icon based on validation error state
   *
   * Sets or removes the error overlay icon. If the node already has an overlay
   * (e.g., 'overlay-hidden' for disabled elements), the error icon takes precedence
   * when there's an error, otherwise the original overlay is restored.
   *
   * @param node - Tree node to update
   * @param hasError - Whether the node has a validation error
   */
  private updateNodeOverlayIcon(node: TreeNodeInterface, hasError: boolean): void {
    const originalOverlay = this.getOriginalOverlayIcon(node);

    if (hasError) {
      node.overlayIcon = VALIDATION_ERROR_ICON;
    } else {
      node.overlayIcon = originalOverlay;
    }
  }

  /**
   * Get the original overlay icon for a node (before validation errors)
   *
   * Returns the original overlay icon that was stored when the node was created.
   * This is used to restore the overlay when validation errors are cleared.
   *
   * @param node - Tree node to check
   * @returns Original overlay icon identifier (e.g., 'overlay-hidden' for disabled elements)
   */
  private getOriginalOverlayIcon(node: TreeNodeInterface): string {
    const metadata = this.nodeMetadata.get(node.identifier);
    if (metadata) {
      return metadata.originalOverlayIcon;
    }
    return '';
  }

  /**
   * Get the validation state for a node
   *
   * @param identifierPath - Full identifier path of the node
   * @returns The validation state or null if no state is set
   */
  public getNodeValidationState(identifierPath: string): NodeValidationState | null {
    return this.nodeValidationState.get(identifierPath) || null;
  }

  /**
   * Search/filter tree nodes
   *
   * @param term - Search term to filter nodes by
   */
  public search(term: string): void {
    this.filter(term);
  }

  /**
   * Override filter to implement client-side filtering for FormEditor
   *
   * @param searchTerm - Optional search term to filter by
   */
  public override filter(searchTerm?: string | null): void {
    if (typeof searchTerm === 'string') {
      this.searchTerm = searchTerm;
    }

    if (this.searchTerm) {
      const term = this.searchTerm.toLowerCase();

      this.nodes.forEach(node => {
        const matches = this.nodeMatchesSearchTerm(node, term);

        if (matches) {
          node.__hidden = false;
          this.showParentNodes(node);
        } else {
          node.__hidden = true;
        }
      });
    } else {
      // Clear filter - show all nodes
      this.nodes.forEach(node => {
        node.__hidden = false;
      });
    }

    this.requestUpdate();
  }

  /**
   * Check if a node matches the search term
   *
   * @param node - Tree node to check
   * @param term - Normalized (lowercase) search term
   * @returns True if node matches the search term
   */
  private nodeMatchesSearchTerm(node: TreeNodeInterface, term: string): boolean {
    return node.name.toLowerCase().includes(term)
        || node.identifier.toLowerCase().includes(term)
        || (node.note && node.note.toLowerCase().includes(term));
  }

  public override async loadData(): Promise<void> {
    this.loading = false;
  }

  public override async fetchData(): Promise<TreeNodeInterface[]> {
    return [];
  }

  public override async loadChildren(node: TreeNodeInterface): Promise<void> {
    node.loaded = true;
  }

  public override hideChildren(node: TreeNodeInterface): void {
    node.__expanded = false;
    this.saveNodeStatus(node);
    this.dispatchEvent(new CustomEvent('typo3:tree:expand-toggle', { detail: { node } }));
    this.requestUpdate();
  }

  public override async showChildren(node: TreeNodeInterface): Promise<void> {
    node.__expanded = true;
    await this.loadChildren(node);
    this.saveNodeStatus(node);
    this.dispatchEvent(new CustomEvent('typo3:tree:expand-toggle', { detail: { node } }));
    this.requestUpdate();
  }

  public override selectNode(node: TreeNodeInterface, propagate: boolean = true): void {
    // Call parent implementation to handle selection (sets node.checked, adds .node-selected class)
    super.selectNode(node, propagate);

    // Trigger re-render so the .node-selected class is applied immediately
    this.requestUpdate();

    // Then dispatch our custom event for form editor integration
    this.dispatchEvent(new CustomEvent('typo3:form-editor-tree:node-clicked', {
      detail: { identifierPath: node.identifier },
      bubbles: true,
      composed: true
    }));
  }

  /**
   * Move a node to a new position in the tree
   *
   * Handles the physical movement of nodes in the tree array and updates
   * all related metadata (parent, depth, hasChildren). Dispatches appropriate
   * events based on whether the parent changed or just the position within
   * the same parent.
   *
   * @param node - The node to move
   * @param target - The target node (reference point for the move)
   * @param position - Position relative to target (BEFORE, AFTER, or INSIDE)
   */
  public override async moveNode(node: TreeNodeInterface, target: TreeNodeInterface, position: TreeNodePositionEnum): Promise<void> {
    // Store original identifiers and parent before move
    const movedIdentifier = node.identifier;
    const targetIdentifier = target.identifier;
    const oldParentIdentifier = node.parentIdentifier;
    const oldParentNode = this.getParentNode(node);

    // Determine new parent after move
    let newParentIdentifier: string;
    let newParentNode: TreeNodeInterface | null;
    if (position === TreeNodePositionEnum.INSIDE) {
      newParentIdentifier = targetIdentifier;
      newParentNode = target;
    } else {
      // For before/after, parent is the same as target's parent
      newParentIdentifier = target.parentIdentifier;
      newParentNode = this.getParentNode(target);
    }

    // Calculate previous/next identifiers BEFORE the move (while nodes are still in old positions)
    const allNodesBeforeMove = Array.from(this.nodes);
    const targetIndex = allNodesBeforeMove.indexOf(target);
    let previousIdentifierPath = '';
    let nextIdentifierPath = '';

    if (position === TreeNodePositionEnum.BEFORE) {
      if (targetIndex > 0) {
        previousIdentifierPath = allNodesBeforeMove[targetIndex - 1].identifier;
      }
      nextIdentifierPath = targetIdentifier;
    } else if (position === TreeNodePositionEnum.AFTER) {
      previousIdentifierPath = targetIdentifier;
      if (targetIndex < allNodesBeforeMove.length - 1) {
        nextIdentifierPath = allNodesBeforeMove[targetIndex + 1].identifier;
      }
    }

    // Check if parent changed
    const parentChanged = oldParentIdentifier !== newParentIdentifier;

    // IMPORTANT: Physically move the node in the nodes array
    // Remove from old position
    const oldIndex = this.nodes.indexOf(node);
    if (oldIndex > -1) {
      this.nodeMap.splice(oldIndex, 1);
    }

    // Update node's parent and depth
    node.parentIdentifier = newParentIdentifier;
    if (newParentNode) {
      node.depth = newParentNode.depth + 1;
    } else {
      node.depth = 0;
    }

    // Update old parent's hasChildren if it has no more children
    if (oldParentNode) {
      const oldParentChildren = this.getNodeChildren(oldParentNode);
      if (oldParentChildren.length === 0) {
        oldParentNode.hasChildren = false;
        oldParentNode.__expanded = false;
      }
    }

    // Update new parent's hasChildren
    if (newParentNode) {
      if (!newParentNode.hasChildren) {
        newParentNode.hasChildren = true;
        newParentNode.__expanded = true;
      }
      // Expand parent if not already expanded
      if (!newParentNode.__expanded) {
        await this.showChildren(newParentNode);
      }
    }

    // Insert at new position
    let insertIndex = this.nodes.indexOf(target);
    if (position === TreeNodePositionEnum.INSIDE) {
      // Insert as first child
      insertIndex++;
    } else if (position === TreeNodePositionEnum.AFTER) {
      insertIndex++;
    }
    // BEFORE position uses the target index directly

    this.nodeMap.splice(insertIndex, 0, node);

    // Trigger re-render
    this.requestUpdate();

    // Dispatch appropriate event
    if (parentChanged) {
      // Parent changed - use dnd-change event
      // Include position information so the element can be placed correctly
      this.dispatchEvent(new CustomEvent('typo3:form-editor-tree:dnd-change', {
        detail: {
          itemIdentifierPath: movedIdentifier,
          parentIdentifierPath: newParentIdentifier,
          position: position,
          previousIdentifierPath: previousIdentifierPath,
          nextIdentifierPath: nextIdentifierPath
        },
        bubbles: true,
        composed: true
      }));
    } else {
      // Same parent, just reordering - use dnd-update event

      this.dispatchEvent(new CustomEvent('typo3:form-editor-tree:dnd-update', {
        detail: {
          movedIdentifierPath: movedIdentifier,
          previousIdentifierPath,
          nextIdentifierPath
        },
        bubbles: true,
        composed: true
      }));
    }
  }

  /**
   * First update lifecycle - call parent implementation
   */
  protected override async firstUpdated(): Promise<void> {
    // Base class handles localStorage state restoration via getNodeStatus()
    await super.firstUpdated();
  }

  /**
   * Override getNodeClasses to add validation error styling
   *
   * Adds CSS classes for validation errors:
   * - 'formeditor-validation-errors': Node has a direct validation error
   * - 'formeditor-validation-child-has-error': A child node has an error
   *
   * @param node - Tree node to get classes for
   * @returns Array of CSS class names
   */
  protected override getNodeClasses(node: TreeNodeInterface): string[] {
    const classes = super.getNodeClasses(node);

    // Add validation error classes based on node state
    const validationState = this.nodeValidationState.get(node.identifier);
    if (validationState) {
      if (validationState.hasError) {
        classes.push(VALIDATION_ERROR_CLASS);
      }
      if (validationState.childHasError) {
        classes.push(VALIDATION_CHILD_ERROR_CLASS);
      }
    }

    return classes;
  }

  /**
   * Override handleNodeDrop to ensure drag tooltip is properly cleaned up
   *
   * The base class handleNodeDrop is called after the drag operation completes.
   * We need to ensure the drag tooltip is hidden and drag state is cleaned up.
   *
   * The drag tooltip listens to the native 'dragend' event to hide itself.
   * Since our DnD happens via custom events, we need to manually trigger this.
   */
  protected override handleNodeDrop(event: DragEvent): boolean {
    // Call parent implementation first
    const result = super.handleNodeDrop(event);

    // Ensure drag tooltip is hidden after drop
    // This is needed because the async event dispatching might interfere with cleanup
    this.cleanDrag();

    // Force tooltip update to hide it
    if (result) {
      // Reset drag mode and position
      this.nodeDragMode = null;
      this.nodeDragPosition = null;
      this.refreshDragToolTip();

      // Manually trigger dragend event to hide the drag tooltip immediately
      // The tooltip component listens to 'dragend' to set active=false
      // Without this, the tooltip would remain visible until the next drag operation
      window.dispatchEvent(new DragEvent('dragend', {
        bubbles: true,
        cancelable: false
      }));
    }

    return result;
  }

  protected override handleNodeDoubleClick(event: PointerEvent, node: TreeNodeInterface): void {
    event.preventDefault();
    event.stopPropagation();
    this.dispatchFormEditorEvent(FORM_EDITOR_TREE_EVENTS.NODE_EDIT, {
      identifierPath: node.identifier,
      currentLabel: node.name
    });
  }

  protected override handleNodeDelete(node: TreeNodeInterface): void {
    this.dispatchFormEditorEvent(FORM_EDITOR_TREE_EVENTS.NODE_DELETE, {
      identifierPath: node.identifier
    });
  }

  protected override handleNodeMove(): void {
    // Don't dispatch event here - it's handled in moveNode override
    // This is called after the physical move, when identifierPaths are already changed
  }

  protected override handleNodeDragStart(event: DragEvent, node: TreeNodeInterface): void {
    // Don't allow dragging the root node (depth 0)
    if (node.depth === 0) {
      event.preventDefault();
      return;
    }

    // Call parent implementation
    super.handleNodeDragStart(event, node);
  }

  protected override createDataTransferItemsFromNode(node: TreeNodeInterface): DataTransferStringItem[] {
    return [
      {
        type: DataTransferTypes.treenode,
        data: this.getNodeTreeIdentifier(node),
      },
    ];
  }

  protected override handleNodeDragOver(event: DragEvent): boolean {
    // First, let parent set up the basic drag over state (including nodeDragPosition)
    const handled = super.handleNodeDragOver(event);

    if (!handled) {
      return false;
    }

    // Now validate with FormEditor-specific rules
    const targetNode = this.getNodeFromDragEvent(event);
    if (!targetNode || !this.draggingNode) {
      return false;
    }

    // Check if drop is allowed according to FormEditor rules
    if (!this.isDropAllowedForFormEditor(this.draggingNode, targetNode, this.nodeDragPosition)) {
      // Not allowed - reset drag state completely
      this.nodeDragMode = null;
      this.nodeDragPosition = null;
      this.refreshDragToolTip();

      // Clean up visual indicators
      this.cleanDrag();

      return false;
    }

    return true;
  }

  /**
   * Check if dropping a node is allowed according to FormEditor rules
   *
   * @param draggingNode - The node being dragged
   * @param targetNode - The target node where the drop would occur
   * @param position - The drop position relative to target
   * @returns True if the drop is allowed
   */
  private isDropAllowedForFormEditor(
    draggingNode: TreeNodeInterface,
    targetNode: TreeNodeInterface,
    position: TreeNodePositionEnum | null
  ): boolean {
    // Can't drop on itself
    if (draggingNode === targetNode) {
      return false;
    }

    // Can't drop root node
    if (draggingNode.depth === ROOT_DEPTH) {
      return false;
    }

    // Get metadata for dragging element
    const draggingFormElement = this.getFormElementByNode(draggingNode);

    // Rule: Top-level elements (Pages) can't be dragged inside other elements
    if (draggingFormElement?.isTopLevel && position === TreeNodePositionEnum.INSIDE) {
      return false;
    }

    // Determine the target depth after drop
    const targetDepth = this.getTargetDepthAfterDrop(targetNode, position);

    // Rule: Top-level elements (Pages) must always be at depth 1
    if (draggingFormElement?.isTopLevel && targetDepth !== TOP_LEVEL_DEPTH) {
      return false;
    }

    // Rule: On depth 1 (directly under form/root), only Pages/SummaryPages allowed
    if (targetDepth === TOP_LEVEL_DEPTH) {
      const formElement = this.getFormElementByNode(draggingNode);
      if (formElement && !formElement.isTopLevel) {
        return false;
      }
    }

    // Rule: Can only drop inside composite elements
    if (position === TreeNodePositionEnum.INSIDE) {
      const targetFormElement = this.getFormElementByNode(targetNode);

      if (targetFormElement && !targetFormElement.isComposite) {
        return false;
      }

      // Rule: Can't drop top-level elements (Pages) inside other top-level elements (Pages)
      if (targetFormElement?.isTopLevel && draggingFormElement?.isTopLevel) {
        return false;
      }
    }

    // Can't drop a parent into its own child
    return !this.isNodeDescendantOf(targetNode, draggingNode);
  }

  /**
   * Helper: Calculate target depth after a drop operation
   *
   * @param targetNode - Node where drop would occur
   * @param position - Position relative to target (before, after, inside)
   * @returns Depth level after the drop
   */
  private getTargetDepthAfterDrop(targetNode: TreeNodeInterface, position: TreeNodePositionEnum): number {
    if (position === TreeNodePositionEnum.INSIDE) {
      return targetNode.depth + 1;
    }
    return targetNode.depth;
  }

  /**
   * Get FormElement metadata for a tree node
   *
   * @param node - Tree node to get metadata for
   * @returns FormEditor node metadata or null if not found
   */
  private getFormElementByNode(node: TreeNodeInterface): FormEditorTreeNode | null {
    const metadata = this.nodeMetadata.get(node.identifier);
    if (!metadata) {
      return null;
    }

    return {
      identifier: node.identifier,
      identifierPath: node.identifier,
      label: node.name,
      type: node.recordType,
      iconIdentifier: node.icon,
      isComposite: metadata.isComposite,
      isTopLevel: metadata.isTopLevel,
      enabled: !node.overlayIcon?.includes('hidden'),
    };
  }

  /**
   * Check if a node is a descendant of another node
   *
   * @param node - Node to check
   * @param potentialAncestor - Potential ancestor node
   * @returns True if node is a descendant of potentialAncestor
   */
  private isNodeDescendantOf(node: TreeNodeInterface, potentialAncestor: TreeNodeInterface): boolean {
    return node.__treeParents.includes(potentialAncestor.__treeIdentifier);
  }

  /**
   * Helper to dispatch FormEditor-specific CustomEvents
   *
   * Event details are type-defined in form-editor-tree-events.ts
   *
   * @param eventName - Name of the event to dispatch
   * @param detail - Event detail payload
   */
  private dispatchFormEditorEvent(eventName: string, detail: unknown): void {
    this.dispatchEvent(new CustomEvent(eventName, {
      detail,
      bubbles: true,
      composed: true
    }));
  }

  /**
   * Sync expanded states to localStorage before rebuilding tree
   *
   * This ensures that when enhanceNodes() is called, it can read the correct
   * expanded state from localStorage via getNodeStatus().
   */
  private syncExpandedStates(): void {
    if (this.nodes && this.nodes.length > 0) {
      this.nodes.forEach(node => {
        // Save the current expanded state to localStorage
        // This way, enhanceNodes() will restore it via getNodeStatus()
        this.saveNodeStatus(node);
      });
    }
  }

  /**
   * Make all parent nodes visible for a given node
   *
   * @param node - Node whose parents should be shown
   */
  private showParentNodes(node: TreeNodeInterface): void {
    if (node.__treeParents && node.__treeParents.length > 0) {
      node.__treeParents.forEach(parentTreeId => {
        const parentNode = this.getNodeByTreeIdentifier(parentTreeId);
        if (parentNode) {
          parentNode.__hidden = false;
          parentNode.__expanded = true;
        }
      });
    }
  }

  /**
   * Convert FormEditor tree nodes to internal TreeNodeInterface format
   *
   * Recursively converts the FormEditor's node structure to the basic format
   * expected by the base Tree component. The base Tree's enhanceNodes() method
   * will add internal properties like __treeIdentifier, __treeParents, __expanded, etc.
   *
   * @param nodes - FormEditor nodes to convert
   * @param parentIdentifier - Identifier of the parent node (empty for root)
   * @param depth - Current depth in the tree (0 for root)
   * @returns Flat array of partial TreeNodeInterface objects (will be enhanced by enhanceNodes())
   */
  private convertToTreeNodes(
    nodes: FormEditorTreeNode[],
    parentIdentifier: string = '',
    depth: number = 0
  ): Partial<TreeNodeInterface>[] {
    const result: Partial<TreeNodeInterface>[] = [];

    nodes.forEach((node) => {
      // Determine the original overlay icon based on enabled state
      const originalOverlayIcon = node.enabled === false ? 'overlay-hidden' : '';

      // Store FormEditor-specific metadata (needed for drag & drop validation and overlay restoration)
      this.nodeMetadata.set(node.identifierPath, {
        isComposite: node.isComposite,
        isTopLevel: node.isTopLevel,
        originalOverlayIcon: originalOverlayIcon
      });

      // Create basic tree node with only essential properties
      // The base Tree class will add internal properties (__treeIdentifier, etc.) via enhanceNodes()
      const treeNode: Partial<TreeNodeInterface> = {
        type: 'form_element',
        identifier: node.identifierPath,
        parentIdentifier: parentIdentifier,
        recordType: node.type,
        name: node.label,
        note: node.type ? node.type : '',
        prefix: '',
        suffix: '',
        tooltip: `identifier=${node.identifier}`,
        depth: depth,
        hasChildren: node.isComposite && !!node.children && node.children.length > 0,
        loaded: true,
        editable: false,
        deletable: false,
        icon: node.iconIdentifier,
        overlayIcon: node.enabled === false ? 'overlay-hidden' : '',
        statusInformation: [],
        labels: [],
        nameSourceField: 'label',
        locked: false,
        selectable: true,
      };

      result.push(treeNode);

      // Recursively convert children
      if (node.children && node.children.length > 0) {
        const childNodes = this.convertToTreeNodes(
          node.children,
          node.identifierPath,
          depth + 1
        );
        result.push(...childNodes);
      }
    });

    return result;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-navigation-component-formeditor-tree': FormEditorTree;
  }
}

