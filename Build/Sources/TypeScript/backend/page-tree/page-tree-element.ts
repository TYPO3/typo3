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

import {html, LitElement, TemplateResult, PropertyValues} from 'lit';
import {customElement, property, query} from 'lit/decorators';
import {until} from 'lit/directives/until';
import {lll} from '@typo3/core/lit-helper';
import {PageTree} from './page-tree';
import {TreeNode} from './../tree/tree-node';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import Persistent from '@typo3/backend/storage/persistent';
import {getRecordFromName} from '../module';
import ContextMenu from '../context-menu';
import * as d3selection from 'd3-selection';
import {KeyTypesEnum as KeyTypes} from '@typo3/backend/enum/key-types';
import {TreeNodeSelection, TreeWrapperSelection, Toolbar} from '../svg-tree';
import {DragDrop, DragDropHandler, DraggablePositionEnum} from '../tree/drag-drop';
import {D3DragEvent} from 'd3-drag';
import Modal from '../modal';
import Severity from '../severity';
import {ModuleStateStorage} from '../storage/module-state-storage';

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

/**
 * PageTree which allows for drag+drop, and in-place editing, as well as
 * tree highlighting from the outside
 */
@customElement('typo3-backend-navigation-component-pagetree-tree')
export class EditablePageTree extends PageTree {
  public nodeIsEdit: boolean;
  public dragDrop: PageTreeDragDrop;

  public selectFirstNode(): void {
    this.selectNode(this.nodes[0], true);
  }

  public sendChangeCommand(data: any): void {
    let params = '';
    let targetUid = 0;

    if (data.target) {
      targetUid = data.target.identifier;
      if (data.position === 'after') {
        targetUid = -targetUid;
      }
    }

    if (data.command === 'new') {
      params = '&data[pages][NEW_1][pid]=' + targetUid +
        '&data[pages][NEW_1][title]=' + encodeURIComponent(data.name) +
        '&data[pages][NEW_1][doktype]=' + data.type;
    } else if (data.command === 'edit') {
      params = '&data[pages][' + data.uid + '][' + data.nameSourceField + ']=' + encodeURIComponent(data.title);
    } else if (data.command === 'delete') {
      // @todo currently it's "If uid of deleted record (data.uid) is still selected, randomly select the first node"
      const moduleStateStorage = ModuleStateStorage.current('web');
      if (data.uid === moduleStateStorage.identifier) {
        this.selectFirstNode();
      }
      params = '&cmd[pages][' + data.uid + '][delete]=1';
    } else {
      params = 'cmd[pages][' + data.uid + '][' + data.command + ']=' + targetUid;
    }

    this.requestTreeUpdate(params).then((response) => {
      if (response && response.hasErrors) {
        this.errorNotification(response.messages, false);
        this.nodesContainer.selectAll('.node').remove();
        this.updateVisibleNodes();
        this.nodesRemovePlaceholder();
      } else {
        this.refreshOrFilterTree();
      }
    });
  }

  /**
   * Make the DOM element of the node given as parameter focusable and focus it
   */
  public switchFocusNode(node: TreeNode) {
    // Focus node only if it's not currently in edit mode
    if (!this.nodeIsEdit) {
      this.switchFocus(this.getNodeElement(node));
    }
  }

  public nodesUpdate(nodes: TreeNodeSelection): TreeNodeSelection {
    return super.nodesUpdate.call(this, nodes).call(this.initializeDragForNode());
  }

  public updateNodeBgClass(nodeBg: TreeNodeSelection) {
    return super.updateNodeBgClass.call(this, nodeBg).call(this.initializeDragForNode());
  }

  /**
   * Initializes a drag&drop when called on the page tree. Should be moved somewhere else at some point
   */
  public initializeDragForNode() {
    return this.dragDrop.connectDragHandler(new PageTreeNodeDragHandler(this, this.dragDrop))
  }

  public removeEditedText() {
    const inputWrapper = d3selection.selectAll('.node-edit');
    if (inputWrapper.size()) {
      try {
        inputWrapper.remove();
        this.nodeIsEdit = false;
      } catch (e) {
        // ...
      }
    }
  }

