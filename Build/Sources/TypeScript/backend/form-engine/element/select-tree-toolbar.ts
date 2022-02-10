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
import {Tooltip} from 'bootstrap';
import {html, LitElement, TemplateResult} from 'lit';
import {customElement} from 'lit/decorators';
import {lll} from '@typo3/core/lit-helper';
import {TreeNode} from '../../tree/tree-node';

@customElement('typo3-backend-form-selecttree-toolbar')
export class SelectTreeToolbar extends LitElement {
  public tree: SelectTree;
  private settings = {
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
  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected firstUpdated(): void {
    this.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((tooltipTriggerEl: HTMLElement) => new Tooltip(tooltipTriggerEl));
  }

  protected render(): TemplateResult {
    return html`
      <div class="tree-toolbar btn-toolbar">
        <div class="input-group">
          <span class="input-group-addon input-group-icon filter">
            <typo3-backend-icon identifier="actions-filter" size="small"></typo3-backend-icon>
          </span>
          <input type="text" class="form-control ${this.settings.searchInput}" placeholder="${lll('tcatree.findItem')}" @input="${(evt: InputEvent) => this.filter(evt)}">
        </div>
        <div class="btn-group">
          <button type="button" data-bs-toggle="tooltip" class="btn btn-default ${this.settings.expandAllBtn}" title="${lll('tcatree.expandAll')}" @click="${() => this.expandAll()}">
            <typo3-backend-icon identifier="apps-pagetree-category-expand-all" size="small"></typo3-backend-icon>
          </button>
          <button type="button" data-bs-toggle="tooltip" class="btn btn-default ${this.settings.collapseAllBtn}" title="${lll('tcatree.collapseAll')}" @click="${(evt: MouseEvent) => this.collapseAll(evt)}">
            <typo3-backend-icon identifier="apps-pagetree-category-collapse-all" size="small"></typo3-backend-icon>
          </button>
          <button type="button" data-bs-toggle="tooltip" class="btn btn-default ${this.settings.toggleHideUnchecked}" title="${lll('tcatree.toggleHideUnchecked')}" @click="${() => this.toggleHideUnchecked()}">
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
    this.tree.nodes.forEach((node: TreeNode) => {
      if (node.parents.length) {
        this.tree.hideChildren(node);
      }
    });
    this.tree.prepareDataForVisibleNodes();
    this.tree.updateVisibleNodes();
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
          node.hidden = false;
        } else {
          node.hidden = true;
          node.expanded = false;
        }
      });
    } else {
      this.tree.nodes.forEach((node: any) => node.hidden = false);
    }
    this.tree.prepareDataForVisibleNodes();
    this.tree.updateVisibleNodes();
  }
}
