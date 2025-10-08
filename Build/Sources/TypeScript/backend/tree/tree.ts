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

import { html, LitElement, type TemplateResult, nothing } from 'lit';
import { property, state, query } from 'lit/decorators';
import { repeat } from 'lit/directives/repeat';
import { styleMap } from 'lit/directives/style-map';
import { ifDefined } from 'lit/directives/if-defined';
import { TreeNodeCommandEnum, TreeNodePositionEnum, type TreeNodeInterface, type TreeNodeStatusInformation, type TreeNodeLabel } from './tree-node';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Notification from '../notification';
import { KeyTypesEnum as KeyTypes } from '../enum/key-types';
import '@typo3/backend/element/icon-element';
import ClientStorage from '@typo3/backend/storage/client';
import { DataTransferTypes } from '@typo3/backend/enum/data-transfer-types';
import Severity from '@typo3/backend/severity';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { DragTooltipMetadata } from '@typo3/backend/drag-tooltip';

interface TreeNodeStatus {
  expanded: boolean
}

export interface DataTransferStringItem {
  type: DataTransferTypes,
  data: string,
}

export interface TreeSettings {
  [keys: string]: any;
  defaultProperties: {[keys: string]: any};
}

export interface TreeWrapper extends HTMLElement {
  tree?: Tree
}

export class Tree extends LitElement {
  @property({ type: Object }) setup?: {[keys: string]: any} = null;
  @state() settings: TreeSettings = {
    showIcons: false,
    width: 300,
    dataUrl: '',
    filterUrl: '',
    defaultProperties: {},
    expandUpToLevel: null as any,
    actions: []
  };

  @query('.nodes-root') root: HTMLElement;
  @state() nodes: TreeNodeInterface[] = [];
  @state() currentScrollPosition: number = 0;
  @state() currentVisibleHeight: number = 0;
  @state() searchTerm: string|null = null;
  @state() loading: boolean = false;

  @state() hoveredNode: TreeNodeInterface|null = null;
  @state() nodeDragAllowed: boolean = false;

  public isOverRoot: boolean = false;

  public nodeDragPosition: TreeNodePositionEnum|null = null;
  public nodeDragMode: TreeNodeCommandEnum|null = null;
  public draggingNode: TreeNodeInterface|null = null;

  protected nodeHeight: number = 32;
  protected indentWidth: number = 20;
  protected displayNodes: TreeNodeInterface[] = [];
  protected focusedNode: TreeNodeInterface|null = null;
  protected lastFocusedNode: TreeNodeInterface|null = null;
  protected editingNode: TreeNodeInterface|null = null;

  protected openNodeTimeout: { targetNode: TreeNodeInterface|null, timeout: number|null } = { targetNode: null, timeout: null };

  protected unfilteredNodes: string = '';
  protected muteErrorNotifications: boolean = false;

  protected networkErrorTitle: string = top.TYPO3.lang.tree_networkError;
  protected networkErrorMessage: string = top.TYPO3.lang.tree_networkErrorDescription;

  protected allowNodeEdit: boolean = false;
  protected allowNodeDrag: boolean = false;
  protected allowNodeSorting: boolean = false;

  protected currentFilterRequest: AjaxRequest|null = null;

  private __loadFinished: () => void;
  private __loadPromise: Promise<void> = new Promise(res => this.__loadFinished = res);

  public get loadComplete(): Promise<void> {
    return this.__loadPromise;
  }

  public getNodeFromElement(element: HTMLElement): TreeNodeInterface|null
  {
    if (element === null || !('treeId' in element.dataset)) {
      return null;
    }

    return this.getNodeByTreeIdentifier(element.dataset.treeId);
  }

  public getElementFromNode(node: TreeNodeInterface): HTMLElement|null {
    return this.querySelector('[data-tree-id="' + this.getNodeTreeIdentifier(node) + '"]');
  }

  public hideChildren(node: TreeNodeInterface): void {
    node.__expanded = false;
    this.saveNodeStatus(node);
    this.dispatchEvent(new CustomEvent('typo3:tree:expand-toggle', { detail: { node: node } }));
  }

  public async showChildren(node: TreeNodeInterface): Promise<void> {
    node.__expanded = true;
    await this.loadChildren(node);
    this.saveNodeStatus(node);
    this.dispatchEvent(new CustomEvent('typo3:tree:expand-toggle', { detail: { node: node } }));
  }

  public getDataUrl(parentNode: TreeNodeInterface|null = null): string {
    if (parentNode === null) {
      return this.settings.dataUrl;
    }

    return this.settings.dataUrl + '&parent=' + parentNode.identifier + '&depth=' + parentNode.depth;
  }

  public getFilterUrl(): string {
    return this.settings.filterUrl + '&q=' + this.searchTerm;
  }

  public async loadData(): Promise<void> {
    this.loading = true;
    this.nodes = this.prepareNodes(await this.fetchData());
    this.__loadFinished();
    this.__loadPromise = new Promise(res => this.__loadFinished = res);
    this.loading = false;
  }

  public async fetchData(parentNode: TreeNodeInterface|null = null): Promise<TreeNodeInterface[]> {
    try {
      const response = await new AjaxRequest(this.getDataUrl(parentNode)).get({ cache: 'no-cache' });
      let nodes: TreeNodeInterface[] = await response.resolve();

      if (!Array.isArray(nodes)) {
        return [];
      }

      if (parentNode !== null) {
        nodes = nodes.filter((node: TreeNodeInterface) => {
          // Filter (already processed) parentNode from server response
          // (if given. note: filetree does not deliver this, pagetree does)
          return node.identifier !== parentNode.identifier;
        });
        // parentNode is needed for enhanceNodes to operate based on the
        // (already processed) data in parentNode
        nodes.unshift(parentNode);
      }

      nodes = this.enhanceNodes(nodes);
      if (parentNode !== null) {
        // drop parentNode from resultset (it is always the first node, as we've added it as reference for enhanceNodes above)
        nodes.shift();
      }

      const all = await Promise.all(
        nodes.map(async (node: TreeNodeInterface): Promise<TreeNodeInterface[]> => {
          const parentNodeTreeIdentifier = node.__parents.join('_');
          const parentNode = nodes.find(p => p.__treeIdentifier === parentNodeTreeIdentifier) || null;
          const isVisible = parentNode === null || parentNode.__expanded;
          if (!node.loaded && node.hasChildren && node.__expanded && isVisible) {
            const children = await this.fetchData(node);
            node.loaded = true;
            return [ node, ...children ];
          } else {
            return [ node ];
          }
        })
      );
      return all.flat();
    } catch (error: any) {
      this.errorNotification(error);
      return [];
    }
  }

