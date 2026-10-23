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
 * Module: @typo3/form/backend/form-editor/tree-component-adapter
 *
 * This adapter bridges the old tree-component API with the new Web Component implementation,
 * maintaining backward compatibility with existing publish/subscribe events.
 */

import * as Helper from '@typo3/form/backend/form-editor/helper';
import type { FormEditor } from '@typo3/form/backend/form-editor';
import type { FormElement, PublisherSubscriber } from '@typo3/form/backend/form-editor/core';
import type { FormEditorTreeNode } from '@typo3/form/backend/form-editor-tree';
import type { FormEditorTreeContainer } from '@typo3/form/backend/form-editor-tree-container';
import {
  FORM_EDITOR_TREE_EVENTS,
  type NodeClickedEvent,
  type NodeEditEvent,
  type DndUpdateEvent,
  type DndChangeEvent
} from '@typo3/form/backend/form-editor-tree-events';
import { stripTags } from '@typo3/form/backend/form-editor/utility/string-utility';
import '@typo3/form/backend/form-editor-tree-container';


let formEditorApp: FormEditor | null = null;
let treeContainer: FormEditorTreeContainer | null = null;
let treeDomElement: HTMLElement | null = null;

function getFormEditorApp(): FormEditor {
  return formEditorApp;
}

function getPublisherSubscriber(): PublisherSubscriber {
  return getFormEditorApp().getPublisherSubscriber();
}

function getRootFormElement(): FormElement {
  return getFormEditorApp().getRootFormElement();
}

function getCurrentlySelectedFormElement(): FormElement {
  return getFormEditorApp().getCurrentlySelectedFormElement();
}

function getFormElementDefinition<T extends keyof import('@typo3/form/backend/form-editor/core').FormElementDefinition>(
  formElement: FormElement,
  formElementDefinitionKey?: T
): any {
  return getFormEditorApp().getFormElementDefinition(formElement, formElementDefinitionKey);
}

/**
 * Convert FormElement to TreeNode format
 *
 * @param formElement - The form element to convert
 * @returns FormEditorTreeNode representation
 */
function formElementToTreeNode(formElement: FormElement): FormEditorTreeNode {
  const rawLabel = formElement.get('label') || formElement.get('identifier');
  const node: FormEditorTreeNode = {
    identifier: formElement.get('identifier'),
    identifierPath: formElement.get('__identifierPath'),
    label: stripTags(rawLabel),
    type: getFormElementDefinition(formElement, 'label'),
    iconIdentifier: getFormElementDefinition(formElement, 'iconIdentifier'),
    isComposite: getFormElementDefinition(formElement, '_isCompositeFormElement'),
    isTopLevel: getFormElementDefinition(formElement, '_isTopLevelFormElement'),
    enabled: formElement.get('renderingOptions.enabled') !== false,
    children: []
  };

  const childFormElements = formElement.get('renderables');
  if (Array.isArray(childFormElements) && childFormElements.length > 0) {
    node.children = childFormElements.map(child => formElementToTreeNode(child));
  }

  return node;
}

/**
 * Build tree nodes from root form element
 *
 * Constructs the complete tree structure from the current form state.
 * The root element is included as the top-level node and is expanded by default.
 *
 * @returns Array containing the root node with its children
 */
function buildTreeNodes(): FormEditorTreeNode[] {
  const rootFormElement = getRootFormElement();

  // Return the root form element itself as top-level node (with its children)
  const rootNode = formElementToTreeNode(rootFormElement);

  // Ensure root element is expanded by default so children are visible
  rootNode.expanded = true;

  return [rootNode];
}

/**
 * Renew the tree - rebuild from current form state
 *
 * Rebuilds the entire tree structure and optionally selects a specific element.
 * Uses requestAnimationFrame to ensure DOM is ready before updating.
 *
 * @param formElement - Optional form element to select after renewal
 */
