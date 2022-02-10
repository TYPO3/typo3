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

import type {SelectTree} from './select-tree';
import type {SelectTreeToolbar} from './select-tree-toolbar';
import './select-tree';
import './select-tree-toolbar';
import '@typo3/backend/element/icon-element';
import {TreeNode} from '@typo3/backend/tree/tree-node';
import FormEngine from '@typo3/backend/form-engine';
import OnFieldChangeItem = TYPO3.CMS.Backend.OnFieldChangeItem;

export class SelectTreeElement {
  private readonly recordField: HTMLInputElement = null;
  private readonly tree: SelectTree = null;

  constructor(treeWrapperId: string, treeRecordFieldId: string, callback?: Function, onFieldChangeItems?: OnFieldChangeItem[]) {
    if (callback instanceof Function) {
      throw new Error('Function `callback` is not supported anymore since TYPO3 v12.0');
    }

    this.recordField = <HTMLInputElement>document.getElementById(treeRecordFieldId);
    const treeWrapper = <HTMLElement>document.getElementById(treeWrapperId);
    this.tree = document.createElement('typo3-backend-form-selecttree') as SelectTree;
    this.tree.classList.add('svg-tree-wrapper');
    this.tree.addEventListener('typo3:svg-tree:nodes-prepared', this.loadDataAfter);
    this.tree.addEventListener('typo3:svg-tree:node-selected', this.selectNode);

    if (onFieldChangeItems instanceof Array) {
      this.tree.addEventListener('typo3:svg-tree:node-selected', () => { FormEngine.processOnFieldChange(onFieldChangeItems) } );
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
    this.tree.addEventListener('svg-tree:initialized', () => {
      if (this.recordField.dataset.treeShowToolbar) {
        const toolbarElement = document.createElement('typo3-backend-form-selecttree-toolbar') as SelectTreeToolbar;
        toolbarElement.tree = this.tree;
        this.tree.prepend(toolbarElement);
      }
    });
    this.tree.setup = settings;
    treeWrapper.append(this.tree);
    this.listenForVisibleTree();
  }

  /**
   * If the Select item is in an invisible tab, it needs to be rendered once the tab
   * becomes visible.
   */
  private listenForVisibleTree(): void {
    if (!this.tree.offsetParent) {
      // Search for the parents that are tab containers
      let idOfTabContainer = this.tree.closest('.tab-pane').getAttribute('id');
      if (idOfTabContainer) {
        let btn = document.querySelector('[aria-controls="' + idOfTabContainer + '"]');
        btn.addEventListener('shown.bs.tab', () => { this.tree.dispatchEvent(new Event('svg-tree:visible')); });
      }
    }
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
    return TYPO3.settings.ajaxUrls.record_tree_data + '&' + new URLSearchParams(params);
  }

  private selectNode = (evt: CustomEvent) => {
    const node = evt.detail.node as TreeNode;
    this.updateAncestorsIndeterminateState(node);
    // check all nodes again, to ensure correct display of indeterminate state
    this.calculateIndeterminate(this.tree.nodes);
    this.saveCheckboxes();
    this.tree.setup.input.dispatchEvent(new Event('change', {bubbles: true, cancelable: true}));
  }

  /**
   * Resets the node.indeterminate for the whole tree.
   * It's done once after loading data.
   * Later indeterminate state is updated just for the subset of nodes
   */
  private loadDataAfter = () => {
    this.tree.nodes = this.tree.nodes.map((node: TreeNode) => {
      node.indeterminate = false;
      return node;
    });
    this.calculateIndeterminate(this.tree.nodes);
  }

  /**
   * Sets a comma-separated list of selected nodes identifiers to configured input
   */
  private saveCheckboxes = (): void => {
    if (typeof this.recordField === 'undefined') {
      return;
    }
    this.recordField.value = this.tree.getSelectedNodes().map((node: TreeNode): string => node.identifier).join(',');
  }

  /**
   * Updates the indeterminate state for ancestors of the current node
   */
  private updateAncestorsIndeterminateState(node: TreeNode): void {
    // foreach ancestor except node itself
    let indeterminate = false;
    node.parents.forEach((index: number) => {
      const node = this.tree.nodes[index];
      node.indeterminate = (node.checked || node.indeterminate || indeterminate);
      // check state for the next level
      indeterminate = (node.checked || node.indeterminate || node.checked || node.indeterminate);
    });
  }

  /**
   * Sets indeterminate state for a subtree.
   * It relays on the tree to have indeterminate state reset beforehand.
   */
  private calculateIndeterminate(nodes: TreeNode[]): void {
    nodes.forEach((node: TreeNode) => {
      if ((node.checked || node.indeterminate) && node.parents && node.parents.length > 0) {
        node.parents.forEach((parentNodeIndex: number) => {
          nodes[parentNodeIndex].indeterminate = true;
        });
      }
    });
  }
}
