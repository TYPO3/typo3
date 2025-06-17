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

import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import DocumentService from '@typo3/core/document-service';
import { html } from 'lit';
import '@typo3/backend/element/icon-element';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import '@typo3/backend/input/clearable';
import '@typo3/workspaces/renderable/record-table';
import '@typo3/backend/element/pagination';
import Workspaces from './workspaces';
import { default as Modal, type ModalElement } from '@typo3/backend/modal';
import Persistent from '@typo3/backend/storage/persistent';
import Utility from '@typo3/backend/utility';
import windowManager from '@typo3/backend/window-manager';
import RegularEvent from '@typo3/core/event/regular-event';
import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';
import { selector } from '@typo3/core/literals';
import IconHelper from '@typo3/workspaces/utility/icon-helper';
import DeferredAction from '@typo3/backend/action-button/deferred-action';
import type { PaginationElement } from '@typo3/backend/element/pagination';
import { RecordTableElement } from '@typo3/workspaces/renderable/record-table';

enum Identifiers {
  searchForm = '#workspace-settings-form',
  searchTextField = '#workspace-settings-form input[name="search-text"]',
  searchSubmitBtn = '#workspace-settings-form button[type="submit"]',
  depthSelector = '#workspace-settings-form [name="depth"]',
  languageSelector = '#workspace-settings-form select[name="languages"]',
  stagesSelector = '#workspace-settings-form select[name="stages"]',
  workspaceActions = '.workspace-actions',
  chooseStageAction = '.workspace-actions [name="stage-action"]',
  chooseSelectionAction = '.workspace-actions [name="selection-action"]',
  chooseMassAction = '.workspace-actions [name="mass-action"]',
  publishAction = '[data-action="publish"]',
  prevStageAction = '[data-action="prevstage"]',
  nextStageAction = '[data-action="nextstage"]',
  changesAction = '[data-action="changes"]',
  previewAction = '[data-action="preview"]',
  openAction = '[data-action="open"]',
  versionAction = '[data-action="version"]',
  removeAction = '[data-action="remove"]',
  expandAction = '[data-action="expand"]',
  workspaceRecipientsSelectAll = '.t3js-workspace-recipients-selectall',
  workspaceRecipientsDeselectAll = '.t3js-workspace-recipients-deselectall',
  container = '#workspace-panel',
  contentsContainer = '#workspace-contents',
  noContentsContainer = '#workspace-contents-empty',
  previewLinksButton = '.t3js-preview-link',
  pagination = '#workspace-pagination',
}

/**
 * Backend workspace module. Loaded only in Backend context, not in
 * workspace preview. Contains all JavaScript of the main BE module.
 */
class Backend extends Workspaces {
  private readonly settings: { [key: string]: string | number } = {
    dir: 'ASC',
    id: TYPO3.settings.Workspaces.id,
    depth: 1,
    language: 'all',
    limit: 30,
    query: '',
    sort: 'label_Workspace',
    start: 0,
    filterTxt: '',
  };
  private readonly paging: Record<string, number> = {
    currentPage: 1,
    totalPages: 1,
    totalItems: 0,
  };
  private markedRecordsForMassAction: string[] = [];

  constructor() {
    super();

    topLevelModuleImport('@typo3/workspaces/renderable/send-to-stage-form.js');
    topLevelModuleImport('@typo3/workspaces/renderable/record-information.js');

    DocumentService.ready().then((): void => {
      this.registerEvents();
      this.notifyWorkspaceSwitchAction();

      // Set the depth from the main element
      this.settings.depth = (document.querySelector(Identifiers.depthSelector) as HTMLInputElement)?.value;
      this.settings.language = (document.querySelector(Identifiers.languageSelector) as HTMLInputElement)?.value;
      this.settings.stage = (document.querySelector(Identifiers.stagesSelector) as HTMLInputElement)?.value;

      // Fetch workspace info (listing) if workspace is accessible
      if (document.querySelector(Identifiers.container) !== null) {
        this.getWorkspaceInfos();
      }
    });
  }

  /**
   * Reloads the page tree
   */
  private static refreshPageTree(): void {
    top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
  }

