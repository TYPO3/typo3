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
import {customElement, query} from 'lit/decorators';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import {TreeNode} from './tree-node';
import {Toolbar, TreeNodeSelection} from '../svg-tree';
import ElementBrowser from '@typo3/recordlist/element-browser';
import LinkBrowser from '@typo3/recordlist/link-browser';
import '@typo3/backend/element/icon-element';
import Persistent from '@typo3/backend/storage/persistent';
import {FileStorageTree} from './file-storage-tree';

const componentName: string = 'typo3-backend-component-filestorage-browser';

/**
 * Extension of the SVG Tree, allowing to show additional actions on the right hand of the tree to directly link
 * select a folder
 */
@customElement('typo3-backend-component-filestorage-browser-tree')
class FileStorageBrowserTree extends FileStorageTree {

  protected updateNodeActions(nodesActions: TreeNodeSelection): TreeNodeSelection {
    const nodes = super.updateNodeActions(nodesActions);
    if (this.settings.actions.includes('link')) {
      // Check if a node can be linked
      const linkAction = nodes
        .append('g')
        .on('click', (evt: MouseEvent, node: TreeNode) => {
          this.linkItem(node);
        });
      this.createIconAreaForAction(linkAction, 'actions-link');
    } else if (this.settings.actions.includes('select')) {
      // Check if a node can be selected
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
   * Link to a folder - Link Handler specific
   */
  private linkItem(node: TreeNode): void {
    LinkBrowser.finalizeFunction('t3://folder?storage=' + node.storage + '&identifier=' + node.pathIdentifier);
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

@customElement(componentName)
export class FileStorageBrowser extends LitElement {
  @query('.svg-tree-wrapper') tree: FileStorageBrowserTree;

  private activeFolder: String = '';
  private actions: Array<string> = [];

  public connectedCallback(): void {
    super.connectedCallback();
    document.addEventListener('typo3:navigation:resized', this.triggerRender);
  }

  public disconnectedCallback(): void {
    document.removeEventListener('typo3:navigation:resized', this.triggerRender);
    super.disconnectedCallback();
  }

  protected firstUpdated() {
    this.activeFolder = this.getAttribute('active-folder') || '';
  }

  // disable shadow dom for now
  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected triggerRender = (): void => {
    this.tree.dispatchEvent(new Event('svg-tree:visible'));
  }

  protected render(): TemplateResult {
    if (this.hasAttribute('tree-actions') && this.getAttribute('tree-actions').length) {
      this.actions = JSON.parse(this.getAttribute('tree-actions'));
    }
    const treeSetup = {
      dataUrl: top.TYPO3.settings.ajaxUrls.filestorage_tree_data,
      filterUrl: top.TYPO3.settings.ajaxUrls.filestorage_tree_filter,
      showIcons: true,
      actions: this.actions
    };

    const initialized = () => {
      this.tree.dispatchEvent(new Event('svg-tree:visible'));
      this.tree.addEventListener('typo3:svg-tree:expand-toggle', this.toggleExpandState);
      this.tree.addEventListener('typo3:svg-tree:node-selected', this.loadFolderDetails);
      this.tree.addEventListener('typo3:svg-tree:nodes-prepared', this.selectActiveNode);
      // set up toolbar now with updated properties
      const toolbar = this.querySelector('typo3-backend-tree-toolbar') as Toolbar;
      toolbar.tree = this.tree;
    }

    return html`
      <div class="svg-tree">
        <div>
          <typo3-backend-tree-toolbar .tree="${this.tree}" class="svg-toolbar"></typo3-backend-tree-toolbar>
          <div class="navigation-tree-container">
            <typo3-backend-component-filestorage-browser-tree class="svg-tree-wrapper" .setup=${treeSetup} @svg-tree:initialized=${initialized}></typo3-backend-component-page-browser-tree>
          </div>
        </div>
        <div class="svg-tree-loader">
          <typo3-backend-icon identifier="spinner-circle-light" size="large"></typo3-backend-icon>
        </div>
      </div>
    `;
  }

  private selectActiveNode = (evt: CustomEvent): void => {
    // Activate the current node
    let nodes = evt.detail.nodes as Array<TreeNode>;
    evt.detail.nodes = nodes.map((node: TreeNode) => {
      if (decodeURIComponent(node.identifier) === this.activeFolder) {
        node.checked = true;
      }
      return node;
    });
  }

  private toggleExpandState = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNode;
    if (node) {
      Persistent.set('BackendComponents.States.FileStorageTree.stateHash.' + node.stateIdentifier, (node.expanded ? '1' : '0'));
    }
  }

  /**
   * If a page is clicked, the content area needs to be updated
   */
  private loadFolderDetails = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNode;
    if (!node.checked) {
      return;
    }
    let contentsUrl = document.location.href + '&contentOnly=1&expandFolder=' + node.identifier;
    (new AjaxRequest(contentsUrl)).get()
      .then((response: AjaxResponse) => response.resolve())
      .then((response) => {
        const contentContainer = document.querySelector('.element-browser-main-content .element-browser-body') as HTMLElement;
        contentContainer.innerHTML = response;
      });
  }
}
