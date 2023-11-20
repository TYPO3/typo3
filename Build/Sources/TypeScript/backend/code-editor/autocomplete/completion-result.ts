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

import type { TsRef, TsRefType } from './ts-ref';
import type { TreeNode } from './ts-parser';

export type Proposal = {
  type: string,
  word?: string,
  cssClass?: string,
};

export class CompletionResult {
  private readonly tsRef: TsRef;
  private readonly tsTreeNode: TreeNode;

  constructor(tsRef: TsRef, tsTreeNode: TreeNode) {
    this.tsRef = tsRef;
    this.tsTreeNode = tsTreeNode;
  }

  /**
   * returns the type of the currentTsTreeNode
   */
  public getType(): TsRefType | null {
    const val = this.tsTreeNode.getValue();
    if (this.tsRef.isType(val)) {
      return this.tsRef.getType(val);
    }
    return null;
  }

  /**
   * returns a list of possible path completions (proposals), which is:
   * a list of the children of the current TsTreeNode (= userdefined properties)
   * and a list of properties allowed for the current object in the TsRef
   * remove all words from list that don't start with the string in filter
   */
  public getFilteredProposals(filter: string): Array<Proposal> {
    const defined: Record<string, boolean> = {};
    const propArr: Array<Proposal> = [];
    const childNodes = this.tsTreeNode.getChildNodes();
    const value = this.tsTreeNode.getValue();

    // first get the childNodes of the Node (=properties defined by the user)
    for (const key in childNodes) {
      if (typeof childNodes[key].value !== 'undefined' && childNodes[key].value !== null) {
        const propObj: Proposal = {} as Proposal;
        propObj.word = key;
        if (this.tsRef.typeHasProperty(value, childNodes[key].name)) {
          this.tsRef.cssClass = 'definedTSREFProperty';
          propObj.type = childNodes[key].value;
        } else {
          propObj.cssClass = 'userProperty';
          if (this.tsRef.isType(childNodes[key].value)) {
            propObj.type = childNodes[key].value;
          } else {
            propObj.type = '';
          }
        }
        propArr.push(propObj);
        defined[key] = true;
      }
    }

    // then get the tsref properties
    const props = this.tsRef.getPropertiesFromTypeId(this.tsTreeNode.getValue());
    for (const key in props) {
      // show just the TSREF properties - no properties of the array-prototype and no properties which have been defined by the user
      if (typeof props[key].value !== 'undefined' && defined[key] !== true) {
        const propObj = {
          word: key,
          cssClass: 'undefinedTSREFProperty',
          type: props[key].value,
        };
        propArr.push(propObj);
      }
    }

    const result: Array<Proposal> = [];
    let wordBeginning = '';

    for (let i = 0; i < propArr.length; i++) {
      if (filter.length === 0) {
        result.push(propArr[i]);
        continue;
      }
      wordBeginning = propArr[i].word.substring(0, filter.length);
      if (wordBeginning.toLowerCase() === filter.toLowerCase()) {
        result.push(propArr[i]);
      }
    }
    return result;
  }
}