export function renew(formElement?: FormElement): void {
  if (!treeContainer) {
    return;
  }

  const nodes = buildTreeNodes();

  // Use requestAnimationFrame to ensure DOM is ready
  requestAnimationFrame(async () => {
    await treeContainer.setNodes(nodes);

    let currentElement = formElement;
    if (!currentElement) {
      try {
        currentElement = getCurrentlySelectedFormElement();
      } catch {
        // Element might not be found if path is stale - ignore and don't select anything
        currentElement = null;
      }
    }

    if (currentElement) {
      const identifierPath = currentElement.get('__identifierPath');
      treeContainer.setSelectedNode(identifierPath);
    }

    // Publish after setNodes() completed so validation subscribers
    // operate on a fully rendered tree with this.tree available.
    getPublisherSubscriber().publish('view/structure/renew/postProcess');
  });
}

/**
 * Select a tree node without rebuilding the entire tree.
 *
 * Use this when only the selection needs to change (e.g. stage element clicked).
 *
 * @param formElement - Form element to select (defaults to currently selected)
 */
export function selectTreeNode(formElement?: FormElement): void {
  if (!treeContainer) {
    return;
  }

  let element = formElement;
  if (!element) {
    try {
      element = getCurrentlySelectedFormElement();
    } catch {
      return;
    }
  }

  treeContainer.setSelectedNode(element.get('__identifierPath'));
}

/**
 * Set tree node title (update label)
 *
 * Updates the label of a form element and refreshes the tree to reflect the change.
 *
 * @param title - New title/label for the element
 * @param formElement - Form element to update (defaults to currently selected)
 */
export function setTreeNodeTitle(title?: string, formElement?: FormElement): void {
  if (!formElement) {
    try {
      formElement = getCurrentlySelectedFormElement();
    } catch {
      // Element might not be found - ignore
      return;
    }
  }

  if (title) {
    formElement.set('label', title);
  }

  // Refresh tree to reflect changes
  renew(formElement);
}

/**
 * Get tree node for a form element
 *
 * Returns the DOM element for the specified form element.
 * Supports both FormElement objects and identifier path strings.
 *
 * @param formElement - Form element or identifier path (defaults to currently selected)
 * @returns The tree node element or null if not found
 */
export function getTreeNode(formElement?: FormElement | string): HTMLElement | null {
  let identifierPath: string;

  if (typeof formElement === 'string') {
    identifierPath = formElement;
  } else {
    let element = formElement;
    if (!element) {
      try {
        element = getCurrentlySelectedFormElement();
      } catch {
        // Element might not be found - return null
        return null;
      }
    }
    identifierPath = element.get('__identifierPath');
  }

  return treeDomElement ? treeDomElement.querySelector(`[data-id="${identifierPath}"]`) : null;
}

/**
 * Get all tree nodes
 *
 * @returns NodeList containing all tree item elements
 */
export function getAllTreeNodes(): NodeListOf<HTMLElement> {
  return treeDomElement
    ? treeDomElement.querySelectorAll<HTMLElement>('.tree-item')
    : document.querySelectorAll<HTMLElement>('.tree-item-none');
}

/**
 * Set validation error state for a tree node
 *
 * Marks a node as having a validation error. The tree component will
 * highlight the node accordingly.
 *
 * @param identifierPath - Full identifier path of the node
 * @param hasError - Whether the node has a direct validation error
 */
export function setNodeValidationError(identifierPath: string, hasError: boolean = true): void {
  if (treeContainer) {
    treeContainer.setNodeValidationError(identifierPath, hasError);
  }
}

/**
 * Set child-has-error state for a tree node
 *
 * Marks a node as having a child with a validation error. The tree component
 * will highlight the node accordingly.
 *
 * @param identifierPath - Full identifier path of the node
 * @param childHasError - Whether a child node has a validation error
 */
export function setNodeChildHasError(identifierPath: string, childHasError: boolean = true): void {
  if (treeContainer) {
    treeContainer.setNodeChildHasError(identifierPath, childHasError);
  }
}

/**
 * Clear all validation error states from the tree
 *
 * Removes all validation error markers from all nodes.
 * Typically called before re-validating the form.
 */
export function clearAllValidationErrors(): void {
  if (treeContainer) {
    treeContainer.clearAllValidationErrors();
  }
}

/**
 * Get the tree DOM element
 *
 * @returns The tree's root DOM element or null
 */
