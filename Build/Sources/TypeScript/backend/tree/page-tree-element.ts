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
import { customElement, property, query } from 'lit/decorators';
import { until } from 'lit/directives/until';
import { lll } from '@typo3/core/lit-helper';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Persistent from '@typo3/backend/storage/persistent';
import { ModuleUtility } from '@typo3/backend/module';
import ContextMenu from '../context-menu';
import { PageTree } from '@typo3/backend/tree/page-tree';
import { TreeNodeCommandEnum, TreeNodePositionEnum, type TreeNodeInterface } from '@typo3/backend/tree/tree-node';
import { TreeToolbar } from '@typo3/backend/tree/tree-toolbar';
import { TreeModuleState } from '@typo3/backend/tree/tree-module-state';
import Modal from '../modal';
import Severity from '../severity';
import { ModuleStateStorage } from '@typo3/backend/storage/module-state-storage';
import { DataTransferTypes } from '@typo3/backend/enum/data-transfer-types';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { DragTooltipMetadata } from '@typo3/backend/drag-tooltip';
import type { DataTransferStringItem } from '@typo3/backend/tree/tree';
import 'bootstrap'; // for data-bs-toggle="dropdown"

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

interface NodeChangeCommandDataInterface {
  command: TreeNodeCommandEnum,
  node: TreeNodeInterface,
  target?: TreeNodeInterface,
  position?: TreeNodePositionEnum,
  title?: string,
}

interface NodePositionOptions extends NodeChangeCommandDataInterface {
  command: TreeNodeCommandEnum.NEW | TreeNodeCommandEnum.COPY | TreeNodeCommandEnum.MOVE,
  target: TreeNodeInterface
  position: TreeNodePositionEnum,
}

interface NodeDeleteOptions extends NodeChangeCommandDataInterface {
  command: TreeNodeCommandEnum.DELETE,
}

interface NodeEditOptions extends NodeChangeCommandDataInterface {
  command: TreeNodeCommandEnum.EDIT,
  title: string,
}
interface NodeNewOptions extends NodePositionOptions {
  command: TreeNodeCommandEnum.NEW,
  title: string,
  position: TreeNodePositionEnum,
  doktype: number,
}

/**
 * PageTree which allows for drag+drop, and in-place editing, as well as
 * tree highlighting from the outside
 */
@customElement('typo3-backend-navigation-component-pagetree-tree')
export class EditablePageTree extends PageTree {
  protected override allowNodeEdit: boolean = true;
  protected override allowNodeDrag: boolean = true;
  protected override allowNodeSorting: boolean = true;

  public sendChangeCommand(data: NodeChangeCommandDataInterface): void {
    let params: string = '';
    let targetUid: string = '0';

    if (data.target) {
      targetUid = data.target.identifier;
      if (data.position === TreeNodePositionEnum.BEFORE) {
        const previousNode = this.getPreviousNode(data.target);
        targetUid = ((previousNode.depth === data.target.depth) ? '-' : '') + previousNode.identifier;
      }
      else if (data.position === TreeNodePositionEnum.AFTER) {
        targetUid = '-' + targetUid;
      }
    }

    if (data.command === TreeNodeCommandEnum.NEW) {
      const newData = data as NodeNewOptions;
      params = '&data[pages][' + data.node.identifier + '][pid]=' + encodeURIComponent(targetUid) +
        '&data[pages][' + data.node.identifier + '][title]=' + encodeURIComponent(newData.title) +
        '&data[pages][' + data.node.identifier + '][doktype]=' + encodeURIComponent(newData.doktype);
    } else if (data.command === TreeNodeCommandEnum.EDIT) {
      params = '&data[pages][' + data.node.identifier + '][title]=' + encodeURIComponent(data.title);
    } else if (data.command === TreeNodeCommandEnum.DELETE) {
      // @todo currently it's "If uid of deleted record (data.uid) is still selected, randomly select the first node"
      const moduleStateStorage = ModuleStateStorage.current('web');
      if (data.node.identifier === moduleStateStorage.identifier) {
        this.selectFirstNode();
      }
      params = '&cmd[pages][' + data.node.identifier + '][delete]=1';
    } else {
      params = 'cmd[pages][' + data.node.identifier + '][' + data.command + ']=' + targetUid;
    }

    this.requestTreeUpdate(params).then((response) => {
      if (response && response.hasErrors) {
        this.errorNotification(response.messages);
      } else {
        if (data.command === TreeNodeCommandEnum.NEW) {
          const parentNode = this.getParentNode(data.node);
          parentNode.loaded = false;
          this.loadChildren(parentNode);
        } else {
          this.refreshOrFilterTree();
        }
      }
    });
  }

