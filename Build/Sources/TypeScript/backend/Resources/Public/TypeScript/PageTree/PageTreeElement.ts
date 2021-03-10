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
import {PageTree} from './PageTree';
import {PageTreeDragDrop, ToolbarDragHandler} from './PageTreeDragDrop';
import AjaxRequest from 'TYPO3/CMS/Core/Ajax/AjaxRequest';
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {select as d3select} from 'd3-selection';
import DebounceEvent from 'TYPO3/CMS/Core/Event/DebounceEvent';
import 'TYPO3/CMS/Backend/Element/IconElement';
import Persistent from 'TYPO3/CMS/Backend/Storage/Persistent';

/**
 * This module defines the Custom Element for rendering the navigation component for an editable page tree
 * including drag+drop, deletion, in-place editing and a custom toolbar for this component.
 *
 * It is used as custom element via "<typo3-backend-navigation-component-pagetree>".
 *
 * The navigationComponentName export is used by the NavigationContainer in order to
 * create an instance of PageTreeNavigationComponent via document.createElement().
 */

export const navigationComponentName: string = 'typo3-backend-navigation-component-pagetree';
const toolbarComponentName: string = 'typo3-backend-navigation-component-pagetree-toolbar';

@customElement(navigationComponentName)
export class PageTreeNavigationComponent extends LitElement {
  @property({type: String}) mountPointPath: string = null;

  // @todo: Migrate svg-tree-wrapper into a custom element
  @query('.svg-tree-wrapper') treeWrapper: HTMLElement;

  private readonly tree: PageTree = null;

  public constructor() {
    super();
    this.tree = new PageTree();
  }

  connectedCallback(): void {
    super.connectedCallback();
    document.addEventListener('typo3:pagetree:refresh', this.refresh);
    document.addEventListener('typo3:pagetree:mountPoint', this.setMountPoint);
    document.addEventListener('typo3:pagetree:selectFirstNode', this.selectFirstNode);
  }

  disconnectedCallback(): void {
    document.removeEventListener('typo3:pagetree:refresh', this.refresh);
    document.removeEventListener('typo3:pagetree:mountPoint', this.setMountPoint);
    document.removeEventListener('typo3:pagetree:selectFirstNode', this.selectFirstNode);
    super.disconnectedCallback();
  }

