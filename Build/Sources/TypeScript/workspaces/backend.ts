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

import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import $ from 'jquery';
import '@typo3/backend/element/icon-element';
import {SeverityEnum} from '@typo3/backend/enum/severity';
import '@typo3/backend/input/clearable';
import Workspaces from './workspaces';
import Modal from '@typo3/backend/modal';
import Persistent from '@typo3/backend/storage/persistent';
import Tooltip from '@typo3/backend/tooltip';
import Utility from '@typo3/backend/utility';
import Wizard from '@typo3/backend/wizard';
import SecurityUtility from '@typo3/core/security-utility';
import windowManager from '@typo3/backend/window-manager';
import RegularEvent from '@typo3/core/event/regular-event';

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
  private elements: { [key: string]: JQuery } = {};
  private settings: { [key: string]: string | number } = {
    dir: 'ASC',
    id: TYPO3.settings.Workspaces.id,
    depth: 1,
    language: 'all',
    limit: 30,
    query: '',
    sort: 'label_Live',
    start: 0,
    filterTxt: '',
  };
  private paging: { [key: string]: number } = {
    currentPage: 1,
    totalPages: 1,
    totalItems: 0,
  };
  private latestPath: string = '';
  private markedRecordsForMassAction: Array<any> = [];
  private indentationPadding: number = 26;

  /**
   * Reloads the page tree
   */
  private static refreshPageTree(): void {
    top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
  }

  /**
   * Generates the diff view of a record
   *
   * @param {Object} diff
   * @return {$}
   */
  private static generateDiffView(diff: Array<any>): JQuery {
    const $diff = $('<div />', {class: 'diff'});

    for (let currentDiff of diff) {
      $diff.append(
        $('<div />', {class: 'diff-item'}).append(
          $('<div />', {class: 'diff-item-title'}).text(currentDiff.label),
          $('<div />', {class: 'diff-item-result diff-item-result-inline'}).html(currentDiff.content),
        ),
      );
    }
    return $diff;
  }

  /**
   * Generates the comments view of a record
   *
   * @param {Object} comments
   * @return {$}
   */
  private static generateCommentView(comments: Array<any>): JQuery {
    const $comments = $('<div />');

    for (let comment of comments) {
      const $panel = $('<div />', {class: 'panel panel-default'});

      if (comment.user_comment.length > 0) {
        $panel.append(
          $('<div />', {class: 'panel-body'}).html(comment.user_comment),
        );
      }

      $panel.append(
        $('<div />', {class: 'panel-footer'}).append(
          $('<span />', {class: 'label label-success'}).text(comment.stage_title),
          $('<span />', {class: 'label label-info'}).text(comment.tstamp),
        ),
      );

      $comments.append(
        $('<div />', {class: 'media'}).append(
          $('<div />', {class: 'media-left text-center'}).text(comment.user_username).prepend(
            $('<div />').html(comment.user_avatar),
          ),
          $('<div />', {class: 'media-body'}).append($panel),
        ),
      );
    }

    return $comments;
  }

  /**
   * Renders the record's history
   *
   * @param {Object} data
   * @return {JQuery}
   */
  private static generateHistoryView(data: Array<any>): JQuery {
    const $history = $('<div />');

    for (let currentData of data) {
      const $panel = $('<div />', {class: 'panel panel-default'});
      let $diff;

      if (typeof currentData.differences === 'object') {
        if (currentData.differences.length === 0) {
          // Somehow here are no differences. What a pity, skip that record
          continue;
        }
        $diff = $('<div />', {class: 'diff'});

        for (let j = 0; j < currentData.differences.length; ++j) {
          $diff.append(
            $('<div />', {class: 'diff-item'}).append(
              $('<div />', {class: 'diff-item-title'}).text(currentData.differences[j].label),
              $('<div />', {class: 'diff-item-result diff-item-result-inline'}).html(currentData.differences[j].html),
            ),
          );
        }

        $panel.append(
          $('<div />').append($diff),
        );
      } else {
        $panel.append(
          $('<div />', {class: 'panel-body'}).text(currentData.differences),
        );
      }
      $panel.append(
        $('<div />', {class: 'panel-footer'}).append(
          $('<span />', {class: 'label label-info'}).text(currentData.datetime),
        ),
      );

      $history.append(
        $('<div />', {class: 'media'}).append(
          $('<div />', {class: 'media-left text-center'}).text(currentData.user).prepend(
            $('<div />').html(currentData.user_avatar),
          ),
          $('<div />', {class: 'media-body'}).append($panel),
        ),
      );
    }

    return $history;
  }

  /**
   * This changes the checked state of a parent checkbox belonging
   * to the given collection (e.g. sys_file_reference > tt_content).
   *
   * This also sets a data attribute which will be respected by
   * the multi record selection module. This is to prevent the
   * module from overriding the manually changed state.
   *
   * @param {string} collection The collection identifier
   * @param {boolean} check The checked state
   */
  private static changeCollectionParentState(collection: string, check: boolean): void {
    const parent: HTMLInputElement = document.querySelector('tr[data-collection-current="' + collection + '"] input[type=checkbox]');
    if (parent !== null && parent.checked !== check) {
      parent.checked = check;
      parent.dataset.manuallyChanged = 'true';
      parent.dispatchEvent(new CustomEvent('multiRecordSelection:checkbox:state:changed', {bubbles: true, cancelable: false}));
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
   *
   * @param {string} collectionCurrent The collection current identifier
   * @param {boolean} check The checked state
   */
  private static changeCollectionChildrenState(collectionCurrent: string, check: boolean): void {
    const collectionChildren: NodeListOf<HTMLInputElement> = document.querySelectorAll('tr[data-collection="' + collectionCurrent + '"] input[type=checkbox]');
    if (collectionChildren.length) {
      collectionChildren.forEach((checkbox: HTMLInputElement): void => {
        if (checkbox.checked !== check) {
          checkbox.checked = check;
          checkbox.dataset.manuallyChanged = 'true';
          checkbox.dispatchEvent(new CustomEvent('multiRecordSelection:checkbox:state:changed', {bubbles: true, cancelable: false}));
        }
      })
    }
  }

  constructor() {
    super();

    $((): void => {
      this.getElements();
      this.registerEvents();
      this.notifyWorkspaceSwitchAction();

      // Set the depth from the main element
      this.settings.depth = this.elements.$depthSelector.val();
      this.settings.language = this.elements.$languageSelector.val();
      this.settings.stage = this.elements.$stagesSelector.val();

      // Fetch workspace info (listing) if workspace is accessible
      if (this.elements.$container.length) {
        this.getWorkspaceInfos();
      }
    });
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
   *
   * @param {Array} payload
   * @return {$}
   */
  private checkIntegrity(payload: object): Promise<AjaxResponse> {
    return this.sendRemoteRequest(
      this.generateRemotePayload('checkIntegrity', payload),
    );
  }

  private getElements(): void {
    this.elements.$searchForm = $(Identifiers.searchForm);
    this.elements.$searchTextField = $(Identifiers.searchTextField);
    this.elements.$searchSubmitBtn = $(Identifiers.searchSubmitBtn);
    this.elements.$depthSelector = $(Identifiers.depthSelector);
    this.elements.$languageSelector = $(Identifiers.languageSelector);
    this.elements.$stagesSelector = $(Identifiers.stagesSelector);
    this.elements.$container = $(Identifiers.container);
    this.elements.$contentsContainer = $(Identifiers.contentsContainer);
    this.elements.$noContentsContainer = $(Identifiers.noContentsContainer);
    this.elements.$tableBody = this.elements.$contentsContainer.find('tbody');
    this.elements.$workspaceActions = $(Identifiers.workspaceActions);
    this.elements.$chooseStageAction = $(Identifiers.chooseStageAction);
    this.elements.$chooseSelectionAction = $(Identifiers.chooseSelectionAction);
    this.elements.$chooseMassAction = $(Identifiers.chooseMassAction);
    this.elements.$previewLinksButton = $(Identifiers.previewLinksButton);
    this.elements.$pagination = $(Identifiers.pagination);
  }

  private registerEvents(): void {
    $(document).on('click', '[data-action="publish"]', (e: JQueryEventObject): void => {
      const row = <HTMLTableRowElement>e.target.closest('tr');
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
          this.addIntegrityCheckWarningToWizard();
        }

        Wizard.setForceSelection(false);
        Wizard.addSlide(
          'publish-confirm',
          'Publish',
          TYPO3.lang['window.publish.message'],
          SeverityEnum.info,
        );
        Wizard.addFinalProcessingSlide((): void => {
          // We passed this slide, publish the record now
          this.sendRemoteRequest(
            this.generateRemoteActionsPayload('publishSingleRecord', [
              row.dataset.table,
              row.dataset.t3ver_oid,
              row.dataset.uid,
            ]),
          ).then((): void => {
            Wizard.dismiss();
            this.getWorkspaceInfos();
            Backend.refreshPageTree();
          });
        }).then((): void => {
          Wizard.show();
        });
      });
    }).on('click', '[data-action="prevstage"]', (e: JQueryEventObject): void => {
      this.sendToStage($(e.currentTarget).closest('tr'), 'prev');
    }).on('click', '[data-action="nextstage"]', (e: JQueryEventObject): void => {
      this.sendToStage($(e.currentTarget).closest('tr'), 'next');
    }).on('click', '[data-action="changes"]', this.viewChanges)
      .on('click', '[data-action="preview"]', this.openPreview.bind(this))
      .on('click', '[data-action="open"]', (e: JQueryEventObject): void => {
        const row = <HTMLTableRowElement>e.currentTarget.closest('tr');
        let newUrl = TYPO3.settings.FormEngine.moduleUrl
          + '&returnUrl=' + encodeURIComponent(document.location.href)
          + '&id=' + TYPO3.settings.Workspaces.id + '&edit[' + row.dataset.table + '][' + row.dataset.uid + ']=edit';

        window.location.href = newUrl;
      }).on('click', '[data-action="version"]', (e: JQueryEventObject): void => {
        const row = <HTMLTableRowElement>e.currentTarget.closest('tr');
        const recordUid = row.dataset.table === 'pages' ? row.dataset.t3ver_oid : row.dataset.pid;
        window.location.href = TYPO3.settings.WebLayout.moduleUrl
        + '&id=' + recordUid;
      }).on('click', '[data-action="remove"]', this.confirmDeleteRecordFromWorkspace)
      .on('click', '[data-action="expand"]', (e: JQueryEventObject): void => {
        const $me = $(e.currentTarget);
        let iconIdentifier;

        if ($me.first().attr('aria-expanded') === 'true') {
          iconIdentifier = 'apps-pagetree-expand';
        } else {
          iconIdentifier = 'apps-pagetree-collapse';
        }

        $me.empty().append(this.getIcon(iconIdentifier));
      });
    $(window.top.document).on('click', '.t3js-workspace-recipients-selectall', (): void => {
      $('.t3js-workspace-recipient', window.top.document).not(':disabled').prop('checked', true);
    }).on('click', '.t3js-workspace-recipients-deselectall', (): void => {
      $('.t3js-workspace-recipient', window.top.document).not(':disabled').prop('checked', false);
    });

    this.elements.$searchForm.on('submit', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.settings.filterTxt = this.elements.$searchTextField.val();
      this.getWorkspaceInfos();
    });

    this.elements.$searchTextField.on('keyup', (e: JQueryEventObject): void => {
      const me = <HTMLInputElement>e.target;

      if (me.value !== '') {
        this.elements.$searchSubmitBtn.removeClass('disabled');
      } else {
        this.elements.$searchSubmitBtn.addClass('disabled');
        this.getWorkspaceInfos();
      }
    });

    const searchTextField = <HTMLInputElement>this.elements.$searchTextField.get(0);
    if (searchTextField !== undefined) {
      searchTextField.clearable(
        {
          onClear: (): void => {
            this.elements.$searchSubmitBtn.addClass('disabled');
            this.settings.filterTxt = '';
            this.getWorkspaceInfos();
          },
        },
      );
    }

    // checkboxes in the table
    new RegularEvent('multiRecordSelection:checkbox:state:changed', this.handleCheckboxStateChanged).bindTo(document);

    // Listen for depth changes
    this.elements.$depthSelector.on('change', (e: JQueryEventObject): void => {
      const depth = (<HTMLSelectElement>e.target).value;
      Persistent.set('moduleData.workspaces.settings.depth', depth);
      this.settings.depth = depth;
      this.getWorkspaceInfos();
    });

    // Generate preview links
    this.elements.$previewLinksButton.on('click', this.generatePreviewLinks);

    // Listen for language changes
    this.elements.$languageSelector.on('change', (e: JQueryEventObject): void => {
      const $me = $(e.target);
      Persistent.set('moduleData.workspaces.settings.language', $me.val());
      this.settings.language = $me.val();
      this.sendRemoteRequest(
        this.generateRemotePayload('getWorkspaceInfos', this.settings),
      ).then(async (response: AjaxResponse): Promise<void> => {
        const actionResponse = await response.resolve();
        this.elements.$languageSelector.prev().html($me.find(':selected').data('icon'));
        this.renderWorkspaceInfos(actionResponse[0].result);
      });
    });

    this.elements.$stagesSelector.on('change', (e: JQueryEventObject): void => {
      const stage = (<HTMLSelectElement>e.target).value;
      Persistent.set('moduleData.workspaces.settings.stage', stage);
      this.settings.stage = stage;
      this.getWorkspaceInfos();
    });

    // Listen for actions
    this.elements.$chooseStageAction.on('change', this.sendToSpecificStageAction);
    this.elements.$chooseSelectionAction.on('change', this.runSelectionAction);
    this.elements.$chooseMassAction.on('change', this.runMassAction);

    // clicking an action in the paginator
    this.elements.$pagination.on('click', '[data-action]', (e: JQueryEventObject): void => {
      e.preventDefault();

      const $el = $(e.currentTarget);
      let reload = false;

      switch ($el.data('action')) {
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
          this.paging.currentPage = parseInt($el.data('page'), 10);
          reload = true;
          break;
        default:
          throw 'Unknown action "' + $el.data('action') + '"';
      }

      if (reload) {
        // Adjust settings
        this.settings.start = parseInt(this.settings.limit.toString(), 10) * (this.paging.currentPage - 1);
        this.getWorkspaceInfos();
      }
    });
  }

  private handleCheckboxStateChanged = (e: Event): void => {
    const $checkbox = $(e.target);
    const $tr = $checkbox.parents('tr');
    const checked = $checkbox.prop('checked');
    const table = $tr.data('table');
    const uid = $tr.data('uid');
    const t3ver_oid = $tr.data('t3ver_oid');
    const record = table + ':' + uid + ':' + t3ver_oid;

    if (checked) {
      this.markedRecordsForMassAction.push(record);
    } else {
      const index = this.markedRecordsForMassAction.indexOf(record);
      if (index > -1) {
        this.markedRecordsForMassAction.splice(index, 1);
      }
    }

    if ($tr.data('collectionCurrent')) {
      // change checked state from all collection children
      Backend.changeCollectionChildrenState($tr.data('collectionCurrent'), checked);
    } else if ($tr.data('collection')) {
      // change checked state from all collection children and the collection parent
      Backend.changeCollectionChildrenState($tr.data('collection'), checked);
      Backend.changeCollectionParentState($tr.data('collection'), checked);
    }

    this.elements.$chooseMassAction.prop('disabled', this.markedRecordsForMassAction.length > 0);
  }

  /**
   * Sends a record to a stage
   *
   * @param {Object} $row
   * @param {String} direction
   */
  private sendToStage($row: JQuery, direction: string): void {
    let nextStage: string;
    let stageWindowAction: string;
    let stageExecuteAction: string;

    if (direction === 'next') {
      nextStage = $row.data('nextStage');
      stageWindowAction = 'sendToNextStageWindow';
      stageExecuteAction = 'sendToNextStageExecute';
    } else if (direction === 'prev') {
      nextStage = $row.data('prevStage');
      stageWindowAction = 'sendToPrevStageWindow';
      stageExecuteAction = 'sendToPrevStageExecute';
    } else {
      throw 'Invalid direction given.';
    }

    this.sendRemoteRequest(
      this.generateRemoteActionsPayload(stageWindowAction, [
        $row.data('uid'), $row.data('table'), $row.data('t3ver_oid'),
      ]),
    ).then(async (response: AjaxResponse): Promise<void> => {
      const $modal = this.renderSendToStageWindow(await response.resolve());
      $modal.on('button.clicked', (modalEvent: JQueryEventObject): void => {
        if ((<HTMLAnchorElement>modalEvent.target).name === 'ok') {
          const serializedForm = Utility.convertFormToObject(modalEvent.currentTarget.querySelector('form'));
          serializedForm.affects = {
            table: $row.data('table'),
            nextStage: nextStage,
            t3ver_oid: $row.data('t3ver_oid'),
            uid: $row.data('uid'),
            elements: [],
          };

          this.sendRemoteRequest([
            this.generateRemoteActionsPayload(stageExecuteAction, [serializedForm]),
            this.generateRemotePayload('getWorkspaceInfos', this.settings),
          ]).then(async (response: AjaxResponse): Promise<void> => {
            const requestResponse = await response.resolve();
            $modal.modal('hide');
            this.renderWorkspaceInfos(requestResponse[1].result);
            Backend.refreshPageTree();
          });
        }
      });
    });
  }

  /**
   * Gets the workspace infos (= filling the contents).
   *
   * @return {Promise}
   * @protected
   */
  private getWorkspaceInfos(): void {
    this.sendRemoteRequest(
      this.generateRemotePayload('getWorkspaceInfos', this.settings),
    ).then(async (response: AjaxResponse): Promise<void> => {
      this.renderWorkspaceInfos((await response.resolve())[0].result);
    });
  }

  /**
   * Renders fetched workspace information
   *
   * @param {Object} result
   */
  private renderWorkspaceInfos(result: any): void {
    this.elements.$tableBody.children().remove();
    this.resetMassActionState(result.data.length);
    this.buildPagination(result.total);

    // disable the contents area
    if (result.total === 0) {
      this.elements.$contentsContainer.hide();
      this.elements.$noContentsContainer.show();
    } else {
      this.elements.$contentsContainer.show();
      this.elements.$noContentsContainer.hide();
    }

    for (let i = 0; i < result.data.length; ++i) {
      const item = result.data[i];
      const $actions = $('<div />', {class: 'btn-group'});
      let $integrityIcon: JQuery;
      let hasSubitems = item.Workspaces_CollectionChildren > 0 && item.Workspaces_CollectionCurrent !== '';
      $actions.append(
        this.getAction(
          hasSubitems,
          'expand',
          (item.expanded ? 'apps-pagetree-expand' : 'apps-pagetree-collapse'),
        ).attr('title', TYPO3.lang['tooltip.expand'])
          .attr('data-bs-target', '[data-collection="' + item.Workspaces_CollectionCurrent + '"]')
          .attr('aria-expanded', !hasSubitems || item.expanded ? 'true' : 'false')
          .attr('data-bs-toggle', 'collapse'),
        this.getAction(
          item.hasChanges,
          'changes',
          'actions-document-info')
          .attr('title', TYPO3.lang['tooltip.showChanges']),
        this.getAction(
          item.allowedAction_publish && item.Workspaces_CollectionParent === '',
          'publish',
          'actions-version-swap-version')
          .attr('title', TYPO3.lang['tooltip.publish']),
        this.getAction(
          item.allowedAction_view,
          'preview',
          'actions-version-workspace-preview',
        ).attr('title', TYPO3.lang['tooltip.viewElementAction']),
        this.getAction(
          item.allowedAction_edit,
          'open',
          'actions-open',
        ).attr('title', TYPO3.lang['tooltip.editElementAction']),
        this.getAction(
          item.allowedAction_versionPageOpen,
          'version',
          'actions-version-page-open',
        ).attr('title', TYPO3.lang['tooltip.openPage']),
        this.getAction(
          item.allowedAction_delete,
          'remove',
          'actions-version-document-remove').attr('title', TYPO3.lang['tooltip.discardVersion'],
        ),
      );

      if (item.integrity.messages !== '') {
        $integrityIcon = $('<span>' + this.getIcon(item.integrity.status) + '</span>');
        $integrityIcon
          .attr('data-bs-toggle', 'tooltip')
          .attr('data-bs-placement', 'top')
          .attr('data-bs-html', 'true')
          .attr('title', item.integrity.messages);
      }

      if (this.latestPath !== item.path_Workspace) {
        this.latestPath = item.path_Workspace;
        this.elements.$tableBody.append(
          $('<tr />').append(
            $('<th />'),
            $('<th />', {colspan: 6}).html(
              '<span title="' + item.path_Workspace + '">' + item.path_Workspace_crop + '</span>'
            ),
          ),
        );
      }
      const $checkbox = $('<span />', {class: 'form-check form-toggle'}).append(
        $('<input />', {type: 'checkbox', class: 'form-check-input t3js-multi-record-selection-check'})
      );

      const rowConfiguration: { [key: string]: any } = {
        'data-uid': item.uid,
        'data-pid': item.livepid,
        'data-t3ver_oid': item.t3ver_oid,
        'data-t3ver_wsid': item.t3ver_wsid,
        'data-table': item.table,
        'data-next-stage': item.value_nextStage,
        'data-prev-stage': item.value_prevStage,
        'data-stage': item.stage,
      };

      if (item.Workspaces_CollectionParent !== '') {
        // fetch parent and see if this one is expanded
        let parentItem = result.data.find((element: any) => {
          return element.Workspaces_CollectionCurrent === item.Workspaces_CollectionParent;
        });
        rowConfiguration['data-collection'] = item.Workspaces_CollectionParent;
        rowConfiguration.class = 'collapse' + (parentItem.expanded ? ' show' :  '');
      } else if (item.Workspaces_CollectionCurrent !== '') {
        // Set CollectionCurrent attribute for parent records
        rowConfiguration['data-collection-current'] = item.Workspaces_CollectionCurrent
      }

      this.elements.$tableBody.append(
        $('<tr />', rowConfiguration).append(
          $('<td />').empty().append($checkbox),
          $('<td />', {
            class: 't3js-title-workspace',
            style: item.Workspaces_CollectionLevel > 0
              ? 'padding-left: ' + this.indentationPadding * item.Workspaces_CollectionLevel + 'px'
              : '',
          }).html(
            '<span class="icon icon-size-small">' + this.getIcon(item.icon_Workspace) + '</span>'
            + '&nbsp;'
            + '<a href="#" data-action="changes">'
            + '<span class="workspace-state-' + item.state_Workspace + '" title="' + item.label_Workspace + '">' + item.label_Workspace_crop + '</span>'
            + '</a>',
          ),
          $('<td />', {class: 't3js-title-live'}).html(
            '<span class="icon icon-size-small">' + this.getIcon(item.icon_Live) + '</span>'
            + '&nbsp;'
            + '<span class"workspace-live-title title="' + item.label_Live + '">' + item.label_Live_crop + '</span>'
          ),
          $('<td />').text(item.label_Stage),
          $('<td />').empty().append($integrityIcon),
          $('<td />').html(this.getIcon(item.language.icon)),
          $('<td />', {class: 'text-end nowrap'}).append($actions),
        ),
      );

      Tooltip.initialize('[data-bs-toggle="tooltip"]', {
        delay: {
          show: 500,
          hide: 100,
        },
        trigger: 'hover',
        container: 'body',
      });
    }
  }

  /**
   * Renders the pagination
   *
   * @param {Number} totalItems
   */
  private buildPagination(totalItems: number): void {
    if (totalItems === 0) {
      this.elements.$pagination.contents().remove();
      return;
    }

    this.paging.totalItems = totalItems;
    this.paging.totalPages = Math.ceil(totalItems / parseInt(this.settings.limit.toString(), 10));

    if (this.paging.totalPages === 1) {
      // early abort if only one page is available
      this.elements.$pagination.contents().remove();
      return;
    }

    const $ul = $('<ul />', {class: 'pagination'});
    const liElements: Array<JQuery> = [];
    const $controlFirstPage = $('<li />', {class: 'page-item'}).append(
        $('<button />', {class: 'page-link', type: 'button', 'data-action': 'previous'}).append(
          $('<typo3-backend-icon />', {'identifier': 'actions-arrow-left-alt', 'size': 'small'}),
        ),
      ),
      $controlLastPage = $('<li />', {class: 'page-item'}).append(
        $('<button />', {class: 'page-link', type: 'button', 'data-action': 'next'}).append(
          $('<typo3-backend-icon />', {'identifier': 'actions-arrow-right-alt', 'size': 'small'}),
        ),
      );

    if (this.paging.currentPage === 1) {
      $controlFirstPage.disablePagingAction();
    }

    if (this.paging.currentPage === this.paging.totalPages) {
      $controlLastPage.disablePagingAction();
    }

    for (let i = 1; i <= this.paging.totalPages; i++) {
      const $li = $('<li />', {class: 'page-item' + (this.paging.currentPage === i ? ' active' : '')});
      $li.append(
        $('<button />', {class: 'page-link', type: 'button',  'data-action': 'page', 'data-page': i}).append(
          $('<span />').text(i),
        ),
      );
      liElements.push($li);
    }

    $ul.append($controlFirstPage, liElements, $controlLastPage);
    this.elements.$pagination.empty().append($ul);
  }

  /**
   * View changes of a record
   *
   * @param {Event} e
   */
  private viewChanges = (e: JQueryEventObject): void => {
    e.preventDefault();

    const $tr = $(e.currentTarget).closest('tr');
    this.sendRemoteRequest(
      this.generateRemotePayload('getRowDetails', {
        stage: $tr.data('stage'),
        t3ver_oid: $tr.data('t3ver_oid'),
        table: $tr.data('table'),
        uid: $tr.data('uid'),
        filterFields: true
      }),
    ).then(async (response: AjaxResponse): Promise<void> => {
      const item = (await response.resolve())[0].result.data[0];
      const $content = $('<div />');
      const $tabsNav = $('<ul />', {class: 'nav nav-tabs', role: 'tablist'});
      const $tabsContent = $('<div />', {class: 'tab-content'});
      const modalButtons = [];

      $content.append(
        $('<p />').html(TYPO3.lang.path.replace('{0}', item.path_Live)),
        $('<p />').html(
          TYPO3.lang.current_step.replace('{0}', item.label_Stage)
            .replace('{1}', item.stage_position)
            .replace('{2}', item.stage_count),
        ),
      );

      if (item.diff.length > 0) {
        $tabsNav.append(
          $('<li />', {role: 'presentation', class: 'nav-item'}).append(
            $('<a />', {
              class: 'nav-link',
              href: '#workspace-changes',
              'aria-controls': 'workspace-changes',
              role: 'tab',
              'data-bs-toggle': 'tab',
            }).text(TYPO3.lang['window.recordChanges.tabs.changeSummary']),
          ),
        );
        $tabsContent.append(
          $('<div />', {role: 'tabpanel', class: 'tab-pane', id: 'workspace-changes'}).append(
            $('<div />', {class: 'form-section'}).append(
              Backend.generateDiffView(item.diff),
            ),
          ),
        );
      }

      if (item.comments.length > 0) {
        $tabsNav.append(
          $('<li />', {role: 'presentation', class: 'nav-item'}).append(
            $('<a />', {
              class: 'nav-link',
              href: '#workspace-comments',
              'aria-controls': 'workspace-comments',
              role: 'tab',
              'data-bs-toggle': 'tab',
            }).html(TYPO3.lang['window.recordChanges.tabs.comments'] + '&nbsp;').append(
              $('<span />', {class: 'badge'}).text(item.comments.length),
            ),
          ),
        );
        $tabsContent.append(
          $('<div />', {role: 'tabpanel', class: 'tab-pane', id: 'workspace-comments'}).append(
            $('<div />', {class: 'form-section'}).append(
              Backend.generateCommentView(item.comments),
            ),
          ),
        );
      }

      if (item.history.total > 0) {
        $tabsNav.append(
          $('<li />', {role: 'presentation', class: 'nav-item'}).append(
            $('<a />', {
              class: 'nav-link',
              href: '#workspace-history',
              'aria-controls': 'workspace-history',
              role: 'tab',
              'data-bs-toggle': 'tab',
            }).text(TYPO3.lang['window.recordChanges.tabs.history']),
          ),
        );

        $tabsContent.append(
          $('<div />', {role: 'tabpanel', class: 'tab-pane', id: 'workspace-history'}).append(
            $('<div />', {class: 'form-section'}).append(
              Backend.generateHistoryView(item.history.data),
            ),
          ),
        );
      }

      // Mark the first tab and pane as active
      $tabsNav.find('li > a').first().addClass('active');
      $tabsContent.find('.tab-pane').first().addClass('active');

      // Attach tabs
      $content.append(
        $('<div />').append(
          $tabsNav,
          $tabsContent,
        ),
      );

      if (item.label_PrevStage !== false && $tr.data('stage') !== $tr.data('prevStage')) {
        modalButtons.push({
          text: item.label_PrevStage.title,
          active: true,
          btnClass: 'btn-default',
          name: 'prevstage',
          trigger: (): void => {
            Modal.currentModal.trigger('modal-dismiss');
            this.sendToStage($tr, 'prev');
          },
        });
      }

      if (item.label_NextStage !== false) {
        modalButtons.push({
          text: item.label_NextStage.title,
          active: true,
          btnClass: 'btn-default',
          name: 'nextstage',
          trigger: (): void => {
            Modal.currentModal.trigger('modal-dismiss');
            this.sendToStage($tr, 'next');
          },
        });
      }
      modalButtons.push({
        text: TYPO3.lang.close,
        active: true,
        btnClass: 'btn-info',
        name: 'cancel',
        trigger: (): void => {
          Modal.currentModal.trigger('modal-dismiss');
        },
      });

      Modal.advanced({
        type: Modal.types.default,
        title: TYPO3.lang['window.recordInformation'].replace('{0}', $tr.find('.t3js-title-live').text().trim()),
        content: $content,
        severity: SeverityEnum.info,
        buttons: modalButtons,
        size: Modal.sizes.medium,
      });
    });
  }

  /**
   * Opens a record in a preview window
   *
   * @param {JQueryEventObject} evt
   */
  private openPreview(evt: JQueryEventObject): void {
    const $tr = $(evt.currentTarget).closest('tr');

    this.sendRemoteRequest(
      this.generateRemoteActionsPayload('viewSingleRecord', [
        $tr.data('table'), $tr.data('uid'),
      ]),
    ).then(async (response: AjaxResponse): Promise<void> => {
      const previewUri: string = (await response.resolve())[0].result;
      windowManager.localOpen(previewUri);
    });
  }

  /**
   * Shows a confirmation modal and deletes the selected record from workspace.
   *
   * @param {Event} e
   */
  private confirmDeleteRecordFromWorkspace = (e: JQueryEventObject): void => {
    const $tr = $(e.target).closest('tr');
    const $modal = Modal.confirm(
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
            $modal.modal('hide');
          },
        },
        {
          text: TYPO3.lang.ok,
          btnClass: 'btn-warning',
          name: 'ok',
        },
      ],
    );
    $modal.on('button.clicked', (modalEvent: JQueryEventObject): void => {
      if ((<HTMLAnchorElement>modalEvent.target).name === 'ok') {
        this.sendRemoteRequest([
          this.generateRemoteActionsPayload('deleteSingleRecord', [
            $tr.data('table'),
            $tr.data('uid'),
          ]),
        ]).then((): void => {
          $modal.modal('hide');
          this.getWorkspaceInfos();
          Backend.refreshPageTree();
        });
      }
    });
  }

  /**
   * Runs a mass action
   */
  private runSelectionAction = (e: JQueryEventObject): void => {
    const selectedAction = $(e.currentTarget).val();
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
      Wizard.setForceSelection(false);
      this.renderSelectionActionWizard(selectedAction, affectedRecords);
    } else {
      this.checkIntegrity(
        {
          selection: affectedRecords,
          type: 'selection',
        },
      ).then(async (response: AjaxResponse): Promise<void> => {
        Wizard.setForceSelection(false);
        if ((await response.resolve())[0].result.result === 'warning') {
          this.addIntegrityCheckWarningToWizard();
        }
        this.renderSelectionActionWizard(selectedAction, affectedRecords);
      });
    }
  }

  /**
   * Adds a slide to the wizard concerning an integrity check warning.
   */
  private addIntegrityCheckWarningToWizard = (): void => {
    Wizard.addSlide(
      'integrity-warning',
      'Warning',
      TYPO3.lang['integrity.hasIssuesDescription'] + '<br>' + TYPO3.lang['integrity.hasIssuesQuestion'],
      SeverityEnum.warning,
    );
  }

  /**
   * Renders the wizard for selection actions
   *
   * @param {String} selectedAction
   * @param {Array<object>} affectedRecords
   */
  private renderSelectionActionWizard(selectedAction: string, affectedRecords: Array<object>): void {
    Wizard.addSlide(
      'mass-action-confirmation',
      TYPO3.lang['window.selectionAction.title'],
      '<p>'
      + new SecurityUtility().encodeHtml(TYPO3.lang['tooltip.' + selectedAction + 'Selected'])
      + '</p>',
      SeverityEnum.warning,
    );
    Wizard.addFinalProcessingSlide((): void => {
      this.sendRemoteRequest(
        this.generateRemoteActionsPayload('executeSelectionAction', {
          action: selectedAction,
          selection: affectedRecords,
        }),
      ).then((): void => {
        this.markedRecordsForMassAction = [];
        this.getWorkspaceInfos();
        Wizard.dismiss();
        Backend.refreshPageTree();
      });
    }).then((): void => {
      Wizard.show();

      Wizard.getComponent().on('wizard-dismissed', (): void => {
        this.elements.$chooseSelectionAction.val('');
      });
    });
  }

  /**
   * Runs a mass action
   */
  private runMassAction = (e: JQueryEventObject): void => {
    const selectedAction = $(e.currentTarget).val();
    const integrityCheckRequired = selectedAction !== 'discard';

    if (selectedAction.length === 0) {
      // Don't do anything if that value is empty
      return;
    }

    if (!integrityCheckRequired) {
      Wizard.setForceSelection(false);
      this.renderMassActionWizard(selectedAction);
    } else {
      this.checkIntegrity(
        {
          language: this.settings.language,
          type: selectedAction,
        },
      ).then(async (response: AjaxResponse): Promise<void> => {
        Wizard.setForceSelection(false);
        if ((await response.resolve())[0].result.result === 'warning') {
          this.addIntegrityCheckWarningToWizard();
        }
        this.renderMassActionWizard(selectedAction);
      });
    }
  }

  /**
   * Renders the wizard for mass actions
   *
   * @param {String} selectedAction
   */
  private renderMassActionWizard(selectedAction: string): void {
    let massAction: string;

    switch (selectedAction) {
      case 'publish':
        massAction = 'publishWorkspace';
        break;
      case 'discard':
        massAction = 'flushWorkspace';
        break;
      default:
        throw 'Invalid mass action ' + selectedAction + ' called.';
    }

    const securityUtility = new SecurityUtility();
    Wizard.setForceSelection(false);
    Wizard.addSlide(
      'mass-action-confirmation',
      TYPO3.lang['window.massAction.title'],
      '<p>'
      + securityUtility.encodeHtml(TYPO3.lang['tooltip.' + selectedAction + 'All']) + '<br><br>'
      + securityUtility.encodeHtml(TYPO3.lang['tooltip.affectWholeWorkspace'])
      + '</p>',
      SeverityEnum.warning,
    );

    const sendRequestsUntilAllProcessed = async (response: AjaxResponse): Promise<void> => {
      const result = (await response.resolve())[0].result;
      // Make sure to process all items
      if (result.processed < result.total) {
        this.sendRemoteRequest(
          this.generateRemoteMassActionsPayload(massAction, result),
        ).then(sendRequestsUntilAllProcessed);
      } else {
        this.getWorkspaceInfos();
        Wizard.dismiss();
      }
    };

    Wizard.addFinalProcessingSlide((): void => {
      this.sendRemoteRequest(
        this.generateRemoteMassActionsPayload(massAction, {
          init: true,
          total: 0,
          processed: 0,
          language: this.settings.language
        }),
      ).then(sendRequestsUntilAllProcessed);
    }).then((): void => {
      Wizard.show();

      Wizard.getComponent().on('wizard-dismissed', (): void => {
        this.elements.$chooseMassAction.val('');
      });
    });
  }

  /**
   * Sends marked records to a stage
   *
   * @param {Event} e
   */
  private sendToSpecificStageAction = (e: JQueryEventObject): void => {
    const affectedRecords: Array<{ [key: string]: number | string }> = [];
    const stage = $(e.currentTarget).val();
    for (let i = 0; i < this.markedRecordsForMassAction.length; ++i) {
      const affected = this.markedRecordsForMassAction[i].split(':');
      affectedRecords.push({
        table: affected[0],
        uid: affected[1],
        t3ver_oid: affected[2],
      });
    }
    this.sendRemoteRequest(
      this.generateRemoteActionsPayload('sendToSpecificStageWindow', [
        stage, affectedRecords,
      ]),
    ).then(async (response: AjaxResponse): Promise<void> => {
      const $modal = this.renderSendToStageWindow(await response.resolve());
      $modal.on('button.clicked', (modalEvent: JQueryEventObject): void => {
        if ((<HTMLAnchorElement>modalEvent.target).name === 'ok') {
          const serializedForm = Utility.convertFormToObject(modalEvent.currentTarget.querySelector('form'));
          serializedForm.affects = {
            elements: affectedRecords,
            nextStage: stage,
          };

          this.sendRemoteRequest([
            this.generateRemoteActionsPayload('sendToSpecificStageExecute', [serializedForm]),
            this.generateRemotePayload('getWorkspaceInfos', this.settings),
          ]).then(async (response: AjaxResponse): Promise<void> => {
            const actionResponse = await response.resolve();
            $modal.modal('hide');
            this.renderWorkspaceInfos(actionResponse[1].result);
            Backend.refreshPageTree();
          });
        }
      }).on('modal-destroyed', (): void => {
        this.elements.$chooseStageAction.val('');
      });
    });
  }

  /**
   * Renders the action button based on the user's permission.
   *
   * @param {string} condition
   * @param {string} action
   * @param {string} iconIdentifier
   * @return {JQuery}
   */
  private getAction(condition: boolean, action: string, iconIdentifier: string): JQuery {
    if (condition) {
      return $('<button />', {
        class: 'btn btn-default',
        'data-action': action,
        'data-bs-toggle': 'tooltip',
      }).append(this.getIcon(iconIdentifier));
    }
    return $('<span />', {class: 'btn btn-default disabled'}).append(this.getIcon('empty-empty'));
  }

  /**
   * Fetches and renders available preview links
   */
  private generatePreviewLinks = (): void => {
    this.sendRemoteRequest(
      this.generateRemoteActionsPayload('generateWorkspacePreviewLinksForAllLanguages', [
        this.settings.id,
      ]),
    ).then(async (response: AjaxResponse): Promise<void> => {
      const result = (await response.resolve())[0].result;
      const $list = $('<dl />');

      $.each(result, (language: string, url: string): void => {
        $list.append(
          $('<dt />').text(language),
          $('<dd />').append(
            $('<a />', {href: url, target: '_blank'}).text(url),
          ),
        );
      });

      Modal.show(
        TYPO3.lang.previewLink,
        $list,
        SeverityEnum.info,
        [{
          text: TYPO3.lang.ok,
          active: true,
          btnClass: 'btn-info',
          name: 'ok',
          trigger: (): void => {
            Modal.currentModal.trigger('modal-dismiss');
          },
        }],
        ['modal-inner-scroll'],
      );
    });
  }

  /**
   * Gets a specific icon. A specific "switch" is added due to the integrity
   * flags that are added in the IntegrityService.
   */
  private getIcon(identifier: string): string {
    switch (identifier) {
      case 'language':
        identifier = 'flags-multiple';
        break;
      case 'integrity':
      case 'info':
        identifier = 'status-dialog-information';
        break;
      case 'success':
        identifier = 'status-dialog-ok';
        break;
      case 'warning':
        identifier = 'status-dialog-warning';
        break;
      case 'error':
        identifier = 'status-dialog-error';
        break;
      default:
    }
    return '<typo3-backend-icon identifier="' + identifier + '" size="small"></typo3-backend-icon>';
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
      this.elements.$workspaceActions.removeClass('hidden');
      this.elements.$chooseMassAction.prop('disabled', false);
    }
    document.dispatchEvent(new CustomEvent('multiRecordSelection:actions:hide'));
  }
}

/**
 * Changes the markup of a pagination action being disabled
 */
$.fn.disablePagingAction = function(): void {
  $(this).addClass('disabled').find('button').prop('disabled', true);
};

export default new Backend();