export function getTreeDomElement(): HTMLElement | null {
  return treeDomElement;
}

/**
 * Build title by form element (for backward compatibility)
 *
 * @param formElement - Form element to build title for
 * @returns HTML element containing the formatted title
 */
export function buildTitleByFormElement(formElement: FormElement): HTMLElement {
  const span = document.createElement('span');
  span.textContent = formElement.get('label') ? formElement.get('label') : formElement.get('identifier');
  const small = document.createElement('small');
  small.textContent = '(' + getFormElementDefinition(formElement, 'label') + ')';
  span.appendChild(small);
  return span;
}

/**
 * Helper functions for getting tree node information from DOM elements
 */

/**
 * Get tree node within DOM element
 *
 * @param element - HTML element to search within
 * @returns The tree item content element or null
 */
export function getTreeNodeWithinDomElement(element: HTMLElement | null): HTMLElement | null {
  return element ? element.querySelector('.tree-item-content') : null;
}

/**
 * Get tree node identifier path from DOM element
 *
 * @param element - HTML element
 * @returns Identifier path of the tree node
 */
export function getTreeNodeIdentifierPathWithinDomElement(element: HTMLElement | null): string {
  // Use data-id which is set by the base Tree class
  return element?.closest('[data-id]')?.getAttribute('data-id') ?? '';
}

/**
 * Get parent tree node within DOM element
 *
 * @param element - HTML element
 * @returns The parent tree node element or null
 */
export function getParentTreeNodeWithinDomElement(element: HTMLElement | null): HTMLElement | null {
  // Navigate up to parent list item (use the tree structure: div.node > parent li)
  const parentLi = element?.parentElement?.closest('li[data-id]');
  return parentLi ? parentLi.querySelector('.tree-item-content') : null;
}

/**
 * Get parent tree node identifier path from DOM element
 *
 * @param element - HTML element
 * @returns Identifier path of the parent tree node
 */
export function getParentTreeNodeIdentifierPathWithinDomElement(element: HTMLElement | null): string {
  const parentLi = element?.parentElement?.closest('li[data-id]');
  return parentLi?.getAttribute('data-id') ?? '';
}

/**
 * Get sibling tree node identifier path from DOM element
 *
 * @param element - HTML element
 * @param position - Position of sibling ('prev' or 'next')
 * @returns Identifier path of the sibling tree node
 */
export function getSiblingTreeNodeIdentifierPathWithinDomElement(
  element: HTMLElement | null,
  position: string = 'prev'
): string {
  const li = element?.closest('li[data-id]');
  if (!li) {
    return '';
  }
  const sibling = position === 'prev'
    ? li.previousElementSibling?.matches('li[data-id]') ? li.previousElementSibling : null
    : li.nextElementSibling?.matches('li[data-id]') ? li.nextElementSibling : null;
  return sibling?.getAttribute('data-id') ?? '';
}

/**
 * Render composite form element children as sortable list (for backward compatibility)
 *
 * @returns Empty element (actual rendering handled by web component)
 */
export function renderCompositeFormElementChildsAsSortableList(): HTMLElement {
  // This is called during initialization, we just return an empty element
  // as the web component handles rendering
  return document.createElement('div');
}

/**
 * The tree container is always in the parent window (outside the iframe),
 * never in the current document.
 *
 * @returns The container element or null if not found
 */
function findTreeContainer(): FormEditorTreeContainer | null {
  // Tree container is always in parent window (FormEditor is in iframe)
  return window.parent.document.querySelector('typo3-backend-navigation-component-formeditortree');
}

/**
 * Wait for tree container to be ready
 *
 * Listens for 'typo3:tree-container:ready' event dispatched by the container
 * when it's fully initialized. Falls back to timeout after 5 seconds.
 *
 * The tree container is always in the parent window (FormEditor runs in iframe).
 *
 * @returns Promise resolving to the container or null if not found
 */