  /**
   * Event handler for double click on a node's label
   */
  protected appendTextElement(nodes: TreeNodeSelection): TreeNodeSelection {
    let clicks = 0;
    return super.appendTextElement(nodes)
      .on('click', (event, node: TreeNode) => {
        if (node.identifier === '0') {
          this.selectNode(node, true);
          return;
        }
        if (++clicks === 1) {
          setTimeout(() => {
            if (clicks === 1) {
              this.selectNode(node, true);
            } else {
              this.editNodeLabel(node);
            }
            clicks = 0;
          }, 300);
        }
      });
  }

  private sendEditNodeLabelCommand(node: TreeNode) {
    const params = '&data[pages][' + node.identifier + '][' + node.nameSourceField + ']=' + encodeURIComponent(node.newName);
    this.requestTreeUpdate(params, node)
      .then((response) => {
        if (response && response.hasErrors) {
          this.errorNotification(response.messages, false);
        } else {
          node.name = node.newName;
        }
        this.refreshOrFilterTree();
      });
  }

  private requestTreeUpdate(params: any, node: any = null): Promise<any> {
    // remove old node from svg tree
    this.nodesAddPlaceholder(node);
    return (new AjaxRequest(top.TYPO3.settings.ajaxUrls.record_process))
      .post(params, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
      })
      .then((response) => {
        return response.resolve();
      })
      .catch((error) => {
        this.errorNotification(error, true);
      });
  }

  private editNodeLabel(node: TreeNode) {
    if (!node.allowEdit) {
      return;
    }
    this.removeEditedText();
    this.nodeIsEdit = true;

    d3selection.select(this.svg.node().parentNode as HTMLElement)
      .append('input')
      .attr('class', 'node-edit')
      .style('top', () => {
        const top = node.y + this.settings.marginTop;
        return top + 'px';
      })
      .style('left', (node.x + this.textPosition + 5) + 'px')
      .style('width', this.settings.width - (node.x + this.textPosition + 20) + 'px')
      .style('height', this.settings.nodeHeight + 'px')
      .attr('type', 'text')
      .attr('value', node.name)
      .on('keydown', (event: KeyboardEvent) => {
        // @todo Migrate to `evt.code`, see https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent/code
        const code = event.keyCode;

        if (code === KeyTypes.ENTER || code === KeyTypes.TAB) {
          const target = event.target as HTMLInputElement;
          const newName = target.value.trim();
          this.nodeIsEdit = false;
          this.removeEditedText();
          if (newName.length && (newName !== node.name)) {
            node.nameSourceField = node.nameSourceField || 'title';
            node.newName = newName;
            this.sendEditNodeLabelCommand(node);
          }
        } else if (code === KeyTypes.ESCAPE) {
          this.nodeIsEdit = false;
          this.removeEditedText();
        }
      })
      .on('blur', (evt: FocusEvent) => {
        if (!this.nodeIsEdit) {
          return;
        }
        const target = evt.target as HTMLInputElement;
        const newName = target.value.trim();
        if (newName.length && (newName !== node.name)) {
          node.nameSourceField = node.nameSourceField || 'title';
          node.newName = newName;
          this.sendEditNodeLabelCommand(node);
        }
        this.removeEditedText();
      })
      .node()
      .select();
  }
}

interface Configuration {
  [keys: string]: any;
}

@customElement(navigationComponentName)
export class PageTreeNavigationComponent extends LitElement {
  @property({type: String}) mountPointPath: string = null;

  @query('.svg-tree-wrapper') tree: EditablePageTree;
  @query('typo3-backend-navigation-component-pagetree-toolbar') toolbar: PageTreeToolbar;

