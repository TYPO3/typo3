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

import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import $ from 'jquery';
import 'nprogress';
import {SeverityEnum} from 'TYPO3/CMS/Backend/Enum/Severity';
import 'TYPO3/CMS/Backend/Input/Clearable';
import Workspaces from './Workspaces';
import Modal = require('TYPO3/CMS/Backend/Modal');
import Persistent = require('TYPO3/CMS/Backend/Storage/Persistent');
import Tooltip = require('TYPO3/CMS/Backend/Tooltip');
import Utility = require('TYPO3/CMS/Backend/Utility');
import Viewport = require('TYPO3/CMS/Backend/Viewport');
import Wizard = require('TYPO3/CMS/Backend/Wizard');
import SecurityUtility = require('TYPO3/CMS/Core/SecurityUtility');

enum Identifiers {
  searchForm = '#workspace-settings-form',
  searchTextField = '#workspace-settings-form input[name="search-text"]',
  searchSubmitBtn = '#workspace-settings-form button[type="submit"]',
  depthSelector = '#workspace-settings-form [name="depth"]',
  languageSelector = '#workspace-settings-form select[name="languages"]',
  chooseStageAction = '#workspace-actions-form [name="stage-action"]',
  chooseSelectionAction = '#workspace-actions-form [name="selection-action"]',
  chooseMassAction = '#workspace-actions-form [name="mass-action"]',
  container = '#workspace-panel',
  actionIcons = '#workspace-action-icons',
  toggleAll = '.t3js-toggle-all',
  previewLinksButton = '.t3js-preview-link',
  pagination = '#workspace-pagination',
}