  /**
   * This changes the checked state of a parent checkbox belonging
   * to the given collection (e.g. sys_file_reference > tt_content).
   *
   * This also sets a data attribute which will be respected by
   * the multi record selection module. This is to prevent the
   * module from overriding the manually changed state.
   */
  private static changeCollectionParentState(collection: string, check: boolean): void {
    const parent: HTMLInputElement = document.querySelector('tr[data-collection-current="' + collection + '"] input[type=checkbox]');
    if (parent !== null && parent.checked !== check) {
      parent.checked = check;
      parent.dataset.manuallyChanged = 'true';
      parent.dispatchEvent(new CustomEvent('multiRecordSelection:checkbox:state:changed', { bubbles: true, cancelable: false }));
    }
  }

  /**
   * This changes the checked state of all checkboxes belonging
   * to the given collectionCurrent. Those are the child records
   * of a parent record (e.g. tt_content > sys_file_reference).
   *
   * This also sets a data attribute which will be respected by
   * the multi record selection module. This is to prevent the
   * module from overriding the manually changed state.
   */
  private static changeCollectionChildrenState(collectionCurrent: string, check: boolean): void {
    const collectionChildren: NodeListOf<HTMLInputElement> = document.querySelectorAll(selector`tr[data-collection="${collectionCurrent}"] input[type=checkbox]`);
    if (collectionChildren.length) {
      collectionChildren.forEach((checkbox: HTMLInputElement): void => {
        if (checkbox.checked !== check) {
          checkbox.checked = check;
          checkbox.dataset.manuallyChanged = 'true';
          checkbox.dispatchEvent(new CustomEvent('multiRecordSelection:checkbox:state:changed', { bubbles: true, cancelable: false }));
        }
      });
    }
  }

  private notifyWorkspaceSwitchAction(): void {
    const mainElement = document.querySelector('main[data-workspace-switch-action]') as HTMLElement;
    if (mainElement.dataset.workspaceSwitchAction) {
      const workspaceSwitchInformation = JSON.parse(mainElement.dataset.workspaceSwitchAction);
      // we need to do this manually, but this should be done better via proper events
      top.TYPO3.WorkspacesMenu.performWorkspaceSwitch(workspaceSwitchInformation.id, workspaceSwitchInformation.title);
      top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
      top.TYPO3.ModuleMenu.App.refreshMenu();
    }
  }

  /**
   * Checks the integrity of a record
   */
  private checkIntegrity(payload: object): Promise<AjaxResponse> {
    return this.sendRemoteRequest(
      this.generateRemotePayloadBody('checkIntegrity', payload),
    );
  }