  // disable shadow dom for now
  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected render(): TemplateResult {
    return html`
      <div id="typo3-pagetree" class="svg-tree">
        <div>
          <div id="typo3-pagetree-toolbar" class="svg-toolbar">
              <typo3-backend-navigation-component-pagetree-toolbar .tree="${this.tree}"></typo3-backend-navigation-component-pagetree-toolbar>
          </div>
          <div id="typo3-pagetree-treeContainer" class="navigation-tree-container">
            ${this.renderMountPoint()}
            <div id="typo3-pagetree-tree" class="svg-tree-wrapper">
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

  protected firstUpdated() {
    this.treeWrapper.dispatchEvent(new Event('svg-tree:visible'));
    const configurationUrl = top.TYPO3.settings.ajaxUrls.page_tree_configuration;
    (new AjaxRequest(configurationUrl)).get()
      .then(async (response: AjaxResponse): Promise<void> => {
        const configuration = await response.resolve('json');
        Object.assign(configuration, {
          dataUrl: top.TYPO3.settings.ajaxUrls.page_tree_data,
          filterUrl: top.TYPO3.settings.ajaxUrls.page_tree_filter,
          showIcons: true
        });
        const dragDrop = new PageTreeDragDrop(this.tree);
        // Initialize the toolbar once the tree was rendered
        this.treeWrapper.addEventListener('svg-tree:initialized', () => {
          // set up toolbar now with updated settings
          const toolbar = this.querySelector(toolbarComponentName) as Toolbar;
          toolbar.requestUpdate('tree').then(() => toolbar.initializeDragDrop(dragDrop));
          if (configuration.temporaryMountPoint) {
            this.mountPointPath = configuration.temporaryMountPoint;
          }
        });
        this.tree.initialize(this.treeWrapper, configuration, dragDrop);
      });
  }

  private refresh = (): void => {
    this.tree.refreshOrFilterTree();
  }

  private setMountPoint = (e: CustomEvent): void => {
    this.setTemporaryMountPoint(e.detail.pageId as number);
  }

  private selectFirstNode = (): void => {
    const node = this.tree.nodes[0];
    if (node) {
      this.tree.selectNode(node);
    }
  }

  private unsetTemporaryMountPoint() {
    this.mountPointPath = null;
    Persistent.unset('pageTree_temporaryMountPoint').then(() => {
      this.tree.refreshTree();
    });
  }

  private renderMountPoint(): TemplateResult {
    if (this.mountPointPath === null) {
      return html``;
    }
    return html`
      <div class="node-mount-point">
        <div class="node-mount-point__icon"><typo3-backend-icon identifier="actions-document-info" size="small"></typo3-backend-icon></div>
        <div class="node-mount-point__text">${this.mountPointPath}</div>
        <div class="node-mount-point__icon mountpoint-close" @click="${() => this.unsetTemporaryMountPoint()}" title="${lll('labels.temporaryDBmount')}">
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
        </div>
      </div>
    `;
  }

  private setTemporaryMountPoint(pid: number): void {
    (new AjaxRequest(top.TYPO3.settings.ajaxUrls.page_tree_set_temporary_mount_point))
      .post('pid=' + pid, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
      })
      .then((response) => response.resolve())
      .then((response) => {
        if (response && response.hasErrors) {
          this.tree.errorNotification(response.message, true);
          this.tree.update();
        } else {
          this.mountPointPath = response.mountPointPath;
          this.tree.refreshOrFilterTree();
        }
      })
      .catch((error) => {
        this.tree.errorNotification(error, true);
      });
  }
}

@customElement(toolbarComponentName)
class Toolbar extends LitElement {
  @property({type: PageTree}) tree: PageTree = null;

  private settings = {
    searchInput: '.search-input',
    filterTimeout: 450
  };

  public initializeDragDrop(dragDrop: PageTreeDragDrop): void
  {
    if (this.tree.settings?.doktypes?.length) {
      this.tree.settings.doktypes.forEach((item: any) => {
        if (item.icon) {
          const htmlElement = this.querySelector('[data-tree-icon="' + item.icon + '"]');
          d3select(htmlElement).call(this.dragToolbar(item, dragDrop));
        } else {
          console.warn('Missing icon definition for doktype: ' + item.nodeType);
        }
      });
    }
  }

  // disable shadow dom for now
  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected firstUpdated(): void
  {
    const inputEl = this.querySelector(this.settings.searchInput) as HTMLInputElement;
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

  protected render(): TemplateResult {
    /* eslint-disable @typescript-eslint/indent */
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
        <div class="svg-toolbar__submenu">
          ${this.tree.settings?.doktypes?.length
            ? this.tree.settings.doktypes.map((item: any) => {
              return html`
                <div class="svg-toolbar__drag-node" data-tree-icon="${item.icon}" data-node-type="${item.nodeType}"
                     title="${item.title}" tooltip="${item.tooltip}">
                  <typo3-backend-icon identifier="${item.icon}" size="small"></typo3-backend-icon>
                </div>
              `;
              })
            : ''
          }
        </div>
      </div>
    `;
  }

  private refreshTree(): void {
    this.tree.refreshOrFilterTree();
  }

  private search(inputEl: HTMLInputElement): void {
    this.tree.refreshOrFilterTree(inputEl.value.trim());
    this.tree.prepareDataForVisibleNodes();
    this.tree.update();
  }

  /**
   * Register Drag and drop for new elements of toolbar
   * Returns method from d3drag
   */
  private dragToolbar(item: any, dragDrop: PageTreeDragDrop) {
    return dragDrop.connectDragHandler(new ToolbarDragHandler(item, this.tree, dragDrop));
  }
}