class Backend extends Workspaces {
  private elements: { [key: string]: JQuery } = {};
  private settings: { [key: string]: string | number } = {
    dir: 'ASC',
    id: TYPO3.settings.Workspaces.id,
    language: TYPO3.settings.Workspaces.language,
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
  private allToggled: boolean = false;
  private latestPath: string = '';
  private markedRecordsForMassAction: Array<any> = [];
  private indentationPadding: number = 26;

  /**
   * Reloads the page tree
   */
  private static refreshPageTree(): void {
    if (Viewport.NavigationContainer && Viewport.NavigationContainer.PageTree) {
      Viewport.NavigationContainer.PageTree.refreshTree();
    }
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

  constructor() {
    super();

    $((): void => {
      let persistedDepth;
      this.getElements();
      this.registerEvents();

      if (Persistent.isset('this.Module.depth')) {
        persistedDepth = Persistent.get('this.Module.depth');
        this.elements.$depthSelector.val(persistedDepth);
        this.settings.depth = persistedDepth;
      } else {
        this.settings.depth = TYPO3.settings.Workspaces.depth;
      }

      this.loadWorkspaceComponents();
    });
  }

  private getElements(): void {
    this.elements.$searchForm = $(Identifiers.searchForm);
    this.elements.$searchTextField = $(Identifiers.searchTextField);
    this.elements.$searchSubmitBtn = $(Identifiers.searchSubmitBtn);
    this.elements.$depthSelector = $(Identifiers.depthSelector);
    this.elements.$languageSelector = $(Identifiers.languageSelector);
    this.elements.$container = $(Identifiers.container);
    this.elements.$tableBody = this.elements.$container.find('tbody');
    this.elements.$actionIcons = $(Identifiers.actionIcons);
    this.elements.$toggleAll = $(Identifiers.toggleAll);
    this.elements.$chooseStageAction = $(Identifiers.chooseStageAction);
    this.elements.$chooseSelectionAction = $(Identifiers.chooseSelectionAction);
    this.elements.$chooseMassAction = $(Identifiers.chooseMassAction);
    this.elements.$previewLinksButton = $(Identifiers.previewLinksButton);
    this.elements.$pagination = $(Identifiers.pagination);
  }

  private registerEvents(): void {
    $(document).on('click', '[data-action="swap"]', (e: JQueryEventObject): void => {
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
          'swap-confirm',
          'Swap',
          TYPO3.lang['window.swap.message'],
          SeverityEnum.info,
        );
        Wizard.addFinalProcessingSlide((): void => {
          // We passed this slide, swap the record now
          this.sendRemoteRequest(
            this.generateRemoteActionsPayload('swapSingleRecord', [
              row.dataset.table,
              row.dataset.t3ver_oid,
              row.dataset.uid,
            ]),
          ).then((): void => {
            Wizard.dismiss();
            this.getWorkspaceInfos();
            Backend.refreshPageTree();
          });
        }).done((): void => {
          Wizard.show();
        });
      });
    }).on('click', '[data-action="prevstage"]', (e: JQueryEventObject): void => {
      this.sendToStage($(e.currentTarget).closest('tr'), 'prev');
    }).on('click', '[data-action="nextstage"]', (e: JQueryEventObject): void => {
      this.sendToStage($(e.currentTarget).closest('tr'), 'next');
    }).on('click', '[data-action="changes"]', this.viewChanges)
      .on('click', '[data-action="preview"]', this.openPreview)
      .on('click', '[data-action="open"]', (e: JQueryEventObject): void => {
        const row = <HTMLTableRowElement>e.currentTarget.closest('tr');
        let newUrl = TYPO3.settings.FormEngine.moduleUrl
          + '&returnUrl=' + encodeURIComponent(document.location.href)
          + '&id=' + TYPO3.settings.Workspaces.id + '&edit[' + row.dataset.table + '][' + row.dataset.uid + ']=edit';

        window.location.href = newUrl;
      }).on('click', '[data-action="version"]', (e: JQueryEventObject): void => {
        const row = <HTMLTableRowElement>e.currentTarget.closest('tr');
        const recordUid = row.dataset.table === 'pages' ? row.dataset.t3ver_oid : row.dataset.pid;
        window.location.href = top.TYPO3.configuration.pageModuleUrl
        + '&id=' + recordUid
        + '&returnUrl=' + encodeURIComponent(window.location.href);
      }).on('click', '[data-action="remove"]', this.confirmDeleteRecordFromWorkspace)
      .on('click', '[data-action="expand"]', (e: JQueryEventObject): void => {
        const $me = $(e.currentTarget);
        const $target = this.elements.$tableBody.find($me.data('target'));
        let iconIdentifier;

        if ($target.first().attr('aria-expanded') === 'true') {
          iconIdentifier = 'apps-pagetree-expand';
        } else {
          iconIdentifier = 'apps-pagetree-collapse';
        }

        $me.empty().append(this.getPreRenderedIcon(iconIdentifier));
      });
    $(window.top.document).on('click', '.t3js-workspace-recipients-selectall', (e: JQueryEventObject): void => {
      e.preventDefault();
      $('.t3js-workspace-recipient', window.top.document).not(':disabled').prop('checked', true);
    }).on('click', '.t3js-workspace-recipients-deselectall', (e: JQueryEventObject): void => {
      e.preventDefault();
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
    (<HTMLInputElement>this.elements.$searchTextField.get(0)).clearable(
      {
        onClear: (): void => {
          this.elements.$searchSubmitBtn.addClass('disabled');
          this.settings.filterTxt = '';
          this.getWorkspaceInfos();
        },
      },
    );

    // checkboxes in the table
    this.elements.$toggleAll.on('click', (): void => {
      this.allToggled = !this.allToggled;
      this.elements.$tableBody.find('input[type="checkbox"]').prop('checked', this.allToggled).trigger('change');
    });
    this.elements.$tableBody.on('change', 'tr input[type=checkbox]', this.handleCheckboxChange);

    // Listen for depth changes
    this.elements.$depthSelector.on('change', (e: JQueryEventObject): void => {
      const depth = (<HTMLSelectElement>e.target).value;
      Persistent.set('this.Module.depth', depth);
      this.settings.depth = depth;
      this.getWorkspaceInfos();
    });

    // Generate preview links
    this.elements.$previewLinksButton.on('click', this.generatePreviewLinks);

    // Listen for language changes
    this.elements.$languageSelector.on('change', (e: JQueryEventObject): void => {
      const $me = $(e.target);
      this.settings.language = $me.val();

      this.sendRemoteRequest([
        this.generateRemoteActionsPayload('saveLanguageSelection', [$me.val()]),
        this.generateRemotePayload('getWorkspaceInfos', this.settings),
      ]).then((response: any): void => {
        this.elements.$languageSelector.prev().html($me.find(':selected').data('icon'));
        this.renderWorkspaceInfos(response[1].result);
      });
    });

    // Listen for actions
    this.elements.$chooseStageAction.on('change', this.sendToSpecificStageAction);
    this.elements.$chooseSelectionAction.on('change', this.runSelectionAction);
    this.elements.$chooseMassAction.on('change', this.runMassAction);

    // clicking an action in the paginator
    this.elements.$pagination.on('click', 'a[data-action]', (e: JQueryEventObject): void => {
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

  private handleCheckboxChange = (e: JQueryEventObject): void => {
    const $checkbox = $(e.currentTarget);
    const $tr = $checkbox.parents('tr');
    const table = $tr.data('table');
    const uid = $tr.data('uid');
    const t3ver_oid = $tr.data('t3ver_oid');
    const record = table + ':' + uid + ':' + t3ver_oid;

    if ($checkbox.prop('checked')) {
      this.markedRecordsForMassAction.push(record);
      $tr.addClass('warning');
    } else {
      const index = this.markedRecordsForMassAction.indexOf(record);
      if (index > -1) {
        this.markedRecordsForMassAction.splice(index, 1);
      }
      $tr.removeClass('warning');
    }

    this.elements.$chooseStageAction.prop('disabled', this.markedRecordsForMassAction.length === 0);
    this.elements.$chooseSelectionAction.prop('disabled', this.markedRecordsForMassAction.length === 0);
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
   * Loads the workspace components, like available stage actions and items of the workspace
   */
  private loadWorkspaceComponents(): void {
    this.sendRemoteRequest([
      this.generateRemotePayload('getWorkspaceInfos', this.settings),
      this.generateRemotePayload('getStageActions', {}),
      this.generateRemoteMassActionsPayload('getMassStageActions', {}),
      this.generateRemotePayload('getSystemLanguages', {
        pageUid: this.elements.$container.data('pageUid'),
      }),
    ]).then(async (response: AjaxResponse): Promise<void> => {
      const resolvedResponse = await response.resolve();
      this.elements.$depthSelector.prop('disabled', false);

      // Records
      this.renderWorkspaceInfos(resolvedResponse[0].result);

      // Stage actions
      const stageActions = resolvedResponse[1].result.data;
      let i;
      for (i = 0; i < stageActions.length; ++i) {
        this.elements.$chooseStageAction.append(
          $('<option />').val(stageActions[i].uid).text(stageActions[i].title),
        );
      }

      // Mass actions
      const massActions = resolvedResponse[2].result.data;
      for (i = 0; i < massActions.length; ++i) {
        this.elements.$chooseSelectionAction.append(
          $('<option />').val(massActions[i].action).text(massActions[i].title),
        );

        this.elements.$chooseMassAction.append(
          $('<option />').val(massActions[i].action).text(massActions[i].title),
        );
      }

      // Languages
      const languages = resolvedResponse[3].result.data;
      for (i = 0; i < languages.length; ++i) {
        const $option = $('<option />').val(languages[i].uid).text(languages[i].title).data('icon', languages[i].icon);
        if (String(languages[i].uid) === String(TYPO3.settings.Workspaces.language)) {
          $option.prop('selected', true);
          this.elements.$languageSelector.prev().html(languages[i].icon);
        }
        this.elements.$languageSelector.append($option);
      }
      this.elements.$languageSelector.prop('disabled', false);
    });
  }

  /**
   * Gets the workspace infos
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
   * Renders fetched workspace informations
   *
   * @param {Object} result
   */
  private renderWorkspaceInfos(result: any): void {
    this.elements.$tableBody.children().remove();
    this.allToggled = false;
    this.elements.$chooseStageAction.prop('disabled', true);
    this.elements.$chooseSelectionAction.prop('disabled', true);
    this.elements.$chooseMassAction.prop('disabled', result.data.length === 0);

    this.buildPagination(result.total);

    for (let i = 0; i < result.data.length; ++i) {
      const item = result.data[i];
      const $actions = $('<div />', {class: 'btn-group'});
      let $integrityIcon: JQuery;
      $actions.append(
        this.getAction(
          item.Workspaces_CollectionChildren > 0 && item.Workspaces_CollectionCurrent !== '',
          'expand',
          'apps-pagetree-collapse',
        ).attr('title', TYPO3.lang['tooltip.expand'])
          .attr('data-target', '[data-collection="' + item.Workspaces_CollectionCurrent + '"]')
          .attr('data-toggle', 'collapse'),
        $('<button />', {
          class: 'btn btn-default',
          'data-action': 'changes',
          'data-toggle': 'tooltip',
          title: TYPO3.lang['tooltip.showChanges'],
        }).append(this.getPreRenderedIcon('actions-document-info')),
        this.getAction(
          item.allowedAction_swap && item.Workspaces_CollectionParent === '',
          'swap',
          'actions-version-swap-version')
          .attr('title', TYPO3.lang['tooltip.swap']),
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
          true,
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
        $integrityIcon = $(TYPO3.settings.Workspaces.icons[item.integrity.status]);
        $integrityIcon
          .attr('data-toggle', 'tooltip')
          .attr('data-placement', 'top')
          .attr('data-html', 'true')
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
      const $checkbox = $('<label />', {class: 'btn btn-default btn-checkbox'}).append(
        $('<input />', {type: 'checkbox'}),
        $('<span />', {class: 't3-icon fa'}),
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
        rowConfiguration['data-collection'] = item.Workspaces_CollectionParent;
        rowConfiguration.class = 'collapse';
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
            item.icon_Workspace + '&nbsp;'
            + '<a href="#" data-action="changes">'
            + '<span class="workspace-state-' + item.state_Workspace + '" title="' + item.label_Workspace + '">' + item.label_Workspace_crop + '</span>'
            + '</a>',
          ),
          $('<td />', {class: 't3js-title-live'}).html(
            item.icon_Live
            + '&nbsp;'
            + '<span class"workspace-live-title title="' + item.label_Live + '">' + item.label_Live_crop + '</span>'
          ),
          $('<td />').text(item.label_Stage),
          $('<td />').empty().append($integrityIcon),
          $('<td />').html(item.language.icon),
          $('<td />', {class: 'text-right nowrap'}).append($actions),
        ),
      );

      Tooltip.initialize('[data-toggle="tooltip"]', {
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

    const $ul = $('<ul />', {class: 'pagination pagination-block'});
    const liElements: Array<JQuery> = [];
    const $controlFirstPage = $('<li />').append(
        $('<a />', {'data-action': 'previous'}).append(
          $('<span />', {class: 't3-icon fa fa-arrow-left'}),
        ),
      ),
      $controlLastPage = $('<li />').append(
        $('<a />', {'data-action': 'next'}).append(
          $('<span />', {class: 't3-icon fa fa-arrow-right'}),
        ),
      );

    if (this.paging.currentPage === 1) {
      $controlFirstPage.disablePagingAction();
    }

    if (this.paging.currentPage === this.paging.totalPages) {
      $controlLastPage.disablePagingAction();
    }

    for (let i = 1; i <= this.paging.totalPages; i++) {
      const $li = $('<li />', {class: this.paging.currentPage === i ? 'active' : ''});
      $li.append(
        $('<a />', {'data-action': 'page', 'data-page': i}).append(
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
          $('<li />', {role: 'presentation'}).append(
            $('<a />', {
              href: '#workspace-changes',
              'aria-controls': 'workspace-changes',
              role: 'tab',
              'data-toggle': 'tab',
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
          $('<li />', {role: 'presentation'}).append(
            $('<a />', {
              href: '#workspace-comments',
              'aria-controls': 'workspace-comments',
              role: 'tab',
              'data-toggle': 'tab',
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
          $('<li />', {role: 'presentation'}).append(
            $('<a />', {
              href: '#workspace-history',
              'aria-controls': 'workspace-history',
              role: 'tab',
              'data-toggle': 'tab',
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
      $tabsNav.find('li').first().addClass('active');
      $tabsContent.find('.tab-pane').first().addClass('active');

      // Attach tabs
      $content.append(
        $('<div />').append(
          $tabsNav,
          $tabsContent,
        ),
      );

      if ($tr.data('stage') !== $tr.data('prevStage')) {
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
   * @param {Event} e
   */
  private openPreview = (e: JQueryEventObject): void => {
    const $tr = $(e.currentTarget).closest('tr');

    this.sendRemoteRequest(
      this.generateRemoteActionsPayload('viewSingleRecord', [
        $tr.data('table'), $tr.data('uid'),
      ]),
    ).then(async (response: AjaxResponse): Promise<void> => {
      // eslint-disable-next-line no-eval
      eval((await response.resolve())[0].result);
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
  private runSelectionAction = (): void => {
    const selectedAction = this.elements.$chooseSelectionAction.val();
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
    }).done((): void => {
      Wizard.show();

      Wizard.getComponent().on('wizard-dismissed', (): void => {
        this.elements.$chooseSelectionAction.val('');
      });
    });
  }

  /**
   * Runs a mass action
   */
  private runMassAction = (): void => {
    const selectedAction = this.elements.$chooseMassAction.val();
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
    let doSwap = false;

    switch (selectedAction) {
      case 'publish':
        massAction = 'publishWorkspace';
        break;
      case 'swap':
        massAction = 'publishWorkspace';
        doSwap = true;
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
          language: this.settings.language,
          swap: doSwap,
        }),
      ).then(sendRequestsUntilAllProcessed);
    }).done((): void => {
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
        'data-toggle': 'tooltip',
      }).append(this.getPreRenderedIcon(iconIdentifier));
    }
    return $('<span />', {class: 'btn btn-default disabled'}).append(this.getPreRenderedIcon('empty-empty'));
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
   * Gets the pre-rendered icon
   * This method is intended to be dropped once we use Fluid's StandaloneView.
   *
   * @param {String} identifier
   * @returns {$}
   */
  private getPreRenderedIcon(identifier: string): JQuery {
    return this.elements.$actionIcons.find('[data-identifier="' + identifier + '"]').clone();
  }
}

export = new Backend();