  private registerEvents(): void {
    new RegularEvent('click', (event: Event, target) => {
      const row = target.closest('tr') as HTMLTableRowElement;

      this.checkIntegrity(
        {
          selection: [
            {
              liveId: row.dataset.uid,
              versionId: row.dataset.t3ver_oid,
              table: row.dataset.table,
            },
          ],
          type: 'selection',
        },
      ).then(async (response: AjaxResponse): Promise<void> => {
        if ((await response.resolve())[0].result.result === 'warning') {
          this.openIntegrityWarningModal().addEventListener('confirm.button.ok', (): void => {
            this.renderPublishModal(row);
          });
        } else {
          this.renderPublishModal(row);
        }
      });
    }).delegateTo(document, Identifiers.publishAction);

    new RegularEvent('click', (event: Event, target: HTMLElement) => {
      this.sendToStage(target.closest('tr'), 'prev');
    }).delegateTo(document, Identifiers.prevStageAction);

    new RegularEvent('click', (event: Event, target: HTMLElement) => {
      this.sendToStage(target.closest('tr'), 'next');
    }).delegateTo(document, Identifiers.nextStageAction);

    new RegularEvent('click', this.viewChanges.bind(this)).delegateTo(document, Identifiers.changesAction);
    new RegularEvent('click', this.openPreview.bind(this)).delegateTo(document, Identifiers.previewAction);

    new RegularEvent('click', (event: Event, target: HTMLElement) => {
      const row = target.closest('tr') as HTMLTableRowElement;
      const newUrl = TYPO3.settings.FormEngine.moduleUrl
        + '&returnUrl=' + encodeURIComponent(document.location.href)
        + '&id=' + TYPO3.settings.Workspaces.id + '&edit[' + row.dataset.table + '][' + row.dataset.uid + ']=edit';

      window.location.href = newUrl;
    }).delegateTo(document, Identifiers.openAction);

    new RegularEvent('click', (event: Event, target: HTMLElement) => {
      const row = target.closest('tr') as HTMLTableRowElement;
      const recordUid = row.dataset.table === 'pages' ? row.dataset.t3ver_oid : row.dataset.pid;
      window.location.href = TYPO3.settings.WebLayout.moduleUrl
        + '&id=' + recordUid;
    }).delegateTo(document, Identifiers.versionAction);

    new RegularEvent('click', this.confirmDeleteRecordFromWorkspace.bind(this)).delegateTo(document, Identifiers.removeAction);

    new RegularEvent('click', (event: Event, target: HTMLElement) => {
      let iconIdentifier;

      if (target.ariaExpanded === 'true') {
        iconIdentifier = 'actions-caret-down';
      } else {
        iconIdentifier = 'actions-caret-right';
      }

      target.replaceChildren(document.createRange().createContextualFragment(IconHelper.getIcon(iconIdentifier)));
    }).delegateTo(document, Identifiers.expandAction);

    new RegularEvent('click', () => {
      const workspaceRecipients = window.top.document.querySelectorAll('.t3js-workspace-recipient');

      workspaceRecipients.forEach((recipient: HTMLInputElement) => {
        if (!recipient.disabled) {
          recipient.checked = true;
        }
      });
    }).delegateTo(window.top.document, Identifiers.workspaceRecipientsSelectAll);

    new RegularEvent('click', () => {
      const workspaceRecipients = window.top.document.querySelectorAll('.t3js-workspace-recipient');

      workspaceRecipients.forEach((recipient: HTMLInputElement) => {
        if (!recipient.disabled) {
          recipient.checked = false;
        }
      });
    }).delegateTo(window.top.document, Identifiers.workspaceRecipientsDeselectAll);

    new RegularEvent('submit', (event: Event) => {
      event.preventDefault();
      this.getWorkspaceInfos();
    }).delegateTo(document, Identifiers.searchForm);

    new RegularEvent('input', (event: Event, target: HTMLInputElement) => {
      const searchSubmitButton = document.querySelector(Identifiers.searchSubmitBtn) as HTMLButtonElement;

      if (target.value !== '') {
        searchSubmitButton.disabled = false;
      } else {
        searchSubmitButton.disabled = true;
        this.settings.filterTxt = '';
        this.getWorkspaceInfos();
      }
    }).delegateTo(document, Identifiers.searchTextField);

    new RegularEvent('change', (event: Event, target: HTMLInputElement) => {
      this.settings.filterTxt = target.value;
      if (this.settings.filterTxt !== '') {
        this.getWorkspaceInfos();
      }
    }).delegateTo(document, Identifiers.searchTextField);

    const searchTextField = document.querySelector(Identifiers.searchTextField) as HTMLInputElement;
    if (searchTextField !== null) {
      searchTextField.clearable(
        {
          onClear: (): void => {
            const searchSubmitButton = document.querySelector(Identifiers.searchSubmitBtn) as HTMLButtonElement;
            searchSubmitButton.disabled = true;
            this.settings.filterTxt = '';
            this.getWorkspaceInfos();
          },
        },
      );
    }

    // checkboxes in the table
    new RegularEvent('multiRecordSelection:checkbox:state:changed', this.handleCheckboxStateChanged).bindTo(document);

    // Listen for depth changes
    new RegularEvent('change', (event: Event, target: HTMLSelectElement) => {
      const depth = target.value;
      Persistent.set('moduleData.workspaces_admin.depth', depth);
      this.settings.depth = depth;
      this.getWorkspaceInfos();
    }).delegateTo(document, Identifiers.depthSelector);

    // Generate preview links
    new RegularEvent('click', this.generatePreviewLinks.bind(this)).delegateTo(document, Identifiers.previewLinksButton);

    // Listen for language changes
    new RegularEvent('change', (event: Event, target: HTMLSelectElement) => {
      Persistent.set('moduleData.workspaces_admin.language', target.value);
      this.settings.language = target.value;
      this.sendRemoteRequest(
        this.generateRemotePayloadBody('getWorkspaceInfos', this.settings),
      ).then(async (response: AjaxResponse): Promise<void> => {
        const actionResponse = await response.resolve();
        target.previousElementSibling.innerHTML = (target.querySelector('option:checked') as HTMLElement).dataset.icon;
        this.renderWorkspaceInfos(actionResponse[0].result);
      });
    }).delegateTo(document, Identifiers.languageSelector);

    new RegularEvent('change', (event: Event, target: HTMLSelectElement) => {
      const stage = target.value;
      Persistent.set('moduleData.workspaces_admin.stage', stage);
      this.settings.stage = stage;
      this.getWorkspaceInfos();
    }).delegateTo(document, Identifiers.stagesSelector);

    // Listen for actions
    new RegularEvent('change', this.sendToSpecificStageAction.bind(this)).delegateTo(document, Identifiers.chooseStageAction);
    new RegularEvent('change', this.runSelectionAction.bind(this)).delegateTo(document, Identifiers.chooseSelectionAction);
    new RegularEvent('change', this.runMassAction.bind(this)).delegateTo(document, Identifiers.chooseMassAction);

    // clicking an action in the paginator
    new RegularEvent('click', (event: Event) => {
      event.preventDefault();

      const paginator = (event.target as HTMLElement).closest('button') as HTMLButtonElement;

      let reload = false;

      switch (paginator.dataset.action) {
        case 'previous':
          if (this.paging.currentPage > 1) {
            this.paging.currentPage--;
            reload = true;
          }
          break;
        case 'next':
          if (this.paging.currentPage < this.paging.totalPages) {
            this.paging.currentPage++;
            reload = true;
          }
          break;
        case 'page':
          this.paging.currentPage = parseInt(paginator.dataset.page, 10);
          reload = true;
          break;
        default:
          throw 'Unknown action "' + paginator.dataset.action + '"';
      }

      if (reload) {
        // Adjust settings
        this.settings.start = parseInt(this.settings.limit.toString(), 10) * (this.paging.currentPage - 1);
        this.getWorkspaceInfos();
      }
    }).delegateTo(document, Identifiers.pagination);
  }