  private configuration: Configuration = null;

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
        ${until(this.renderTree(), this.renderLoader())}
      </div>
    `;
  }

  protected getConfiguration(): Promise<Configuration> {
    if (this.configuration !== null) {
      return Promise.resolve(this.configuration);
    }

    const configurationUrl = top.TYPO3.settings.ajaxUrls.page_tree_configuration;
    return (new AjaxRequest(configurationUrl)).get()
      .then(async (response: AjaxResponse): Promise<Configuration> => {
        const configuration = await response.resolve('json');
        this.configuration = configuration;
        this.mountPointPath = configuration.temporaryMountPoint || null;
        return configuration;
      });
  }

  protected renderTree(): Promise<TemplateResult> {
    return this.getConfiguration()
      .then((configuration: Configuration): TemplateResult => {
        // Initialize the toolbar once the tree was rendered
        const initialized = () => {
          this.tree.dragDrop = new PageTreeDragDrop(this.tree);
          this.toolbar.tree = this.tree;
          this.tree.addEventListener('typo3:svg-tree:expand-toggle', this.toggleExpandState);
          this.tree.addEventListener('typo3:svg-tree:node-selected', this.loadContent);
          this.tree.addEventListener('typo3:svg-tree:node-context', this.showContextMenu);
          this.tree.addEventListener('typo3:svg-tree:nodes-prepared', this.selectActiveNode);
        }

        return html`
          <div>
            <typo3-backend-navigation-component-pagetree-toolbar id="typo3-pagetree-toolbar" class="svg-toolbar" .tree="${this.tree}"></typo3-backend-navigation-component-pagetree-toolbar>
            <div id="typo3-pagetree-treeContainer" class="navigation-tree-container">
              ${this.renderMountPoint()}
              <typo3-backend-navigation-component-pagetree-tree id="typo3-pagetree-tree" class="svg-tree-wrapper" .setup=${configuration} @svg-tree:initialized=${initialized}></typo3-backend-navigation-component-pagetree-tree>
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

  private refresh = (): void => {
    this.tree.refreshOrFilterTree();
  }

  private setMountPoint = (e: CustomEvent): void => {
    this.setTemporaryMountPoint(e.detail.pageId as number);
  }

  private selectFirstNode = (): void => {
    this.tree.selectFirstNode();
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

  private toggleExpandState = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNode;
    if (node) {
      Persistent.set('BackendComponents.States.Pagetree.stateHash.' + node.stateIdentifier, (node.expanded ? '1' : '0'));
    }
  }

  private loadContent = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNode;
    if (!node?.checked) {
      return;
    }
    //remember the selected page in the global state
    ModuleStateStorage.update('web', node.identifier, true, node.stateIdentifier.split('_')[0]);

    if (evt.detail.propagate === false) {
      return;
    }

    // Load the currently selected module with the updated URL
    const moduleMenu = top.TYPO3.ModuleMenu.App;
    let contentUrl = getRecordFromName(moduleMenu.getCurrentModule()).link;
    contentUrl += contentUrl.includes('?') ? '&' : '?';
    top.TYPO3.Backend.ContentContainer.setUrl(contentUrl + 'id=' + node.identifier);
  }

  private showContextMenu = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNode;
    if (!node) {
      return;
    }
    ContextMenu.show(
      node.itemType,
      parseInt(node.identifier, 10),
      'tree',
      '',
      '',
      this.tree.getNodeElement(node)
    );
  }

  /**
   * Event listener called for each loaded node,
   * here used to mark node remembered in ModuleState as selected
   */
  private selectActiveNode = (evt: CustomEvent): void => {
    const selectedNodeIdentifier = ModuleStateStorage.current('web').selection;
    let nodes = evt.detail.nodes as Array<TreeNode>;
    evt.detail.nodes = nodes.map((node: TreeNode) => {
      if (node.stateIdentifier === selectedNodeIdentifier) {
        node.checked = true;
      }
      return node;
    });
  }
}

@customElement('typo3-backend-navigation-component-pagetree-toolbar')
class PageTreeToolbar extends Toolbar {
  @property({type: EditablePageTree}) tree: EditablePageTree = null;

