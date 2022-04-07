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
import {customElement, property, state} from 'lit/decorators';
import {TreeNode} from './tree/tree-node';
import * as d3selection from 'd3-selection';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Notification from './notification';
import {KeyTypesEnum as KeyTypes} from './enum/key-types';
import Icons from './icons';
import Tooltip from './tooltip';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import {MarkupIdentifiers} from './enum/icon-types';
import {lll} from '@typo3/core/lit-helper';
import DebounceEvent from '@typo3/core/event/debounce-event';
import '@typo3/backend/element/icon-element';
import {Tooltip as BootstrapTooltip} from 'bootstrap';

export type TreeWrapperSelection<TBase extends d3selection.BaseType> = d3selection.Selection<TBase, any, any, any>;
export type TreeNodeSelection = d3selection.Selection<d3selection.BaseType, TreeNode, any, any>;

interface SvgTreeData {
  nodes: TreeNode[];
  links: SvgTreeDataLink[];
}

interface SvgTreeDataLink {
  source: TreeNode;
  target: TreeNode;
}

interface SvgTreeDataIcon {
  identifier: string;
  icon: string|null|SVGElement;
}

export interface SvgTreeSettings {
  [keys: string]: any;
  defaultProperties: {[keys: string]: any};
}

export interface SvgTreeWrapper extends HTMLElement {
  svgtree?: SvgTree
}

export class SvgTree extends LitElement {
  @property({type: Object}) setup?: {[keys: string]: any} = null;
  @state() settings: SvgTreeSettings = {
    showIcons: false,
    marginTop: 15,
    nodeHeight: 26,
    icon: {
      size: 16,
      containerSize: 20,
    },
    indentWidth: 20,
    width: 300,
    duration: 400,
    dataUrl: '',
    filterUrl: '',
    defaultProperties: {},
    expandUpToLevel: null as any,
    actions: []
  };

  /**
   * Check if cursor is over the SVG element
   */
  public isOverSvg: boolean = false;

  /**
   * Root <svg> element
   */
  public svg: TreeWrapperSelection<SVGSVGElement> = null;

  /**
   * SVG <g> container wrapping all .nodes, .links, .nodes-bg  elements
   */
  public container: TreeWrapperSelection<SVGGElement> = null;

  /**
   * SVG <g> container wrapping all .node elements
   */
  public nodesContainer: TreeWrapperSelection<SVGGElement> = null;

  /**
   * SVG <g> container wrapping all .nodes-bg elements
   */
  public nodesBgContainer: TreeWrapperSelection<SVGGElement> = null;

  /**
   * Is set when the input device is hovered over a node
   */
  public hoveredNode: TreeNode|null = null;

  public nodes: TreeNode[] = [];

  public textPosition: number = 10;

  protected icons: {[keys: string]: SvgTreeDataIcon} = {};
  protected nodesActionsContainer: TreeWrapperSelection<SVGGElement> = null;

  /**
   * SVG <defs> container wrapping all icon definitions
   */
  protected iconsContainer: TreeWrapperSelection<SVGDefsElement> = null;

  /**
   * SVG <g> container wrapping all links (lines between parent and child)
   *
   * @type {Selection}
   */
  protected linksContainer: TreeWrapperSelection<SVGGElement> = null;

  protected data: SvgTreeData = new class implements SvgTreeData {
    links: SvgTreeDataLink[] = [];
    nodes: TreeNode[] = [];
  };

  protected viewportHeight: number = 0;
  protected scrollBottom: number = 0;
  protected searchTerm: string|null = null;
  protected unfilteredNodes: string = '';

  protected networkErrorTitle: string = top.TYPO3.lang.tree_networkError;
  protected networkErrorMessage: string = top.TYPO3.lang.tree_networkErrorDescription;

  protected tooltipOptions: Partial<BootstrapTooltip.Options> = {};

  /**
   * Initializes the tree component - created basic markup, loads and renders data
   * @todo declare private
   */
  public doSetup(settings: any): void {
    Object.assign(this.settings, settings);
    if (this.settings.showIcons) {
      this.textPosition += this.settings.icon.containerSize;
    }

    this.svg = d3selection.select(this).select('svg');
    this.container = this.svg.select('.nodes-wrapper') as TreeWrapperSelection<SVGGElement>;
    this.nodesBgContainer = this.container.select('.nodes-bg') as TreeWrapperSelection<SVGGElement>;
    this.nodesActionsContainer = this.container.select('.nodes-actions') as TreeWrapperSelection<SVGGElement>;
    this.linksContainer = this.container.select('.links') as TreeWrapperSelection<SVGGElement>;
    this.nodesContainer = this.container.select('.nodes') as TreeWrapperSelection<SVGGElement>;
    this.iconsContainer = this.svg.select('defs') as TreeWrapperSelection<SVGGElement>;

    this.tooltipOptions = {
      delay: 50,
      trigger: 'hover',
      placement: 'right',
      container: typeof this.settings.id !== 'undefined' ? '#' + this.settings.id : 'body',
    }

    this.updateScrollPosition();
    this.loadCommonIcons();
    this.loadData();
    this.dispatchEvent(new Event('svg-tree:initialized'));
  }

  /**
   * Preloads common icons to have them available as early
   * as possible and avoid unnessesary early node updates.
   */
  public loadCommonIcons(): void
  {
    this.fetchIcon('actions-chevron-right', false); // used as toggle icon
    this.fetchIcon('overlay-backenduser', false);   // used as locked indicator (a different user is editing)
    this.fetchIcon('actions-caret-right', false);   // used as enter icon for stopped trees
    this.fetchIcon('actions-link', false);          // used as link indicator
  }

  /**
   * Make the DOM element given as parameter focusable and focus it
   *
   * @param {SVGElement} element
   */
  public focusElement(element: SVGElement|HTMLElement): void {
    if (element === null) {
      return;
    }
    const visibleElements = element.parentNode.querySelectorAll('[tabindex]');
    visibleElements.forEach((visibleElement) => {
      visibleElement.setAttribute('tabindex','-1');
    });
    element.setAttribute('tabindex', '0');
    element.focus();
  }

  /**
   * Make the DOM element of the node given as parameter focusable and focus it
   */
  public focusNode(node: TreeNode): void {
    this.disableFocusedNodes();
    node.focused = true;
    this.focusElement(this.getElementFromNode(node));
  }