  private readonly handleCheckboxStateChanged = (event: Event): void => {
    const checkbox = event.target as HTMLInputElement;
    const tableRow = checkbox.closest('tr') as HTMLTableRowElement;
    const checked = checkbox.checked;
    const table = tableRow.dataset.table;
    const uid = tableRow.dataset.uid;
    const t3ver_oid = tableRow.dataset.t3ver_oid;
    const record = table + ':' + uid + ':' + t3ver_oid;

    if (checked) {
      this.markedRecordsForMassAction.push(record);
    } else {
      const index = this.markedRecordsForMassAction.indexOf(record);
      if (index > -1) {
        this.markedRecordsForMassAction.splice(index, 1);
      }
    }

    if (tableRow.dataset.collectionCurrent) {
      // change checked state from all collection children
      Backend.changeCollectionChildrenState(tableRow.dataset.collectionCurrent, checked);
    } else if (tableRow.dataset.collection) {
      // change checked state from all collection children and the collection parent
      Backend.changeCollectionChildrenState(tableRow.dataset.collection, checked);
      Backend.changeCollectionParentState(tableRow.dataset.collection, checked);
    }

    const chooseMassAction = document.querySelector(Identifiers.chooseMassAction) as HTMLSelectElement;
    if (chooseMassAction !== null) {
      chooseMassAction.disabled = this.markedRecordsForMassAction.length > 0;
    }
  };

  /**
   * Sends a record to a stage
   */
  private sendToStage(row: HTMLTableRowElement, direction: string): void {
    let nextStage: string;
    let stageWindowAction: string;
    let stageExecuteAction: string;

    if (direction === 'next') {
      nextStage = row.dataset.nextStage;
      stageWindowAction = 'sendToNextStageWindow';
      stageExecuteAction = 'sendToNextStageExecute';
    } else if (direction === 'prev') {
      nextStage = row.dataset.prevStage;
      stageWindowAction = 'sendToPrevStageWindow';
      stageExecuteAction = 'sendToPrevStageExecute';
    } else {
      throw 'Invalid direction given.';
    }

    this.sendRemoteRequest(
      this.generateRemotePayloadBody(stageWindowAction, [
        row.dataset.uid, row.dataset.table, row.dataset.t3ver_oid,
      ]),
    ).then(async (response: AjaxResponse): Promise<void> => {
      const modal = this.renderSendToStageWindow(await response.resolve());
      modal.addEventListener('button.clicked', (modalEvent: Event): void => {
        const target = modalEvent.target as HTMLButtonElement;
        if (target.name === 'ok') {
          const serializedForm = Utility.convertFormToObject(modal.querySelector('form'));
          serializedForm.affects = {
            table: row.dataset.table,
            nextStage: nextStage,
            t3ver_oid: row.dataset.t3ver_oid,
            uid: row.dataset.uid,
            elements: [],
          };
          this.sendRemoteRequest([
            this.generateRemotePayloadBody(stageExecuteAction, [serializedForm]),
            this.generateRemotePayloadBody('getWorkspaceInfos', this.settings),
          ]).then(async (response: AjaxResponse): Promise<void> => {
            const requestResponse = await response.resolve();
            modal.hideModal();
            this.renderWorkspaceInfos(requestResponse[1].result);
            Backend.refreshPageTree();
          });
        }
      });
    });
  }