  /**
   * Initializes a drag&drop when called on the page tree. Should be moved somewhere else at some point
   */
  public initializeDragForNode() {
    throw new Error('unused');
  }

  protected override async handleNodeEdit(node: TreeNodeInterface, newName: string): Promise<void> {
    node.__loading = true;

    if (node.identifier.startsWith('NEW')) {
      const target = this.getPreviousNode(node);
      const position = (node.depth === target.depth) ? TreeNodePositionEnum.AFTER : TreeNodePositionEnum.INSIDE;
      const options: NodeNewOptions = {
        command: TreeNodeCommandEnum.NEW,
        node: node,
        title: newName,
        position: position,
        target: target,
        doktype: node.doktype
      };
      await this.sendChangeCommand(options);
    } else {
      const options: NodeEditOptions = {
        command: TreeNodeCommandEnum.EDIT,
        node: node,
        title: newName,
      };
      await this.sendChangeCommand(options);
    }
    node.__loading = false;
  }

  protected override createDataTransferItemsFromNode(node: TreeNodeInterface): DataTransferStringItem[] {
    return [
      {
        type: DataTransferTypes.treenode,
        data: this.getNodeTreeIdentifier(node),
      },
      {
        type: DataTransferTypes.pages,
        data: JSON.stringify({
          records: [
            {
              identifier: node.identifier,
              tablename: 'pages',
            }
          ]
        })
      },
    ];
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  protected override async handleNodeAdd(node: TreeNodeInterface, target: TreeNodeInterface, position: TreeNodePositionEnum): Promise<void> {
    this.updateComplete.then(() => {
      this.editNode(node);
    });
  }

  protected override handleNodeDelete(node: TreeNodeInterface): void {
    const options: NodeDeleteOptions = {
      node: node,
      command: TreeNodeCommandEnum.DELETE
    };

    if (this.settings.displayDeleteConfirmation) {
      const modal = Modal.confirm(
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
        ]
      );
      modal.addEventListener('button.clicked', (e: Event) => {
        const target = e.target as HTMLInputElement;
        if (target.name === 'delete') {
          this.sendChangeCommand(options);
        }
        Modal.dismiss();
      });
    } else {
      this.sendChangeCommand(options);
    }
  }

  protected override handleNodeMove(
    node: TreeNodeInterface,
    target: TreeNodeInterface,
    position: TreeNodePositionEnum
  ): void {
    const options: NodePositionOptions = {
      node: node,
      target: target,
      position: position,
      command: TreeNodeCommandEnum.MOVE
    };

    let modalText = '';
    switch(position) {
      case TreeNodePositionEnum.BEFORE:
        modalText = TYPO3.lang['mess.move_before'];
        break;
      case TreeNodePositionEnum.AFTER:
        modalText = TYPO3.lang['mess.move_after'];
        break;
      default:
        modalText = TYPO3.lang['mess.move_into'];
        break;
    }
    modalText = modalText.replace('%s', node.name).replace('%s', target.name);

    const modal = Modal.confirm(
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
      ]
    );

    modal.addEventListener('button.clicked', (e: JQueryEventObject) => {
      const target = e.target as HTMLInputElement;
      if (target.name === 'move') {
        options.command = TreeNodeCommandEnum.MOVE;
        this.sendChangeCommand(options);
      } else if (target.name === 'copy') {
        options.command = TreeNodeCommandEnum.COPY;
        this.sendChangeCommand(options);
      }
      modal.hideModal();
    });
  }

  private requestTreeUpdate(params: any): Promise<any> {
    return (new AjaxRequest(top.TYPO3.settings.ajaxUrls.record_process))
      .post(params, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      })
      .then((response) => {
        return response.resolve();
      })
      .catch((error) => {
        this.errorNotification(error);
        this.loadData();
      });
  }
}