  public getNodeFromElement(element: SVGElement|HTMLElement): TreeNode|null
  {
    if (element === null || !('stateId' in element.dataset)) {
      return null;
    }

    return this.getNodeByIdentifier(element.dataset.stateId);
  }

  /**
   * Return the DOM element of a tree node
   */
  public getElementFromNode(node: TreeNode): HTMLElement|null {
    return this.querySelector('#identifier-' + this.getNodeStateIdentifier(node));
  }

  /**
   * Loads tree data (json) from configured url
   */
  public loadData() {
    this.nodesAddPlaceholder();
    (new AjaxRequest(this.settings.dataUrl))
      .get({cache: 'no-cache'})
      .then((response: AjaxResponse) => response.resolve())
      .then((json) => {
        const nodes = Array.isArray(json) ? json : [];
        this.replaceData(nodes);
        this.nodesRemovePlaceholder();
        // @todo: needed?
        this.updateScrollPosition();
        this.updateVisibleNodes();
      })
      .catch((error) => {
        this.errorNotification(error, false);
        this.nodesRemovePlaceholder();
        throw error;
      });
  }

  /**
   * Delete old tree and create new one
   */
  public replaceData(nodes: TreeNode[]) {
    this.setParametersNode(nodes);
    this.prepareDataForVisibleNodes();
    this.nodesContainer.selectAll('.node').remove();
    this.nodesBgContainer.selectAll('.node-bg').remove();
    this.nodesActionsContainer.selectAll('.node-action').remove();
    this.linksContainer.selectAll('.link').remove();
    this.updateVisibleNodes();
  }

  /**
   * Set parameters like node parents, parentsStateIdentifier, checked.
   * Usually called when data is loaded initially or replaced completely.
   *
   * @param {Node[]} nodes
   */
  public setParametersNode(nodes: TreeNode[] = null): void {
    nodes = nodes || this.nodes;
    nodes = nodes.map((node, index) => {
      if (typeof node.command === 'undefined') {
        node = Object.assign({}, this.settings.defaultProperties, node);
      }
      node.expanded = (this.settings.expandUpToLevel !== null) ? node.depth < this.settings.expandUpToLevel : Boolean(node.expanded);
      node.parents = [];
      node.parentsStateIdentifier = [];
      if (node.depth > 0) {
        let currentDepth = node.depth;
        for (let i = index; i >= 0; i--) {
          let currentNode = nodes[i];
          if (currentNode.depth < currentDepth) {
            node.parents.push(i);
            node.parentsStateIdentifier.push(nodes[i].stateIdentifier);
            currentDepth = currentNode.depth;
          }
        }
      }

      if (typeof node.checked === 'undefined') {
        node.checked = false;
      }
      if (typeof node.focused === 'undefined') {
        node.focused = false;
      }
      return node;
    });

    // get nodes with depth 0, if there is only 1 then open it and disable toggle
    const nodesOnRootLevel = nodes.filter((node) => node.depth === 0);
    if (nodesOnRootLevel.length === 1) {
      nodes[0].expanded = true;
    }
    const evt = new CustomEvent('typo3:svg-tree:nodes-prepared', {detail: {nodes: nodes}, bubbles: false});
    this.dispatchEvent(evt);
    this.nodes = evt.detail.nodes;
  }

  public nodesRemovePlaceholder() {
    const nodeLoader = this.querySelector('.node-loader') as HTMLElement;
    if (nodeLoader) {
      nodeLoader.style.display = 'none';
    }
    const componentWrapper = this.closest('.svg-tree');
    const treeLoader = componentWrapper?.querySelector('.svg-tree-loader') as HTMLElement;
    if (treeLoader) {
      treeLoader.style.display = 'none';
    }
  }

  public nodesAddPlaceholder(node: TreeNode = null) {
    if (node) {
      const nodeLoader = this.querySelector('.node-loader') as HTMLElement;
      if (nodeLoader) {
        nodeLoader.style.top = '' + (node.y + this.settings.marginTop);
        nodeLoader.style.display = 'block';
      }
    } else {
      const componentWrapper = this.closest('.svg-tree');
      const treeLoader = componentWrapper?.querySelector('.svg-tree-loader') as HTMLElement;
      if (treeLoader) {
        treeLoader.style.display = 'block';
      }
    }
  }

  /**
   * Updates node's data to hide/collapse children
   *
   * @param {Node} node
   */
  public hideChildren(node: TreeNode): void {
    node.expanded = false;
    this.setExpandedState(node);
    this.dispatchEvent(new CustomEvent('typo3:svg-tree:expand-toggle', {detail: {node: node}}));
  }

  /**
   * Updates node's data to show/expand children
   *
   * @param {Node} node
   */
  public showChildren(node: TreeNode): void {
    node.expanded = true;
    this.setExpandedState(node);
    this.dispatchEvent(new CustomEvent('typo3:svg-tree:expand-toggle', {detail: {node: node}}));
  }

  /**
   * Updates the expanded state of the DOM element that belongs to the node.
   * This is required because the node is not recreated on update and thus the change in the expanded state
   * of the node data is not represented in DOM on hideChildren and showChildren.
   *
   * @param {Node} node
   */
  public setExpandedState(node: TreeNode): void {
    const nodeElement = this.getElementFromNode(node);
    if (nodeElement) {
      if (node.hasChildren) {
        nodeElement.setAttribute('aria-expanded', node.expanded ? 'true' : 'false');
      } else {
        nodeElement.removeAttribute('aria-expanded');
      }
    }
  }

  /**
   * Refresh view with new data
   */
  public refreshTree(): void {
    this.loadData();
  }

  public refreshOrFilterTree(): void {
    if (this.searchTerm !== '') {
      this.filter(this.searchTerm);
    } else {
      this.refreshTree();
    }
  }