  /**
   * Gets the workspace infos (= filling the contents).
   */
  private getWorkspaceInfos(): void {
    this.sendRemoteRequest(
      this.generateRemotePayloadBody('getWorkspaceInfos', this.settings),
    ).then(async (response: AjaxResponse): Promise<void> => {
      this.renderWorkspaceInfos((await response.resolve())[0].result);
    });
  }

  /**
   * Renders fetched workspace information
   */
  private renderWorkspaceInfos(result: any): void {
    const contentsContainer = document.querySelector(Identifiers.contentsContainer) as HTMLElement;
    const noContentsContainer = document.querySelector(Identifiers.noContentsContainer) as HTMLElement;

    this.resetMassActionState(result.data.length);
    this.buildPagination(result.total);

    // disable the contents area
    if (result.total === 0) {
      contentsContainer.style.display = 'none';
      noContentsContainer.style.display = 'block';
    } else {
      contentsContainer.style.display = 'block';
      noContentsContainer.style.display = 'none';
    }

    const workspacesRecordTable: RecordTableElement = document.querySelector('typo3-workspaces-record-table');
    workspacesRecordTable.results = result.data;
  }

  /**
   * Renders the pagination
   */
  private buildPagination(totalItems: number): void {
    const paginationContainer = document.querySelector(Identifiers.pagination) as HTMLElement;

    if (totalItems === 0) {
      paginationContainer.replaceChildren();
      return;
    }

    this.paging.totalItems = totalItems;
    this.paging.totalPages = Math.ceil(totalItems / parseInt(this.settings.limit.toString(), 10));

    if (this.paging.totalPages === 1) {
      // early abort if only one page is available
      paginationContainer.replaceChildren();
      return;
    }

    let pagination = paginationContainer.querySelector('typo3-backend-pagination') as PaginationElement | null;
    if (pagination === null) {
      pagination = document.createElement('typo3-backend-pagination');
      paginationContainer.append(pagination);
    }

    pagination.paging = { ...this.paging };
  }

  /**
   * View changes of a record
   */
  private viewChanges(event: Event, target: HTMLElement): void {
    event.preventDefault();

    const tableRow = target.closest('tr') as HTMLTableRowElement;
    this.sendRemoteRequest(
      this.generateRemotePayloadBody('getRowDetails', {
        stage: parseInt(tableRow.dataset.stage, 10),
        t3ver_oid: parseInt(tableRow.dataset.t3ver_oid, 10),
        table: tableRow.dataset.table,
        uid: parseInt(tableRow.dataset.uid, 10),
        filterFields: true
      }),
    ).then(async (response: AjaxResponse): Promise<void> => {
      const item = (await response.resolve())[0].result.data[0];
      const modalButtons = [];

      const content = document.createElement('typo3-workspaces-record-information');
      content.record = item;
      content.TYPO3lang = TYPO3.lang;

      if (item.label_PrevStage !== false && tableRow.dataset.stage !== tableRow.dataset.prevStage) {
        modalButtons.push({
          text: item.label_PrevStage.title,
          active: true,
          btnClass: 'btn-default',
          name: 'prevstage',
          trigger: (e: Event, modal: ModalElement) => {
            modal.hideModal();
            this.sendToStage(tableRow, 'prev');
          },
        });
      }

      if (item.label_NextStage !== false) {
        modalButtons.push({
          text: item.label_NextStage.title,
          active: true,
          btnClass: 'btn-default',
          name: 'nextstage',
          trigger: (e: Event, modal: ModalElement) => {
            modal.hideModal();
            this.sendToStage(tableRow, 'next');
          },
        });
      }
      modalButtons.push({
        text: TYPO3.lang.close,
        active: true,
        btnClass: 'btn-info',
        name: 'cancel',
        trigger: (e: Event, modal: ModalElement) => modal.hideModal(),
      });

      Modal.advanced({
        type: Modal.types.default,
        title: TYPO3.lang['window.recordInformation'].replace('{0}', (tableRow.querySelector('.t3js-title-workspace') as HTMLElement).innerText.trim()),
        content: content,
        severity: SeverityEnum.info,
        buttons: modalButtons,
        size: Modal.sizes.medium,
      });
    });
  }

