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

import {Tooltip} from 'bootstrap';
import {render} from 'lit-html';
import {html, TemplateResult} from 'lit-element';
import {icon, lll} from 'TYPO3/CMS/Core/lit-helper';

/**
 * @exports TYPO3/CMS/Backend/FormEngine/Element/TreeToolbar
 */
export class TreeToolbar
{
  private settings = {
    toolbarSelector: 'tree-toolbar btn-toolbar',
    collapseAllBtn: 'collapse-all-btn',
    expandAllBtn: 'expand-all-btn',
    searchInput: 'search-input',
    toggleHideUnchecked: 'hide-unchecked-btn'
  };

  private readonly treeContainer: HTMLElement;
  private tree: any;

  /**
   * State of the hide unchecked toggle button
   *
   * @type {boolean}
   */
  private hideUncheckedState: boolean = false;

  public constructor(treeContainer: HTMLElement, settings: any = {}) {
    this.treeContainer = treeContainer;
    Object.assign(this.settings, settings);
    if (!this.treeContainer.dataset.svgTreeInitialized
      || typeof (this.treeContainer as any).svgtree !== 'object'
    ) {
      //both toolbar and tree are loaded independently through require js,
      //so we don't know which is loaded first
      //in case of toolbar being loaded first, we wait for an event from svgTree
      this.treeContainer.addEventListener('svg-tree:initialized', this.render.bind(this));
    } else {
      this.render();
    }
  }

  /**
   * Collapse children of root node
   */
  private collapseAll() {
    this.tree.collapseAll();
  };

  /**
   * Expand all nodes
   */
  private expandAll() {
    this.tree.expandAll();
  };

  private search(event: InputEvent): void {
    const inputEl = <HTMLInputElement>event.target;
    if (this.tree.nodes.length) {
      this.tree.nodes[0].open = false;
    }
    const name = inputEl.value.trim()
    const regex = new RegExp(name, 'i');

    this.tree.nodes.forEach((node: any) => {
      if (regex.test(node.name)) {
        this.showParents(node);
        node.open = true;
        node.hidden = false;
      } else {
        node.hidden = true;
        node.open = false;
      }
    });

    this.tree.prepareDataForVisibleNodes();
    this.tree.update();
  }

  /**
   * Show only checked items
   */
  private toggleHideUnchecked(): void {
    this.hideUncheckedState = !this.hideUncheckedState;
    if (this.hideUncheckedState) {
      this.tree.nodes.forEach((node: any) => {
        if (node.checked) {
          this.showParents(node);
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
    this.tree.update();
  }

  /**
   * Finds and show all parents of node
   */
  private showParents(node: any): void {
    if (node.parents.length === 0) {
      return;
    }
    const parent = this.tree.nodes[node.parents[0]];
    parent.hidden = false;
    // expand parent node
    parent.expanded = true;
    this.showParents(parent);
  }

  private render(): void {
    this.tree = (this.treeContainer as any).svgtree;

    // @todo Better use initialize() settings, drop this assignment here
    Object.assign(this.settings, this.tree.settings);

    const placeholderElement = document.createElement('div');
    this.treeContainer.prepend(placeholderElement);
    render(this.renderTemplate(), placeholderElement);
    const toolbarElement = this.treeContainer
      .querySelector('.' + this.settings.toolbarSelector);
    if (toolbarElement) {
      toolbarElement.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((tooltipTriggerEl: HTMLElement) => new Tooltip(tooltipTriggerEl));
    }
  }

  private renderTemplate(): TemplateResult {
    return html`
      <div class="${this.settings.toolbarSelector}">
        <div class="input-group">
          <span class="input-group-addon input-group-icon filter">${icon('actions-filter', 'small')}</span>
          <input type="text" class="form-control ${this.settings.searchInput}" placeholder="${lll('tcatree.findItem')}" @input="${(evt: InputEvent) => this.search(evt)}">
        </div>
        <div class="btn-group">
          <button type="button" data-bs-toggle="tooltip" class="btn btn-default ${this.settings.expandAllBtn}" title="${lll('tcatree.expandAll')}" @click="${() => this.expandAll()}">
            ${icon('apps-pagetree-category-expand-all', 'small')}
          </button>
          <button type="button" data-bs-toggle="tooltip" class="btn btn-default ${this.settings.collapseAllBtn}" title="${lll('tcatree.collapseAll')}" @click="${() => this.collapseAll()}">
            ${icon('apps-pagetree-category-collapse-all', 'small')}
          </button>
          <button type="button" data-bs-toggle="tooltip" class="btn btn-default ${this.settings.toggleHideUnchecked}" title="${lll('tcatree.toggleHideUnchecked')}" @click="${() => this.toggleHideUnchecked()}">
            ${icon('apps-pagetree-category-toggle-hide-checked', 'small')}
          </button>
        </div>
      </div>
    `;
  }
}