  public async loadChildren(parentNode: TreeNodeInterface): Promise<void> {
    try {
      if (parentNode.loaded) {
        await Promise.all(
          this.nodes
            .filter(n => n.__parents.join('_') === parentNode.__treeIdentifier && !n.loaded && n.hasChildren && n.__expanded)
            .map(n => this.loadChildren(n))
        );
        return;
      }

      parentNode.__loading = true;

      const nodes = this.prepareNodes(await this.fetchData(parentNode));
      const positionAfterParentNode = this.nodes.indexOf(parentNode) + 1;
      let deleteCount = 0;
      for (let i = positionAfterParentNode; i < this.nodes.length; ++i) {
        if (this.nodes[i].depth <= parentNode.depth) {
          break;
        }
        deleteCount++;
      }
      this.nodes.splice(positionAfterParentNode, deleteCount, ...nodes);
      // @todo: do we need to "prepare" all nodes again?

      parentNode.__loading = false;
      parentNode.loaded = true;
    } catch (error: any) {
      this.errorNotification(error);
      parentNode.__loading = false;
      throw error;
    }
  }

  public getIdentifier(): string
  {
    return this.id ?? this.setup.id;
  }

  public getLocalStorageIdentifier(): string
  {
    return 'tree-state-' + this.getIdentifier();
  }

  public getNodeStatus(node: TreeNodeInterface): TreeNodeStatus {
    const treeState = JSON.parse(ClientStorage.get(this.getLocalStorageIdentifier())) ?? {};
    return treeState[node.__treeIdentifier] ?? {
      expanded: false
    };
  }

  public saveNodeStatus(node: TreeNodeInterface): void {
    const treeState = JSON.parse(ClientStorage.get(this.getLocalStorageIdentifier())) ?? {};
    treeState[node.__treeIdentifier] = {
      expanded: node.__expanded
    };
    ClientStorage.set(this.getLocalStorageIdentifier(), JSON.stringify(treeState));
  }

  public refreshOrFilterTree(): void {
    if (this.searchTerm !== '') {
      this.filter(this.searchTerm);
    } else {
      this.loadData();
    }
  }

  public selectFirstNode(): void {
    const firstNode = this.getFirstNode();
    this.selectNode(firstNode, true);
    this.focusNode(firstNode);
  }

  /**
   * Node selection logic (triggered by different events)
   * This represents a dummy method and is usually overridden
   * The second argument can be interpreted by the listened events to e.g. not avoid reloading the content frame and instead
   * used for just updating the state within the tree
   */
  public selectNode(node: TreeNodeInterface, propagate: boolean = true): void
  {
    if (!this.isNodeSelectable(node)) {
      return;
    }

    this.resetSelectedNodes();
    node.checked = true;
    this.dispatchEvent(new CustomEvent('typo3:tree:node-selected', { detail: { node: node, propagate: propagate } }));
  }

  public async focusNode(node: TreeNodeInterface): Promise<void>
  {
    this.lastFocusedNode = this.focusedNode;
    this.focusedNode = node;
    this.requestUpdate();
    const element = this.getElementFromNode(this.focusedNode);
    if (element) {
      element.focus();
    } else {
      this.updateComplete.then(() => {
        this.getElementFromNode(this.focusedNode)?.focus();
      });
    }
  }

  public async editNode(node: TreeNodeInterface): Promise<void> {
    if (this.isNodeEditable(node)) {
      this.editingNode = node;
      this.requestUpdate();
      this.updateComplete.then(() => {
        const inputField = this.getElementFromNode(this.editingNode)?.querySelector('.node-edit') as HTMLInputElement;
        if (inputField) {
          inputField.focus();
          inputField.select();
        }
      });
    }
  }

  public async deleteNode(node: TreeNodeInterface): Promise<void> {
    if (!node.deletable) {
      console.error('The Node cannot be deleted.');
      return;
    }

    this.handleNodeDelete(node);
  }

  public async moveNode(node: TreeNodeInterface, target: TreeNodeInterface, position: TreeNodePositionEnum) {
    this.handleNodeMove(node, target, position);
  }

  public async addNode(node: TreeNodeInterface, target: TreeNodeInterface, position: TreeNodePositionEnum) {
    let index = this.nodes.indexOf(target);
    const parentNode = position === TreeNodePositionEnum.INSIDE ? target : this.getParentNode(target);
    const newNode = this.enhanceNodes([parentNode, {
      ...node,
      depth: parentNode ? parentNode.depth + 1 : 0,
    }]).pop();

    if (parentNode) {
      if (parentNode.hasChildren && !parentNode.__expanded) {
        await this.showChildren(parentNode);
      }

      if (!parentNode.hasChildren) {
        parentNode.hasChildren = true;
        parentNode.__expanded = true;
      }
    }

    if (position === TreeNodePositionEnum.INSIDE || position === TreeNodePositionEnum.AFTER) {
      index++;
    }

    this.nodes.splice(index, 0, newNode);
    this.handleNodeAdd(newNode, target, position);
  }

  public async removeNode(node: TreeNodeInterface) {
    const index = this.nodes.indexOf(node);
    const parentNode = this.getParentNode(node);
    if (index > -1) {
      this.nodes.splice(index, 1);
    }
    this.requestUpdate();
    this.updateComplete.then(() => {
      if (parentNode.__expanded && parentNode.hasChildren && this.getNodeChildren(parentNode).length === 0) {
        parentNode.hasChildren = false;
        parentNode.__expanded = false;
      }
    });
  }

  public filter(searchTerm?: string|null): void {
    if (typeof searchTerm === 'string') {
      this.searchTerm = searchTerm;
    }
    if (this.searchTerm && this.settings.filterUrl) {
      this.loading = true;
      this.currentFilterRequest?.abort();
      this.currentFilterRequest = new AjaxRequest(this.getFilterUrl());
      this.currentFilterRequest
        .get({ cache: 'no-cache' })
        .then((response: AjaxResponse) => response.resolve())
        .then((json) => {
          const nodes = Array.isArray(json) ? json : [];
          if (nodes.length > 0) {
            if (this.unfilteredNodes === '') {
              this.unfilteredNodes = JSON.stringify(this.nodes);
            }
            this.nodes = this.enhanceNodes(nodes);
          }
        })
        .catch((error: any) => {
          if (error instanceof DOMException && error.name === 'AbortError') {
            // Request has been aborted, do not flood the error console
            return;
          }

          this.errorNotification(error);
          throw error;
        }).then(() => {
          this.loading = false;
          this.currentFilterRequest = null;
        });
    } else {
      // restore original state without filters
      this.resetFilter();
      this.loading = false;
    }
  }