  public initializeDragDrop(dragDrop: PageTreeDragDrop): void
  {
    if (this.tree?.settings?.doktypes?.length) {
      this.tree.settings.doktypes.forEach((item: any) => {
        if (item.icon) {
          const htmlElement = this.querySelector('[data-tree-icon="' + item.icon + '"]');
          d3selection.select(htmlElement).call(this.dragToolbar(item, dragDrop));
        } else {
          console.warn('Missing icon definition for doktype: ' + item.nodeType);
        }
      });
    }
  }

  protected updated(changedProperties: PropertyValues): void {
    changedProperties.forEach((oldValue, propName) => {
      if (propName === 'tree' && this.tree !== null) {
        this.initializeDragDrop(this.tree.dragDrop);
      }
    });
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
          ${this.tree?.settings?.doktypes?.length
            ? this.tree.settings.doktypes.map((item: any) => {
              return html`
                <div class="svg-toolbar__menuitem svg-toolbar__drag-node" data-tree-icon="${item.icon}" data-node-type="${item.nodeType}"
                     title="${item.title}" tooltip="${item.tooltip}">
                  <typo3-backend-icon identifier="${item.icon}" size="small"></typo3-backend-icon>
                </div>
              `;
              })
            : ''
          }
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

  /**
   * Register Drag and drop for new elements of toolbar
   * Returns method from d3drag
   */
  private dragToolbar(item: any, dragDrop: PageTreeDragDrop) {
    return dragDrop.connectDragHandler(new ToolbarDragHandler(item, this.tree, dragDrop));
  }
}


type TreeNodeDragEvent = D3DragEvent<SVGElement, any, TreeNode>;

interface NodeCreationOptions {
  type: string,
  name: string,
  title?: string;
  tooltip: string,
  icon: string,
  position: DraggablePositionEnum,
  target: TreeNode
}

interface NodePositionOptions {
  node: TreeNode,
  target: TreeNode,
  uid: string,
  position: DraggablePositionEnum,
  command: string
}

interface NodeTargetPosition {
  target: TreeNode,
  position: DraggablePositionEnum
}

/**
 * Extends Drag&Drop functionality for Page Tree positioning when dropping
 */
class PageTreeDragDrop extends DragDrop {
  public changeNodePosition(droppedNode: TreeNode, command: string = ''): null|NodePositionOptions {
    const nodes = this.tree.nodes;
    const uid = this.tree.settings.nodeDrag.identifier;
    let position = this.tree.settings.nodeDragPosition;
    let target = droppedNode || this.tree.settings.nodeDrag;

    if (uid === target.identifier && command !== 'delete') {
      return null;
    }

    if (position === DraggablePositionEnum.BEFORE) {
      const index = nodes.indexOf(droppedNode);
      const positionAndTarget = this.setNodePositionAndTarget(index);
      if (positionAndTarget === null) {
        return null;
      }
      position = positionAndTarget.position;
      target = positionAndTarget.target;
    }

    return {
      node: this.tree.settings.nodeDrag,
      uid: uid, // dragged node id
      target: target, // hovered node
      position: position, // before, in, after
      command: command // element is copied or moved
    }
  }

  /**
   * Returns Array of position and target node
   *
   * @param {number} index of node which is over mouse
   * @returns {Array} [position, target]
   * @todo this should be moved into PageTree.js
   */
  public setNodePositionAndTarget(index: number): null|NodeTargetPosition {
    const nodes = this.tree.nodes;
    const nodeOver = nodes[index];
    const nodeOverDepth = nodeOver.depth;
    if (index > 0) {
      index--;
    }
    const nodeBefore = nodes[index];
    const nodeBeforeDepth = nodeBefore.depth;
    const target = this.tree.nodes[index];

    if (nodeBeforeDepth === nodeOverDepth) {
      return {position: DraggablePositionEnum.AFTER, target};
    } else if (nodeBeforeDepth < nodeOverDepth) {
      return {position: DraggablePositionEnum.INSIDE, target};
    } else {
      for (let i = index; i >= 0; i--) {
        if (nodes[i].depth === nodeOverDepth) {
          return {position: DraggablePositionEnum.AFTER, target: this.tree.nodes[i]};
        } else if (nodes[i].depth < nodeOverDepth) {
          return {position: DraggablePositionEnum.AFTER, target: nodes[i]};
        }
      }
    }
    return null;
  }
}

/**
 * Main Handler for the toolbar when creating new items
 */
class ToolbarDragHandler implements DragDropHandler {
  public startDrag: boolean = false;
  public startPageX: number = 0;
  public startPageY: number = 0;
  private readonly id: string = '';
  private readonly name: string = '';
  private readonly tooltip: string = '';
  private readonly icon: string = '';
  private isDragged: boolean = false;
  private dragDrop: PageTreeDragDrop;
  private tree: EditablePageTree;

