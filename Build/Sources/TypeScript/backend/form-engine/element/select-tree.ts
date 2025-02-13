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

import { html, TemplateResult } from 'lit';
import { Tree, TreeSettings } from '@typo3/backend/tree/tree';
import { TreeNodeInterface } from '@typo3/backend/tree/tree-node';
import { customElement, state } from 'lit/decorators';

interface SelectTreeSettings extends TreeSettings {
  exclusiveNodesIdentifiers: '';
  validation: {[keys: string]: any};
  unselectableElements: Array<any>,
  readOnlyMode: false
}

@customElement('typo3-backend-form-selecttree')
export class SelectTree extends Tree
{
  @state() override settings: SelectTreeSettings = {
    unselectableElements: [],
    exclusiveNodesIdentifiers: '',
    validation: {},
    readOnlyMode: false,
    showIcons: true,
    width: 300,
    dataUrl: '',
    defaultProperties: {},
    expandUpToLevel: null as any,
  };

  /**
   * Exclusive node which is currently selected
   */
  @state() exclusiveSelectedNode: TreeNodeInterface = null;

  constructor() {
    super();
    this.addEventListener('typo3:tree:nodes-prepared', this.prepareLoadedNodes);
  }

  /**
   * Expand all nodes and refresh view
   */
  public expandAll(): void {
    this.nodes.forEach((node: TreeNodeInterface) => { this.showChildren(node); });
  }

  /**
   * Node selection logic (triggered by different events) to select multiple
   * nodes (unlike SVG Tree itself).
   */
  public override selectNode(node: TreeNodeInterface, propagate: boolean = true): void {
    if (!this.isNodeSelectable(node)) {
      return;
    }

    const checked = node.checked;
    this.handleExclusiveNodeSelection(node);

    if (!checked && this.settings.validation.maxItems == 1 && this.getSelectedNodes().length > 0) {
      //  we unselect now, if only one checked node allowed AND a node is already checked,
      this.getSelectedNodes()[0].checked = false;
    }

    if (this.settings.validation && this.settings.validation.maxItems) {
      if (!checked && this.getSelectedNodes().length >= this.settings.validation.maxItems) {
        return;
      }
    }

    node.checked = !checked;
    this.dispatchEvent(new CustomEvent('typo3:tree:node-selected', { detail: { node: node, propagate: propagate } }));
  }

  public override filter(searchTerm?: string|null): void {
    const results: TreeNodeInterface[] = [];
    this.searchTerm = searchTerm;
    if (this.nodes.length) {
      this.nodes[0].__expanded = false;
    }
    const firstNode = this.nodes[0];
    const regex = new RegExp(searchTerm, 'i');

    this.nodes.forEach((node: any) => {
      // skip the root node in searches
      if (node === firstNode) {
        return;
      }

      node.__expanded = false;
      node.__hidden = true;

      if (regex.test(node.name)) {
        results.push(node);
      }
    });

    results.forEach((node) => {
      node.__hidden = false;
      this.showParents(node);
    });

    // filter for children of results and show them
    const children = this.nodes.filter(node => results.some(result => node.__parents.includes(result.identifier)));
    children.forEach((child) => {
      child.__hidden = false;
    });
  }

  /**
   * Finds and show all parents of node
   */
  public showParents(node: any): void {
    if (node.__parents.length === 0) {
      return;
    }
    const parent = this.nodes.find((searchNode) => searchNode.identifier === node.__parents.at(-1));
    parent.__hidden = false;
    parent.__expanded = true;
    this.showParents(parent);
  }

  /**
   * Check whether node can be selected.
   * In some cases (e.g. selecting a parent) it should not be possible to select
   * element (as it's own parent).
   */
  protected override isNodeSelectable(node: TreeNodeInterface): boolean {
    return !this.settings.readOnlyMode && this.settings.unselectableElements.indexOf(node.identifier) === -1;
  }

  /**
   * Add checkbox before the icon
   */
  protected override createNodeContent(node: TreeNodeInterface): TemplateResult {
    return html`
      ${this.renderCheckbox(node)}
      ${super.createNodeContent(node)}
    `;
  }

  /**
   * Adds svg elements for checkbox rendering.
   */
  private renderCheckbox(node: TreeNodeInterface): TemplateResult {
    const checked = Boolean(node.checked);

    let icon = 'actions-square';
    if (!this.isNodeSelectable(node) && !checked) {
      icon = 'actions-minus-circle';
    } else if (node.checked) {
      icon = 'actions-check-square';
    } else if (node.__indeterminate && !checked) {
      icon = 'actions-minus-square';
    }

    return html`
      <span class="node-select">
        <typo3-backend-icon identifier="${icon}" size="small"></typo3-backend-icon>
      </span>
    `;
  }

  /**
   * Check if a node has all information to be used.
   */
  private prepareLoadedNodes(evt: CustomEvent): void {
    const nodes = evt.detail.nodes as Array<TreeNodeInterface>;
    evt.detail.nodes = nodes.map((node: TreeNodeInterface) => {
      if (node.selectable === false) {
        this.settings.unselectableElements.push(node.identifier);
      }
      return node;
    });
  }

  /**
   * Handle exclusive nodes functionality
   * If a node is one of the exclusiveNodesIdentifiers list,
   * all other nodes has to be unselected before selecting this node.
   *
   * @param {Node} node
   */
  private handleExclusiveNodeSelection(node: TreeNodeInterface): void {
    const exclusiveKeys = this.settings.exclusiveNodesIdentifiers.split(',');
    if (this.settings.exclusiveNodesIdentifiers.length && node.checked === false) {
      if (exclusiveKeys.indexOf('' + node.identifier) > -1) {
        // this key is exclusive, so uncheck all others
        this.resetSelectedNodes();
        this.exclusiveSelectedNode = node;
      } else if (exclusiveKeys.indexOf('' + node.identifier) === -1 && this.exclusiveSelectedNode) {

        // current node is not exclusive, but other exclusive node is already selected
        this.exclusiveSelectedNode.checked = false;
        this.exclusiveSelectedNode = null;
      }
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-form-selecttree': SelectTree;
  }
}