  public resetFilter(): void
  {
    this.searchTerm = '';
    if (this.unfilteredNodes.length > 0) {
      const currentlySelected = this.getSelectedNodes()[0];
      if (typeof currentlySelected === 'undefined') {
        this.loadData();
        return;
      }
      this.nodes = this.enhanceNodes(JSON.parse(this.unfilteredNodes));
      this.unfilteredNodes = '';
      // re-select the node from the identifier because the nodes have been updated
      const currentlySelectedNode = this.getNodeByTreeIdentifier(currentlySelected.__treeIdentifier);
      if (currentlySelectedNode) {
        this.selectNode(currentlySelectedNode, false);
      } else {
        this.loadData();
      }
    } else {
      this.loadData();
    }
  }

  /**
   * Displays a notification message and refresh nodes
   */
  public errorNotification(error: any = null): void {
    if (this.muteErrorNotifications) {
      return;
    }
    if (Array.isArray(error)) {
      error.forEach((message: any) => { Notification.error(
        message.title,
        message.message
      );});
    } else {
      let title = this.networkErrorTitle;
      if (error && error.target && (error.target.status || error.target.statusText)) {
        title += ' - ' + (error.target.status || '') + ' ' + (error.target.statusText || '');
      }
      Notification.error(title, this.networkErrorMessage);
    }
  }

  public getSelectedNodes(): TreeNodeInterface[] {
    return this.nodes.filter((node: TreeNodeInterface) => node.checked);
  }

  public getNodeByTreeIdentifier(treeIdentifier: string): TreeNodeInterface|null {
    return this.nodes.find((node: TreeNodeInterface) => {
      return node.__treeIdentifier === treeIdentifier;
    });
  }

  public getNodeDragStatusIcon(): string
  {
    if (this.nodeDragMode === TreeNodeCommandEnum.DELETE) {
      return 'actions-delete';
    }
    if (this.nodeDragMode === TreeNodeCommandEnum.NEW) {
      return 'actions-add';
    }

    if (this.nodeDragPosition === TreeNodePositionEnum.BEFORE) {
      return 'apps-pagetree-drag-move-above';
    }

    if (this.nodeDragPosition === TreeNodePositionEnum.INSIDE) {
      return 'apps-pagetree-drag-move-into';
    }

    if (this.nodeDragPosition === TreeNodePositionEnum.AFTER) {
      return 'apps-pagetree-drag-move-below';
    }

    return 'actions-ban';
  }

  public async expandParents(parents: string[]) {
    for (const id of parents) {
      const node = this.nodes.find((node) => node.identifier === id.toString());
      if (!node) {
        // :\ user has no access
        return;
      }
      if (!node.__expanded) {
        await this.showChildren(node);
      }
    }
  }

  public async expandNodeParents(node: TreeNodeInterface) {
    await this.expandParents(node.__parents);
  }

  protected prepareNodes(nodes: TreeNodeInterface[]): TreeNodeInterface[] {
    const evt = new CustomEvent('typo3:tree:nodes-prepared', { detail: { nodes }, bubbles: false });
    this.dispatchEvent(evt);
    return evt.detail.nodes;
  }

  /**
   * Set parameters like node parents, checked.
   * Usually called when data is loaded initially or replaced completely.
   */
  protected enhanceNodes(nodes: TreeNodeInterface[]): TreeNodeInterface[] {
    const enhancedNodes = nodes.reduce((nodes: TreeNodeInterface[], node: TreeNodeInterface) => {
      if (node.__processed === true) {
        return [...nodes, node];
      }

      node = Object.assign({}, this.settings.defaultProperties, node);
      node.__parents = [];
      const parentNode = node.depth > 0 ? nodes.findLast(p => p.depth < node.depth) : null;
      if (parentNode) {
        node.__parents = [ ...parentNode.__parents, parentNode.identifier ];
      }

      // Internal Identifier
      node.__treeIdentifier = node.identifier;
      node.__loading = false;
      node.__treeParents = [];
      if (parentNode) {
        node.__treeIdentifier = parentNode.__treeIdentifier + '_' + node.__treeIdentifier;
        node.__treeParents = [ ...parentNode.__treeParents, parentNode.__treeIdentifier ];
      }

      // State
      if (this.searchTerm) {
        node.__expanded = node.loaded && node.hasChildren;
      } else if (node.hasChildren) {
        node.__expanded = (this.settings.expandUpToLevel !== null)
          ? node.depth < this.settings.expandUpToLevel
          : Boolean(this.getNodeStatus(node).expanded);
      } else {
        node.__expanded = false;
      }

      node.__processed = true;

      // eslint-disable-next-line @typescript-eslint/no-this-alias
      const that = this;
      return [
        ...nodes,
        new Proxy(node, {
          set<K extends keyof TreeNodeInterface>(target: TreeNodeInterface, key: K, value: TreeNodeInterface[K]) {
            if(target[key] !== value) {
              target[key] = value as never;
              that.requestUpdate();
            }
            return true;
          },
        })
      ];
    }, []);

    // get nodes with depth 0, if there is only 1 then open it and disable toggle
    const nodesOnRootLevel = enhancedNodes.filter((node) => node.depth === 0);
    if (nodesOnRootLevel.length === 1) {
      enhancedNodes[0].__expanded = true;
    }

    return enhancedNodes;
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    const loader = this.loading
      ? html`
        <div class="nodes-loader">
            <div class="nodes-loader-inner">
            <typo3-backend-icon identifier="spinner-circle" size="medium"></typo3-backend-icon>
          </div>
        </div>`
      : nothing;

    return html`
      <div class="nodes-container">
        ${loader}
        <div
          class="nodes-root"
          @scroll="${(event: Event) => { this.currentScrollPosition = (event.currentTarget as HTMLElement).scrollTop; }}"
          @mouseover=${() => this.isOverRoot = true}
          @mouseout=${() => this.isOverRoot = false}
          @keydown=${(event: KeyboardEvent) => this.handleKeyboardInteraction(event)}
        >
          ${this.renderVisibleNodes()}
        </div>
      </div>
      `;
  }

