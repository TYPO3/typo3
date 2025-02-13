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

import { Tree } from '@typo3/backend/tree/tree';
import { TreeNodeInterface } from '@typo3/backend/tree/tree-node';

/**
 * A tree for folders / storages
 */
export class FileStorageTree extends Tree
{
  public constructor() {
    super();
    this.settings.defaultProperties = {
      hasChildren: false,
      nameSourceField: 'title',
      type: 'sys_file',
      prefix: '',
      suffix: '',
      locked: false,
      loaded: false,
      overlayIcon: '',
      selectable: true,
      expanded: false,
      checked: false,
    };
  }

  protected override getNodeTitle(node: TreeNodeInterface): string {
    return decodeURIComponent(node.name);
  }
}
