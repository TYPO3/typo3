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
import { customElement, property, query, state } from 'lit/decorators.js';
import { PageTree } from '@typo3/backend/tree/page-tree';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import '@typo3/backend/tree/tree-toolbar';
import '@typo3/backend/element/icon-element';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { TreeToolbar } from '@typo3/backend/tree/tree-toolbar';
import '@typo3/backend/element/breadcrumb';
import { type TreeNodeInterface, TreeNodePositionEnum } from './tree-node';
import { cache } from 'lit/directives/cache.js';
import labels from '~labels/core.core';
import type { BreadcrumbNodeInterface } from '@typo3/backend/element/breadcrumb';

interface Configuration {
  [keys: string]: any;
}

export type Position = 'inside' | 'after';

export const insertPositionOptions: {label: string, value: Position, iconIdentifier: string}[] = [
  {
    label: labels.get('insert_inside'),
    value: 'inside',
    iconIdentifier: 'actions-arrow-end',
  },
  {
    label: labels.get('insert_after'),
    value: 'after',
    iconIdentifier: 'actions-arrow-down',
  },
];

export class InsertPositionChangeEvent extends CustomEvent<{pageUid: number, position: Position}> {
  static readonly eventName = 'typo3:page-position-select-tree:insert-position-change';
  constructor(pageUid: number, position: Position) {
    super(InsertPositionChangeEvent.eventName, {
      detail: {
        pageUid: pageUid,
        position: position,
      },
      bubbles: true,
      composed: true
    });
  }
}
@customElement('typo3-backend-component-page-position-select-tree')
export class PagePositionSelectTree extends PageTree {

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  protected override async handleNodeAdd(node: TreeNodeInterface, target: TreeNodeInterface, position: TreeNodePositionEnum): Promise<void> {
    this.requestUpdate();
  }
}

@customElement('typo3-backend-component-page-position-select')
export class PagePositionSelect extends LitElement {
  @property({ type: Number, reflect: true, attribute: 'active-page' }) activePageId?: number = null;
  @property({ type: Array }) actions: Array<string> = ['select'];
  @property({ type: String, reflect: true }) insertPosition: Position = 'inside';

  @query('typo3-backend-component-page-position-select-tree') tree: PagePositionSelectTree;

  @state() private configuration: Configuration = null;
  @state() private breadcrumbItems: BreadcrumbNodeInterface[] = [];