  constructor(item: any, tree: EditablePageTree, dragDrop: PageTreeDragDrop) {
    this.id = item.nodeType;
    this.name = item.title;
    this.tooltip = item.tooltip;
    this.icon = item.icon;
    this.tree = tree;
    this.dragDrop = dragDrop;
  }

  public dragStart(event: TreeNodeDragEvent): boolean {
    this.isDragged = false;
    this.startDrag = false;
    this.startPageX = event.sourceEvent.pageX;
    this.startPageY = event.sourceEvent.pageY;
    return true;
  }

  public dragDragged(event: TreeNodeDragEvent): boolean {
    if (this.dragDrop.isDragNodeDistanceMore(event, this)) {
      this.startDrag = true;
    } else {
      return false;
    }

    // Add the draggable element
    if (this.isDragged === false) {
      this.isDragged = true;
      this.dragDrop.createDraggable('#icon-' + this.icon, this.name);
    }
    this.dragDrop.openNodeTimeout();
    this.dragDrop.updateDraggablePosition(event);
    this.dragDrop.changeNodeClasses(event);
    return true;
  }

  public dragEnd(event: TreeNodeDragEvent): boolean {
    if (!this.startDrag) {
      return false;
    }

    this.isDragged = false;
    this.dragDrop.removeNodeDdClass();
    if (this.tree.settings.allowDragMove !== true || !this.tree.hoveredNode || !this.tree.isOverSvg) {
      return false;
    }
    if (this.tree.settings.canNodeDrag) {
      this.addNewNode({
        type: this.id,
        name: this.name,
        tooltip: this.tooltip,
        icon: this.icon,
        position: this.tree.settings.nodeDragPosition,
        target: this.tree.hoveredNode
      });
    }
    return true;
  }

