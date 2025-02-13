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

import type { SelectTree } from './select-tree';
import { html, LitElement, TemplateResult } from 'lit';
import { customElement } from 'lit/decorators';
import { lll } from '@typo3/core/lit-helper';
import { TreeNodeInterface } from '../../tree/tree-node';

@customElement('typo3-backend-form-selecttree-toolbar')
export class SelectTreeToolbar extends LitElement {
  public tree: SelectTree;
  private readonly settings = {
    collapseAllBtn: 'collapse-all-btn',
    expandAllBtn: 'expand-all-btn',
    searchInput: 'search-input',
    toggleHideUnchecked: 'hide-unchecked-btn'
  };

  /**
   * State of the hide unchecked toggle button
   *
   * @type {boolean}
   */
  private hideUncheckedState: boolean = false;

  // disable shadow dom for now
  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <div class="tree-toolbar btn-toolbar">
        <div class="input-group">
          <span class="input-group-text input-group-icon filter">
            <typo3-backend-icon identifier="actions-filter" size="small"></typo3-backend-icon>
          </span>
          <input type="search" class="form-control ${this.settings.searchInput}" placeholder="${lll('tcatree.findItem')}" @input="${(evt: InputEvent) => this.filter(evt)}">
        </div>
        <div class="btn-group">
          <button type="button" class="btn btn-default ${this.settings.expandAllBtn}" title="${lll('tcatree.expandAll')}" @click="${() => this.expandAll()}">
            <typo3-backend-icon identifier="apps-pagetree-category-expand-all" size="small"></typo3-backend-icon>
          </button>
          <button type="button" class="btn btn-default ${this.settings.collapseAllBtn}" title="${lll('tcatree.collapseAll')}" @click="${(evt: MouseEvent) => this.collapseAll(evt)}">
            <typo3-backend-icon identifier="apps-pagetree-category-collapse-all" size="small"></typo3-backend-icon>
          </button>
          <button type="button" class="btn btn-default ${this.settings.toggleHideUnchecked}" title="${lll('tcatree.toggleHideUnchecked')}" @click="${() => this.toggleHideUnchecked()}">
            <typo3-backend-icon identifier="apps-pagetree-category-toggle-hide-checked" size="small"></typo3-backend-icon>
          </button>
        </div>
      </div>
    `;
  }

  /**
   * Collapse children of root node
   */
  private collapseAll(evt: MouseEvent): void {
    evt.preventDefault();
    // Only collapse nodes that aren't on the root level
    this.tree.nodes.forEach((node: TreeNodeInterface) => {
      if (node.__parents.length) {
        this.tree.hideChildren(node);
      }
    });
  }


  /**
   * Expand all nodes
   */
  private expandAll() {
    this.tree.expandAll();
  }

  private filter(event: InputEvent): void {
    const inputEl = <HTMLInputElement>event.target;
    this.tree.filter(inputEl.value.trim());
  }

  /**
   * Show only checked items
   */
  private toggleHideUnchecked(): void {
    this.hideUncheckedState = !this.hideUncheckedState;
    if (this.hideUncheckedState) {
      this.tree.nodes.forEach((node: any) => {
        if (node.checked) {
          this.tree.showParents(node);
          node.expanded = true;
          node.__hidden = false;
        } else {
          node.expanded = false;
          node.__hidden = true;
        }
      });
    } else {
      this.tree.nodes.forEach((node: any) => node.__hidden = false);
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-form-selecttree-toolbar': SelectTreeToolbar;
  }
}
