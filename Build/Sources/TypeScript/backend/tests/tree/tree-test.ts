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
import { spy, stub } from 'sinon';
import type { } from 'mocha';

class TestTree extends Tree {
  public enhance(nodes: TreeNodeInterface[]): TreeNodeInterface[] {
    return this.enhanceNodes(nodes);
  }

  public showFiltered(searchTerm: string, filteredNodes: TreeNodeInterface[]): void {
    this.unfilteredNodes = JSON.stringify(this.nodes);
    this.searchTerm = searchTerm;
    this.nodes = this.enhanceNodes(filteredNodes);
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

describe('tree filter reset', () => {
  const createFilteredTree = (beforeFiltering: (tree: TestTree) => void = () => undefined): TestTree => {
    const tree = document.createElement('typo3-test-storage-tree') as TestTree;
    tree.nodes = tree.enhance([
      { identifier: '1', depth: 0, loaded: true, hasChildren: true },
      { identifier: '2', depth: 1, loaded: false, hasChildren: true },
      { identifier: '3', depth: 1, loaded: false, hasChildren: false },
    ] as unknown as TreeNodeInterface[]);
    beforeFiltering(tree);
    tree.showFiltered('3', [
      { identifier: '1', depth: 0, loaded: true, hasChildren: true },
      { identifier: '3', depth: 1, loaded: true, hasChildren: false, checked: true },
    ] as unknown as TreeNodeInterface[]);
    return tree;
  };

  it('restores the nodes in the state they had before filtering', async () => {
    const tree = createFilteredTree();
    // Expanding a branch while filtering stores it as expanded, but its children were never loaded
    // into the restored nodes, so the stored status must not decide their state
    stub(tree, 'getNodeStatus').returns({ expanded: true });

    await tree.resetFilter();

    expect(tree.nodes.map(node => [node.identifier, node.__expanded, node.checked === true]))
      .to.deep.equal([['1', true, false], ['2', false, false], ['3', false, true]]);
  });

  it('does not restore a node that was loading when the filter was applied as loading', async () => {
    const tree = createFilteredTree((tree) => {
      tree.nodes[1].__loading = true;
    });

    await tree.resetFilter();

    expect(tree.nodes.map(node => node.__loading)).to.deep.equal([false, false, false]);
  });

  it('updates the tree when a restored node changes', async () => {
    const tree = createFilteredTree();
    await tree.resetFilter();
    const requestUpdate = spy(tree, 'requestUpdate');

    tree.nodes[1].__expanded = true;

    expect(requestUpdate.called).to.equal(true);
  });

  it('does not reset a filter when the tree is refreshed without one', async () => {
    const tree = document.createElement('typo3-test-storage-tree') as TestTree;
    stub(tree, 'loadData').resolves();
    const filterReset = spy();
    tree.addEventListener('typo3:tree:filter-reset', filterReset);

    tree.refreshOrFilterTree();
    await new Promise((resolve) => setTimeout(resolve));

    expect(filterReset.called).to.equal(false);
  });
});