  /**
   * Add new node to the tree (used in drag+drop)
   *
   * @type {Object} options
   * @private
   */
  private addNewNode(options: NodeCreationOptions): void {
    const target = options.target;
    let index = this.tree.nodes.indexOf(target);
    const newNode = {} as TreeNode;
    newNode.command = 'new';
    newNode.type = options.type;
    newNode.identifier = '-1';
    newNode.target = target;
    newNode.parents = target.parents;
    newNode.parentsStateIdentifier = target.parentsStateIdentifier;
    newNode.depth = target.depth;
    newNode.position = options.position;
    newNode.name = (typeof options.title !== 'undefined') ? options.title : TYPO3.lang['tree.defaultPageTitle'];
    newNode.y = newNode.y || newNode.target.y;
    newNode.x = newNode.x || newNode.target.x;

    this.tree.nodeIsEdit = true;

    if (options.position === DraggablePositionEnum.INSIDE) {
      newNode.depth++;
      newNode.parents.unshift(index);
      newNode.parentsStateIdentifier.unshift(this.tree.nodes[index].stateIdentifier);
      this.tree.nodes[index].hasChildren = true;
      this.tree.showChildren(this.tree.nodes[index]);
    }

    if (options.position === DraggablePositionEnum.INSIDE || options.position === DraggablePositionEnum.AFTER) {
      index++;
    }

    if (options.icon) {
      newNode.icon = options.icon;
    }

    if (newNode.position === DraggablePositionEnum.BEFORE) {
      const positionAndTarget = this.dragDrop.setNodePositionAndTarget(index);
      // @todo Check whether an error should be thrown in case of `null`
      if (positionAndTarget !== null) {
        newNode.position = positionAndTarget.position;
        newNode.target = positionAndTarget.target;
      }
    }

    this.tree.nodes.splice(index, 0, newNode);
    this.tree.setParametersNode();
    this.tree.prepareDataForVisibleNodes();
    this.tree.updateVisibleNodes();
    this.tree.removeEditedText();

    d3selection.select(this.tree.svg.node().parentNode as HTMLElement)
      .append('input')
      .attr('class', 'node-edit')
      .style('top', newNode.y + this.tree.settings.marginTop + 'px')
      .style('left', newNode.x + this.tree.textPosition + 5 + 'px')
      .style('width', this.tree.settings.width - (newNode.x + this.tree.textPosition + 20) + 'px')
      .style('height', this.tree.settings.nodeHeight + 'px')
      .attr('text', 'text')
      .attr('value', newNode.name)
      .on('keydown', (evt: KeyboardEvent) => {
        const target = evt.target as HTMLInputElement;
        const code = evt.keyCode;
        if (code === 13 || code === 9) { // enter || tab
          this.tree.nodeIsEdit = false;
          const newName = target.value.trim();
          if (newName.length) {
            newNode.name = newName;
            this.tree.removeEditedText();
            this.tree.sendChangeCommand(newNode);
          } else {
            this.removeNode(newNode);
          }
        } else if (code === 27) { // esc
          this.tree.nodeIsEdit = false;
          this.removeNode(newNode);
        }
      })
      .on('blur', (evt: FocusEvent) => {
        if (this.tree.nodeIsEdit && (this.tree.nodes.indexOf(newNode) > -1)) {
          const target = evt.target as HTMLInputElement;
          const newName = target.value.trim();
          if (newName.length) {
            newNode.name = newName;
            this.tree.removeEditedText();
            this.tree.sendChangeCommand(newNode);
          } else {
            this.removeNode(newNode);
          }
        }
      })
      .node()
      .select();
  }

  private removeNode(newNode: TreeNode) {
    let index = this.tree.nodes.indexOf(newNode);
    // if newNode is only one child
    if (this.tree.nodes[index - 1].depth != newNode.depth
      && (!this.tree.nodes[index + 1] || this.tree.nodes[index + 1].depth != newNode.depth)) {
      this.tree.nodes[index - 1].hasChildren = false;
    }
    this.tree.nodes.splice(index, 1);
    this.tree.setParametersNode();
    this.tree.prepareDataForVisibleNodes();
    this.tree.updateVisibleNodes();
    this.tree.removeEditedText();
  };
}

/**
 * Drag and drop for nodes (copy/move) including the deleting / drop functionality.
 */
class PageTreeNodeDragHandler implements DragDropHandler {
  public startDrag: boolean = false;
  public startPageX: number = 0;
  public startPageY: number = 0;

  /**
   * SVG <g> container for deleting drop zone
   *
   * @type {Selection}
   */
  private dropZoneDelete: null|TreeWrapperSelection<SVGGElement>;
  private isDragged: boolean = false;
  private tree: any;
  private dragDrop: PageTreeDragDrop;
  private nodeIsOverDelete: boolean = false;

  constructor(tree: any, dragDrop: PageTreeDragDrop) {
    this.tree = tree;
    this.dragDrop = dragDrop;
  }