  /**
   * Filters out invisible nodes (collapsed) from the full dataset (this.rootNode)
   * and enriches dataset with additional properties
   * Visible dataset is stored in this.data
   */
  public prepareDataForVisibleNodes(): void {
    const blacklist: {[keys: string]: boolean} = {};
    this.nodes.forEach((node: TreeNode, index: number): void => {
      if (!node.expanded) {
        blacklist[index] = true;
      }
    });

    this.data.nodes = this.nodes.filter((node: TreeNode): boolean => {
      return node.hidden !== true && !node.parents.some((index: number) => Boolean(blacklist[index]))
    });

    this.data.links = [];
    let pathAboveMounts = 0;

    this.data.nodes.forEach((node: TreeNode, i: number) => {
      // delete n.children;
      node.x = node.depth * this.settings.indentWidth;
      if (node.readableRootline) {
        pathAboveMounts += this.settings.nodeHeight;
      }

      node.y = (i * this.settings.nodeHeight) + pathAboveMounts;
      if (node.parents[0] !== undefined) {
        this.data.links.push({
          source: this.nodes[node.parents[0]],
          target: node
        });
      }

      if (this.settings.showIcons) {
        this.fetchIcon(node.icon);
        this.fetchIcon(node.overlayIcon);
      }
    });

    this.svg.attr('height', ((this.data.nodes.length * this.settings.nodeHeight) + (this.settings.nodeHeight / 2) + pathAboveMounts));
  }

  /**
   * Fetch icon from Icon API and store it in this.icons
   */
  public fetchIcon(iconName: string, update: boolean = true): void {
    if (!iconName) {
      return;
    }

    if (!(iconName in this.icons)) {
      this.icons[iconName] = {
        identifier: iconName,
        icon: null
      } as SvgTreeDataIcon;
      Icons.getIcon(iconName, Icons.sizes.small, null, null, MarkupIdentifiers.inline).then((icon: string) => {
        let result = icon.match(/<svg[\s\S]*<\/svg>/i);
        if (result) {
          let iconEl = document.createRange().createContextualFragment(result[0]);
          this.icons[iconName].icon = iconEl.firstElementChild as SVGElement;
        }
        if (update) {
          this.updateVisibleNodes();
        }
      });
    }
  }

  /**
   * Renders the subset of the tree nodes fitting the viewport (adding, modifying and removing SVG nodes)
   */
  public updateVisibleNodes(): void {
    const visibleRows = Math.ceil(this.viewportHeight / this.settings.nodeHeight + 1);
    const position = Math.floor(Math.max(this.scrollTop - (this.settings.nodeHeight * 2), 0) / this.settings.nodeHeight);

    const visibleNodes = this.data.nodes.slice(position, position + visibleRows);
    const focusableElement = this.querySelector('[tabindex="0"]');
    const focusedNodeInViewport = visibleNodes.find((node: TreeNode) => node.focused);
    const checkedNodeInViewport = visibleNodes.find((node: TreeNode) => node.checked);

    let nodes = this.nodesContainer.selectAll('.node')
      .data(visibleNodes, (node: TreeNode) => node.stateIdentifier);
    const nodesBg = this.nodesBgContainer.selectAll('.node-bg')
      .data(visibleNodes, (node: TreeNode) => node.stateIdentifier);
    const nodesActions = this.nodesActionsContainer.selectAll('.node-action')
      .data(visibleNodes, (node: TreeNode) => node.stateIdentifier);

    // delete nodes without corresponding data
    nodes.exit().remove();
    nodesBg.exit().remove();
    nodesActions.exit().remove();

    // update nodes actions
    this.updateNodeActions(nodesActions);
    // update nodes background
    const nodeBgClass = this.updateNodeBgClass(nodesBg);

    nodeBgClass
      .attr('class', (node: TreeNode, i: number) => {
        return this.getNodeBgClass(node, i, nodeBgClass);
      })
      .attr('style', (node: TreeNode) => {
        return node.backgroundColor ? 'fill: ' + node.backgroundColor + ';' : '';
      });

    this.updateLinks();
    nodes = this.enterSvgElements(nodes);

    // update nodes
    nodes
      .attr('tabindex', (node: TreeNode, index: number) => {
        if (typeof focusedNodeInViewport !== 'undefined') {
          if (focusedNodeInViewport === node) {
            return '0';
          }
        } else {
          if (typeof checkedNodeInViewport !== 'undefined') {
            if (checkedNodeInViewport === node) {
              return '0';
            }
          } else {
            if (focusableElement === null) {
              if (index === 0) {
                return '0';
              }
            } else {
              if (d3selection.select(focusableElement).datum() === node) {
                return '0';
              }
            }
          }
        }

        return '-1';
      })
      .attr('transform', this.getNodeTransform)
      .select('.node-name')
      .html((node: TreeNode) => this.getNodeLabel(node));

    nodes
      .select('.node-toggle')
      .attr('class', this.getToggleClass)
      .attr('visibility', this.getToggleVisibility);

    if (this.settings.showIcons) {
      nodes
        .select('use.node-icon')
        .attr('xlink:href', this.getIconId);
      nodes
        .select('use.node-icon-overlay')
        .attr('xlink:href', this.getIconOverlayId);
      nodes
        .select('use.node-icon-locked')
        .attr('xlink:href', (node: TreeNode) => {
          return '#icon-' + (node.locked ? 'overlay-backenduser' : '');
        });
    }
  }

  public updateNodeBgClass(nodesBg: TreeNodeSelection): TreeNodeSelection {
    let nodeHeight = this.settings.nodeHeight;

    // IMPORTANT
    //
    // The SVG spec does not support stroke alignment, and the stroke is always centered on the edge
    // this results in blurry edges with 1px borders. Setting shape rendering to crispEdges has
    // different results depending on the used browser.
    //
    // To resolve this issue need to:
    // 1. reduce the height -1px - done in SvgTree::updateNodeBgClass
    // 2. offset the element by 0.5px - done in SvgTree::getNodeBackgroundTransform
    nodeHeight = nodeHeight - 1;

    let node = nodesBg.enter()
      .append('rect')
      .merge(nodesBg as d3selection.Selection<SVGRectElement, TreeNode, any, any>);
    return node
      .attr('width', '100%')
      .attr('height', nodeHeight)
      .attr('data-state-id', this.getNodeStateIdentifier)
      .attr('transform', (node: TreeNode) => this.getNodeBackgroundTransform(node, this.settings.indentWidth, this.settings.nodeHeight))
      .on('mouseover', (evt: MouseEvent, node: TreeNode) => this.onMouseOverNode(node))
      .on('mouseout', (evt: MouseEvent, node: TreeNode) => this.onMouseOutOfNode(node))
      .on('click', (evt: MouseEvent, node: TreeNode) => {
        this.selectNode(node, true);
        this.focusNode(node);
        this.updateVisibleNodes();
      })
      .on('contextmenu', (evt: MouseEvent, node: TreeNode) => {
        evt.preventDefault();
        this.dispatchEvent(new CustomEvent('typo3:svg-tree:node-context', {detail: {node: node}}));
      });
  }

