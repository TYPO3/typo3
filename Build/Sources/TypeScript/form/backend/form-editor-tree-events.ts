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
 * Module: @typo3/form/backend/form-editor-tree-events
 *
 * Defines all custom events dispatched by the FormEditor Tree component
 * with their corresponding type definitions for type-safe event handling.
 */

import type { TreeNodePositionEnum } from '@typo3/backend/tree/tree-node';

/**
 * Form Editor Tree Event Names
 *
 * Centralized event name constants to prevent typos and enable
 * type-safe event handling across the tree component and adapter.
 */
export const FORM_EDITOR_TREE_EVENTS = {
  /** Dispatched when a tree node is clicked/selected */
  NODE_CLICKED: 'typo3:form-editor-tree:node-clicked',

  /** Dispatched when a tree node is double-clicked for editing */
  NODE_EDIT: 'typo3:form-editor-tree:node-edit',

  /** Dispatched when a tree node is requested to be deleted */
  NODE_DELETE: 'typo3:form-editor-tree:node-delete',

  /** Dispatched when a node is reordered within the same parent */
  DND_UPDATE: 'typo3:form-editor-tree:dnd-update',

  /** Dispatched when a node is moved to a different parent */
  DND_CHANGE: 'typo3:form-editor-tree:dnd-change',
} as const;

/**
 * Event detail for node-clicked event
 */
export interface NodeClickedEventDetail {
  /** Full identifier path of the clicked node */
  identifierPath: string;
}

/**
 * Event detail for node-edit event
 */
export interface NodeEditEventDetail {
  /** Full identifier path of the node to edit */
  identifierPath: string;
  /** Current label of the node */
  currentLabel: string;
}

/**
 * Event detail for node-delete event
 */
export interface NodeDeleteEventDetail {
  /** Full identifier path of the node to delete */
  identifierPath: string;
}

/**
 * Event detail for dnd-update event (reordering within same parent)
 */
export interface DndUpdateEventDetail {
  /** Identifier path of the moved node */
  movedIdentifierPath: string;
  /** Identifier path of the previous sibling (empty if first) */
  previousIdentifierPath: string;
  /** Identifier path of the next sibling (empty if last) */
  nextIdentifierPath: string;
}

/**
 * Event detail for dnd-change event (moving to different parent)
 */
export interface DndChangeEventDetail {
  /** Identifier path of the moved item */
  itemIdentifierPath: string;
  /** Identifier path of the new parent */
  parentIdentifierPath: string;
  /** Position relative to target (before, after, inside) */
  position: TreeNodePositionEnum;
  /** Identifier path of the previous sibling (empty if first) */
  previousIdentifierPath: string;
  /** Identifier path of the next sibling (empty if last) */
  nextIdentifierPath: string;
}

/**
 * Typed CustomEvent interfaces for better type safety
 */
export interface NodeClickedEvent extends CustomEvent<NodeClickedEventDetail> {
  type: typeof FORM_EDITOR_TREE_EVENTS.NODE_CLICKED;
}

export interface NodeEditEvent extends CustomEvent<NodeEditEventDetail> {
  type: typeof FORM_EDITOR_TREE_EVENTS.NODE_EDIT;
}

export interface NodeDeleteEvent extends CustomEvent<NodeDeleteEventDetail> {
  type: typeof FORM_EDITOR_TREE_EVENTS.NODE_DELETE;
}

export interface DndUpdateEvent extends CustomEvent<DndUpdateEventDetail> {
  type: typeof FORM_EDITOR_TREE_EVENTS.DND_UPDATE;
}

export interface DndChangeEvent extends CustomEvent<DndChangeEventDetail> {
  type: typeof FORM_EDITOR_TREE_EVENTS.DND_CHANGE;
}

/**
 * Type map for all FormEditor Tree events
 *
 * Can be used for type-safe event listener registration:
 * @example
 * tree.addEventListener(FORM_EDITOR_TREE_EVENTS.NODE_CLICKED, (e: NodeClickedEvent) => {
 *   console.log(e.detail.identifierPath);
 * });
 */
export interface FormEditorTreeEventMap {
  [FORM_EDITOR_TREE_EVENTS.NODE_CLICKED]: NodeClickedEvent;
  [FORM_EDITOR_TREE_EVENTS.NODE_EDIT]: NodeEditEvent;
  [FORM_EDITOR_TREE_EVENTS.NODE_DELETE]: NodeDeleteEvent;
  [FORM_EDITOR_TREE_EVENTS.DND_UPDATE]: DndUpdateEvent;
  [FORM_EDITOR_TREE_EVENTS.DND_CHANGE]: DndChangeEvent;
}
