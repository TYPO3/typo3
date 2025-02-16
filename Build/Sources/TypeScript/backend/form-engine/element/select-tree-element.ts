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

import { type SelectTree } from './select-tree';
import { type SelectTreeToolbar } from './select-tree-toolbar';
import '@typo3/backend/element/icon-element';
import FormEngine, { type OnFieldChangeItem } from '@typo3/backend/form-engine';
import type { TreeNodeInterface } from '@typo3/backend/tree/tree-node';

export class SelectTreeElement {
  private readonly recordField: HTMLInputElement = null;
  private readonly tree: SelectTree = null;

  constructor(treeWrapperId: string, treeRecordFieldId: string, callback?: () => void, onFieldChangeItems?: OnFieldChangeItem[]) {
    if (callback instanceof Function) {
      throw new Error('Function `callback` is not supported anymore since TYPO3 v12.0');
    }

    this.recordField = <HTMLInputElement>document.getElementById(treeRecordFieldId);
    const treeWrapper = <HTMLElement>document.getElementById(treeWrapperId);
    this.tree = document.createElement('typo3-backend-form-selecttree') as SelectTree;
    this.tree.classList.add('tree-wrapper');
    this.tree.addEventListener('typo3:tree:nodes-prepared', this.loadDataAfter);
    this.tree.addEventListener('typo3:tree:node-selected', this.selectNode);

    if (onFieldChangeItems instanceof Array) {
      this.tree.addEventListener('typo3:tree:node-selected', () => { FormEngine.processOnFieldChange(onFieldChangeItems); } );
    }

    const settings = {
      id: treeWrapperId,
      dataUrl: this.generateRequestUrl(),
      readOnlyMode: parseInt(this.recordField.dataset.readOnly, 10) === 1,
      input: this.recordField,
      exclusiveNodesIdentifiers: this.recordField.dataset.treeExclusiveKeys,
      validation: JSON.parse(this.recordField.dataset.formengineValidationRules)[0],
      expandUpToLevel: this.recordField.dataset.treeExpandUpToLevel,
      unselectableElements: [] as Array<any>
    };
    this.tree.addEventListener('tree:initialized', () => {
      if (this.recordField.dataset.treeShowToolbar) {
        const toolbarElement = document.createElement('typo3-backend-form-selecttree-toolbar') as SelectTreeToolbar;
        toolbarElement.tree = this.tree;
        this.tree.prepend(toolbarElement);
      }
    });
    this.tree.setup = settings;
    treeWrapper.append(this.tree);
  }

  private generateRequestUrl(): string {
    const params = {
      tableName: this.recordField.dataset.tablename,
      fieldName: this.recordField.dataset.fieldname,
      uid: this.recordField.dataset.uid,
      defaultValues: this.recordField.dataset.defaultvalues,
      overrideValues: this.recordField.dataset.overridevalues,
      recordTypeValue: this.recordField.dataset.recordtypevalue,
      dataStructureIdentifier: this.recordField.dataset.datastructureidentifier,
      flexFormSheetName: this.recordField.dataset.flexformsheetname,
      flexFormFieldName: this.recordField.dataset.flexformfieldname,
      flexFormContainerName: this.recordField.dataset.flexformcontainername,
      flexFormContainerIdentifier: this.recordField.dataset.flexformcontaineridentifier,
      flexFormContainerFieldName: this.recordField.dataset.flexformcontainerfieldname,
      flexFormSectionContainerIsNew: this.recordField.dataset.flexformsectioncontainerisnew,
      command: this.recordField.dataset.command,
    };
    return TYPO3.settings.ajaxUrls.record_tree_data + '&' + new URLSearchParams(params).toString();
  }

  private readonly selectNode = (evt: CustomEvent) => {
    const node = evt.detail.node as TreeNodeInterface;
    this.updateAncestorsIndeterminateState(node);
    // check all nodes again, to ensure correct display of __indeterminate state
    this.calculateIndeterminate(this.tree.nodes);
    this.saveCheckboxes();
    this.tree.setup.input.dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));
  };

  /**
   * Resets the node.__indeterminate for the whole tree.
   * It's done once after loading data.
   * Later __indeterminate state is updated just for the subset of nodes
   */
  private readonly loadDataAfter = (evt: CustomEvent) => {
    this.tree.nodes = evt.detail.nodes.map((node: TreeNodeInterface) => {
      node.__indeterminate = false;
      return node;
    });
    this.calculateIndeterminate(this.tree.nodes);
  };

  /**
   * Sets a comma-separated list of selected nodes identifiers to configured input
   */
  private readonly saveCheckboxes = (): void => {
    if (typeof this.recordField === 'undefined') {
      return;
    }
    this.recordField.value = this.tree.getSelectedNodes().map((node: TreeNodeInterface): string => node.identifier).join(',');
  };

  /**
   * Updates the __indeterminate state for ancestors of the current node
   */
  private updateAncestorsIndeterminateState(node: TreeNodeInterface): void {
    // foreach ancestor except node itself
    let __indeterminate = false;
    node.__treeParents.forEach((treeParentIdentifier: string) => {
      const TreeNodeInterface = this.tree.getNodeByTreeIdentifier(treeParentIdentifier);
      TreeNodeInterface.__indeterminate = (node.checked || node.__indeterminate || __indeterminate);
      // check state for the next level
      __indeterminate = (TreeNodeInterface.checked || TreeNodeInterface.__indeterminate || node.checked || node.__indeterminate);
    });
  }

  /**
   * Sets __indeterminate state for a subtree.
   * It relays on the tree to have __indeterminate state reset beforehand.
   */
  private calculateIndeterminate(nodes: TreeNodeInterface[]): void {
    nodes.forEach((node: TreeNodeInterface) => {
      if ((node.checked || node.__indeterminate) && node.__treeParents && node.__treeParents.length > 0) {
        node.__treeParents.forEach((treeParentIdentifier: string) => {
          const TreeNodeInterface = this.tree.getNodeByTreeIdentifier(treeParentIdentifier);
          TreeNodeInterface.__indeterminate = true;
        });
      }
    });
  }
}