  /**
   * Renders the subset of the tree nodes fitting the
   * viewport (adding, modifying and removing nodes)
   */
  protected renderVisibleNodes(): TemplateResult {
    const blacklist: string[] = [];
    this.nodes.forEach((node: TreeNodeInterface): void => {
      if (node.__expanded === false) {
        blacklist.push(this.getNodeTreeIdentifier(node));
      }
    });

    this.displayNodes = this.nodes.filter((node: TreeNodeInterface): boolean => {
      return node.__hidden !== true && !node.__treeParents.some((parentTreeIdentifier: string) => Boolean(blacklist.indexOf(parentTreeIdentifier) !== -1));
    });
    this.displayNodes.forEach((node: TreeNodeInterface, i: number) => {
      node.__x = node.depth * this.indentWidth;
      node.__y = i * this.nodeHeight;
    });

    const visibleRows = Math.ceil(this.currentVisibleHeight / this.nodeHeight);
    const position = Math.floor(this.currentScrollPosition / this.nodeHeight);
    const visibleNodes = this.displayNodes.filter((node: TreeNodeInterface, index: number) => {
      // first node is fallback target for tabindex, needs to be available every time
      if (this.getFirstNode() === node) {
        return true;
      }
      // focused node always needs to be available
      if (this.focusedNode === node) {
        return true;
      }
      // last focused node needs to be available for re-focus after scrolling (can have tabindex="0")
      if (this.lastFocusedNode === node) {
        return true;
      }
      return index + 2 >= position && index - 2 < position + visibleRows;
    });

    return html`
      <div class="nodes-list" role="tree" style="${styleMap({ 'height': (this.displayNodes.length * this.nodeHeight) + 'px' })}">
        ${repeat(visibleNodes, (node: TreeNodeInterface) => this.getNodeTreeIdentifier(node), (node: TreeNodeInterface) => html`
          <div
            class="${this.getNodeClasses(node).join(' ')}"
            role="treeitem"
            draggable="true"
            title="${this.getNodeTitle(node)}"
            aria-owns="${ifDefined(node.hasChildren ? 'group-identifier-' + this.getNodeIdentifier(node) : null)}"
            aria-expanded="${ifDefined(node.hasChildren ? (node.__expanded ? 'true' : 'false') : null)}"
            aria-level="${(this.getNodeDepth(node) + 1)}"
            aria-setsize="${this.getNodeSetsize(node)}"
            aria-posinset="${this.getNodePositionInSet(node)}"
            data-id="${this.getNodeIdentifier(node)}"
            data-tree-id="${this.getNodeTreeIdentifier(node)}"
            style="top: ${node.__y + 'px'}; height: ${this.nodeHeight + 'px'};"
            tabindex="${this.getNodeTabindex(node)}"

            @dragover="${(event: DragEvent) => { this.handleNodeDragOver(event); }}"
            @dragstart="${(event: DragEvent) => { this.handleNodeDragStart(event, node); }}"
            @dragleave="${(event: DragEvent) => { this.handleNodeDragLeave(event); }}"
            @dragend="${(event: DragEvent) => { this.handleNodeDragEnd(event); }}"
            @drop="${(event: DragEvent) => { this.handleNodeDrop(event); }}"

            @click="${(event: PointerEvent) => { this.handleNodeClick(event, node); }}"
            @dblclick="${(event: PointerEvent) => { this.handleNodeDoubleClick(event, node); }}"
            @focusin="${() => { this.focusedNode = node; }}"
            @focusout="${() => { if (this.focusedNode === node) { this.lastFocusedNode = node; this.focusedNode = null; } }}"
            @contextmenu="${(event: MouseEvent) => { event.preventDefault(); event.stopPropagation(); this.dispatchEvent(new CustomEvent('typo3:tree:node-context', { detail: { node, originalEvent: event } })); }}"
          >
            ${this.createNodeLabel(node)}
            ${this.createNodeGuides(node)}
            ${this.createNodeLoader(node) || this.createNodeToggle(node) || nothing}
            ${this.createNodeContent(node)}
            ${this.createNodeStatusInformation(node)}
            ${this.createNodeDeleteDropZone(node)}
          </div>
        `)}
      </div>
      `;
  }

  protected override async firstUpdated(): Promise<void> {
    const resizeObserver = new ResizeObserver((entries: ResizeObserverEntry[]) => {
      for (const entry of entries) {
        if (entry.target === this.root) {
          this.currentVisibleHeight = entry.target.getBoundingClientRect().height;
        }
      }
    });
    resizeObserver.observe(this.root);

    Object.assign(this.settings, this.setup || {});
    this.registerUnloadHandler();
    await this.loadData();
    this.dispatchEvent(new Event('tree:initialized'));
  }

