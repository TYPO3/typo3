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

import {html, LitElement, TemplateResult} from 'lit';
import {customElement, property, query} from 'lit/decorators';
import {until} from 'lit/directives/until';
import {lll} from '@typo3/core/lit-helper';
import {PageTree} from '../page-tree/page-tree';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import {TreeNode} from './tree-node';
import {TreeNodeSelection, Toolbar} from '../svg-tree';
import ElementBrowser from '@typo3/recordlist/element-browser';
import LinkBrowser from '@typo3/recordlist/link-browser';
import '@typo3/backend/element/icon-element';
import Persistent from '@typo3/backend/storage/persistent';


const componentName: string = 'typo3-backend-component-page-browser';

interface Configuration {
  [keys: string]: any;
}

/**
 * Extension of the SVG Tree, allowing to show additional actions on the right hand of the tree to directly link
 * select a page
 */
@customElement('typo3-backend-component-page-browser-tree')
class PageBrowserTree extends PageTree {

  /**
   * Check if the page is linkable, if not, let's grey it out.
   */
  protected appendTextElement(nodes: TreeNodeSelection): TreeNodeSelection {
    return super.appendTextElement(nodes).attr('opacity', (node: TreeNode) => {
      if (!this.settings.actions.includes('link')) {
        return 1;
      }
      if (this.isLinkable(node)) {
        return 1;
      }
      return 0.5;
    });
  }

  protected updateNodeActions(nodesActions: TreeNodeSelection): TreeNodeSelection {
    const nodes = super.updateNodeActions(nodesActions);
    if (this.settings.actions.includes('link')) {
      // Check if a node can be linked
      this.fetchIcon('actions-link');
      const linkAction = this.nodesActionsContainer.selectAll('.node-action')
        .append('g')
        .attr('visibility', (node: TreeNode) => {
          return this.isLinkable(node) ? 'visible' : 'hidden'
        })
        .on('click', (evt: MouseEvent, node: TreeNode) => {
          this.linkItem(node);
        });
      this.createIconAreaForAction(linkAction, 'actions-link');
    } else if (this.settings.actions.includes('select')) {
      // Check if a node can be selected
      this.fetchIcon('actions-link');
      const linkAction = nodes
        .append('g')
        .on('click', (evt: MouseEvent, node: TreeNode) => {
          this.selectItem(node);
        });
      this.createIconAreaForAction(linkAction, 'actions-link');
    }
    return nodes;
  }

  /**
   * Page Link Handler specific
   */
  private linkItem(node: TreeNode): void {
    LinkBrowser.finalizeFunction('t3://page?uid=' + node.identifier);
  }

  /**
   * The following page doktypes can be browsed, but not directly added as "action":
   * - Spacer
   * - SysFolder
   * - Recycler
   */
  private isLinkable(node: TreeNode): boolean {
    const nonLinkableDoktypes = ['199', '254', '255'];
    return nonLinkableDoktypes.includes(String(node.type)) === false;
  }

  /**
   * Element Browser specific
   */
  private selectItem(node: TreeNode): void {
    ElementBrowser.insertElement(
      node.itemType,
      node.identifier,
      node.name,
      node.identifier,
      true
    );
  }
}

/**
 * The actual element used in the HTML composing the tree and the toolbar
 */
@customElement(componentName)
export class PageBrowser extends LitElement {
  @property({type: String}) mountPointPath: string = null;
  @query('.svg-tree-wrapper') tree: PageBrowserTree;

  private activePageId: number = 0;
  // selectPage
  private actions: Array<string> = [];
  private configuration: Configuration = null;

  public connectedCallback(): void {
    super.connectedCallback();
    document.addEventListener('typo3:navigation:resized', this.triggerRender);
    document.addEventListener('typo3:pagetree:mountPoint', this.setMountPoint);
  }

  public disconnectedCallback(): void {
    document.removeEventListener('typo3:navigation:resized', this.triggerRender);
    document.removeEventListener('typo3:pagetree:mountPoint', this.setMountPoint);
    super.disconnectedCallback();
  }

