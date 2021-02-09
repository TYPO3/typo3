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

/** @ts-ignore */
import {select as d3select} from 'd3-selection';
import {render} from 'lit-html';
import {html, TemplateResult} from 'lit-element';
import {icon, lll} from 'TYPO3/CMS/Core/lit-helper';
import {PageTreeDragDrop, ToolbarDragHandler} from './PageTreeDragDrop';
import DebounceEvent from 'TYPO3/CMS/Core/Event/DebounceEvent';

/**
 * @exports TYPO3/CMS/Backend/PageTree/PageTreeToolbar
 */
export class PageTreeToolbar
{
  private settings = {
    toolbarSelector: 'tree-toolbar',
    searchInput: '.search-input',
    filterTimeout: 450
  };

  private treeContainer: any;
  private targetEl: HTMLElement;

  private tree: any;
  private readonly dragDrop: any;

  public constructor(dragDrop: PageTreeDragDrop) {
    this.dragDrop = dragDrop;
  }

  public initialize(treeContainer: HTMLElement, toolbar: HTMLElement, settings: any = {}): void {
    this.treeContainer = treeContainer;
    this.targetEl = toolbar;

    if (!this.treeContainer.dataset.svgTreeInitialized
      || typeof this.treeContainer.svgtree !== 'object'
    ) {
      //both toolbar and tree are loaded independently through require js,
      //so we don't know which is loaded first
      //in case of toolbar being loaded first, we wait for an event from svgTree
      this.treeContainer.addEventListener('svg-tree:initialized', () => this.render());
      return;
    }

    Object.assign(this.settings, settings);
    this.render();
  }

  private refreshTree(): void {
    this.tree.refreshOrFilterTree();
  }

  private search(inputEl: HTMLInputElement): void {
    this.tree.searchQuery = inputEl.value.trim()
    this.tree.refreshOrFilterTree();
    this.tree.prepareDataForVisibleNodes();
    this.tree.update();
  }

  private render(): void
  {
    this.tree = this.treeContainer.svgtree;
    // @todo Better use initialize() settings, drop this assignment here
    Object.assign(this.settings, this.tree.settings);
    render(this.renderTemplate(), this.targetEl);

    const d3Toolbar = d3select('.svg-toolbar');
    this.tree.settings.doktypes.forEach((item: any) => {
      if (item.icon) {
        d3Toolbar
          .selectAll('[data-tree-icon=' + item.icon + ']')
          .call(this.dragToolbar(item));
      } else {
        console.warn('Missing icon definition for doktype: ' + item.nodeType);
      }
    });

    const inputEl = this.targetEl.querySelector(this.settings.searchInput) as HTMLInputElement;
    if (inputEl) {
      new DebounceEvent('input', (evt: InputEvent) => {
        this.search(evt.target as HTMLInputElement);
      }, this.settings.filterTimeout).bindTo(inputEl);
      inputEl.focus();
      inputEl.clearable({
        onClear: () => {
          this.tree.resetFilter();
          this.tree.prepareDataForVisibleNodes();
          this.tree.update();
        }
      });
    }
  }

  private renderTemplate(): TemplateResult {
    /* eslint-disable @typescript-eslint/indent */
    return html`
      <div class="${this.settings.toolbarSelector}">
        <div class="svg-toolbar__menu">
          <div class="svg-toolbar__search">
              <input type="text" class="form-control form-control-sm search-input" placeholder="${lll('tree.searchTermInfo')}">
          </div>
          <button class="btn btn-default btn-borderless btn-sm" @click="${() => this.refreshTree()}" data-tree-icon="actions-refresh" title="${lll('labels.refresh')}">
              ${icon('actions-refresh', 'small')}
          </button>
        </div>
        <div class="svg-toolbar__submenu">
          ${this.tree.settings.doktypes && this.tree.settings.doktypes.length
            ? this.tree.settings.doktypes.map((item: any) => {
              // @todo Unsure, why this has to be done for doktype icons
              this.tree.fetchIcon(item.icon, false);
              return html`
                <div class="svg-toolbar__drag-node" data-tree-icon="${item.icon}" data-node-type="${item.nodeType}"
                     title="${item.title}" tooltip="${item.tooltip}">
                  ${icon(item.icon, 'small')}
                </div>
              `;
            })
            : ''
          }
        </div>
      </div>
    `;
  }

  /**
   * Register Drag and drop for new elements of toolbar
   * Returns method from d3drag
   */
  private dragToolbar(itm: any) {
    return this.dragDrop.connectDragHandler(new ToolbarDragHandler(itm, this.tree, this.dragDrop));
  }
}
