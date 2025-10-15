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

import { html, LitElement, type TemplateResult } from 'lit';
import { customElement, query } from 'lit/decorators';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { type TreeToolbar } from '@typo3/backend/tree/tree-toolbar';
import ElementBrowser from '@typo3/backend/element-browser';
import LinkBrowser from '@typo3/backend/link-browser';
import '@typo3/backend/element/icon-element';
import { FileStorageTree } from './file-storage-tree';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { TreeNodeInterface } from '@typo3/backend/tree/tree-node';

/**
 * Extension of the Tree, allowing to show additional actions on the right hand of the tree to directly link
 * select a folder
 */
@customElement('typo3-backend-component-filestorage-browser-tree')
export class FileStorageBrowserTree extends FileStorageTree {

  protected override createNodeContentAction(node: TreeNodeInterface): TemplateResult {
    if (this.settings.actions.includes('link')) {
      return html`
        <span class="node-action" @click="${() => this.linkItem(node)}">
          <typo3-backend-icon identifier="actions-link" size="small"></typo3-backend-icon>
        </span>
      `;
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
   * Link to a folder - Link Handler specific
   */
  private linkItem(node: TreeNodeInterface): void {
    LinkBrowser.finalizeFunction('t3://folder?storage=' + node.storage + '&identifier=' + node.pathIdentifier);
  }

  /**
   * Element Browser specific
   */
  private selectItem(node: TreeNodeInterface): void {
    ElementBrowser.insertElement(
      node.recordType,
      node.identifier,
      node.name,
      node.identifier,
      true
    );
  }
}

@customElement('typo3-backend-component-filestorage-browser')
export class FileStorageBrowser extends LitElement {
  @query('.tree-wrapper') tree: FileStorageBrowserTree;

  private activeFolder: string = '';
  private actions: Array<string> = [];

  protected override firstUpdated(): void {
    this.activeFolder = this.getAttribute('active-folder') || '';
  }

  // disable shadow dom for now
  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    if (this.hasAttribute('tree-actions') && this.getAttribute('tree-actions').length) {
      this.actions = JSON.parse(this.getAttribute('tree-actions'));
    }
    const treeSetup = {
      dataUrl: top.TYPO3.settings.ajaxUrls.filestorage_tree_data,
      rootlineUrl: top.TYPO3.settings.ajaxUrls.filestorage_tree_rootline,
      filterUrl: top.TYPO3.settings.ajaxUrls.filestorage_tree_filter,
      showIcons: true,
      actions: this.actions
    };

    const initialized = () => {
      // set up toolbar now with updated properties
      const toolbar = this.querySelector('typo3-backend-tree-toolbar') as TreeToolbar;
      toolbar.tree = this.tree;
      // Expand to the active folder if one is set
      if (this.activeFolder) {
        this.expandToActiveFolder();
      }
    };

    return html`
      <div class="tree">
        <typo3-backend-tree-toolbar .tree="${this.tree}"></typo3-backend-tree-toolbar>
        <div class="navigation-tree-container">
          <typo3-backend-component-filestorage-browser-tree
            class="tree-wrapper"
            .setup=${treeSetup}
            @tree:initialized=${initialized}
            @typo3:tree:node-selected=${this.loadFolderDetails}
            @typo3:tree:nodes-prepared=${this.selectActiveNode}
          ></typo3-backend-component-page-browser-tree>
        </div>
      </div>
    `;
  }

  private readonly selectActiveNode = (evt: CustomEvent): void => {
    // Activate the current node
    const nodes = evt.detail.nodes as Array<TreeNodeInterface>;
    evt.detail.nodes = nodes.map((node: TreeNodeInterface) => {
      if (decodeURIComponent(node.identifier) === this.activeFolder) {
        node.checked = true;
      }
      return node;
    });
  };

  /**
   * Expand the tree to show the active folder
   */
  private async expandToActiveFolder(): Promise<void> {
    if (!this.activeFolder || !this.tree.settings.rootlineUrl) {
      return;
    }

    // Check if the active node is already in the tree
    const existingNode = this.tree.nodes.find((node) => decodeURIComponent(node.identifier) === this.activeFolder);
    if (existingNode) {
      // Node is already loaded, just expand its parents
      await this.tree.expandNodeParents(existingNode);
      return;
    }

    // Fetch the rootline to find the path to the active folder
    try {
      const url = new URL(this.tree.settings.rootlineUrl, window.location.origin);
      url.searchParams.set('identifier', this.activeFolder);
      const response = await new AjaxRequest(url.toString()).get({ cache: 'no-cache' });
      const { rootline }: { rootline: string[] } = await response.resolve();

      // Expand all parent nodes
      if (rootline && rootline.length > 0) {
        // Remove the last element (the active folder itself)
        rootline.pop();
        // Encode identifiers to match the URL-encoded format stored in tree nodes
        await this.tree.expandParents(rootline.map(id => encodeURIComponent(id)));
      }
    } catch (error) {
      // If rootline fetch fails, silently ignore (folder might not exist or no access)
      console.debug('Could not expand to active folder:', error);
    }
  }

  /**
   * If a page is clicked, the content area needs to be updated
   */
  private readonly loadFolderDetails = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNodeInterface;
    if (!node.checked) {
      return;
    }
    const contentsUrl = document.location.href + '&contentOnly=1&expandFolder=' + node.identifier;
    (new AjaxRequest(contentsUrl)).get()
      .then((response: AjaxResponse) => response.resolve())
      .then((response) => {
        const contentContainer = document.querySelector('.element-browser-main-content .element-browser-body') as HTMLElement;
        contentContainer.innerHTML = response;
      });
  };
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-component-filestorage-browser-tree': FileStorageBrowserTree;
    'typo3-backend-component-filestorage-browser': FileStorageBrowser;
  }
}