  public override connectedCallback(): void {
    super.connectedCallback();
    this.loadConfiguration();
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected loadConfiguration(): void {
    const configurationUrl = top.TYPO3.settings.ajaxUrls.page_tree_browser_configuration;
    const alternativeEntryPoints = this.hasAttribute('alternative-entry-points') ? JSON.parse(this.getAttribute('alternative-entry-points')) : [];
    let request = new AjaxRequest(configurationUrl);
    if (alternativeEntryPoints.length) {
      request = request.withQueryArguments('alternativeEntryPoints=' + encodeURIComponent(alternativeEntryPoints));
    }
    request.get()
      .then(async (response: AjaxResponse): Promise<void> => {
        const configuration = await response.resolve('json');
        configuration.actions = this.actions;
        this.configuration = configuration;
      });
  }

  protected override render(): TemplateResult {
    return html`
      <typo3-breadcrumb .nodes="${this.breadcrumbItems}"></typo3-breadcrumb>
      ${this.configuration ? cache(this.renderTree()) : nothing}
    `;
  }

  protected renderTree(): TemplateResult {
    const initialized = async () => {
      // set up toolbar now with updated properties
      const toolbar = this.querySelector('typo3-backend-tree-toolbar') as TreeToolbar;
      toolbar.tree = this.tree;
      await this.tree.ensureActiveNodeLoaded(this.activePageId);
      const activeNode = await this.applyActiveInsertPosition();
      if (activeNode) {
        await this.tree.updateComplete;
        this.tree.scrollNodeIntoViewIfNeeded(activeNode);
        this.tree.focusNode(activeNode);
      }
    };

    return html`
        <typo3-backend-tree-toolbar
          .tree="${this.tree}"
        >
        </typo3-backend-tree-toolbar>
        <typo3-backend-component-page-position-select-tree
          id="typo3-pagetree-tree"
          .setup=${this.configuration}
          @tree:initialized=${initialized}
          @typo3:tree:node-selected="${this.handleNodeSelected}"
          @typo3:tree:filter-applied=${() => this.applyActiveInsertPosition()}
          @typo3:tree:filter-reset=${() => this.applyActiveInsertPosition()}
        >
        </typo3-backend-component-page-position-select-tree>
      `;
  }

  private async handleNodeSelected(event: CustomEvent): Promise<void> {
    const selectedNode: TreeNodeInterface = event.detail.node;
    const [pageUid, insertPosition] = selectedNode.identifier.split('-');
    this.insertPosition = (insertPosition ?? 'inside') as Position;
    this.activePageId = Number(pageUid);

    const activeNode = /^\d+$/.test(selectedNode.identifier)
      ? await this.applyActiveInsertPosition()
      : this.syncActiveInsertPositionState();

    if (activeNode) {
      this.tree.focusNode(activeNode);
    }

    this.dispatchEvent(new InsertPositionChangeEvent(
      this.activePageId,
      this.insertPosition
    ));
  }

  private async applyActiveInsertPosition(): Promise<TreeNodeInterface|null> {
    if (this.activePageId === null || this.activePageId === undefined) {
      return null;
    }
    let currentNode = this.tree.nodes.find(n => n.identifier === String(this.activePageId));
    if (!currentNode) {
      // Load its rootline and expand the path
      await this.tree.ensureActiveNodeLoaded(this.activePageId);
      currentNode = this.tree.nodes.find(n => n.identifier === String(this.activePageId));
      if (!currentNode) {
        return null;
      }
    }

    await this.tree.expandNodeParents(currentNode);
    await this.toggleDynamicInsertNodes(currentNode);
    return this.syncActiveInsertPositionState();
  }

  private syncActiveInsertPositionState(): TreeNodeInterface|null {
    let activeNode: TreeNodeInterface|null = null;
    this.tree.nodes.forEach((node: TreeNodeInterface) => {
      node.checked = this.isActivePagePositionNode(node);
      if (node.checked) {
        activeNode = node;
      }
    });
    this.updateBreadcrumb(this.tree.nodes);
    this.tree.requestUpdate();
    return activeNode;
  }

  private async toggleDynamicInsertNodes(currentNode: TreeNodeInterface): Promise<void> {
    const nodesToDrop = [];

    for (const node of this.tree.nodes) {
      if (['-after', '-inside'].some(suffix => node.identifier.endsWith(suffix))) {
        nodesToDrop.push(node);
      }
    }

    for (const nodeToDrop of nodesToDrop) {
      await this.tree.removeNode(nodeToDrop);
    }

    const insideNode: TreeNodeInterface = {
      ...currentNode,
      __processed: false,
      hasChildren: false,
      loaded: true,
      identifier: currentNode.identifier + '-inside',
      parentIdentifier: currentNode.identifier,
      name: labels.get('insert_subpage'),
      icon: 'actions-arrow-end',
      overlayIcon: '',
      tooltip: labels.get('insert_inside') + ' ' + currentNode.tooltip,
    };

    const parentNode = this.tree.nodes.find(node => node.identifier === currentNode.parentIdentifier);

    const afterNode: TreeNodeInterface = {
      ...parentNode,
      __processed: false,
      hasChildren: false,
      loaded: true,
      identifier: `${currentNode.identifier}-after`,
      parentIdentifier: currentNode.parentIdentifier,
      name: labels.get('insert_page'),
      icon: 'actions-arrow-end',
      overlayIcon: '',
      tooltip: labels.get('insert_after') + ' ' + currentNode.tooltip,
    };

    let childNodes: TreeNodeInterface[] = [];
    if (currentNode.hasChildren) {
      childNodes = this.tree.nodes.filter(node => node.parentIdentifier === currentNode.identifier);
    }

    await this.tree.addNode(insideNode, currentNode, TreeNodePositionEnum.INSIDE);
    await this.tree.addNode(afterNode, currentNode, TreeNodePositionEnum.AFTER);

    for (const childNode of childNodes) {
      const afterChildNode: TreeNodeInterface = {
        ...childNode,
        __processed: false,
        hasChildren: false,
        loaded: true,
        identifier: `${childNode.identifier}-after`,
        parentIdentifier: childNode.parentIdentifier,
        name: labels.get('insert_page'),
        icon: 'actions-arrow-end',
        overlayIcon: '',
        tooltip: labels.get('insert_after') + ' ' + childNode.tooltip,
      };
      await this.tree.addNode(afterChildNode, childNode, TreeNodePositionEnum.AFTER);
    }
  }

  private isActivePagePositionNode(node: TreeNodeInterface): boolean {
    return node.identifier === `${this.activePageId}-${this.insertPosition}`;
  }

  private updateBreadcrumb(nodes: readonly TreeNodeInterface[]): void {
    let currentNode = nodes.find(n => n.identifier === String(this.activePageId));
    const breadcrumbItems: BreadcrumbNodeInterface[] = [];

    while (currentNode) {
      breadcrumbItems.push({
        identifier: currentNode.identifier,
        label: currentNode.name,
        icon: currentNode.icon,
        iconOverlay: currentNode.overlayIcon,
        url: null,
        forceShowIcon: false,
      });
      currentNode = nodes.find(n => n.identifier === currentNode!.parentIdentifier);
    }

    // If inserting "after", remove the first page from the breadcrumb
    // so that only its parent remains visible in the trail
    if (this.insertPosition === 'after') {
      breadcrumbItems.shift();
    }

    breadcrumbItems.unshift({
      identifier: `${this.activePageId}-${this.insertPosition}`,
      label: labels.get('labels.createNew'),
      icon: 'apps-pagetree-page-default',
      iconOverlay: 'overlay-new',
      url: null,
      forceShowIcon: false,
    });

    this.breadcrumbItems = breadcrumbItems.reverse();
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-component-page-position-select': PagePositionSelect;
    'typo3-backend-component-page-position-select-tree': PagePositionSelectTree;
  }
}

