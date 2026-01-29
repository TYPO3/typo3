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
import { customElement, query } from 'lit/decorators.js';
import '@typo3/backend/tree/tree-toolbar';
import type { TreeToolbar } from '@typo3/backend/tree/tree-toolbar';
import type { FormEditorTree, FormEditorTreeNode } from './form-editor-tree';
import './form-editor-tree';

export const navigationComponentName: string = 'typo3-backend-navigation-component-formeditortree';

/**
 * Form Editor Tree Container - Navigation Component
 * Similar structure to FileStorageTreeNavigationComponent
 * Contains toolbar and tree component
 */
@customElement('typo3-backend-navigation-component-formeditortree')
export class FormEditorTreeContainer extends LitElement {
  @query('typo3-backend-navigation-component-formeditor-tree') tree: FormEditorTree;
  @query('typo3-backend-tree-toolbar') toolbar: TreeToolbar;

  public async setNodes(nodes: FormEditorTreeNode[]): Promise<void> {
    await this.updateComplete;
    if (this.tree) {
      this.tree.setNodes(nodes);
    }
  }

  public setSelectedNode(identifierPath: string): void {
    if (this.tree) {
      this.tree.setSelectedNode(identifierPath);
    }
  }

  public search(term: string): void {
    if (this.tree) {
      this.tree.search(term);
    }
  }

  /**
   * Set validation error state for a node
   *
   * @param identifierPath - Full identifier path of the node
   * @param hasError - Whether the node has a direct validation error
   */
  public setNodeValidationError(identifierPath: string, hasError: boolean = true): void {
    if (this.tree) {
      this.tree.setNodeValidationError(identifierPath, hasError);
    }
  }

  /**
   * Set child-has-error state for a node
   *
   * @param identifierPath - Full identifier path of the node
   * @param childHasError - Whether a child node has a validation error
   */
  public setNodeChildHasError(identifierPath: string, childHasError: boolean = true): void {
    if (this.tree) {
      this.tree.setNodeChildHasError(identifierPath, childHasError);
    }
  }

  /**
   * Clear all validation error states
   */
  public clearAllValidationErrors(): void {
    if (this.tree) {
      this.tree.clearAllValidationErrors();
    }
  }

  // Disable shadow DOM for compatibility with backend styles
  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <typo3-backend-tree-toolbar
        .tree="${this.tree}"
        .showRefresh="${false}"
        id="typo3-formeditortree-toolbar"
      ></typo3-backend-tree-toolbar>
      <typo3-backend-navigation-component-formeditor-tree
        id="typo3-formeditortree-tree"
      ></typo3-backend-navigation-component-formeditor-tree>
    `;
  }

  protected override firstUpdated(): void {
    if (this.toolbar && this.tree) {
      this.toolbar.tree = this.tree;
    }

    // Dispatch ready event for components waiting for tree container
    this.dispatchEvent(new CustomEvent('typo3:tree-container:ready', {
      bubbles: true,
      composed: true
    }));
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-form-editor-tree-container': FormEditorTreeContainer;
  }
}

