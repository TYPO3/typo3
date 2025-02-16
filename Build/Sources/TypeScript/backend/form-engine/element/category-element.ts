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
import { selector } from '@typo3/core/literals';
import type { TreeNodeInterface } from '@typo3/backend/tree/tree-node';

/**
 * Module: @typo3/backend/form-engine/element/category-element
 *
 * Functionality for the category element (renders a tree view)
 *
 * @example
 * <typo3-formengine-element-category recordFieldId="some-id" treeWrapperId="some-id">
 *   ...
 * </typo3-formengine-element-category>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
class CategoryElement extends HTMLElement{
  private recordField: HTMLInputElement = null;
  private treeWrapper: HTMLElement = null;
  private tree: SelectTree = null;

  public connectedCallback(): void {
    this.recordField = <HTMLInputElement>this.querySelector(selector`#${this.getAttribute('recordFieldId') || '' as string}`);
    this.treeWrapper = <HTMLElement>this.querySelector(selector`#${this.getAttribute('treeWrapperId') || '' as string}`);

    if (!this.recordField || !this.treeWrapper) {
      return;
    }

    this.tree = document.createElement('typo3-backend-form-selecttree') as SelectTree;
    this.tree.classList.add('tree-wrapper');
    this.tree.setup = {
      id: this.treeWrapper.id,
      dataUrl: this.generateDataUrl(),
      readOnlyMode: this.recordField.dataset.readOnly,
      input: this.recordField,
      exclusiveNodesIdentifiers: this.recordField.dataset.treeExclusiveKeys,
      validation: JSON.parse(this.recordField.dataset.formengineValidationRules)[0],
      expandUpToLevel: this.recordField.dataset.treeExpandUpToLevel,
      unselectableElements: [] as Array<any>
    };

    this.treeWrapper.append(this.tree);
    this.registerTreeEventListeners();
  }

  private registerTreeEventListeners(): void {
    this.tree.addEventListener('typo3:tree:nodes-prepared', this.loadDataAfter);
    this.tree.addEventListener('typo3:tree:node-selected', this.selectNode);
    this.tree.addEventListener('tree:initialized', () => {
      if (this.recordField.dataset.treeShowToolbar) {
        const toolbarElement = document.createElement('typo3-backend-form-selecttree-toolbar') as SelectTreeToolbar;
        toolbarElement.tree = this.tree;
        this.tree.prepend(toolbarElement);
      }
    });
  }

  private generateDataUrl(): string {
    return TYPO3.settings.ajaxUrls.record_tree_data + '&' + new URLSearchParams({
      uid: this.recordField.dataset.uid,
      command: this.recordField.dataset.command,
      tableName: this.recordField.dataset.tablename,
      fieldName: this.recordField.dataset.fieldname,
      defaultValues: this.recordField.dataset.defaultvalues,
      overrideValues: this.recordField.dataset.overridevalues,
      recordTypeValue: this.recordField.dataset.recordtypevalue,
      flexFormSheetName: this.recordField.dataset.flexformsheetname,
      flexFormFieldName: this.recordField.dataset.flexformfieldname,
      flexFormContainerName: this.recordField.dataset.flexformcontainername,
      dataStructureIdentifier: this.recordField.dataset.datastructureidentifier,
      flexFormContainerFieldName: this.recordField.dataset.flexformcontainerfieldname,
      flexFormContainerIdentifier: this.recordField.dataset.flexformcontaineridentifier,
      flexFormSectionContainerIsNew: this.recordField.dataset.flexformsectioncontainerisnew,
    }).toString();
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
    this.recordField.value = this.tree.getSelectedNodes().map((node: TreeNodeInterface): string => node.identifier).join(',');
  };

  /**
   * Updates the __indeterminate state for ancestors of the current node
   */
  private updateAncestorsIndeterminateState(node: TreeNodeInterface): void {
    // foreach ancestor except node itself
    let __indeterminate = false;
    node.__treeParents.forEach((treeIdentifier: string) => {
      const TreeNodeInterface = this.tree.getNodeByTreeIdentifier(treeIdentifier);
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

window.customElements.define('typo3-formengine-element-category', CategoryElement);