  /**
   * Returns icon's href attribute value
   */
  public getIconId(node: TreeNode): string {
    return '#icon-' + node.icon;
  }

  /**
   * Returns icon's href attribute value
   */
  public getIconOverlayId(node: TreeNode): string {
    return '#icon-' + node.overlayIcon;
  }

  /**
   * Node selection logic (triggered by different events)
   * This represents a dummy method and is usually overridden
   * The second argument can be interpreted by the listened events to e.g. not avoid reloading the content frame and instead
   * used for just updating the state within the tree
   */
  public selectNode(node: TreeNode, propagate: boolean = true): void {
    if (!this.isNodeSelectable(node)) {
      return;
    }
    // Disable already selected nodes
    this.disableSelectedNodes();
    this.disableFocusedNodes();
    node.checked = true;
    node.focused = true;
    this.dispatchEvent(new CustomEvent('typo3:svg-tree:node-selected', {detail: {node: node, propagate: propagate}}));
    this.updateVisibleNodes();
  }

  public filter(searchTerm?: string|null): void {
    if (typeof searchTerm === 'string') {
      this.searchTerm = searchTerm;
    }
    this.nodesAddPlaceholder();
    if (this.searchTerm && this.settings.filterUrl) {
      (new AjaxRequest(this.settings.filterUrl + '&q=' + this.searchTerm))
        .get({cache: 'no-cache'})
        .then((response: AjaxResponse) => response.resolve())
        .then((json) => {
          let nodes = Array.isArray(json) ? json : [];
          if (nodes.length > 0) {
            if (this.unfilteredNodes === '') {
              this.unfilteredNodes = JSON.stringify(this.nodes);
            }
            this.replaceData(nodes);
          }
          this.nodesRemovePlaceholder();
        })
        .catch((error: any) => {
          this.errorNotification(error, false)
          this.nodesRemovePlaceholder();
          throw error;
        });
    } else {
      // restore original state without filters
      this.resetFilter();
    }
  }

  public resetFilter(): void
  {
    this.searchTerm = '';
    if (this.unfilteredNodes.length > 0) {
      let currentlySelected = this.getSelectedNodes()[0];
      if (typeof currentlySelected === 'undefined') {
        this.refreshTree();
        return;
      }
      this.nodes = JSON.parse(this.unfilteredNodes);
      this.unfilteredNodes = '';
      // re-select the node from the identifier because the nodes have been updated
      const currentlySelectedNode = this.getNodeByIdentifier(currentlySelected.stateIdentifier);
      if (currentlySelectedNode) {
        this.selectNode(currentlySelectedNode, false);
        this.focusNode(currentlySelectedNode);
        // Remove placeholder, in case this method was called from this.filter()
        // and there was currently a node selected.
        this.nodesRemovePlaceholder();
      } else {
        this.refreshTree();
      }
    } else {
      this.refreshTree();
    }
    this.prepareDataForVisibleNodes();
    this.updateVisibleNodes();
  }

  /**
   * Displays a notification message and refresh nodes
   */
  public errorNotification(error: any = null, refresh: boolean = false): void {
    if (Array.isArray(error)) {
      error.forEach((message: any) => { Notification.error(
        message.title,
        message.message
      )});
    } else {
      let title = this.networkErrorTitle;
      if (error && error.target && (error.target.status || error.target.statusText)) {
        title += ' - ' + (error.target.status || '') + ' ' + (error.target.statusText || '');
      }
      Notification.error(title, this.networkErrorMessage);
    }
    if (refresh) {
      this.loadData();
    }
  }

  public connectedCallback(): void {
    super.connectedCallback();
    this.addEventListener('resize', () => this.updateView());
    this.addEventListener('scroll', () => this.updateView());
    this.addEventListener('svg-tree:visible', () => this.updateView());
    window.addEventListener('resize', () => {
      if (this.getClientRects().length > 0) {
        this.updateView();
      }
    });
  }

  /**
   * Returns an array of selected nodes
   */
  public getSelectedNodes(): TreeNode[] {
    return this.nodes.filter((node: TreeNode) => node.checked);
  }

  /**
   * Returns an array of focused nodes
   */
  public getFocusedNodes(): TreeNode[] {
    return this.nodes.filter((node: TreeNode) => node.focused);
  }

  /**
   * Disable currently focused nodes
   */
  public disableFocusedNodes(): void {
    this.getFocusedNodes().forEach((node: TreeNode) => {
      if (node.focused === true) {
        node.focused = false;
      }
    });
  }

  // disable shadow dom for now
  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected render(): TemplateResult {
    return html`
      <div class="node-loader">
        <typo3-backend-icon identifier="spinner-circle-light" size="small"></typo3-backend-icon>
      </div>
      <svg version="1.1"
           width="100%"
           @mouseover=${() => this.isOverSvg = true}
           @mouseout=${() => this.isOverSvg = false}
           @keydown=${(evt: KeyboardEvent) => this.handleKeyboardInteraction(evt)}>
        <g class="nodes-wrapper" transform="translate(${this.settings.indentWidth / 2},${this.settings.nodeHeight / 2})">
          <g class="links"></g>
          <g class="nodes-bg"></g>
          <g class="nodes" role="tree"></g>
          <g class="nodes-actions"></g>
        </g>
        <defs></defs>
      </svg>
    `;
  }

  protected firstUpdated(): void {
    this.svg = d3selection.select(this.querySelector('svg'))
    this.container = d3selection.select(this.querySelector('.nodes-wrapper'))
      .attr('transform', 'translate(' + (this.settings.indentWidth / 2) + ',' + (this.settings.nodeHeight / 2) + ')') as any;
    this.nodesBgContainer = d3selection.select(this.querySelector('.nodes-bg')) as any;
    this.nodesActionsContainer = d3selection.select(this.querySelector('.nodes-actions')) as any;
    this.linksContainer = d3selection.select(this.querySelector('.links')) as any;
    this.nodesContainer = d3selection.select(this.querySelector('.nodes')) as any;

    this.doSetup(this.setup || {});
    this.updateView();
  }