  /**
   * Opens a record in a preview window
   */
  private openPreview(event: Event, target: HTMLElement): void {
    const tableRow = target.closest('tr') as HTMLTableRowElement;

    this.sendRemoteRequest(
      this.generateRemotePayloadBody('viewSingleRecord', [
        tableRow.dataset.table, tableRow.dataset.uid,
      ]),
    ).then(async (response: AjaxResponse): Promise<void> => {
      const previewUri: string = (await response.resolve())[0].result;
      windowManager.localOpen(previewUri);
    });
  }

  /**
   * Shows a confirmation modal and deletes the selected record from workspace.
   */
  private confirmDeleteRecordFromWorkspace(e: Event, target: HTMLElement): void {
    const tableRow = target.closest('tr') as HTMLTableRowElement;

    const modal = Modal.confirm(
      TYPO3.lang['window.discard.title'],
      TYPO3.lang['window.discard.message'],
      SeverityEnum.warning,
      [
        {
          text: TYPO3.lang.cancel,
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (): void => {
            modal.hideModal();
          },
        },
        {
          text: TYPO3.lang.ok,
          btnClass: 'btn-warning',
          name: 'ok',
        },
      ],
    );

    modal.addEventListener('button.clicked', (modalEvent: Event): void => {
      if ((<HTMLAnchorElement>modalEvent.target).name === 'ok') {
        this.sendRemoteRequest([
          this.generateRemotePayloadBody('discardSingleRecord', [
            tableRow.dataset.table,
            tableRow.dataset.uid,
          ]),
        ]).then((): void => {
          modal.hideModal();
          this.getWorkspaceInfos();
          Backend.refreshPageTree();
        });
      }
    });
  }

  /**
   * Runs a mass action
   */
  private runSelectionAction(event: Event, target: HTMLSelectElement): void {
    const selectedAction = target.value;
    const integrityCheckRequired = selectedAction !== 'discard';

    if (selectedAction.length === 0) {
      // Don't do anything if that value is empty
      return;
    }

    const affectedRecords: Array<object> = [];
    for (let i = 0; i < this.markedRecordsForMassAction.length; ++i) {
      const affected = this.markedRecordsForMassAction[i].split(':');
      affectedRecords.push({
        table: affected[0],
        liveId: affected[2],
        versionId: affected[1],
      });
    }

    if (!integrityCheckRequired) {
      this.renderSelectionActionModal(selectedAction, affectedRecords);
    } else {
      this.checkIntegrity(
        {
          selection: affectedRecords,
          type: 'selection',
        },
      ).then(async (response: AjaxResponse): Promise<void> => {
        if ((await response.resolve())[0].result.result === 'warning') {
          this.openIntegrityWarningModal().addEventListener('confirm.button.ok', (): void => {
            this.renderSelectionActionModal(selectedAction, affectedRecords);
          });
        } else {
          this.renderSelectionActionModal(selectedAction, affectedRecords);
        }
      });
    }
  }

  private readonly openIntegrityWarningModal = (): ModalElement => {
    const modal = Modal.confirm(
      TYPO3.lang['window.integrity_warning.title'],
      html`<p>${TYPO3.lang['integrity.hasIssuesDescription']}<br>${TYPO3.lang['integrity.hasIssuesQuestion']}</p>`,
      SeverityEnum.warning
    );
    modal.addEventListener('button.clicked', (): void => modal.hideModal());

    return modal;
  };

  private renderPublishModal(row: HTMLTableRowElement): void {
    const modal = Modal.advanced({
      title: TYPO3.lang['window.publish.title'],
      content: TYPO3.lang['window.publish.message'],
      severity: SeverityEnum.info,
      staticBackdrop: true,
      buttons: [
        {
          text: TYPO3.lang.cancel,
          btnClass: 'btn-default',
          trigger: function(): void {
            modal.hideModal();
          },
        }, {
          text: TYPO3.lang.label_doaction_publish,
          btnClass: 'btn-info',
          action: new DeferredAction(async (): Promise<void> => {
            await this.sendRemoteRequest(
              this.generateRemotePayloadBody('publishSingleRecord', [
                row.dataset.table,
                row.dataset.t3ver_oid,
                row.dataset.uid,
              ]),
            );
            this.getWorkspaceInfos();
            Backend.refreshPageTree();
          }),
        },
      ]
    });
  }

