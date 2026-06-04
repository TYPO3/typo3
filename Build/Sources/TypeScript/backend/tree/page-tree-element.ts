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
import { customElement, property, query, state } from 'lit/decorators.js';
import { until } from 'lit/directives/until.js';
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
import { UrlFactory } from '@typo3/core/factory/url-factory';
import { ModuleStateStorage } from '@typo3/backend/storage/module-state-storage';
import { DataTransferTypes } from '@typo3/backend/enum/data-transfer-types';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { DragTooltipMetadata } from '@typo3/backend/drag-tooltip';
import type { DataTransferStringItem } from '@typo3/backend/tree/tree';
import '@typo3/backend/viewport/content-navigation-toggle';
import 'bootstrap'; // for data-bs-toggle="dropdown"
import coreLabels from '~labels/core.core';
import coreCommonLabels from '~labels/core.common';
import listLabels from '~labels/core.mod_web_list';
import backendPagesNewLabels from '~labels/backend.pages_new';
import { openPageWizardModal } from '@typo3/backend/page-wizard/helper/wizard-helper';

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
        coreLabels.get('mess.delete.title'),
        coreLabels.get('mess.delete', [options.node.name]),
        Severity.warning, [
          {
            text: coreLabels.get('labels.cancel'),
            active: true,
            btnClass: 'btn-default',
            name: 'cancel'
          },
          {
            text: coreCommonLabels.get('delete'),
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
    const languageArguments = [node.name, target.name] as const;
    switch(position) {
      case TreeNodePositionEnum.BEFORE:
        modalText = coreLabels.get('mess.move_before', languageArguments);
        break;
      case TreeNodePositionEnum.AFTER:
        modalText = coreLabels.get('mess.move_after', languageArguments);
        break;
      default:
        modalText = coreLabels.get('mess.move_into', languageArguments);
        break;
    }

    const modal = Modal.confirm(
      listLabels.get('move_page'),
      modalText,
      Severity.warning, [
        {
          text: coreLabels.get('labels.cancel'),
          active: true,
          btnClass: 'btn-default',
          name: 'cancel'
        },
        {
          text: coreLabels.get('cm.copy'),
          btnClass: 'btn-warning',
          name: 'copy'
        },
        {
          text: coreLabels.get('labels.move'),
          btnClass: 'btn-warning',
          name: 'move'
        }
      ]
    );

    modal.addEventListener('button.clicked', (e: Event) => {
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

  @query('typo3-backend-navigation-component-pagetree-tree') tree: EditablePageTree;
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
      ${until(this.renderTree(), '')}
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
      ${this.renderMountPoint()}
      <typo3-backend-navigation-component-pagetree-tree
          id="typo3-pagetree-tree"
          .setup=${configuration}
          @tree:initialized=${() => { this.toolbar.tree = this.tree; this.fetchActiveNodeIfMissing(); }}
          @typo3:tree:node-selected=${this.loadContent}
          @typo3:tree:node-context=${this.showContextMenu}
          @typo3:tree:nodes-prepared=${this.selectActiveNodeInLoadedNodes}
      ></typo3-backend-navigation-component-pagetree-tree>
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
        <div class="node-mount-point__icon mountpoint-close" @click="${() => this.unsetTemporaryMountPoint()}" title="${coreLabels.get('labels.temporaryPageTreeEntryPoints')}">
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
    const contentUrl = UrlFactory.createUrl(ModuleUtility.getFromName(moduleMenu.getCurrentModule()).link, {
      id: node.identifier
    });
    top.TYPO3.Backend.ContentContainer.setUrl(contentUrl);
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

  @property({ type: Boolean })
  searchInTranslatedPages: boolean = false;

  @property({ type: Boolean })
  searchByFrontendUri: boolean = false;

  @state()
  private subMenuItemsExpanded: boolean = false;

  @state()
  private hasHiddenSubMenuItems: boolean;

  @query('.tree-toolbar__submenu-items')
  private readonly submenuItemsContainer: HTMLElement | null;

  private resizeObserver!: ResizeObserver;

  private readonly handleResize = this.checkHiddenSubmenuItems.bind(this);

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.resizeObserver.disconnect();
    window.removeEventListener('resize', this.handleResize);
  }

  protected override firstUpdated() {
    super.firstUpdated();

    this.resizeObserver = new ResizeObserver(() => this.checkHiddenSubmenuItems());
    this.resizeObserver.observe(this.submenuItemsContainer);

    window.addEventListener('resize', this.handleResize);
    this.checkHiddenSubmenuItems();
  }

  protected override updated(changedProperties: Map<PropertyKey, unknown>): void {
    super.updated(changedProperties);

    // Update searchInTranslatedPages and searchByFrontendUri when tree property changes (initial load or tree replacement)
    if (changedProperties.has('tree')) {
      if (this.tree?.settings?.searchInTranslatedPagesEnabled !== undefined) {
        this.searchInTranslatedPages = this.tree.settings.searchInTranslatedPagesEnabled;
      }
      if (this.tree?.settings?.searchByFrontendUriEnabled !== undefined) {
        this.searchByFrontendUri = this.tree.settings.searchByFrontendUriEnabled;
      }
      this.checkHiddenSubmenuItems();
    }
  }

  protected override render(): TemplateResult {
    return html`
      <div class="tree-toolbar">
        <div class="tree-toolbar__menu">
          <div class="tree-toolbar__search">
              <label for="toolbarSearch" class="visually-hidden">
                ${coreLabels.get('labels.label.searchString')}
              </label>
              <input type="search" autocomplete="off" id="toolbarSearch" class="form-control form-control-sm search-input" placeholder="${coreLabels.get('tree.searchPageTree')}">
          </div>
          <div class="dropdown">
            <button
              type="button"
              class="btn btn-sm btn-icon btn-default btn-borderless dropdown-toggle dropdown-toggle-no-chevron"
              data-bs-toggle="dropdown"
              data-bs-boundary="window"
              aria-expanded="false"
              aria-label="${coreLabels.get('labels.openPageTreeOptionsMenu')}"
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
                      ${coreLabels.get('labels.refresh')}
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
                      ${coreLabels.get('labels.collapse')}
                    </span>
                  </span>
                </button>
              </li>
              ${this.renderSearchOptions()}
            </ul>
          </div>
          <typo3-backend-content-navigation-toggle
            class="btn btn-sm btn-icon btn-default btn-borderless"
            action="collapse"
          >
          </typo3-backend-content-navigation-toggle>
        </div>
        ${this.renderToolbarSubmenu()}
      </div>
    `;
  }

  protected renderToolbarSubmenu(): TemplateResult {
    const toolbarItemsHtml: TemplateResult[] = [];
    if (this.tree?.settings?.doktypes?.length) {
      toolbarItemsHtml.push( html`
        <button type="button" class="btn btn-sm btn-default" @click="${this.launchPageWizard}">
          <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
          ${backendPagesNewLabels.get('newPage')}
        </button>
      `);

      toolbarItemsHtml.push(this.tree.settings.doktypes.map((item: any) => {
        return html `<div
          class="tree-toolbar__menuitem tree-toolbar__drag-node"
          title="${item.title}"
          draggable="true"
          data-tree-icon="${item.icon}"
          data-node-type="${item.nodeType}"
          aria-hidden="true"
          @dragstart="${(event: DragEvent) => this.handleDragStart(event, item)}"
        >
          <typo3-backend-icon identifier="${item.icon}" size="small"></typo3-backend-icon>
        </div>
        `;
      }));
    }

    return html`
      <div class="tree-toolbar__submenu">
        <div
          class="tree-toolbar__submenu-items ${this.subMenuItemsExpanded ? 'tree-toolbar__submenu-items--expanded' : ''}">
          ${toolbarItemsHtml}
        </div>
        ${this.hasHiddenSubMenuItems ? html`
          <button
            class="btn btn-sm btn-icon btn-default btn-borderless tree-toolbar__submenu-toggle"
            aria-hidden="true"
            tabindex="-1"
            @click=${this.toggleSubmenu}
          >
            <typo3-backend-icon
              identifier=${this.subMenuItemsExpanded ? 'actions-chevron-up' : 'actions-chevron-down'}
              size="small"></typo3-backend-icon>
          </button>` : nothing}
      </div>
    `;
  }

  protected renderSearchOptions(): TemplateResult | symbol {
    const hasTranslationSearch = this.tree?.settings?.searchInTranslatedPagesAvailable;
    const hasFrontendUriSearch = this.tree?.settings?.searchByFrontendUriAvailable;

    if (!hasTranslationSearch && !hasFrontendUriSearch) {
      return nothing;
    }

    return html`
      <li>
        <hr class="dropdown-divider">
      </li>
      ${hasTranslationSearch ? html`
        <li>
          <button class="dropdown-item" @click="${() => this.toggleTranslationSearch()}">
            <span class="dropdown-item-columns">
              <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                <typo3-backend-icon identifier="${this.searchInTranslatedPages ? 'actions-check-square' : 'actions-selection'}" size="small"></typo3-backend-icon>
              </span>
              <span class="dropdown-item-column dropdown-item-column-title">
                ${coreLabels.get('tree.search_in_translated_pages')}
              </span>
            </span>
          </button>
        </li>
      ` : nothing}
      ${hasFrontendUriSearch ? html`
        <li>
          <button class="dropdown-item" @click="${() => this.toggleFrontendUriSearch()}">
            <span class="dropdown-item-columns">
              <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                <typo3-backend-icon identifier="${this.searchByFrontendUri ? 'actions-check-square' : 'actions-selection'}" size="small"></typo3-backend-icon>
              </span>
              <span class="dropdown-item-column dropdown-item-column-title">
                ${coreLabels.get('tree.search_by_frontend_uri')}
              </span>
            </span>
          </button>
        </li>
      ` : nothing}
    `;
  }

  protected async toggleTranslationSearch(): Promise<void> {
    const newValue = !this.searchInTranslatedPages;

    try {
      await Persistent.set('pageTree_searchInTranslatedPages', newValue ? '1' : '0');

      // Update both local state and tree settings
      this.searchInTranslatedPages = newValue;
      if (this.tree?.settings) {
        this.tree.settings.searchInTranslatedPagesEnabled = newValue;
      }

      // Refresh the tree if there's an active search
      const searchInput = this.querySelector('.search-input') as HTMLInputElement;
      if (searchInput && searchInput.value.trim() !== '') {
        this.refreshTree();
      }
    } catch (error) {
      console.error('Failed to toggle translation search:', error);
    }
  }

  protected async toggleFrontendUriSearch(): Promise<void> {
    const newValue = !this.searchByFrontendUri;

    try {
      await Persistent.set('pageTree_searchByFrontendUri', newValue ? '1' : '0');

      // Update both local state and tree settings
      this.searchByFrontendUri = newValue;
      if (this.tree?.settings) {
        this.tree.settings.searchByFrontendUriEnabled = newValue;
      }

      // Refresh the tree if there's an active search
      const searchInput = this.querySelector('.search-input') as HTMLInputElement;
      if (searchInput && searchInput.value.trim() !== '') {
        this.refreshTree();
      }
    } catch (error) {
      console.error('Failed to toggle frontend URI search:', error);
    }
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

  private checkHiddenSubmenuItems(): void {
    requestAnimationFrame(() => {
      const wasExpanded = this.subMenuItemsExpanded;
      if (wasExpanded) {
        this.submenuItemsContainer.classList.remove('tree-toolbar__submenu-items--expanded');
      }
      this.hasHiddenSubMenuItems = this.submenuItemsContainer.scrollHeight > this.submenuItemsContainer.clientHeight;
      if (wasExpanded) {
        this.submenuItemsContainer.classList.add('tree-toolbar__submenu-items--expanded');
      }
    });
  }

  private toggleSubmenu(e: Event): void {
    e.stopPropagation();
    this.subMenuItemsExpanded = !this.subMenuItemsExpanded;
  }

  private launchPageWizard() {
    const selectedNodes = this.tree.getSelectedNodes();

    openPageWizardModal({
      positionData: {
        pageUid: parseInt(selectedNodes[0]?.identifier, 10),
        insertPosition: 'inside'
      },
      preventPositionAutoAdvance: true,
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-navigation-component-pagetree-tree': EditablePageTree
    'typo3-backend-navigation-component-pagetree': PageTreeNavigationComponent;
    'typo3-backend-navigation-component-pagetree-toolbar': PageTreeToolbar;
  }
}