interface Configuration {
  [keys: string]: any;
}

@customElement('typo3-backend-navigation-component-pagetree')
export class PageTreeNavigationComponent extends TreeModuleState(LitElement) {
  @property({ type: String }) mountPointPath: string = null;

  @query('.tree-wrapper') tree: EditablePageTree;
  @query('typo3-backend-navigation-component-pagetree-toolbar') toolbar: PageTreeToolbar;

  protected override moduleStateType: string = 'web';

  private configuration: Configuration = null;

  public override connectedCallback(): void {
    super.connectedCallback();
    document.addEventListener('typo3:pagetree:refresh', this.refresh);
    document.addEventListener('typo3:pagetree:mountPoint', this.setMountPoint);
    document.addEventListener('typo3:pagetree:selectFirstNode', this.selectFirstNode);
  }

  public override disconnectedCallback(): void {
    document.removeEventListener('typo3:pagetree:refresh', this.refresh);
    document.removeEventListener('typo3:pagetree:mountPoint', this.setMountPoint);
    document.removeEventListener('typo3:pagetree:selectFirstNode', this.selectFirstNode);
    super.disconnectedCallback();
  }

  // disable shadow dom for now
  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <div id="typo3-pagetree" class="tree">
      ${until(this.renderTree(), '')}
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

  protected async renderTree(): Promise<TemplateResult> {
    const configuration = await this.getConfiguration();
    return html`
      <typo3-backend-navigation-component-pagetree-toolbar id="typo3-pagetree-toolbar" .tree="${this.tree}"></typo3-backend-navigation-component-pagetree-toolbar>
      <div id="typo3-pagetree-treeContainer" class="navigation-tree-container">
        ${this.renderMountPoint()}
        <typo3-backend-navigation-component-pagetree-tree
            id="typo3-pagetree-tree"
            class="tree-wrapper"
            .setup=${configuration}
            @tree:initialized=${() => { this.toolbar.tree = this.tree; this.fetchActiveNodeIfMissing(); }}
            @typo3:tree:node-selected=${this.loadContent}
            @typo3:tree:node-context=${this.showContextMenu}
            @typo3:tree:nodes-prepared=${this.selectActiveNodeInLoadedNodes}
        ></typo3-backend-navigation-component-pagetree-tree>
      </div>
    `;
  }

  private readonly refresh = (): void => {
    this.tree.refreshOrFilterTree();
  };

  private readonly setMountPoint = (e: CustomEvent): void => {
    this.setTemporaryMountPoint(e.detail.pageId as number);
  };

  private readonly selectFirstNode = (): void => {
    this.tree.selectFirstNode();
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

  private readonly loadContent = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNodeInterface;
    if (!node?.checked) {
      return;
    }

    // remember the selected page in the global state
    ModuleStateStorage.updateWithTreeIdentifier('web', node.identifier, node.__treeIdentifier);

    if (evt.detail.propagate === false) {
      return;
    }

    // Load the currently selected module with the updated URL
    const moduleMenu = top.TYPO3.ModuleMenu.App;
    let contentUrl = ModuleUtility.getFromName(moduleMenu.getCurrentModule()).link;
    contentUrl += contentUrl.includes('?') ? '&' : '?';
    top.TYPO3.Backend.ContentContainer.setUrl(contentUrl + 'id=' + node.identifier);
  };

  private readonly showContextMenu = (evt: CustomEvent): void => {
    const node = evt.detail.node as TreeNodeInterface;
    if (!node) {
      return;
    }
    ContextMenu.show(
      node.recordType,
      node.identifier,
      'tree',
      '',
      '',
      this.tree.getElementFromNode(node),
      evt.detail.originalEvent as PointerEvent
    );
  };
}

@customElement('typo3-backend-navigation-component-pagetree-toolbar')
class PageTreeToolbar extends TreeToolbar {
  @property({ type: EditablePageTree })
  override tree: EditablePageTree = null;