  private renderSelectionActionModal(selectedAction: string, affectedRecords: Array<object>): void {
    const modal = Modal.advanced({
      title: TYPO3.lang['window.selectionAction.title'],
      content: html`<p>${TYPO3.lang['tooltip.' + selectedAction + 'Selected']}</p>`,
      severity: SeverityEnum.warning,
      staticBackdrop: true,
      buttons: [
        {
          text: TYPO3.lang.cancel,
          btnClass: 'btn-default',
          trigger: function(): void {
            modal.hideModal();
          },
        }, {
          text: TYPO3.lang['label_doaction_' + selectedAction],
          btnClass: 'btn-warning',
          action: new DeferredAction(async (): Promise<void> => {
            await this.sendRemoteRequest(
              this.generateRemotePayloadBody('executeSelectionAction', {
                action: selectedAction,
                selection: affectedRecords,
              }),
            );
            this.markedRecordsForMassAction = [];
            this.getWorkspaceInfos();
            Backend.refreshPageTree();
          }),
        },
      ]
    });
    modal.addEventListener('typo3-modal-hidden', (): void => {
      const chooseSelectionAction = document.querySelector(Identifiers.chooseSelectionAction) as HTMLSelectElement;
      if (chooseSelectionAction !== null) {
        chooseSelectionAction.value = '';
      }
    });
  }

  /**
   * Runs a mass action
   */
  private runMassAction(event: Event, target: HTMLSelectElement): void {
    const selectedAction = target.value;
    const integrityCheckRequired = selectedAction !== 'discard';

    if (selectedAction.length === 0) {
      // Don't do anything if that value is empty
      return;
    }

    if (!integrityCheckRequired) {
      this.renderMassActionModal(selectedAction);
    } else {
      this.checkIntegrity(
        {
          language: this.settings.language,
          type: selectedAction,
        },
      ).then(async (response: AjaxResponse): Promise<void> => {
        if ((await response.resolve())[0].result.result === 'warning') {
          this.openIntegrityWarningModal().addEventListener('confirm.button.ok', (): void => {
            this.renderMassActionModal(selectedAction);
          });
        } else {
          this.renderMassActionModal(selectedAction);
        }
      });
    }
  }

  private renderMassActionModal(selectedAction: string): void {
    let massAction: string;
    let continueButtonLabel: string;

    switch (selectedAction) {
      case 'publish':
        massAction = 'publishEntireWorkspace';
        continueButtonLabel = TYPO3.lang.label_doaction_publish;
        break;
      case 'discard':
        massAction = 'discardEntireWorkspace';
        continueButtonLabel = TYPO3.lang.label_doaction_discard;
        break;
      default:
        throw 'Invalid mass action ' + selectedAction + ' called.';
    }

    const sendRequestsUntilAllProcessed = async (response: AjaxResponse): Promise<void> => {
      const result = (await response.resolve())[0].result;
      // Make sure to process all items
      if (result.processed < result.total) {
        this.sendRemoteRequest(
          this.generateRemotePayloadBody(massAction, result),
        ).then(sendRequestsUntilAllProcessed);
      } else {
        this.getWorkspaceInfos();
        Modal.dismiss();
      }
    };

    const modal = Modal.advanced({
      title: TYPO3.lang['window.massAction.title'],
      content: html`
        <p>${TYPO3.lang['tooltip.' + selectedAction + 'All']}</p>
        <p>${TYPO3.lang['tooltip.affectWholeWorkspace']}</p>
      `,
      severity: SeverityEnum.warning,
      staticBackdrop: true,
      buttons: [
        {
          text: TYPO3.lang.cancel,
          btnClass: 'btn-default',
          trigger: function(): void {
            modal.hideModal();
          },
        }, {
          text: continueButtonLabel,
          btnClass: 'btn-warning',
          action: new DeferredAction(async (): Promise<void> => {
            const response = await this.sendRemoteRequest(
              this.generateRemotePayloadBody(massAction, {
                init: true,
                total: 0,
                processed: 0,
                language: this.settings.language
              }),
            );
            await sendRequestsUntilAllProcessed(response);
          }),
        },
      ]
    });
    modal.addEventListener('typo3-modal-hidden', (): void => {
      const chooseMassAction = document.querySelector(Identifiers.chooseMassAction) as HTMLSelectElement;
      if (chooseMassAction !== null) {
        chooseMassAction.value = '';
      }
    });
  }

