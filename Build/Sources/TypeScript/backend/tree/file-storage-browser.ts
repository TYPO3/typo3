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
import { customElement, query } from 'lit/decorators';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { TreeNodeInterface } from '@typo3/backend/tree/tree-node';
import '@typo3/backend/tree/tree-toolbar';
import type { TreeToolbar } from '@typo3/backend/tree/tree-toolbar';
import ElementBrowser from '@typo3/backend/element-browser';
import LinkBrowser from '@typo3/backend/link-browser';
import '@typo3/backend/element/icon-element';
import { FileStorageTree } from './file-storage-tree';

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
      filterUrl: top.TYPO3.settings.ajaxUrls.filestorage_tree_filter,
      showIcons: true,
      actions: this.actions
    };

    const initialized = () => {
      this.tree.addEventListener('typo3:tree:node-selected', this.loadFolderDetails);
      this.tree.addEventListener('typo3:tree:nodes-prepared', this.selectActiveNode);
      // set up toolbar now with updated properties
      const toolbar = this.querySelector('typo3-backend-tree-toolbar') as TreeToolbar;
      toolbar.tree = this.tree;
    };

    return html`
      <div class="tree">
        <typo3-backend-tree-toolbar .tree="${this.tree}"></typo3-backend-tree-toolbar>
        <div class="navigation-tree-container">
          <typo3-backend-component-filestorage-browser-tree class="tree-wrapper" .setup=${treeSetup} @tree:initialized=${initialized}></typo3-backend-component-page-browser-tree>
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