  protected updateView(): void {
    this.updateScrollPosition();
    this.updateVisibleNodes();
    if (this.settings.actions && this.settings.actions.length) {
      this.nodesActionsContainer.attr('transform', 'translate(' + (this.querySelector('svg').clientWidth - 16 - ((16 * this.settings.actions.length))) + ',0)');
    }
  }

  protected disableSelectedNodes(): void {
    // Disable already selected nodes
    this.getSelectedNodes().forEach((node: TreeNode) => {
      if (node.checked === true) {
        node.checked = false;
      }
    });
  }

  /**
   * Ensure to update the actions column to stick to the very end
   */
  protected updateNodeActions(nodesActions: TreeNodeSelection): TreeNodeSelection {
    if (this.settings.actions && this.settings.actions.length) {
      // Remove all existing actions again
      this.nodesActionsContainer.selectAll('.node-action').selectChildren().remove();
      return nodesActions.enter()
        .append('g')
        .merge(nodesActions as d3selection.Selection<SVGGElement, TreeNode, any, any>)
        .attr('class', 'node-action')
        .on('mouseover', (evt: MouseEvent, node: TreeNode) => this.onMouseOverNode(node))
        .on('mouseout', (evt: MouseEvent, node: TreeNode) => this.onMouseOutOfNode(node))
        .attr('data-state-id', this.getNodeStateIdentifier)
        .attr('transform', (node: TreeNode) => this.getNodeActionTransform(node, this.settings.indentWidth, this.settings.nodeHeight))
    }
    return nodesActions.enter();
  }

  /**
   * This is a quick helper function to create custom action icons.
   */
  protected createIconAreaForAction(actionItem: any, iconIdentifier: string): void
  {
    const icon = actionItem
      .append('svg')
      .attr('class', 'node-icon-container')
      .attr('height', this.settings.icon.containerSize)
      .attr('width', this.settings.icon.containerSize)
      .attr('x', '0')
      .attr('y', '0');
    // improve usability by making the click area a 20px square
    icon
      .append('rect')
      .attr('height', this.settings.icon.containerSize)
      .attr('width', this.settings.icon.containerSize)
      .attr('y', '0')
      .attr('x', '0')
      .attr('class', 'node-icon-click');
    const nodeInner = icon
      .append('svg')
      .attr('height', this.settings.icon.size)
      .attr('width', this.settings.icon.size)
      .attr('y', (this.settings.icon.containerSize - this.settings.icon.size) / 2)
      .attr('x', (this.settings.icon.containerSize - this.settings.icon.size) / 2)
      .attr('class', 'node-icon-inner');
    nodeInner
      .append('use')
      .attr('class', 'node-icon')
      .attr('xlink:href', '#icon-' + iconIdentifier);
  }

  /**
   * Check whether node can be selected.
   * In some cases (e.g. selecting a parent) it should not be possible to select
   * element (as it's own parent).
   */
  protected isNodeSelectable(node: TreeNode): boolean {
    return true;
  }

  protected appendTextElement(nodes: TreeNodeSelection): TreeNodeSelection {
    return nodes
      .append('text')
      .attr('dx', this.textPosition)
      .attr('dy', 5)
      .attr('class', 'node-name')
      .on('click', (evt: MouseEvent, node: TreeNode) => {
        this.selectNode(node, true);
        this.focusNode(node);
        this.updateVisibleNodes();
      });
  }

  protected nodesUpdate(nodes: TreeNodeSelection): TreeNodeSelection {
    nodes = nodes
      .enter()
      .append('g')
      .attr('class', 'node')
      .attr('id', (node: TreeNode) => { return 'identifier-' + node.stateIdentifier; })
      .attr('role', 'treeitem')
      .attr('aria-owns', (node: TreeNode) => { return (node.hasChildren ? 'group-identifier-' + node.stateIdentifier : null); })
      .attr('aria-level', this.getNodeDepth)
      .attr('aria-setsize', this.getNodeSetsize)
      .attr('aria-posinset', this.getNodePositionInSet)
      .attr('aria-expanded', (node: TreeNode) => { return (node.hasChildren ? node.expanded : null); })
      .attr('transform', this.getNodeTransform)
      .attr('data-state-id', this.getNodeStateIdentifier)
      .attr('title', this.getNodeTitle)
      .on('mouseover', (evt: MouseEvent, node: TreeNode) => this.onMouseOverNode(node))
      .on('mouseout', (evt: MouseEvent, node: TreeNode) => this.onMouseOutOfNode(node))
      .on('contextmenu', (evt: MouseEvent, node: TreeNode) => {
        evt.preventDefault();
        this.dispatchEvent(new CustomEvent('typo3:svg-tree:node-context', {detail: {node: node}}));
      });

    nodes
      .append('text')
      .text((node: TreeNode) => { return node.readableRootline; })
      .attr('class', 'node-rootline')
      .attr('dx', 0)
      .attr('dy', (this.settings.nodeHeight / 2 * -1))
      .attr('visibility', (node: TreeNode) => node.readableRootline ? 'visible' : 'hidden')
    ;
    return nodes;
  }

  protected getNodeIdentifier(node: TreeNode): string {
    return node.identifier;
  }

  protected getNodeDepth(node: TreeNode): number {
    return node.depth;
  }

  protected getNodeSetsize(node: TreeNode): number {
    return node.siblingsCount;
  }

  protected getNodePositionInSet(node: TreeNode): number {
    return node.siblingsPosition;
  }

  protected getNodeStateIdentifier(node: TreeNode): string {
    return node.stateIdentifier;
  }

  protected getNodeLabel(node: TreeNode): string {
    let label = (node.prefix || '') + node.name + (node.suffix || '');
    // make a text node out of it, and strip out any HTML (this is because the return value uses html()
    // instead of text() which is needed to avoid XSS in a page title
    const labelNode = document.createElement('div');
    labelNode.textContent = label;
    label = labelNode.innerHTML;
    if (this.searchTerm) {
      const regexp = new RegExp(this.searchTerm, 'gi');
      label = label.replace(regexp, '<tspan class="node-highlight-text">$&</tspan>');
    }
    return label;
  }

