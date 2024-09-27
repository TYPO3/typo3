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

import { html, LitElement, TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators';
import { TreeNodeInterface } from './tree-node';
import { lll } from '@typo3/core/lit-helper';
import DebounceEvent from '@typo3/core/event/debounce-event';
import '@typo3/backend/element/icon-element';
import { Tree } from './tree';

@customElement('typo3-backend-tree-toolbar')
export class TreeToolbar extends LitElement {
  @property({ type: Tree }) tree: Tree = null;
  protected settings = {
    searchInput: '.search-input',
    filterTimeout: 450
  };

  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected firstUpdated(): void
  {
    const inputEl = this.querySelector(this.settings.searchInput) as HTMLInputElement;
    if (inputEl) {
      new DebounceEvent('input', (evt: InputEvent) => {
        const el = evt.target as HTMLInputElement;
        this.tree.filter(el.value.trim());
      }, this.settings.filterTimeout).bindTo(inputEl);
    }
  }

  protected render(): TemplateResult {
    return html`
      <div class="tree-toolbar">
        <div class="tree-toolbar__menu">
          <div class="tree-toolbar__search">
              <label for="toolbarSearch" class="visually-hidden">
                ${lll('labels.label.searchString')}
              </label>
              <input type="search" id="toolbarSearch" class="form-control form-control-sm search-input" placeholder="${lll('tree.searchTermInfo')}">
          </div>
        </div>
        <div class="tree-toolbar__submenu">
          <button
            type="button"
            class="tree-toolbar__menuitem dropdown-toggle dropdown-toggle-no-chevron float-end"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            <typo3-backend-icon identifier="actions-menu-alternative" size="small"></typo3-backend-icon>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <button class="dropdown-item" @click="${() => this.refreshTree()}">
                <span class="dropdown-item-columns">
                  <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                    <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
                  </span>
                  <span class="dropdown-item-column dropdown-item-column-title">
                    ${lll('labels.refresh')}
                  </span>
                </span>
              </button>
            </li>
            <li>
              <button class="dropdown-item" @click="${(evt: MouseEvent) => this.collapseAll(evt)}">
                <span class="dropdown-item-columns">
                  <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                    <typo3-backend-icon identifier="apps-pagetree-category-collapse-all" size="small"></typo3-backend-icon>
                  </span>
                  <span class="dropdown-item-column dropdown-item-column-title">
                    ${lll('labels.collapse')}
                  </span>
                </span>
              </button>
            </li>
          </ul>
        </div>
      </div>
    `;
  }

  protected refreshTree(): void {
    this.tree.refreshOrFilterTree();
  }

  protected collapseAll(evt: MouseEvent): void {
    evt.preventDefault();
    // Only collapse nodes that aren't on the root level
    // @TODO Implement into tree
    this.tree.nodes.forEach((node: TreeNodeInterface) => {
      if (node.__parents.length) {
        this.tree.hideChildren(node);
      }
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-tree-toolbar': TreeToolbar;
  }
}
