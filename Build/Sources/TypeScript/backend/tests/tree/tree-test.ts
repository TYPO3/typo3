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

import { expect } from '@open-wc/testing';
import { Tree } from '@typo3/backend/tree/tree.js';
import type { TreeNodeInterface } from '@typo3/backend/tree/tree-node.js';
import type { } from 'mocha';

class TestTree extends Tree {
  public enhance(nodes: TreeNodeInterface[]): TreeNodeInterface[] {
    return this.enhanceNodes(nodes);
  }
}

customElements.define('typo3-test-storage-tree', TestTree);

describe('file storage tree search', () => {
  it('expands every matching storage root', () => {
    const tree = document.createElement('typo3-test-storage-tree') as TestTree;
    tree.searchTerm = 'needle';
    const nodes = [
      { identifier: '1:/', depth: 0, loaded: false, hasChildren: true },
      { identifier: '1:/needle', depth: 1, loaded: true, hasChildren: false },
      { identifier: '2:/', depth: 0, loaded: false, hasChildren: true },
      { identifier: '2:/needle', depth: 1, loaded: true, hasChildren: false },
    ] as unknown as TreeNodeInterface[];

    expect(tree.enhance(nodes).filter(node => node.depth === 0).map(node => node.__expanded))
      .to.deep.equal([true, true]);
  });
});