  /**
   * Finds node by its stateIdentifier (e.g. "0_360")
   */
  protected getNodeByIdentifier(identifier: string): TreeNode|null {
    return this.nodes.find((node: TreeNode) => {
      return node.stateIdentifier === identifier;
    });
  }

  /**
   * Computes the tree node-bg class
   */
  protected getNodeBgClass(node: TreeNode, i: number, nodeBgClass: TreeNodeSelection): string {
    let bgClass = 'node-bg';
    let prevNode = null;
    let nextNode = null;

    if (typeof nodeBgClass === 'object') {
      prevNode = nodeBgClass.data()[i - 1];
      nextNode = nodeBgClass.data()[i + 1];
    }

    if (node.checked) {
      bgClass += ' node-selected';
    }

    if (node.focused) {
      bgClass += ' node-focused';
    }

    if ((prevNode && (node.depth > prevNode.depth)) || !prevNode) {
      node.firstChild = true;
      bgClass += ' node-first-child';
    }

    if ((nextNode && (node.depth > nextNode.depth)) || !nextNode) {
      node.lastChild = true;
      bgClass += ' node-last-child';
    }

    if (node.class) {
      bgClass += ' ' + node.class;
    }

    return bgClass;
  }

  protected getNodeTitle(node: TreeNode): string {
    return node.tip ? node.tip : 'uid=' + node.identifier;
  }

  protected getToggleVisibility(node: TreeNode): string {
    return node.hasChildren ? 'visible' : 'hidden';
  }

  protected getToggleClass(node: TreeNode): string {
    return 'node-toggle node-toggle--' + (node.expanded ? 'expanded' : 'collapsed')
      // Nessesary for testing framework can be removed after testing framework is adapted at some point
      + ' chevron ' + (node.expanded ? 'expanded' : 'collapsed');
  }

  /**
   * Returns a SVG path's 'd' attribute value
   *
   * @param {SvgTreeDataLink} link
   * @returns {String}
   */
  protected getLinkPath(link: SvgTreeDataLink): string {
    const target = {
      x: link.target.x,
      y: link.target.y
    };
    const path = [];
    path.push('M' + link.source.x + ' ' + link.source.y);
    path.push('V' + target.y);
    if (link.target.hasChildren) {
      path.push('H' + (target.x - 2));
    } else {
      path.push('H' + ((target.x + this.settings.indentWidth / 4) - 2));
    }
    return path.join(' ');
  }

  /**
   * Returns a 'transform' attribute value for the tree element (absolute positioning)
   *
   * @param {Node} node
   */
  protected getNodeTransform(node: TreeNode): string {
    return 'translate(' + (node.x || 0) + ',' + (node.y || 0) + ')';
  }

  /**
   * Returns a 'transform' attribute value for the node background element (absolute positioning)
   *
   * @param {Node} node
   * @param {number} indentWidth
   * @param {number} nodeHeight
   */
  protected getNodeBackgroundTransform(node: TreeNode, indentWidth: number, nodeHeight: number): string {

    let positionX = (indentWidth / 2 * -1);
    let positionY = (node.y || 0) - (nodeHeight / 2);

    // IMPORTANT
    //
    // The SVG spec does not support stroke alignment, and the stroke is always centered on the edge
    // this results in blurry edges with 1px borders. Setting shape rendering to crispEdges has
    // different results depending on the used browser.
    //
    // To resolve this issue need to:
    // 1. reduce the height -1px - done in SvgTree::updateNodeBgClass
    // 2. offset the element by 0.5px - done in SvgTree::getNodeBackgroundTransform
    positionY = positionY + 0.5;

    return 'translate(' + positionX + ', ' + positionY + ')';
  }

  /**
   * Returns a 'transform' attribute value for the node action element (absolute positioning)
   *
   * @param {Node} node
   * @param {number} indentWidth
   * @param {number} nodeHeight
   */
  protected getNodeActionTransform(node: TreeNode, indentWidth: number, nodeHeight: number): string {
    return 'translate(' + (indentWidth / 2 * -1) + ', ' + ((node.y || 0) - (nodeHeight / 2)) + ')';
  }


  /**
   * Event handler for clicking on a node's icon
   */
  protected clickOnIcon(node: TreeNode): void {
    this.dispatchEvent(new CustomEvent('typo3:svg-tree:node-context', {detail: {node: node}}));
  }

  /**
   * Event handler for collapsing or expanding nodes
   */
  protected handleNodeToggle(node: TreeNode): void {
    if (node.expanded) {
      this.hideChildren(node);
    } else {
      this.showChildren(node);
    }
    this.prepareDataForVisibleNodes();
    this.updateVisibleNodes();
  }