  protected resetSelectedNodes(): void {
    this.getSelectedNodes().forEach((node: TreeNodeInterface) => {
      if (node.checked === true) {
        node.checked = false;
      }
    });
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  protected isNodeSelectable(node: TreeNodeInterface): boolean {
    return true;
  }

  protected isNodeEditable(node: TreeNodeInterface): boolean {
    return node.editable && this.allowNodeEdit;
  }

  protected handleNodeClick(event: PointerEvent, node: TreeNodeInterface): void {
    // only select node on single click
    if (event.detail === 1) {
      event.preventDefault();
      event.stopPropagation();
      if (this.editingNode !== node) {
        this.selectNode(node, true);
      }
    }
  }

  protected handleNodeDoubleClick(event: PointerEvent, node: TreeNodeInterface): void {
    event.preventDefault();
    event.stopPropagation();
    if (this.editingNode !== node) {
      this.editNode(node);
    }
  }

  //
  // Dragging
  //
  protected cleanDrag(): void {
    const allElements = this.querySelectorAll('.node');
    allElements.forEach(function(element) {
      element.classList.remove('node-dragging-before');
      element.classList.remove('node-dragging-after');
      element.classList.remove('node-hover');
    });
  }

  protected getNodeFromDragEvent(event: DragEvent): TreeNodeInterface|null {
    const target = event.target as HTMLElement;
    return this.getNodeFromElement(target.closest('[data-tree-id]'));
  }

  protected getTooltipDescription(node: TreeNodeInterface): string {
    return 'ID: ' + node.identifier;
  }

  protected handleNodeDragStart(event: DragEvent, node: TreeNodeInterface): void {
    if (this.allowNodeDrag === false || node.depth === 0) {
      event.preventDefault();
      return;
    }

    //document.querySelectorAll('iframe').forEach(frame => frame.style.pointerEvents = 'none');
    this.draggingNode = node;
    this.requestUpdate();

    event.dataTransfer.clearData();

    const metadata: DragTooltipMetadata = {
      statusIconIdentifier: this.getNodeDragStatusIcon(),
      tooltipIconIdentifier: node.icon,
      tooltipLabel: node.name,
      tooltipDescription: this.getTooltipDescription(node),
    };
    event.dataTransfer.setData(DataTransferTypes.dragTooltip, JSON.stringify(metadata));

    this.createDataTransferItemsFromNode(node).forEach(
      ({ data, type }) => event.dataTransfer.items.add(data, type)
    );
    event.dataTransfer.effectAllowed = 'move';
  }

  /**
   * Returns true when it was responsible for the drag
   */
  protected handleNodeDragOver(event: DragEvent): boolean {
    if (
      !event.dataTransfer.types.includes(DataTransferTypes.treenode) &&
      !event.dataTransfer.types.includes(DataTransferTypes.newTreenode)) {
      return false;
    }
    // Find the current hovered node
    // Exit when no node was hovered
    const target = event.target as HTMLElement;
    const targetNode = this.getNodeFromDragEvent(event);
    if (targetNode === null) {
      return false;
    }

    if (this.draggingNode === null) {
      return false;
    }
    this.cleanDrag();
    this.refreshDragToolTip();

    this.nodeDragMode = null;
    this.nodeDragPosition = null;

    // Add hover styling to the current hovered node
    // element, during the drag the default mouse over
    // is disabled by the browser
    const hoverElement = this.getElementFromNode(targetNode);
    hoverElement.classList.add('node-hover');

    // Open node with children while holding the
    // node/element over this node for 1 second
    if (targetNode.hasChildren && !targetNode.__expanded) {
      if (this.openNodeTimeout.targetNode != targetNode) {
        this.openNodeTimeout.targetNode = targetNode;
        clearTimeout(this.openNodeTimeout.timeout);
        this.openNodeTimeout.timeout = setTimeout(() => {
          this.showChildren(this.openNodeTimeout.targetNode);
          this.openNodeTimeout.targetNode = null;
          this.openNodeTimeout.timeout = null;
        }, 1000);
      }
    } else {
      clearTimeout(this.openNodeTimeout.timeout);
      this.openNodeTimeout.targetNode = null;
      this.openNodeTimeout.timeout = null;
    }

    // Check if the target is the current node.
    // If the current node is the target we only allow
    // drop when the user is over the delete drop zone.
    if (this.draggingNode == targetNode) {
      const targetDelete = target.dataset.treeDropzone === 'delete';
      if (targetDelete) {
        this.nodeDragMode = TreeNodeCommandEnum.DELETE;
        event.preventDefault();
        this.refreshDragToolTip();
        return true;
      }
      this.refreshDragToolTip();
      return true;
    }

    // Check if the current node is a parent of the target node.
    // A parent cannot be moved into a child node.
    if (targetNode.__parents.includes(this.draggingNode.identifier)) {
      this.refreshDragToolTip();
      return true;
    }

    // The default behaviour is mode, we currently
    // do not handle copy events seperatly.
    // Set the dragmode to new when a new node
    // exists in the datatransfer.
    this.nodeDragMode = TreeNodeCommandEnum.MOVE;
    if (event.dataTransfer.types.includes(DataTransferTypes.newTreenode)) {
      this.nodeDragMode = TreeNodeCommandEnum.NEW;
    }

    // The default drag position is inside,
    // this is allowed by all node types
    this.nodeDragPosition = TreeNodePositionEnum.INSIDE;

    // Elements on the root, cannot be sorted.
    // If sorting is not allowed, only dragging the node
    // inside the target node is allowed.
    if (targetNode.depth === 0 || this.allowNodeSorting === false) {
      this.refreshDragToolTip();
      event.preventDefault();
      return true;
    }

    // If sorting is allowed we need to check the position
    // of the of the cursor to define if the element needs
    // to be added before or after the current node.
    //
    // On nodes with childs we only allow positioning after
    // the targetnode when it has no children and the node
    // is not expanded.
    const targetElement = this.getElementFromNode(targetNode);
    const targetBounding = targetElement.getBoundingClientRect();
    const targetHoverOffset = event.clientY - targetBounding.y;
    if (targetHoverOffset < 6) {
      this.nodeDragPosition = TreeNodePositionEnum.BEFORE;
      hoverElement.classList.add('node-dragging-before');
    } else if ((this.nodeHeight - targetHoverOffset) < 6 && targetNode.hasChildren === false && targetNode.__expanded === false) {
      this.nodeDragPosition = TreeNodePositionEnum.AFTER;
      hoverElement.classList.add('node-dragging-after');
    }

    this.refreshDragToolTip();
    event.preventDefault();
    return true;
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  protected handleNodeDragLeave(event: DragEvent): void {
    if (this.draggingNode !== null) {
      this.cleanDrag();
    }
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  protected handleNodeDragEnd(event: DragEvent): void {
    this.cleanDrag();
    this.draggingNode = null;
    this.requestUpdate();
  }

  protected handleNodeDrop(event: DragEvent): boolean {
    this.cleanDrag();

    if (event.dataTransfer.types.includes(DataTransferTypes.treenode)) {
      event.preventDefault();
      const identifier = event.dataTransfer.getData(DataTransferTypes.treenode);
      const node = this.getNodeByTreeIdentifier(identifier);
      // delete
      if (this.nodeDragMode === TreeNodeCommandEnum.DELETE) {
        this.deleteNode(node);
      }
      const targetNode = this.getNodeFromDragEvent(event);
      if (targetNode === null) {
        return false;
      }

      // move
      if (this.nodeDragMode === TreeNodeCommandEnum.MOVE) {
        this.moveNode(node, targetNode, this.nodeDragPosition);
      }

      this.nodeDragMode = null;
      this.nodeDragPosition = null;
      return true;
    }

    if (event.dataTransfer.types.includes(DataTransferTypes.newTreenode)) {
      event.preventDefault();
      const targetNode = this.getNodeFromDragEvent(event);
      if (targetNode === null) {
        return false;
      }
      const newNodeData = event.dataTransfer.getData(DataTransferTypes.newTreenode);
      //if (this.nodeDragMode === TreeNodeCommandEnum.NEW) {
      this.addNode(JSON.parse(newNodeData), targetNode, this.nodeDragPosition);

      this.nodeDragMode = null;
      this.nodeDragPosition = null;
      return true;
    }
    return false;
  }

  protected refreshDragToolTip() {
    top.document.dispatchEvent(
      new CustomEvent<DragTooltipMetadata>('typo3:drag-tooltip:metadata-update', {
        detail: {
          statusIconIdentifier: this.getNodeDragStatusIcon(),
        }
      })
    );
  }

  //
  // Node Rendering
  //
  protected createNodeLabel(node: TreeNodeInterface): TemplateResult
  {
    const labels = this.getNodeLabels(node);
    if (labels.length === 0) {
      return html`${nothing}`;
    }

    const label = labels[0];
    const styles = { backgroundColor: label.color };

    return html`
      <span class="node-label" style=${styleMap(styles)}></span>
    `;
  }

  protected createNodeGuides(node: TreeNodeInterface): TemplateResult
  {
    const guides = node.__treeParents.map((treeIdentifier) => {
      const parentNode = this.getNodeByTreeIdentifier(treeIdentifier);
      let className: string = 'none';
      if (this.getNodeSetsize(parentNode) !== this.getNodePositionInSet(parentNode)) {
        className = 'line';
      }
      return html`
        <div
          class="node-treeline node-treeline--${className}"
          data-origin="${this.getNodeTreeIdentifier(parentNode)}"
          data-nodesize="${this.getNodeSetsize(parentNode)}"
          data-position="${this.getNodePositionInSet(parentNode)}"
          >
        </div>
      `;
    });

    if (this.getNodeSetsize(node) === this.getNodePositionInSet(node)) {
      guides.push(html`<div class="node-treeline node-treeline--last" data-origin="${this.getNodeTreeIdentifier(node)}"></div>`);
    } else {
      guides.push(html`<div class="node-treeline node-treeline--connect" data-origin="${this.getNodeTreeIdentifier(node)}"></div>`);
    }

    return html`<div class="node-treelines">${guides}</div>`;
  }

  protected createNodeLoader(node: TreeNodeInterface): TemplateResult|null
  {
    return node.__loading === true
      ? html `
          <span class="node-loading">
            <typo3-backend-icon
              identifier="spinner-circle"
              size="small"
            ></typo3-backend-icon>
          </span>
        `
      : null
    ;
  }

  protected createNodeToggle(node: TreeNodeInterface): TemplateResult|null
  {
    const collapsedIconIdentifier = this.isRTL() ? 'actions-chevron-left' : 'actions-chevron-right';
    return node.hasChildren === true
      ? html `
          <span class="node-toggle" @click="${(event: PointerEvent) => { event.preventDefault(); event.stopImmediatePropagation(); this.handleNodeToggle(node); }}">
            <typo3-backend-icon
              identifier="${(node.__expanded ? 'actions-chevron-down' : collapsedIconIdentifier)}"
              size="small"
            ></typo3-backend-icon>
          </span>
        `
      : null
    ;
  }

  protected createNodeContent(node: TreeNodeInterface): TemplateResult
  {
    return html`
      <div class="node-content">
        ${this.createNodeContentIcon(node)}
        ${this.editingNode === node ? this.createNodeForm(node) : this.createNodeContentLabel(node)}
        ${this.createNodeContentAction(node)}
      </div>
    `;
  }

  protected createNodeContentIcon(node: TreeNodeInterface): TemplateResult
  {
    return this.settings.showIcons
      ? html`
        <span class="node-icon"
          @click="${(event: PointerEvent) => { event.preventDefault(); event.stopImmediatePropagation(); this.dispatchEvent(new CustomEvent('typo3:tree:node-context', { detail: { node: node, originalEvent: event } })); }}"
          @dblclick="${(event: PointerEvent) => { event.preventDefault(); event.stopImmediatePropagation(); }}"
        >
          <typo3-backend-icon
            identifier="${node.icon}"
            overlay="${node.overlayIcon}"
            size="small"
          ></typo3-backend-icon>
        </span>
        `
      : html`${nothing}`;
  }

  protected createNodeContentLabel(node: TreeNodeInterface): TemplateResult
  {
    let label = (node.prefix || '') + node.name + (node.suffix || '');
    // make a text node out of it, and strip out any HTML (this is because the return value uses html()
    // instead of text() which is needed to avoid XSS in a page title
    const labelNode = document.createElement('div');
    labelNode.textContent = label;
    label = labelNode.innerHTML;
    if (this.searchTerm) {
      // Escape all meta characters of regular expressions: ( ) [ ] $ * + ? . { } / | ^ -
      const regexp = new RegExp(this.searchTerm.replace(/[/\-\\^$*+?.()|[\]{}]/g, '\\$&'), 'gi');
      label = label.replace(regexp, '<span class="node-highlight-text">$&</span>');
    }

    return html`
      <div class="node-contentlabel">
      <div class="node-name" .innerHTML="${label}"></div>
      ${node.note ? html`<div class="node-note">${node.note}</div>` : nothing }
      </div>`;
  }

  protected createNodeStatusInformation(node: TreeNodeInterface): TemplateResult
  {
    const statusInformation = this.getNodeStatusInformation(node);
    if (statusInformation.length === 0) {
      return html`${nothing}`;
    }

    const firstInformation = statusInformation[0];
    const severityClass = Severity.getCssClass(firstInformation.severity);
    const iconIdentifier = firstInformation.icon !== '' ? firstInformation.icon : 'actions-dot';
    const overlayIconIdentifier = firstInformation.overlayIcon !== '' ? firstInformation.overlayIcon : undefined;

    return html`
      <span class="node-information">
        <typo3-backend-icon
          class="text-${severityClass}"
          identifier=${iconIdentifier}
          overlay=${ifDefined(overlayIconIdentifier)}
          size="small"
          ></typo3-backend-icon>
      </span>
    `;
  }

  protected createNodeDeleteDropZone(node: TreeNodeInterface): TemplateResult
  {
    return this.draggingNode === node && node.deletable
      ? html`
        <div class="node-dropzone-delete" data-tree-dropzone="delete">
          <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
          ${TYPO3.lang.deleteItem}
        </div>
        `
      : html`${nothing}`;
  }

  protected createNodeForm(node: TreeNodeInterface): TemplateResult
  {
    const command = node.identifier.startsWith('NEW') ? TreeNodeCommandEnum.NEW : TreeNodeCommandEnum.EDIT;

    const keydownFunction = (event: KeyboardEvent) => {
      const code = event.key;
      if (([KeyTypes.ENTER, KeyTypes.TAB] as string[]).includes(code)) {
        const target = event.target as HTMLInputElement;
        const newName = target.value.trim();
        this.editingNode = null;
        this.requestUpdate();
        if (newName !== node.name && newName !== '') {
          this.handleNodeEdit(node, newName);
          this.focusNode(node);
        } else if (command === TreeNodeCommandEnum.NEW && newName === '') {
          this.removeNode(node);
        } else {
          this.focusNode(node);
        }
      } else if (([KeyTypes.ESCAPE] as string[]).includes(code)) {
        this.editingNode = null;
        this.requestUpdate();
        if (command === TreeNodeCommandEnum.NEW) {
          this.removeNode(node);
        } else {
          this.focusNode(node);
        }
      }
    };

    const blurFunction = (event: FocusEvent) => {
      if (this.editingNode !== null) {
        this.editingNode = null;
        const target = event.target as HTMLInputElement;
        const newName = target.value.trim();
        if (newName !== node.name && newName !== '') {
          this.handleNodeEdit(node, newName);
        } else if (command === TreeNodeCommandEnum.NEW) {
          this.removeNode(node);
        }
        this.requestUpdate();
      }
    };

    return html`
      <input
        class="node-edit"
        @click="${(event: PointerEvent) => { event.stopImmediatePropagation(); }}"
        @blur="${blurFunction}"
        @keydown="${keydownFunction}"
        value="${node.name}"
      />
    `;
  }

  //
  // Handling
  //

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  protected async handleNodeEdit(node: TreeNodeInterface, newName: string): Promise<void> {
    console.error('The function Tree->handleNodeEdit is not implemented.');
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  protected handleNodeDelete(node: TreeNodeInterface) {
    console.error('The function Tree->handleNodeDelete is not implemented.');
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  protected handleNodeMove(node: TreeNodeInterface, target: TreeNodeInterface, position: TreeNodePositionEnum) {
    console.error('The function Tree->handleNodeMove is not implemented.');
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  protected async handleNodeAdd(node: TreeNodeInterface, target: TreeNodeInterface, position: TreeNodePositionEnum): Promise<void> {
    console.error('The function Tree->handleNodeAdd is not implemented.');
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  protected createNodeContentAction(node: TreeNodeInterface): TemplateResult
  {
    return html`${nothing}`;
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  protected createDataTransferItemsFromNode(node: TreeNodeInterface): DataTransferStringItem[] {
    throw new Error('The function Tree->createDataTransferItemFromNode is not implemented.');
  }

  //
  // Node
  //
  protected getNodeIdentifier(node: TreeNodeInterface): string {
    return node.identifier;
  }

  protected getNodeTreeIdentifier(node: TreeNodeInterface): string {
    return node.__treeIdentifier;
  }

  protected getNodeParentTreeIdentifier(node: TreeNodeInterface): string {
    return node.__parents.join('_');
  }

  protected getNodeClasses(node: TreeNodeInterface): string[] {
    const classList: Array<string> = ['node'];

    if (node.checked) {
      classList.push('node-selected');
    }

    if (this.draggingNode === node) {
      classList.push('node-dragging');
    }

    return classList;
  }

  protected getNodeLabels(node: TreeNodeInterface): TreeNodeLabel[] {
    let labels = node.labels;
    if (labels.length > 0) {
      labels = labels.sort((a, b) => {
        return b.priority - a.priority;
      });

      return labels;
    }

    const parentNode = this.getParentNode(node);
    if (parentNode === null) {
      return [];
    }

    return this.getNodeLabels(parentNode);
  }

  protected getNodeStatusInformation(node: TreeNodeInterface): TreeNodeStatusInformation[] {
    if (node.statusInformation.length === 0) {
      return [];
    }

    const statusInformation = node.statusInformation.sort((a, b) => {
      if (a.severity !== b.severity) {
        return b.severity - a.severity;
      }
      return b.priority - a.priority;
    });

    return statusInformation;
  }

  protected getNodeDepth(node: TreeNodeInterface): number {
    return node.depth;
  }

  protected getNodeTabindex(node: TreeNodeInterface): number {
    if (this.focusedNode) {
      return this.focusedNode === node ? 0 : -1;
    }
    if (this.lastFocusedNode) {
      return this.lastFocusedNode === node ? 0 : -1;
    }
    return this.getFirstNode() === node ? 0 : -1;
  }

  protected getNodeChildren(node: TreeNodeInterface): TreeNodeInterface[] {
    if (!node.hasChildren) {
      return [];
    }

    return this.displayNodes.filter((filterNode) => {
      return node === this.getParentNode(filterNode);
    });
  }

  protected getNodeSetsize(node: TreeNodeInterface): number {
    if (node.depth === 0) {
      return this.displayNodes.filter((node) => node.depth === 0).length;
    }
    const parentNode = this.getParentNode(node);
    const childNodes = this.getNodeChildren(parentNode);

    return childNodes.length;
  }

  protected getNodePositionInSet(node: TreeNodeInterface): number {
    const parentNode = this.getParentNode(node);
    let nodeSet: TreeNodeInterface[] = [];
    if (node.depth === 0) {
      nodeSet = this.displayNodes.filter((node) => node.depth === 0);
    } else if (parentNode !== null) {
      nodeSet = this.getNodeChildren(parentNode);
    }

    return nodeSet.indexOf(node) + 1;
  }

  protected getFirstNode(): TreeNodeInterface|null {
    if (this.displayNodes.length) {
      return this.displayNodes[0];
    }

    return null;
  }

  protected getPreviousNode(node: TreeNodeInterface): TreeNodeInterface|null {
    const position = this.displayNodes.indexOf(node);
    const previousPosition = position - 1;

    if (this.displayNodes[previousPosition]) {
      return this.displayNodes[previousPosition];
    }

    return null;
  }

  protected getNextNode(node: TreeNodeInterface): TreeNodeInterface|null {
    const position = this.displayNodes.indexOf(node);
    const nextPosition = position + 1;

    if (this.displayNodes[nextPosition]) {
      return this.displayNodes[nextPosition];
    }

    return null;
  }

  protected getLastNode(): TreeNodeInterface|null {
    if (this.displayNodes.length) {
      return this.displayNodes[this.displayNodes.length - 1];
    }

    return null;
  }

  protected getParentNode(node: TreeNodeInterface): TreeNodeInterface|null {
    if (node.__parents.length) {
      return this.getNodeByTreeIdentifier(this.getNodeParentTreeIdentifier(node));
    }

    return null;
  }

  protected getNodeTitle(node: TreeNodeInterface): string {
    let baseNodeTitle = node.tooltip ? node.tooltip : 'uid=' + node.identifier + ' ' + node.name;

    const labels = this.getNodeLabels(node);
    if (labels.length) {
      baseNodeTitle += '; ' + labels.map(label => label.label).join('; ');
    }

    const statusInformation = this.getNodeStatusInformation(node);
    if (statusInformation.length) {
      baseNodeTitle += '; ' + statusInformation.map(information => information.label).join('; ');
    }

    return baseNodeTitle;
  }

  /**
   * Event handler for collapsing or expanding nodes
   */
  protected handleNodeToggle(node: TreeNodeInterface): void {
    if (node.__expanded) {
      this.hideChildren(node);
    } else {
      this.showChildren(node);
    }
  }

  protected isRTL() {
    const rootElementStyle = window.getComputedStyle(document.documentElement);
    const direction = rootElementStyle.getPropertyValue('direction');

    return direction === 'rtl';
  }

  /**
   * Add keydown handling to allow keyboard navigation inside the tree
   */
  private handleKeyboardInteraction(event: KeyboardEvent) {
    // Do not handle keyboard interaction if tree is
    // currently editing a node.
    if (this.editingNode !== null) {
      return;
    }

    // Only handle specific keyboard interactions
    const charCodes: string[] = [
      KeyTypes.ENTER,
      KeyTypes.SPACE,
      KeyTypes.END,
      KeyTypes.HOME,
      KeyTypes.LEFT,
      KeyTypes.UP,
      KeyTypes.RIGHT,
      KeyTypes.DOWN
    ];
    if (charCodes.includes(event.key) === false) {
      return;
    }

    const target = event.target as HTMLElement;
    const currentNode = this.getNodeFromElement(target);
    if (currentNode === null) {
      return;
    }

    const parentNode = this.getParentNode(currentNode);
    const firstNode = this.getFirstNode();
    const previousNode = this.getPreviousNode(currentNode);
    const nextNode = this.getNextNode(currentNode);
    const lastNode = this.getLastNode();

    event.preventDefault();
    switch (event.key) {
      case KeyTypes.HOME:
        // scroll to top, select first node
        if (firstNode !== null) {
          this.scrollNodeIntoVisibleArea(firstNode);
          this.focusNode(firstNode);
        }
        break;
      case KeyTypes.END:
        if (lastNode !== null) {
          this.scrollNodeIntoVisibleArea(lastNode);
          this.focusNode(lastNode);
        }
        break;
      case KeyTypes.UP:
        // select previous visible node on any level
        if (previousNode !== null) {
          this.scrollNodeIntoVisibleArea(previousNode);
          this.focusNode(previousNode);
        }
        break;
      case KeyTypes.DOWN:
        // select next visible node on any level
        if (nextNode !== null) {
          this.scrollNodeIntoVisibleArea(nextNode);
          this.focusNode(nextNode);
        }
        break;
      case KeyTypes.LEFT:
        if (currentNode.__expanded) {
          // collapse node if collapsible
          if (currentNode.hasChildren) {
            this.hideChildren(currentNode);
          }
        } else if (parentNode) {
          // go to parent node
          this.scrollNodeIntoVisibleArea(parentNode);
          this.focusNode(parentNode);
        }
        break;
      case KeyTypes.RIGHT:
        if (currentNode.__expanded && nextNode) {
          // the current node is expanded,
          // goto first child (next element on the list)
          this.scrollNodeIntoVisibleArea(nextNode);
          this.focusNode(nextNode);
        } else {
          if (currentNode.hasChildren) {
            // expand currentNode
            this.showChildren(currentNode);
          }
          //do nothing if node has no children
        }
        break;
      case KeyTypes.ENTER:
      case KeyTypes.SPACE:
        this.selectNode(currentNode);
        break;
      default:
    }
  }

  /**
   * Check is the node is visible in the viewport,
   * if not the element is scrolled into the view
   */
  private scrollNodeIntoVisibleArea(node: TreeNodeInterface): void {
    const nodeAnchorTop = node.__y;
    const nodeAnchorBottom = node.__y + this.nodeHeight;
    const nodeFitsTop = nodeAnchorTop >= this.currentScrollPosition;
    const nodeFitsBottom = nodeAnchorBottom <= this.currentScrollPosition + this.currentVisibleHeight;
    const nodeFits = nodeFitsTop && nodeFitsBottom;

    if (!nodeFits) {
      let scrollTop = this.currentScrollPosition;
      if (!nodeFitsTop && !nodeFitsBottom) {
        scrollTop = nodeAnchorBottom - this.currentVisibleHeight;
      } else if (!nodeFitsTop) {
        scrollTop = nodeAnchorTop;
      } else if (!nodeFitsBottom) {
        scrollTop = nodeAnchorBottom - this.currentVisibleHeight;
      }
      if (scrollTop < 0) {
        scrollTop = 0;
      }
      this.root.scrollTo({ 'top': scrollTop });
    }
  }

  /**
   * If the tree component is embedded in an iframe, and if the iframe src get changed by navigating to another url,
   * pending AjaxRequest will get cancelled by the browser, without letting us the opportunity to properly handle
   * the thrown error. As a workaround, we register a pagehide event on the iframe's window,
   * and turn on the muteErrorNotifications flag.
   */
  private registerUnloadHandler(): void {
    try {
      // Do not proceed if we are not embedded in an iframe (of if CSP prevent for accessing frameElement),
      if (!window.frameElement) {
        return;
      }
      window.addEventListener(
        'pagehide',
        () => this.muteErrorNotifications = true,
        { once: true }
      );
    } catch {
      console.error('Failed to check the existence of window.frameElement – using a foreign origin?');
      // Do nothing if an error occured during the event registration
    }
  }
}