function waitForTreeContainer(): Promise<FormEditorTreeContainer | null> {
  const container = findTreeContainer();
  if (container) {
    return Promise.resolve(container);
  }

  return new Promise((resolve) => {
    const handleReady = () => {
      clearTimeout(timeoutId);
      window.parent.document.removeEventListener('typo3:tree-container:ready', handleReady);
      const container = findTreeContainer();
      resolve(container);
    };

    window.parent.document.addEventListener('typo3:tree-container:ready', handleReady);

    // Timeout as safety net
    const timeoutId = window.setTimeout(() => {
      window.parent.document.removeEventListener('typo3:tree-container:ready', handleReady);
      console.warn('[FormEditor Tree Adapter] Tree container not found within timeout');
      resolve(null);
    }, 5000);
  });
}

/**
 * Bootstrap the tree component
 *
 * Initializes the FormEditor tree adapter and sets up event listeners.
 * Handles both synchronous and asynchronous tree container discovery.
 *
 * @param _formEditorApp - FormEditor application instance
 * @param appendToDomElement - DOM element to append tree to
 * @returns Object containing all exported tree adapter functions
 */
export function bootstrap(
  _formEditorApp: FormEditor,
  appendToDomElement: HTMLElement
): typeof import('./tree-component-adapter') {
  formEditorApp = _formEditorApp;
  treeDomElement = appendToDomElement;

  // Try to find the tree container immediately
  treeContainer = findTreeContainer();

  if (!treeContainer) {
    // Try to find it asynchronously
    waitForTreeContainer().then((container) => {
      if (container) {
        treeContainer = container;
        setupEventListeners();
        // Try initial render if form is ready
        if (formEditorApp && getRootFormElement()) {
          renew();
        }
      }
    });
  } else {
    setupEventListeners();
  }

  return {
    renew,
    selectTreeNode,
    setTreeNodeTitle,
    getTreeNode,
    getAllTreeNodes,
    setNodeValidationError,
    setNodeChildHasError,
    clearAllValidationErrors,
    getTreeDomElement,
    buildTitleByFormElement,
    getTreeNodeWithinDomElement,
    getTreeNodeIdentifierPathWithinDomElement,
    getParentTreeNodeWithinDomElement,
    getParentTreeNodeIdentifierPathWithinDomElement,
    getSiblingTreeNodeIdentifierPathWithinDomElement,
    renderCompositeFormElementChildsAsSortableList,
    bootstrap
  };
}

/**
 * Setup event listeners on the tree container
 *
 * Bridges custom tree events to the legacy publish/subscribe system
 * used by the FormEditor. Handles node clicks, edits, and drag & drop operations.
 *
 * Extracted to be callable after async container discovery.
 */