  /**
   * Adds missing SVG nodes
   *
   * @param {Selection} nodes
   * @returns {Selection}
   */
  protected enterSvgElements(nodes: TreeNodeSelection): TreeNodeSelection {
    if (this.settings.showIcons) {
      const iconsArray = Object.values(this.icons)
        .filter((icon: SvgTreeDataIcon): boolean => icon.icon !== '' && icon.icon !== null);
      const icons = this.iconsContainer
        .selectAll('.icon-def')
        .data(iconsArray, (icon: SvgTreeDataIcon) => icon.identifier);
      icons.exit().remove();

      icons
        .enter()
        .append('g')
        .attr('class', 'icon-def')
        .attr('id', (node: TreeNode) => 'icon-' + node.identifier)
        .append((node: TreeNode): SVGElement => {
          if (node.icon instanceof SVGElement) {
            return node.icon;
          }
          // Once all icons are real SVG Elements, this part can safely be removed
          const markup = '<svg>' + node.icon + '</svg>';
          const parser = new DOMParser();
          const dom = parser.parseFromString(markup, 'image/svg+xml');
          return dom.documentElement.firstChild as SVGElement;
        });
    }

    // create the node elements
    const nodeEnter = this.nodesUpdate(nodes);

    // append the toggle element
    let nodeToggle = nodeEnter
      .append('svg')
      .attr('class', 'node-toggle')
      .attr('y', (this.settings.icon.size / 2 * -1))
      .attr('x', (this.settings.icon.size / 2 * -1))
      .attr('visibility', this.getToggleVisibility)
      .attr('height', this.settings.icon.size)
      .attr('width', this.settings.icon.size)
      .on('click', (evt: MouseEvent, node: TreeNode) => this.handleNodeToggle(node));
    nodeToggle.append('use')
      .attr('class', 'node-toggle-icon')
      .attr('href', '#icon-actions-chevron-right');
    nodeToggle.append('rect')
      .attr('class', 'node-toggle-spacer')
      .attr('height', this.settings.icon.size)
      .attr('width', this.settings.icon.size)
      .attr('fill', 'transparent');

    // append the icon element
    if (this.settings.showIcons) {
      const nodeContainer = nodeEnter
        .append('svg')
        .attr('class', 'node-icon-container')
        .attr('title', this.getNodeTitle)
        .attr('height', '20')
        .attr('width', '20')
        .attr('x', '6')
        .attr('y', '-10')
        .attr('data-bs-toggle', 'tooltip')
        .on('click', (evt: MouseEvent, node: TreeNode) => {
          evt.preventDefault();
          this.clickOnIcon(node)
        });

      // improve usability by making the click area a 20px square
      nodeContainer
        .append('rect')
        .style('opacity', 0)
        .attr('width', '20')
        .attr('height', '20')
        .attr('y', '0')
        .attr('x', '0')
        .attr('class', 'node-icon-click');

      const nodeInner = nodeContainer
        .append('svg')
        .attr('height', '16')
        .attr('width', '16')
        .attr('y', '2')
        .attr('x', '2')
        .attr('class', 'node-icon-inner');

      nodeInner
        .append('use')
        .attr('class', 'node-icon')
        .attr('data-uid', this.getNodeIdentifier);

      const nodeIconOverlay = nodeInner
        .append('svg')
        .attr('height', '11')
        .attr('width', '11')
        .attr('y', '5')
        .attr('x', '5');

      nodeIconOverlay
        .append('use')
        .attr('class', 'node-icon-overlay');

      const nodeIconLocked = nodeInner
        .append('svg')
        .attr('height', '11')
        .attr('width', '11')
        .attr('y', '5')
        .attr('x', '5');

      nodeIconLocked
        .append('use')
        .attr('class', 'node-icon-locked');
    }

    Tooltip.initialize('[data-bs-toggle="tooltip"]', this.tooltipOptions);

    this.appendTextElement(nodeEnter);
    return nodes.merge(nodeEnter);
  }
  /**
   * Updates variables used for visible nodes calculation
   */
  private updateScrollPosition(): void {
    this.viewportHeight = this.getBoundingClientRect().height;
    this.scrollBottom = this.scrollTop + this.viewportHeight + (this.viewportHeight / 2);
    // wait for the tooltip to appear and disable tooltips when scrolling
    setTimeout(() => {
      Tooltip.hide(document.querySelector(<string>this.tooltipOptions.container).querySelectorAll('.bs-tooltip-end'));
    }, <number>this.tooltipOptions.delay)
  }

  /**
   * node background events if mouse enters a node
   */
  private onMouseOverNode(node: TreeNode): void {
    node.isOver = true;
    this.hoveredNode = node;

    let elementNodeBg = this.svg.select('.nodes-bg .node-bg[data-state-id="' + node.stateIdentifier + '"]');
    if (elementNodeBg.size()) {
      elementNodeBg.classed('node-over', true);
    }

    let elementNodeAction = this.nodesActionsContainer.select('.node-action[data-state-id="' + node.stateIdentifier + '"]');
    if (elementNodeAction.size()) {
      elementNodeAction.classed('node-action-over', true);
      // @todo: needs to be adapted for active nodes
      elementNodeAction.attr('fill', elementNodeBg.style('fill'));
    }
  }

  /**
   * node background event if mouse leaves a node
   */
  private onMouseOutOfNode(node: TreeNode): void {
    node.isOver = false;
    this.hoveredNode = null;

    let elementNodeBg = this.svg.select('.nodes-bg .node-bg[data-state-id="' + node.stateIdentifier + '"]');
    if (elementNodeBg.size()) {
      elementNodeBg.classed('node-over node-alert', false);
    }

    let elementNodeAction = this.nodesActionsContainer.select('.node-action[data-state-id="' + node.stateIdentifier + '"]');
    if (elementNodeAction.size()) {
      elementNodeAction.classed('node-action-over', false);
    }
  }

  /**
   * Add keydown handling to allow keyboard navigation inside the tree
   */
  private handleKeyboardInteraction(evt: KeyboardEvent) {
    const evtTarget = evt.target as SVGElement;
    let currentNode = d3selection.select(evtTarget).datum() as TreeNode;
    const charCodes = [
      KeyTypes.ENTER,
      KeyTypes.SPACE,
      KeyTypes.END,
      KeyTypes.HOME,
      KeyTypes.LEFT,
      KeyTypes.UP,
      KeyTypes.RIGHT,
      KeyTypes.DOWN
    ];
    if (charCodes.indexOf(evt.keyCode) === -1) {
      return;
    }
    evt.preventDefault();
    const parentDomNode = evtTarget.parentNode as SVGElement;
    // @todo Migrate to `evt.code`, see https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent/code
    switch (evt.keyCode) {
      case KeyTypes.END:
        // scroll to end, select last node
        this.scrollTop = this.lastElementChild.getBoundingClientRect().height + this.settings.nodeHeight - this.viewportHeight;
        parentDomNode.scrollIntoView({behavior: 'smooth', block: 'end'});
        this.focusNode(this.getNodeFromElement(parentDomNode.lastElementChild as SVGElement));
        this.updateVisibleNodes();
        break;
      case KeyTypes.HOME:
        // scroll to top, select first node
        this.scrollTo({'top': this.nodes[0].y, 'behavior': 'smooth'});
        this.prepareDataForVisibleNodes();
        this.focusNode(this.getNodeFromElement(parentDomNode.firstElementChild as SVGElement));
        this.updateVisibleNodes();
        break;
      case KeyTypes.LEFT:
        if (currentNode.expanded) {
          // collapse node if collapsible
          if (currentNode.hasChildren) {
            this.hideChildren(currentNode);
            this.prepareDataForVisibleNodes();
            this.updateVisibleNodes();
          }
        } else if (currentNode.parents.length > 0) {
          // go to parent node
          let parentNode = this.nodes[currentNode.parents[0]];
          this.scrollNodeIntoVisibleArea(parentNode, 'up');
          this.focusNode(parentNode);
          this.updateVisibleNodes();
        }
        break;
      case KeyTypes.UP:
        // select previous visible node on any level
        this.scrollNodeIntoVisibleArea(currentNode, 'up');
        if (evtTarget.previousSibling) {
          this.focusNode(this.getNodeFromElement(evtTarget.previousSibling as SVGElement));
          this.updateVisibleNodes();
        }
        break;
      case KeyTypes.RIGHT:
        if (currentNode.expanded) {
          // the current node is expanded, goto first child (next element on the list)
          this.scrollNodeIntoVisibleArea(currentNode, 'down');
          this.focusNode(this.getNodeFromElement(evtTarget.nextSibling as SVGElement));
          this.updateVisibleNodes();
        } else {
          if (currentNode.hasChildren) {
            // expand currentNode
            this.showChildren(currentNode);
            this.prepareDataForVisibleNodes();
            this.focusNode(this.getNodeFromElement(evtTarget as SVGElement));
            this.updateVisibleNodes();
          }
          //do nothing if node has no children
        }
        break;
      case KeyTypes.DOWN:
        // select next visible node on any level
        // check if node is at end of viewport and scroll down if so
        this.scrollNodeIntoVisibleArea(currentNode, 'down');
        if (evtTarget.nextSibling) {
          this.focusNode(this.getNodeFromElement(evtTarget.nextSibling as SVGElement));
          this.updateVisibleNodes();
        }
        break;
      case KeyTypes.ENTER:
      case KeyTypes.SPACE:
        this.selectNode(currentNode, true);
        this.focusNode(currentNode)
        break;
      default:
    }
  }

