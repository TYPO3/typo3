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

import { html, LitElement, nothing, type TemplateResult } from 'lit';
import { customElement, property, query } from 'lit/decorators';
import { until } from 'lit/directives/until';
import { lll } from '@typo3/core/lit-helper';
import { PageTree } from '@typo3/backend/tree/page-tree';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import '@typo3/backend/tree/tree-toolbar';
import ElementBrowser from '@typo3/backend/element-browser';
import LinkBrowser from '@typo3/backend/link-browser';
import '@typo3/backend/element/icon-element';
import Persistent from '@typo3/backend/storage/persistent';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { TreeToolbar } from '@typo3/backend/tree/tree-toolbar';
import type { TreeNodeInterface } from './tree-node';

interface Configuration {
  [keys: string]: any;
}

/**
 * Extension of the Tree, allowing to show additional actions on the right hand of the tree to directly link
 * select a page
 */
@customElement('typo3-backend-component-page-browser-tree')
export class PageBrowserTree extends PageTree {

  protected override getNodeClasses(node: TreeNodeInterface): string[] {
    const classList = super.getNodeClasses(node);

    if (!this.settings.actions.includes('link')) {
      return classList;
    }

    if (!this.isLinkable(node)) {
      classList.push('node-disabled');
    }

    return classList;
  }

  protected override createNodeContentAction(node: TreeNodeInterface): TemplateResult {
    if (this.settings.actions.includes('link')) {
      return this.isLinkable(node)
        ? html`
          <span class="node-action" @click="${() => this.linkItem(node)}">
            <typo3-backend-icon identifier="actions-link" size="small"></typo3-backend-icon>
          </span>
        `
        : super.createNodeContentAction(node);
    } else if (this.settings.actions.includes('select')) {
      return html`
        <span class="node-action" @click="${() => this.selectItem(node)}">
          <typo3-backend-icon identifier="actions-link" size="small"></typo3-backend-icon>
        </span>
      `;
    }
    return super.createNodeContentAction(node);
  }

  /**
   * Page Link Handler specific
   */
  private linkItem(node: TreeNodeInterface): void {
    LinkBrowser.finalizeFunction('t3://page?uid=' + node.identifier);
  }

  /**
   * The following page doktypes can be browsed, but not directly added as "action":
   * - Spacer
   * - SysFolder
   * - Recycler
   */
  private isLinkable(node: TreeNodeInterface): boolean {
    const nonLinkableDoktypes = ['199', '254', '255'];
    return nonLinkableDoktypes.includes(String(node.recordType)) === false;
  }

  /**
   * Element Browser specific
   */
  private selectItem(node: TreeNodeInterface): void {
    ElementBrowser.insertElement(
      node.recordType,
      node.identifier,
      node.name,
      '',
      true
    );
  }
}

/**
 * The actual element used in the HTML composing the tree and the toolbar
 * <typo3-backend-component-page-browser type="pages"></typo3-backend-component-page-browser>
 */
@customElement('typo3-backend-component-page-browser')
export class PageBrowser extends LitElement {
  @property({ type: String }) mountPointPath: string = null;
  @query('.tree-wrapper') tree: PageBrowserTree;

  private activePageId: number = 0;
  // selectPage
  private actions: Array<string> = [];
  private configuration: Configuration = null;

  public override connectedCallback(): void {
    super.connectedCallback();
    document.addEventListener('typo3:pagetree:mountPoint', this.setMountPoint);
  }

  public override disconnectedCallback(): void {
    document.removeEventListener('typo3:pagetree:mountPoint', this.setMountPoint);
    super.disconnectedCallback();
  }

  protected override firstUpdated(): void {
    this.activePageId = parseInt(this.getAttribute('active-page'), 10);
    this.actions = JSON.parse(this.getAttribute('tree-actions') ?? '[]');
  }