function setupEventListeners(): void {
  if (!treeContainer) {
    return;
  }

  // Set up event listeners to bridge to old publish/subscribe system

  // NODE CLICKED - User clicks on a tree node to select an element
  treeContainer.addEventListener(FORM_EDITOR_TREE_EVENTS.NODE_CLICKED, (event: Event) => {
    const customEvent = event as NodeClickedEvent;
    const { identifierPath } = customEvent.detail;
    try {
      getPublisherSubscriber().publish('view/tree/node/clicked', [identifierPath]);
    } catch {
      // Element path might be stale after a move operation - silently ignore
    }
  });

  // NODE EDIT - User wants to edit a node (we just select it for Inspector)
  treeContainer.addEventListener(FORM_EDITOR_TREE_EVENTS.NODE_EDIT, (event: Event) => {
    const customEvent = event as NodeEditEvent;
    const { identifierPath } = customEvent.detail;
    // Don't show a prompt dialog - the FormEditor handles editing via the Inspector panel
    // Just select the node so the Inspector shows it
    getPublisherSubscriber().publish('view/tree/node/clicked', [identifierPath]);
  });

  // DND UPDATE - Reordering within same parent (sibling reorder)
  treeContainer.addEventListener(FORM_EDITOR_TREE_EVENTS.DND_UPDATE, (event: Event) => {
    const customEvent = event as DndUpdateEvent;
    const { movedIdentifierPath, previousIdentifierPath, nextIdentifierPath } = customEvent.detail;

    // Find the actual DOM element for the moved item using data-id
    const movedItem = treeDomElement
      ? treeDomElement.querySelector<HTMLElement>(`[data-id="${movedIdentifierPath}"]`)
      : null;

    // Publish to FormEditor backend to update the data model
    getPublisherSubscriber().publish('view/tree/dnd/update', [
      movedItem,
      movedIdentifierPath,
      previousIdentifierPath,
      nextIdentifierPath
    ]);

    // Publish stop event to trigger full update (same as regular DND stop)
    // This will trigger the mediator to re-render the stage
    getPublisherSubscriber().publish('view/tree/dnd/stop', [movedIdentifierPath]);
  });

  // DND CHANGE - Moving to different parent (parent change)
  treeContainer.addEventListener(FORM_EDITOR_TREE_EVENTS.DND_CHANGE, (event: Event) => {
    const customEvent = event as DndChangeEvent;
    const { itemIdentifierPath, parentIdentifierPath, position, previousIdentifierPath, nextIdentifierPath } = customEvent.detail;

    // NOTE: The tree has ALREADY moved the node physically in the nodes array
    // before this event is dispatched. However, the DATA MODEL has not been updated yet.

    // Find the DOM element using data-id
    const item = treeDomElement
      ? treeDomElement.querySelector<HTMLElement>(`[data-id="${itemIdentifierPath}"]`)
      : null;

    // Publish the change event for visual feedback (highlighting parent)
    const enclosingCompositeFormElement = getFormEditorApp().findEnclosingCompositeFormElementWhichIsNotOnTopLevel(parentIdentifierPath);
    getPublisherSubscriber().publish('view/tree/dnd/change', [
      item,
      parentIdentifierPath,
      enclosingCompositeFormElement
    ]);

    // Determine the correct position and reference element for moveFormElement
    let movePosition: string;
    let referenceIdentifierPath: string;

    if (position === 'inside') {
      // Dropped directly on a parent - place as first child
      movePosition = 'inside';
      referenceIdentifierPath = parentIdentifierPath;
    } else if (nextIdentifierPath) {
      // We have a next sibling - place before it
      movePosition = 'before';
      referenceIdentifierPath = nextIdentifierPath;
    } else if (previousIdentifierPath) {
      // We have a previous sibling - place after it
      movePosition = 'after';
      referenceIdentifierPath = previousIdentifierPath;
    } else {
      // No siblings - place inside parent
      movePosition = 'inside';
      referenceIdentifierPath = parentIdentifierPath;
    }

    try {
      const movedFormElement = getFormEditorApp().moveFormElement(
        itemIdentifierPath,
        movePosition,
        referenceIdentifierPath,
        false
      );

      const newPath = movedFormElement.get('__identifierPath');

      // Update the DOM attribute with the new identifier path
      if (movedFormElement && item !== null) {
        item.setAttribute(
          Helper.getDomElementDataAttribute('elementIdentifier'),
          newPath
        );
      }

      // Publish stop event to trigger full re-render of stage, tree, and inspector
      // IMPORTANT: Use the NEW path, not the old one, so the element can be found and selected
      getPublisherSubscriber().publish('view/tree/dnd/stop', [newPath]);
    } catch (e) {
      console.error('[FormEditor Tree] Failed to move element:', e);
    }
  });
}

// Re-export types for backward compatibility
export type { FormEditorTreeNode };

declare global {
  interface PublisherSubscriberTopicArgumentsMap {
    'view/tree/node/changed': readonly [
      formElementIdentifierPath: string,
      newLabel: string,
    ];
    'view/tree/node/clicked': readonly [
      formElementIdentifierPath: string
    ];
    'view/tree/render/listItemAdded': readonly [
      listItem: HTMLElement | null,
      formElement: FormElement
    ];
    'view/tree/dnd/update': readonly [
      dndItem: HTMLElement | null,
      movedFormElementIdentifierPath: string,
      previousFormElementIdentifierPath: string,
      nextFormElementIdentifierPath: string,
    ];
    'view/tree/dnd/change': readonly [
      dndItem: HTMLElement | null,
      parentFormElementIdentifierPath: string,
      enclosingCompositeFormElement: FormElement,
    ];
    'view/tree/dnd/stop': readonly [
      treeNodeIdentifierPathWithinDomElement: string,
    ];
  }
}