  protected firstUpdated() {
    this.activePageId = parseInt(this.getAttribute('active-page'), 10);
    this.actions = JSON.parse(this.getAttribute('tree-actions'));
  }

  // disable shadow dom for now
  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected triggerRender = (): void => {
    this.tree.dispatchEvent(new Event('svg-tree:visible'));
  }

  protected getConfiguration(): Promise<Configuration> {
    if (this.configuration !== null) {
      return Promise.resolve(this.configuration);
    }

    const configurationUrl = top.TYPO3.settings.ajaxUrls.page_tree_browser_configuration;
    const alternativeEntryPoints = this.hasAttribute('alternative-entry-points') ? JSON.parse(this.getAttribute('alternative-entry-points')) : [];
    let request = new AjaxRequest(configurationUrl);
    if (alternativeEntryPoints.length) {
      request = request.withQueryArguments('alternativeEntryPoints=' + encodeURIComponent(alternativeEntryPoints))
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

  protected render(): TemplateResult {
    return html`
      <div class="svg-tree">
        ${until(this.renderTree(), this.renderLoader())}
      </div>
    `;
  }

  protected renderTree(): Promise<TemplateResult> {
    return this.getConfiguration()
      .then((configuration: Configuration): TemplateResult => {
        const initialized = () => {
          this.tree.dispatchEvent(new Event('svg-tree:visible'));
          this.tree.addEventListener('typo3:svg-tree:expand-toggle', this.toggleExpandState);
          this.tree.addEventListener('typo3:svg-tree:node-selected', this.loadRecordsOfPage);
          this.tree.addEventListener('typo3:svg-tree:nodes-prepared', this.selectActivePageInTree);
          // set up toolbar now with updated properties
          const toolbar = this.querySelector('typo3-backend-tree-toolbar') as Toolbar;
          toolbar.tree = this.tree;
        }

        return html`
          <div>
            <typo3-backend-tree-toolbar .tree="${this.tree}" class="svg-toolbar"></typo3-backend-tree-toolbar>
            <div class="navigation-tree-container">
              ${this.renderMountPoint()}
              <typo3-backend-component-page-browser-tree id="typo3-pagetree-tree" class="svg-tree-wrapper" .setup=${configuration} @svg-tree:initialized=${initialized}></typo3-backend-component-page-browser-tree>
            </div>
          </div>
          ${this.renderLoader()}
        `;
      });
  }

  protected renderLoader(): TemplateResult {
    return html`
      <div class="svg-tree-loader">
        <typo3-backend-icon identifier="spinner-circle-light" size="large"></typo3-backend-icon>
      </div>
    `;
  }

  private selectActivePageInTree = (evt: CustomEvent): void => {
    // Activate the current node
    let nodes = evt.detail.nodes as Array<TreeNode>;
    evt.detail.nodes = nodes.map((node: TreeNode) => {
      if (parseInt(node.identifier, 10) === this.activePageId) {
        node.checked = true;
      }
      return node;
    });
  }

  private toggleExpandState = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNode;
    if (node) {
      Persistent.set('BackendComponents.States.Pagetree.stateHash.' + node.stateIdentifier, (node.expanded ? '1' : '0'));
    }
  }
  /**
   * If a page is clicked, the content area needs to be updated
   */
  private loadRecordsOfPage = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNode;
    if (!node.checked) {
      return;
    }
    let contentsUrl = document.location.href + '&contentOnly=1&expandPage=' + node.identifier;
    (new AjaxRequest(contentsUrl)).get()
      .then((response: AjaxResponse) => response.resolve())
      .then((response) => {
        const contentContainer = document.querySelector('.element-browser-main-content .element-browser-body') as HTMLElement;
        contentContainer.innerHTML = response;
      });
  }


  private setMountPoint = (e: CustomEvent): void => {
    this.setTemporaryMountPoint(e.detail.pageId as number);
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
    (new AjaxRequest(this.configuration.setTemporaryMountPointUrl))
      .post('pid=' + pid, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
      })
      .then((response) => response.resolve())
      .then((response) => {
        if (response && response.hasErrors) {
          this.tree.errorNotification(response.message, true);
          this.tree.updateVisibleNodes();
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