  // disable shadow dom for now
  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected getConfiguration(): Promise<Configuration> {
    if (this.configuration !== null) {
      return Promise.resolve(this.configuration);
    }

    const configurationUrl = top.TYPO3.settings.ajaxUrls.page_tree_browser_configuration;
    const alternativeEntryPoints = this.hasAttribute('alternative-entry-points') ? JSON.parse(this.getAttribute('alternative-entry-points')) : [];
    let request = new AjaxRequest(configurationUrl);
    if (alternativeEntryPoints.length) {
      request = request.withQueryArguments('alternativeEntryPoints=' + encodeURIComponent(alternativeEntryPoints));
    }
    return request.get()
      .then(async (response: AjaxResponse): Promise<Configuration> => {
        const configuration = await response.resolve('json');
        configuration.actions = this.actions;
        this.configuration = configuration;
        this.mountPointPath = configuration.temporaryMountPoint || null;
        return configuration;
      });
  }

  protected override render(): TemplateResult {
    return html`
      <div class="tree">
      ${until(this.renderTree(), '')}
      </div>
    `;
  }

  protected renderTree(): Promise<TemplateResult> {
    return this.getConfiguration()
      .then((configuration: Configuration): TemplateResult => {
        const initialized = () => {
          this.tree.addEventListener('typo3:tree:node-selected', this.loadRecordsOfPage);
          this.tree.addEventListener('typo3:tree:nodes-prepared', this.selectActivePageInTree);
          // set up toolbar now with updated properties
          const toolbar = this.querySelector('typo3-backend-tree-toolbar') as TreeToolbar;
          toolbar.tree = this.tree;
        };

        return html`
          <typo3-backend-tree-toolbar .tree="${this.tree}"></typo3-backend-tree-toolbar>
          <div class="navigation-tree-container">
            ${this.renderMountPoint()}
            <typo3-backend-component-page-browser-tree id="typo3-pagetree-tree" class="tree-wrapper" .setup=${configuration} @tree:initialized=${initialized}></typo3-backend-component-page-browser-tree>
          </div>
        `;
      });
  }

  private readonly selectActivePageInTree = (evt: CustomEvent): void => {
    // Activate the current node
    const nodes = evt.detail.nodes as Array<TreeNodeInterface>;
    evt.detail.nodes = nodes.map((node: TreeNodeInterface) => {
      if (parseInt(node.identifier, 10) === this.activePageId) {
        node.checked = true;
      }
      return node;
    });
  };

  /**
   * If a page is clicked, the content area needs to be updated
   */
  private readonly loadRecordsOfPage = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNodeInterface;
    if (!node.checked) {
      return;
    }
    const contentsUrl = new URL(document.location.href, window.location.origin);
    contentsUrl.searchParams.set('contentOnly', '1');
    contentsUrl.searchParams.set('expandPage', node.identifier);
    (new AjaxRequest(contentsUrl)).get()
      .then((response: AjaxResponse) => response.resolve())
      .then((response) => {
        const contentContainer = document.querySelector('.element-browser-main-content .element-browser-body') as HTMLElement;
        contentContainer.innerHTML = response;
      });
  };


  private readonly setMountPoint = (e: CustomEvent): void => {
    this.setTemporaryMountPoint(e.detail.pageId as number);
  };

  private unsetTemporaryMountPoint() {
    Persistent.unset('pageTree_temporaryMountPoint').then(() => {
      this.mountPointPath = null;
    });
  }

  private renderMountPoint(): TemplateResult | symbol {
    if (this.mountPointPath === null) {
      return nothing;
    }
    return html`
      <div class="node-mount-point">
        <div class="node-mount-point__icon"><typo3-backend-icon identifier="actions-info-circle" size="small"></typo3-backend-icon></div>
        <div class="node-mount-point__text">${this.mountPointPath}</div>
        <div class="node-mount-point__icon mountpoint-close" @click="${() => this.unsetTemporaryMountPoint()}" title="${lll('labels.temporaryPageTreeEntryPoints')}">
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
        </div>
      </div>
    `;
  }

  private setTemporaryMountPoint(pid: number): void {
    (new AjaxRequest(this.configuration.setTemporaryMountPointUrl))
      .post('pid=' + pid, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      })
      .then((response) => response.resolve())
      .then((response) => {
        if (response && response.hasErrors) {
          this.tree.errorNotification(response.message);
          this.tree.loadData();
        } else {
          this.mountPointPath = response.mountPointPath;
        }
      })
      .catch((error) => {
        this.tree.errorNotification(error);
        this.tree.loadData();
      });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-component-page-browser-tree': PageBrowserTree;
    'typo3-backend-component-page-browser': PageBrowserTree;
  }
}