  /**
   * If node is at the top of the viewport and direction is up, scroll up by the height of one item
   * If node is at the bottom of the viewport and direction is down, scroll down by the height of one item
   */
  private scrollNodeIntoVisibleArea(node: TreeNode, direction: string = 'up'): void {
    let scrollTop = this.scrollTop;
    if (direction === 'up' && scrollTop > node.y - this.settings.nodeHeight) {
      scrollTop = node.y - this.settings.nodeHeight;
    } else if (direction === 'down' && scrollTop + this.viewportHeight <= node.y + (3 * this.settings.nodeHeight)) {
      scrollTop = scrollTop + this.settings.nodeHeight;
    } else {
      return;
    }
    this.scrollTo({'top': scrollTop, 'behavior': 'smooth'});
    this.updateVisibleNodes();
  }

  /**
   * Renders links(lines) between parent and child nodes and is also used for grouping the children
   * The line element of the first child is used as role=group node to group the children programmatically
   */
  private updateLinks() {
    const visibleLinks = this.data.links
      .filter((link: SvgTreeDataLink) => {
        return link.source.y <= this.scrollBottom && link.target.y >= this.scrollTop - this.settings.nodeHeight;
      })
      .map((link: SvgTreeDataLink) => {
        link.source.owns = link.source.owns || [];
        link.source.owns.push('identifier-' + link.target.stateIdentifier);
        return link;
      });
    const links = this.linksContainer.selectAll('.link').data(visibleLinks);
    // delete
    links.exit().remove();
    // create
    links.enter()
      .append('path')
      .attr('class', 'link')
      .attr('id', this.getGroupIdentifier)
      .attr('role', (link: SvgTreeDataLink): null|string => {
        return link.target.siblingsPosition === 1 && link.source.owns.length > 0 ? 'group' : null
      })
      .attr('aria-owns', (link: SvgTreeDataLink): null|string => {
        return link.target.siblingsPosition === 1 && link.source.owns.length > 0 ? link.source.owns.join(' ') : null
      })
      // create + update
      .merge(links as d3selection.Selection<any, any, any, any>)
      .attr('d', (link: SvgTreeDataLink) => this.getLinkPath(link));
  }

  /**
   * If the link target is the first child, set the group identifier.
   * The group with this id is used for grouping the siblings, thus the identifier uses the stateIdentifier of
   * the link source item.
   */
  private getGroupIdentifier(link: any): string|null {
    return link.target.siblingsPosition === 1 ? 'group-identifier-' + link.source.stateIdentifier : null;
  }

}


/**
 * A basic toolbar allowing to search / filter
 */
@customElement('typo3-backend-tree-toolbar')
export class Toolbar extends LitElement {
  @property({type: SvgTree}) tree: SvgTree = null;
  protected settings = {
    searchInput: '.search-input',
    filterTimeout: 450
  };

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
    }
  }

  protected render(): TemplateResult {
    /* eslint-disable @typescript-eslint/indent */
    return html`
      <div class="tree-toolbar">
        <div class="svg-toolbar__menu">
          <div class="svg-toolbar__search">
              <input type="search" class="form-control form-control-sm search-input" placeholder="${lll('tree.searchTermInfo')}">
          </div>
        </div>
        <div class="svg-toolbar__submenu">
          <a class="svg-toolbar__menuitem nav-link dropdown-toggle dropdown-toggle-no-chevron float-end" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"><typo3-backend-icon identifier="actions-menu-alternative" size="small"></typo3-backend-icon></a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <button class="dropdown-item" @click="${() => this.refreshTree()}">
                <typo3-backend-icon identifier="actions-refresh" size="small" class="icon icon-size-small"></typo3-backend-icon>
                ${lll('labels.refresh')}
              </button>
            </li>
            <li>
              <button class="dropdown-item" @click="${(evt: MouseEvent) => this.collapseAll(evt)}">
                <typo3-backend-icon identifier="apps-pagetree-category-collapse-all" size="small" class="icon icon-size-small"></typo3-backend-icon>
                ${lll('labels.collapse')}
              </button>
            </li>
          </ul>
        </div>
      </div>
    `;
  }

  protected refreshTree(): void {
    this.tree.refreshOrFilterTree();
  }

  protected collapseAll(evt: MouseEvent): void {
    evt.preventDefault();
    // Only collapse nodes that aren't on the root level
    this.tree.nodes.forEach((node: TreeNode) => {
      if (node.parentsStateIdentifier.length) {
        this.tree.hideChildren(node);
      }
    });
    this.tree.prepareDataForVisibleNodes();
    this.tree.updateVisibleNodes();
  }
}