  /**
   * Sends marked records to a stage
   */
  private sendToSpecificStageAction(event: Event, target: HTMLInputElement): void {
    const affectedRecords: Array<{ [key: string]: number | string }> = [];
    const stage = target.value;
    for (let i = 0; i < this.markedRecordsForMassAction.length; ++i) {
      const affected = this.markedRecordsForMassAction[i].split(':');
      affectedRecords.push({
        table: affected[0],
        uid: affected[1],
        t3ver_oid: affected[2],
      });
    }
    this.sendRemoteRequest(
      this.generateRemotePayloadBody('sendToSpecificStageWindow', [
        stage, affectedRecords,
      ]),
    ).then(async (response: AjaxResponse): Promise<void> => {
      const modal = this.renderSendToStageWindow(await response.resolve());
      modal.addEventListener('button.clicked', (modalEvent: Event): void => {
        const target = modalEvent.target as HTMLButtonElement;
        if (target.name === 'ok') {
          const serializedForm = Utility.convertFormToObject(modal.querySelector('form'));
          serializedForm.affects = {
            elements: affectedRecords,
            nextStage: stage,
          };
          this.sendRemoteRequest([
            this.generateRemotePayloadBody('sendToSpecificStageExecute', [serializedForm]),
            this.generateRemotePayloadBody('getWorkspaceInfos', this.settings),
          ]).then(async (response: AjaxResponse): Promise<void> => {
            const actionResponse = await response.resolve();
            modal.hideModal();
            this.renderWorkspaceInfos(actionResponse[1].result);
            Backend.refreshPageTree();
          });
        }
      });
      modal.addEventListener('typo3-modal-hide', (): void => {
        const chooseStageAction = document.querySelector(Identifiers.chooseStageAction) as HTMLSelectElement;
        if (chooseStageAction !== null) {
          chooseStageAction.value = '';
        }
      });
    });
  }

  /**
   * Fetches and renders available preview links
   */
  private generatePreviewLinks(): void {
    this.sendRemoteRequest(
      this.generateRemotePayloadBody('generateWorkspacePreviewLinksForAllLanguages', [
        this.settings.id,
      ]),
    ).then(async (response: AjaxResponse): Promise<void> => {
      const result: Record<string, string> = (await response.resolve())[0].result;
      const list = document.createElement('dl');

      for (const [language, url] of Object.entries(result)) {
        const title = document.createElement('dt');
        title.textContent = language;

        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.textContent = url;

        const listItem = document.createElement('dd');
        listItem.appendChild(link);

        list.append(title, listItem);
      }

      Modal.show(
        TYPO3.lang.previewLink,
        list,
        SeverityEnum.info,
        [{
          text: TYPO3.lang.ok,
          active: true,
          btnClass: 'btn-info',
          name: 'ok',
          trigger: (e: Event, modal: ModalElement) => modal.hideModal(),
        }],
        ['modal-inner-scroll'],
      );
    });
  }

  /**
   * This is used to reset the records, internally stored for
   * mass actions. This is needed as those records may no
   * longer be available in the current view and would therefore
   * led to misbehaviour as "unrelated" records get processed.
   *
   * Furthermore, the mass action "bar" is initialized in case the
   * current view contains records. Also a custom event is being
   * dispatched to hide the mass actions, which are only available
   * when at least one record is selected.
   *
   * @param hasRecords Whether the current view contains records
   */
  private resetMassActionState(hasRecords: boolean): void {
    this.markedRecordsForMassAction = [];
    if (hasRecords) {
      const workspaceActions = document.querySelector(Identifiers.workspaceActions) as HTMLElement;
      if (workspaceActions !== null) {
        workspaceActions.classList.remove('hidden');
      }

      const chooseMassAction = document.querySelector(Identifiers.chooseMassAction) as HTMLSelectElement;
      if (chooseMassAction !== null) {
        chooseMassAction.disabled = false;
      }
    }
    // Hide actions and also uncheck all checkboxes
    document.dispatchEvent(new CustomEvent('multiRecordSelection:actions:hide'));
    document.dispatchEvent(new CustomEvent('multiRecordSelection:checkboxes:uncheck'));
  }
}

export default new Backend();
