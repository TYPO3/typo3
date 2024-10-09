/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

export enum TreeNodeCommandEnum {
  COPY = 'copy',
  EDIT = 'edit',
  MOVE = 'move',
  DELETE = 'delete',
  NEW = 'new'
}

export enum TreeNodePositionEnum {
  INSIDE = 'inside',
  BEFORE = 'before',
  AFTER = 'after'
}


export interface TreeNodeStatusInformation {
  label: string,
  icon: string,
  severity: number,
  overlayIcon: string,
  priority: number
}

export interface TreeNodeLabel {
  label: string,
  color: string,
  priority: number,
}

/**
 * Represents a single node in the tree that is rendered.
 */
export interface TreeNodeInterface {

  // TYPO3\CMS\Backend\Dto\Tree\TreeItem
  type: string,
  identifier: string,
  parentIdentifier: string,
  recordType: string,
  name: string,
  note: string,
  prefix: string,
  suffix: string,
  tooltip: string,
  depth: number,
  hasChildren: boolean,
  loaded: boolean,
  editable: boolean,
  deletable: boolean,
  icon: string,
  overlayIcon: string,
  statusInformation: Array<TreeNodeStatusInformation>,
  labels: Array<TreeNodeLabel>,

  // Calculated Internal
  __treeIdentifier: string,
  __treeParents: Array<string>,
  __treeDragAction: string,
  __parents: Array<string>,
  __processed: boolean,
  __loading: boolean,
  __hidden: boolean,
  __expanded: boolean,
  __indeterminate: boolean,
  __x: number,
  __y: number,

  // TYPO3\CMS\Backend\Dto\Tree\PageTreeItem
  nameSourceField?: string,
  workspaceId?: number,
  locked?: boolean,
  stopPageTree?: boolean,
  mountPoint?: number,
  doktype?: number;

  // TYPO3\CMS\Backend\Dto\Tree\FileTreeItem
  pathIdentifier?: string,
  storage?: number,
  resourceType?: string,

  // TYPO3\CMS\Backend\Dto\Tree\SelectTreeItem
  checked?: boolean,
  selectable?: boolean,
}
