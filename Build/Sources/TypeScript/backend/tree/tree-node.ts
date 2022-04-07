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

import * as d3selection from 'd3-selection';
import {DraggablePositionEnum} from './drag-drop';

/**
 * Represents a single node in the SVG tree that is rendered.
 */
export interface TreeNode extends d3selection.EnterElement {
  firstChild: Node | boolean | null;
  lastChild: Node | boolean | null;
  // e.g. "pages" or "sys_file", depending on the implementation
  itemType: string | null;
  x: number;
  y: number;
  // page-tree specific (doktype)
  type: string;
  depth: number;
  parents: Array<number>;
  loaded: boolean;
  expanded: boolean;
  hasChildren: boolean;
  hidden: boolean;
  isOver: boolean;
  tip: string;
  selectable: boolean;
  checked: boolean;
  focused: boolean;
  locked: boolean;
  readableRootline: string;
  command: string;
  identifier: string;
  name: string;
  class: string;
  prefix: string;
  suffix: string;
  // page-tree specific
  mountPoint: string;
  // page-tree specific
  stopPageTree: boolean;
  stateIdentifier: string;
  parentsStateIdentifier: Array<string>;
  backgroundColor: string;
  overlayIcon: string;
  attr: Function;
  enter: Function;
  append: Function;
  select: Function;
  icon: any;
  node: boolean | TreeNode;
  siblingsCount: number;
  siblingsPosition: number;

  owns?: string[];
  indeterminate?: boolean;

  // folder-tree specific
  pathIdentifier: string;
  storage: number;

  allowDelete?: boolean;
  allowEdit?: boolean;
  // page-tree specific: which DB field should be updated when editing the DB field
  nameSourceField?: string;
  // page-tree specific: which DB field should be updated when editing the DB field
  newName?: string;
  target?: TreeNode;
  position?: DraggablePositionEnum;
}
