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

// Imported for its side effect, the module registers the custom element used below
import '@typo3/backend/form-engine/element/select-tree.js';
import type { SelectTree } from '@typo3/backend/form-engine/element/select-tree.js';
import type { TreeNodeInterface } from '@typo3/backend/tree/tree-node.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/backend/form-engine/element/select-tree', () => {
  let tree: SelectTree;

  const createNode = (identifier: string, name: string, parents: string[]): TreeNodeInterface => {
    return <TreeNodeInterface><unknown>{
      identifier,
      name,
      parents,
      depth: parents.length,
      __parents: parents,
      __treeIdentifier: identifier,
      __processed: true,
      __hidden: false,
      __expanded: false,
    };
  };

  beforeEach((): void => {
    tree = <SelectTree>document.createElement('typo3-backend-form-selecttree');
    tree.nodes = [
      createNode('root', 'Root', []),
      createNode('vanilla', 'Vanille', ['root']),
      createNode('syrup', 'Sirup (Vanille)', ['root']),
      createNode('cocoa', 'Kakao', ['root', 'syrup']),
    ];
  });

  const visibleNodes = (): string[] => tree.nodes
    .filter((node: TreeNodeInterface): boolean => node.identifier !== 'root' && !node.__hidden)
    .map((node: TreeNodeInterface): string => node.name);

  it('matches case insensitively', (): void => {
    tree.filter('VANILLE');
    expect(visibleNodes()).to.eql(['Vanille', 'Sirup (Vanille)', 'Kakao']);
  });

  it('treats regular expression meta characters as literal text', (): void => {
    tree.filter('(Vanille)');
    expect(visibleNodes()).to.eql(['Sirup (Vanille)', 'Kakao']);
  });

  it('does not throw on an unbalanced meta character', (): void => {
    expect((): void => tree.filter('(')).to.not.throw();
    expect(visibleNodes()).to.eql(['Sirup (Vanille)', 'Kakao']);
  });

  it('shows all nodes for an empty search term', (): void => {
    tree.filter('');
    expect(visibleNodes()).to.eql(['Vanille', 'Sirup (Vanille)', 'Kakao']);
  });

  it('hides everything when nothing matches', (): void => {
    tree.filter('Erdbeere');
    expect(visibleNodes()).to.eql([]);
  });
});