  protected override render(): TemplateResult {
    /* eslint-disable @stylistic/indent */
    return html`
      <div class="tree-toolbar">
        <div class="tree-toolbar__menu">
          <div class="tree-toolbar__search">
              <label for="toolbarSearch" class="visually-hidden">
                ${lll('labels.label.searchString')}
              </label>
              <input type="search" id="toolbarSearch" class="form-control form-control-sm search-input" placeholder="${lll('tree.searchTermInfo')}">
          </div>
        </div>
        <div class="tree-toolbar__submenu">
          ${this.tree?.settings?.doktypes?.length
        ? this.tree.settings.doktypes.map((item: any) => {
          return html`
                <div
                  class="tree-toolbar__menuitem tree-toolbar__drag-node"
                  title="${item.title}"
                  draggable="true"
                  data-tree-icon="${item.icon}"
                  data-node-type="${item.nodeType}"
                  aria-hidden="true"
                  @dragstart="${(event: DragEvent) => { this.handleDragStart(event, item); }}"
                >
                  <typo3-backend-icon identifier="${item.icon}" size="small"></typo3-backend-icon>
                </div>
              `;
        })
        : ''
      }
          <button
            type="button"
            class="tree-toolbar__menuitem dropdown-toggle dropdown-toggle-no-chevron float-end"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="${lll('labels.openPageTreeOptionsMenu')}"
          >
            <typo3-backend-icon identifier="actions-menu-alternative" size="small"></typo3-backend-icon>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <button class="dropdown-item" @click="${() => this.refreshTree()}">
                <span class="dropdown-item-columns">
                  <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                    <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
                  </span>
                  <span class="dropdown-item-column dropdown-item-column-title">
                    ${lll('labels.refresh')}
                  </span>
                </span>
              </button>
            </li>
            <li>
              <button class="dropdown-item" @click="${(evt: MouseEvent) => this.collapseAll(evt)}">
                <span class="dropdown-item-columns">
                  <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                    <typo3-backend-icon identifier="apps-pagetree-category-collapse-all" size="small"></typo3-backend-icon>
                  </span>
                  <span class="dropdown-item-column dropdown-item-column-title">
                    ${lll('labels.collapse')}
                  </span>
                </span>
              </button>
            </li>
          </ul>
        </div>
      </div>
    `;
  }

  protected handleDragStart(event: DragEvent, item: any): void {
    const newNode: TreeNodeInterface = {
      __hidden: false,
      __expanded: false,
      __indeterminate: false,
      __loading: false,
      __processed: false,
      __treeDragAction: '',
      __treeIdentifier: '',
      __treeParents: [''],
      __parents: [''],
      __x: 0,
      __y: 0,
      deletable: false,
      depth: 0,
      editable: true,
      hasChildren: false,
      icon: item.icon,
      overlayIcon: '',
      identifier: 'NEW' + Math.floor(Math.random() * 1000000000).toString(16),
      loaded: false,
      name: '',
      note: '',
      parentIdentifier: '',
      prefix: '',
      recordType: 'pages',
      suffix: '',
      tooltip: '',
      type: 'PageTreeItem',
      doktype: item.nodeType,
      statusInformation: [],
      labels: [],
    };
    this.tree.draggingNode = newNode;
    this.tree.nodeDragMode = TreeNodeCommandEnum.NEW;

    event.dataTransfer.clearData();
    const metadata: DragTooltipMetadata = {
      statusIconIdentifier: this.tree.getNodeDragStatusIcon(),
      tooltipIconIdentifier: item.icon,
      tooltipLabel: item.title,
    };
    event.dataTransfer.setData(DataTransferTypes.dragTooltip, JSON.stringify(metadata));
    event.dataTransfer.setData(DataTransferTypes.newTreenode, JSON.stringify(newNode));
    event.dataTransfer.effectAllowed = 'move';
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-navigation-component-pagetree-tree': EditablePageTree
    'typo3-backend-navigation-component-pagetree': PageTreeNavigationComponent;
    'typo3-backend-navigation-component-pagetree-toolbar': PageTreeToolbar;
  }
}
