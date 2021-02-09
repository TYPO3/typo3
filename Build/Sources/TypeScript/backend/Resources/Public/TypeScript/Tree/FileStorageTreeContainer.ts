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

import {render} from 'lit-html';
import {html, TemplateResult} from 'lit-element';
import {lll} from 'TYPO3/CMS/Core/lit-helper';
import {FileStorageTree} from './FileStorageTree';
import viewPort from '../Viewport';
import DebounceEvent from 'TYPO3/CMS/Core/Event/DebounceEvent';
import {FileStorageTreeActions} from './FileStorageTreeActions';
import 'TYPO3/CMS/Backend/Element/IconElement';

/**
 * Responsible for setting up the viewport for the Navigation Component for the File Tree
 */
export class FileStorageTreeContainer {
  public static initialize(selector: string): void {
    const targetEl = document.querySelector(selector);

    // let SvgTree know it shall be visible
    if (targetEl && targetEl.childNodes.length > 0) {
      targetEl.querySelector('.svg-tree').dispatchEvent(new Event('svg-tree:visible'));
      return;
    }

    render(FileStorageTreeContainer.renderTemplate(), targetEl);
    const treeEl = <HTMLElement>targetEl.querySelector('.svg-tree-wrapper');

    const tree = new FileStorageTree();
    const actions = new FileStorageTreeActions(tree);
    tree.initialize(treeEl, {
      dataUrl: top.TYPO3.settings.ajaxUrls.filestorage_tree_data,
      filterUrl: top.TYPO3.settings.ajaxUrls.filestorage_tree_filter,
      showIcons: true
    }, actions);
    viewPort.NavigationContainer.setComponentInstance(tree);
    // Activate the toolbar
    const toolbar = <HTMLElement>targetEl.querySelector('.svg-toolbar');
    new Toolbar(treeEl, toolbar);

    // event listener updating current tree state
    document.addEventListener('typo3:filelist:treeUpdateRequested', (evt: CustomEvent) => {
      tree.selectNodeByIdentifier(evt.detail.payload.identifier);
    });
  }

  private static renderTemplate(): TemplateResult {
    return html`
      <div id="typo3-filestoragetree" class="svg-tree">
        <div>
          <div id="filestoragetree-toolbar" class="svg-toolbar"></div>
          <div class="navigation-tree-container">
            <div id="typo3-filestoragetree-tree" class="svg-tree-wrapper">
              <div class="node-loader">
                <typo3-backend-icon identifier="spinner-circle-light" size="small"></typo3-backend-icon>
              </div>
            </div>
          </div>
        </div>
        <div class="svg-tree-loader">
          <typo3-backend-icon identifier="spinner-circle-light" size="large"></typo3-backend-icon>
        </div>
      </div>
    `;
  }
}

/**
 * Contains the toolbar above the tree
 */
class Toolbar
{
  private settings = {
    toolbarSelector: 'tree-toolbar',
    searchInput: '.search-input',
    filterTimeout: 450
  };
  private readonly treeContainer: any;
  private readonly targetEl: HTMLElement;
  private tree: FileStorageTree;

  public constructor(treeContainer: HTMLElement, toolbar: HTMLElement) {
    this.treeContainer = treeContainer;
    this.targetEl = toolbar;

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
    return html`<div class="${this.settings.toolbarSelector}">
        <div class="svg-toolbar__menu">
          <div class="svg-toolbar__search">
            <input type="text" class="form-control form-control-sm search-input" placeholder="${lll('tree.searchTermInfo')}">
          </div>
          <button class="btn btn-default btn-borderless btn-sm" @click="${() => this.refreshTree()}" data-tree-icon="actions-refresh" title="${lll('labels.refresh')}">
            <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
          </button>
        </div>
      </div>`;
  }
}
