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

/**
 * Represents a single node in the SVG tree that is rendered.
 */
export interface TreeNode extends d3selection.EnterElement {
  firstChild: Node | boolean | null;
  lastChild: Node | boolean | null;

  // @todo those two `_x` and `_y` are never written
  _x?: number;
  _y?: number;

  x: number;
  y: number;
  depth: number;
  parents: Array<number>;
  loaded: boolean;
  expanded: boolean;
  canToggle: boolean;
  hasChildren: boolean;
  hidden: boolean;
  isOver: boolean;
  _isDragged: boolean;
  tip: string;
  selectable: boolean;
  checked: boolean;
  locked: boolean;
  readableRootline: string;
  command: string;
  identifier: string;
  name: string;
  class: string;
  prefix: string;
  suffix: string;
  mountPoint: string;
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
  allowEdit?: boolean;
  nameSourceField?: string;
  newName?: string;
}