  public dragStart(event: TreeNodeDragEvent): boolean {
    const node = event.subject;
    if (this.tree.settings.allowDragMove !== true || node.depth === 0) {
      return false;
    }
    this.dropZoneDelete = null;

    if (node.allowDelete) {
      this.dropZoneDelete = this.tree.nodesContainer
        .select('.node[data-state-id="' + node.stateIdentifier + '"]')
        .append('g')
        .attr('class', 'nodes-drop-zone')
        .attr('height', this.tree.settings.nodeHeight);
      this.nodeIsOverDelete = false;
      this.dropZoneDelete.append('rect')
        .attr('height', this.tree.settings.nodeHeight)
        .attr('width', '50px')
        .attr('x', 0)
        .attr('y', 0)
        .on('mouseover', () => {
          this.nodeIsOverDelete = true;
        })
        .on('mouseout', () => {
          this.nodeIsOverDelete = false;
        });

      this.dropZoneDelete.append('text')
        .text(TYPO3.lang.deleteItem)
        .attr('dx', 5)
        .attr('dy', 15);

      this.dropZoneDelete.node().dataset.open = 'false';
      this.dropZoneDelete.node().style.transform = this.getDropZoneCloseTransform(node);
    }

    this.startPageX = event.sourceEvent.pageX;
    this.startPageY = event.sourceEvent.pageY;
    this.startDrag = false;
    return true;
  };

  public dragDragged(event: TreeNodeDragEvent): boolean {
    const node = event.subject;
    if (this.dragDrop.isDragNodeDistanceMore(event, this)) {
      this.startDrag = true;
    } else {
      return false;
    }

    if (this.tree.settings.allowDragMove !== true || node.depth === 0) {
      return false;
    }

    this.tree.settings.nodeDrag = node;

    const nodeBg = this.tree.svg.node().querySelector('.node-bg[data-state-id="' + node.stateIdentifier + '"]');
    const nodeDd = this.tree.svg.node().parentNode.querySelector('.node-dd');

    // Create the draggable
    if (!this.isDragged) {
      this.isDragged = true;
      this.dragDrop.createDraggable(this.tree.getIconId(node), node.name);
      nodeBg.classList.add('node-bg--dragging');
    }

    this.tree.settings.nodeDragPosition = false;
    this.dragDrop.openNodeTimeout();
    this.dragDrop.updateDraggablePosition(event);

    if (node.isOver
      || (this.tree.hoveredNode && this.tree.hoveredNode.parentsStateIdentifier.indexOf(node.stateIdentifier) !== -1)
      || !this.tree.isOverSvg) {

      this.dragDrop.addNodeDdClass(nodeDd, 'nodrop');

      if (!this.tree.isOverSvg) {
        this.tree.nodesBgContainer.selectAll('.node-bg__border').style('display', 'none');
      }

      if (this.dropZoneDelete && this.dropZoneDelete.node().dataset.open !== 'true' && this.tree.isOverSvg) {
        this.animateDropZone('show', this.dropZoneDelete.node(), node);
      }
    } else if (!this.tree.hoveredNode) {
      this.dragDrop.addNodeDdClass(nodeDd, 'nodrop');
      this.tree.nodesBgContainer.selectAll('.node-bg__border').style('display', 'none');
    } else if (this.dropZoneDelete && this.dropZoneDelete.node().dataset.open !== 'false') {
      this.animateDropZone('hide', this.dropZoneDelete.node(), node);
    }
    this.dragDrop.changeNodeClasses(event);
    return true;
  }

