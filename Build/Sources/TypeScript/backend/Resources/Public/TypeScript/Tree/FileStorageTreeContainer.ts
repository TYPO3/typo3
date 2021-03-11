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

import {html, customElement, property, query, LitElement, TemplateResult} from 'lit-element';
import {lll} from 'TYPO3/CMS/Core/lit-helper';
import {FileStorageTree} from './FileStorageTree';
import DebounceEvent from 'TYPO3/CMS/Core/Event/DebounceEvent';
import {FileStorageTreeActions} from './FileStorageTreeActions';
import 'TYPO3/CMS/Backend/Element/IconElement';

export const navigationComponentName: string = 'typo3-backend-navigation-component-filestoragetree';
const toolbarComponentName: string = 'typo3-backend-navigation-component-filestoragetree-toolbar';

/**
 * Responsible for setting up the viewport for the Navigation Component for the File Tree
 */
@customElement(navigationComponentName)
export class FileStorageTreeNavigationComponent extends LitElement {
  @query('.svg-tree-wrapper') tree: FileStorageTree;
  @query(toolbarComponentName) toolbar: Toolbar;

  public connectedCallback(): void {
    super.connectedCallback();
    document.addEventListener('typo3:filestoragetree:refresh', this.refresh);
    document.addEventListener('typo3:filestoragetree:selectFirstNode', this.selectFirstNode);
    // event listener updating current tree state, this can be removed in TYPO3 v12
    document.addEventListener('typo3:filelist:treeUpdateRequested', this.treeUpdateRequested);
  }

  public disconnectedCallback(): void {
    document.removeEventListener('typo3:filestoragetree:refresh', this.refresh);
    document.removeEventListener('typo3:filestoragetree:selectFirstNode', this.selectFirstNode);
    document.removeEventListener('typo3:filelist:treeUpdateRequested', this.treeUpdateRequested);
    super.disconnectedCallback();
  }

  // disable shadow dom for now
  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected render(): TemplateResult {
    const treeSetup = {
      dataUrl: top.TYPO3.settings.ajaxUrls.filestorage_tree_data,
      filterUrl: top.TYPO3.settings.ajaxUrls.filestorage_tree_filter,
      showIcons: true
    };

    return html`
      <div id="typo3-filestoragetree" class="svg-tree">
        <div>
          <typo3-backend-navigation-component-filestoragetree-toolbar .tree="${this.tree}" id="filestoragetree-toolbar" class="svg-toolbar"></typo3-backend-navigation-component-filestoragetree-toolbar>
          <div class="navigation-tree-container">
            <typo3-backend-filestorage-tree id="typo3-filestoragetree-tree" class="svg-tree-wrapper" .setup=${treeSetup}></typo3-backend-filestorage-tree>
          </div>
        </div>
        <div class="svg-tree-loader">
          <typo3-backend-icon identifier="spinner-circle-light" size="large"></typo3-backend-icon>
        </div>
      </div>
    `;
  }

  protected firstUpdated() {
    this.toolbar.tree = this.tree;
  }

  private refresh = (): void => {
    this.tree.refreshOrFilterTree();
  }

  private selectFirstNode = (): void => {
    const node = this.tree.nodes[0];
    if (node) {
      this.tree.selectNode(node);
    }
  }

  // event listener updating current tree state, this can be removed in TYPO3 v12
  private treeUpdateRequested = (evt: CustomEvent): void => {
    this.tree.selectNodeByIdentifier(evt.detail.payload.identifier);
  }
}

/**
 * Creates the toolbar above the tree
 */
@customElement(toolbarComponentName)
class Toolbar extends LitElement {
  @property({type: FileStorageTree}) tree: FileStorageTree = null;

  private settings = {
    searchInput: '.search-input',
    filterTimeout: 450
  };

  // disable shadow dom for now
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
      inputEl.focus();
      inputEl.clearable({
        onClear: () => {
          this.tree.resetFilter();
        }
      });
    }
  }

  protected render(): TemplateResult {
    return html`
      <div class="tree-toolbar">
        <div class="svg-toolbar__menu">
          <div class="svg-toolbar__search">
            <input type="text" class="form-control form-control-sm search-input" placeholder="${lll('tree.searchTermInfo')}">
          </div>
          <button class="btn btn-default btn-borderless btn-sm" @click="${() => this.refreshTree()}" data-tree-icon="actions-refresh" title="${lll('labels.refresh')}">
            <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
          </button>
        </div>
      </div>
    `;
  }

  private refreshTree(): void {
    this.tree.refreshOrFilterTree();
  }
}