  public dragEnd(event: TreeNodeDragEvent): boolean {
    const node = event.subject;
    if (this.dropZoneDelete && this.dropZoneDelete.node().dataset.open === 'true') {
      const dropZone = this.dropZoneDelete;
      this.animateDropZone('hide', this.dropZoneDelete.node(), node, () => {
        dropZone.remove();
        this.dropZoneDelete = null;
      });
    } else if (this.dropZoneDelete && this.dropZoneDelete.node().dataset.open === 'false') {
      this.dropZoneDelete.remove();
      this.dropZoneDelete = null;
    } else {
      this.dropZoneDelete = null;
    }

    if (!this.startDrag || this.tree.settings.allowDragMove !== true || node.depth === 0) {
      return false;
    }

    const droppedNode = this.tree.hoveredNode;
    this.isDragged = false;
    this.dragDrop.removeNodeDdClass();

    if (
      !(node.isOver
        || (droppedNode && droppedNode.parentsStateIdentifier.indexOf(node.stateIdentifier) !== -1)
        || !this.tree.settings.canNodeDrag
        || !this.tree.isOverSvg
      )
    ) {
      const options = this.dragDrop.changeNodePosition(droppedNode, '');
      if (options === null) {
        return false;
      }
      let modalText = options.position === DraggablePositionEnum.INSIDE ? TYPO3.lang['mess.move_into'] : TYPO3.lang['mess.move_after'];
      modalText = modalText.replace('%s', options.node.name).replace('%s', options.target.name);

      Modal.confirm(
        TYPO3.lang.move_page,
        modalText,
        Severity.warning, [
          {
            text: TYPO3.lang['labels.cancel'] || 'Cancel',
            active: true,
            btnClass: 'btn-default',
            name: 'cancel'
          },
          {
            text: TYPO3.lang['cm.copy'] || 'Copy',
            btnClass: 'btn-warning',
            name: 'copy'
          },
          {
            text: TYPO3.lang['labels.move'] || 'Move',
            btnClass: 'btn-warning',
            name: 'move'
          }
        ])
        .on('button.clicked', (e: JQueryEventObject) => {
          const target = e.target as HTMLInputElement;
          if (target.name === 'move') {
            options.command = 'move';
            this.tree.sendChangeCommand(options);
          } else if (target.name === 'copy') {
            options.command = 'copy';
            this.tree.sendChangeCommand(options);
          }
          Modal.dismiss();
        });
    } else if (this.nodeIsOverDelete) {
      const options = this.dragDrop.changeNodePosition(droppedNode, 'delete');
      if (options === null) {
        return false;
      }
      if (this.tree.settings.displayDeleteConfirmation) {
        const $modal = Modal.confirm(
          TYPO3.lang['mess.delete.title'],
          TYPO3.lang['mess.delete'].replace('%s', options.node.name),
          Severity.warning, [
            {
              text: TYPO3.lang['labels.cancel'] || 'Cancel',
              active: true,
              btnClass: 'btn-default',
              name: 'cancel'
            },
            {
              text: TYPO3.lang.delete || 'Delete',
              btnClass: 'btn-warning',
              name: 'delete'
            }
          ]);
        $modal.on('button.clicked', (e: JQueryEventObject) => {
          const target = e.target as HTMLInputElement;
          if (target.name === 'delete') {
            this.tree.sendChangeCommand(options);
          }
          Modal.dismiss();
        });
      } else {
        this.tree.sendChangeCommand(options);
      }
    }
    return true;
  }

  /**
   * Returns deleting drop zone open 'transform' attribute value
   */
  private getDropZoneOpenTransform(node: TreeNode): string {
    const svgWidth = parseFloat(this.tree.svg.style('width')) || 300;
    return 'translate(' + (svgWidth - 58 - node.x) + 'px, -10px)';
  }

  /**
   * Returns deleting drop zone close 'transform' attribute value
   */
  private getDropZoneCloseTransform(node: TreeNode): string {
    const svgWidth = parseFloat(this.tree.svg.style('width')) || 300;
    return 'translate(' + (svgWidth - node.x) + 'px, -10px)';
  }

  /**
   * Animates the drop zone next to given node
   */
  private animateDropZone(action: string, dropZone: SVGElement, node: TreeNode, onfinish: Function = null) {
    dropZone.classList.add('animating');
    dropZone.dataset.open = (action === 'show') ? 'true' : 'false';
    let keyframes = [
      { transform: this.getDropZoneCloseTransform(node) },
      { transform: this.getDropZoneOpenTransform(node) }
    ];
    if (action !== 'show') {
      keyframes = keyframes.reverse();
    }
    const done = function() {
      dropZone.style.transform = keyframes[1].transform;
      dropZone.classList.remove('animating');
      onfinish && onfinish();
    };
    if ('animate' in dropZone) {
      dropZone.animate(keyframes, {
        duration: 300,
        easing: 'cubic-bezier(.02, .01, .47, 1)'
      }).onfinish = done;
    } else {
      done();
    }
  }
}
