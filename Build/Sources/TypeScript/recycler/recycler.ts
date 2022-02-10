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

import $ from 'jquery';
import NProgress from 'nprogress';
import '@typo3/backend/input/clearable';
import '@typo3/backend/element/icon-element';
import DeferredAction from '@typo3/backend/action-button/deferred-action';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import Severity from '@typo3/backend/severity';
import RegularEvent from '@typo3/core/event/regular-event';

enum RecyclerIdentifiers {
  searchForm = '#recycler-form',
  searchText = '#recycler-form [name=search-text]',
  searchSubmitBtn = '#recycler-form button[type=submit]',
  depthSelector = '#recycler-form [name=depth]',
  tableSelector = '#recycler-form [name=pages]',
  recyclerTable = '#itemsInRecycler',
  paginator = '#recycler-index nav',
  reloadAction = 'a[data-action=reload]',
  undo = 'a[data-action=undo]',
  delete = 'a[data-action=delete]',
  massUndo = 'button[data-multi-record-selection-action=massundo]',
  massDelete = 'button[data-multi-record-selection-action=massdelete]',
}

/**
 * Module: @typo3/recycler/recycler
 * RequireJS module for Recycler
 */
class Recycler {
  public elements: any = {}; // filled in getElements()
  public paging: any = {
    currentPage: 1,
    totalPages: 1,
    totalItems: 0,
    itemsPerPage: TYPO3.settings.Recycler.pagingSize,
  };
  public markedRecordsForMassAction: Array<string> = [];

  /**
   * Reloads the page tree
   */
  public static refreshPageTree(): void {
    top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
  }

  constructor() {
    $((): void => {
      this.initialize();
    });
  }

  /**
   * Gets required elements
   */
  private getElements(): void {
    this.elements = {
      $searchForm: $(RecyclerIdentifiers.searchForm),
      $searchTextField: $(RecyclerIdentifiers.searchText),
      $searchSubmitBtn: $(RecyclerIdentifiers.searchSubmitBtn),
      $depthSelector: $(RecyclerIdentifiers.depthSelector),
      $tableSelector: $(RecyclerIdentifiers.tableSelector),
      $recyclerTable: $(RecyclerIdentifiers.recyclerTable),
      $tableBody: $(RecyclerIdentifiers.recyclerTable).find('tbody'),
      $paginator: $(RecyclerIdentifiers.paginator),
      $reloadAction: $(RecyclerIdentifiers.reloadAction),
      $massUndo: $(RecyclerIdentifiers.massUndo),
      $massDelete: $(RecyclerIdentifiers.massDelete),
    };
  }

  /**
   * Register events
   */
  private registerEvents(): void {
    // submitting the form
    this.elements.$searchForm.on('submit', (e: JQueryEventObject): void => {
      e.preventDefault();
      if (this.elements.$searchTextField.val() !== '') {
        this.loadDeletedElements();
      }
    });

    // changing the search field
    this.elements.$searchTextField.on('keyup', (e: JQueryEventObject): void => {
      let $me = $(e.currentTarget);

      if ($me.val() !== '') {
        this.elements.$searchSubmitBtn.removeClass('disabled');
      } else {
        this.elements.$searchSubmitBtn.addClass('disabled');
        this.loadDeletedElements();
      }
    });
    this.elements.$searchTextField.get(0).clearable(
      {
        onClear: () => {
          this.elements.$searchSubmitBtn.addClass('disabled');
          this.loadDeletedElements();
        },
      },
    );

    // changing "depth"
    this.elements.$depthSelector.on('change', (): void => {
      $.when(this.loadAvailableTables()).done((): void => {
        this.loadDeletedElements();
      });
    });

    // changing "table"
    this.elements.$tableSelector.on('change', (): void => {
      this.paging.currentPage = 1;
      this.loadDeletedElements();
    });

    // clicking "recover" in single row
    new RegularEvent('click', this.undoRecord).delegateTo(document, RecyclerIdentifiers.undo);

    // clicking "delete" in single row
    new RegularEvent('click', this.deleteRecord).delegateTo(document, RecyclerIdentifiers.delete);

    this.elements.$reloadAction.on('click', (e: JQueryEventObject): void => {
      e.preventDefault();
      $.when(this.loadAvailableTables()).done((): void => {
        this.loadDeletedElements();
      });
    });

    // clicking an action in the paginator
    this.elements.$paginator.on('click', '[data-action]', (e: JQueryEventObject): void => {
      e.preventDefault();

      const $el: JQuery = $(e.currentTarget);
      let reload: boolean = false;

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
          this.paging.currentPage = parseInt($el.find('span').text(), 10);
          reload = true;
          break;
        default:
      }

      if (reload) {
        this.loadDeletedElements();
      }
    });

    if (!TYPO3.settings.Recycler.deleteDisable) {
      this.elements.$massDelete.show();
    } else {
      this.elements.$massDelete.remove();
    }

    // checkboxes in the table
    new RegularEvent('multiRecordSelection:checkbox:state:changed', this.handleCheckboxStateChanged).bindTo(document);
    new RegularEvent('multiRecordSelection:action:massundo', this.undoRecord).bindTo(document);
    new RegularEvent('multiRecordSelection:action:massdelete', this.deleteRecord).bindTo(document);
  }

  /**
   * Initialize the recycler module
   */
  private initialize(): void {
    NProgress.configure({parent: '.module-loading-indicator', showSpinner: false});

    this.getElements();
    this.registerEvents();

    if (TYPO3.settings.Recycler.depthSelection > 0) {
      this.elements.$depthSelector.val(TYPO3.settings.Recycler.depthSelection).trigger('change');
    } else {
      $.when(this.loadAvailableTables()).done((): void => {
        this.loadDeletedElements();
      });
    }
  }

  /**
   * Handles the clicks on checkboxes in the records table
   */
  private handleCheckboxStateChanged = (e: Event): void => {
    const $checkbox = $(e.target);
    const $tr = $checkbox.parents('tr');
    const table = $tr.data('table');
    const uid = $tr.data('uid');
    const record = table + ':' + uid;

    if ($checkbox.prop('checked')) {
      this.markedRecordsForMassAction.push(record);
    } else {
      const index = this.markedRecordsForMassAction.indexOf(record);
      if (index > -1) {
        this.markedRecordsForMassAction.splice(index, 1);
      }
    }

    if (this.markedRecordsForMassAction.length > 0) {
      this.elements.$massUndo.find('span.text').text(
        this.createMessage(TYPO3.lang['button.undoselected'], [this.markedRecordsForMassAction.length])
      );
      this.elements.$massDelete.find('span.text').text(
        this.createMessage(TYPO3.lang['button.deleteselected'], [this.markedRecordsForMassAction.length])
      );
    } else {
      this.resetMassActionButtons();
    }
  }

  /**
   * Resets the mass action state
   */
  private resetMassActionButtons(): void {
    this.markedRecordsForMassAction = [];
    this.elements.$massUndo.find('span.text').text(TYPO3.lang['button.undo']);
    this.elements.$massDelete.find('span.text').text(TYPO3.lang['button.delete']);
    document.dispatchEvent(new CustomEvent('multiRecordSelection:actions:hide'));
  }

  /**
   * Loads all tables which contain deleted records.
   *
   */
  private loadAvailableTables(): JQueryXHR {
    return $.ajax({
      url: TYPO3.settings.ajaxUrls.recycler,
      dataType: 'json',
      data: {
        action: 'getTables',
        startUid: TYPO3.settings.Recycler.startUid,
        depth: this.elements.$depthSelector.find('option:selected').val(),
      },
      beforeSend: () => {
        NProgress.start();
        this.elements.$tableSelector.val('');
        this.paging.currentPage = 1;
      },
      success: (data: any) => {
        const tables: Array<JQuery> = [];
        this.elements.$tableSelector.children().remove();
        $.each(data, (_: number, value: Array<string>) => {
          const tableName = value[0];
          const deletedRecords = value[1];
          const tableDescription = value[2] ? value[2] : TYPO3.lang.label_allrecordtypes;
          const optionText = tableDescription + ' (' + deletedRecords + ')';
          tables.push($('<option />').val(tableName).text(optionText));
        });

        if (tables.length > 0) {
          this.elements.$tableSelector.append(tables);
          if (TYPO3.settings.Recycler.tableSelection !== '') {
            this.elements.$tableSelector.val(TYPO3.settings.Recycler.tableSelection);
          }
        }
      },
      complete: () => {
        NProgress.done();
      },
    });
  }

  /**
   * Loads the deleted elements, based on the filters
   */
  private loadDeletedElements(): JQueryXHR {
    return $.ajax({
      url: TYPO3.settings.ajaxUrls.recycler,
      dataType: 'json',
      data: {
        action: 'getDeletedRecords',
        depth: this.elements.$depthSelector.find('option:selected').val(),
        startUid: TYPO3.settings.Recycler.startUid,
        table: this.elements.$tableSelector.find('option:selected').val(),
        filterTxt: this.elements.$searchTextField.val(),
        start: (this.paging.currentPage - 1) * this.paging.itemsPerPage,
        limit: this.paging.itemsPerPage,
      },
      beforeSend: () => {
        NProgress.start();
        this.resetMassActionButtons();
      },
      success: (data: any) => {
        this.elements.$tableBody.html(data.rows);
        this.buildPaginator(data.totalItems);
      },
      complete: () => {
        NProgress.done();
      },
    });
  }

  private deleteRecord = (e: Event): void => {
    if (TYPO3.settings.Recycler.deleteDisable) {
      return;
    }

    const $tr = $(e.target).parents('tr');
    const isMassDelete = $tr.parent().prop('tagName') !== 'TBODY'; // deleteRecord() was invoked by the mass delete button
    let records: Array<string>;
    let message: string;

    if (isMassDelete) {
      records = this.markedRecordsForMassAction;
      message = TYPO3.lang['modal.massdelete.text'];
    } else {
      const uid = $tr.data('uid');
      const table = $tr.data('table');
      const recordTitle = $tr.data('recordtitle');
      records = [table + ':' + uid];
      message = table === 'pages' ? TYPO3.lang['modal.deletepage.text'] : TYPO3.lang['modal.deletecontent.text'];
      message = this.createMessage(message, [recordTitle, '[' + records[0] + ']']);
    }

    Modal.confirm(TYPO3.lang['modal.delete.header'], message, Severity.error, [
      {
        text: TYPO3.lang['button.cancel'],
        btnClass: 'btn-default',
        trigger: function(): void {
          Modal.dismiss();
        },
      }, {
        text: TYPO3.lang['button.delete'],
        btnClass: 'btn-danger',
        action: new DeferredAction(() => {
          return Promise.resolve(this.callAjaxAction('delete', records, isMassDelete));
        }),
      },
    ]);
  }

  private undoRecord = (e: Event): void => {
    const $tr = $(e.target).parents('tr');
    const isMassUndo = $tr.parent().prop('tagName') !== 'TBODY'; // undoRecord() was invoked by the mass delete button

    let records: Array<string>;
    let messageText: string;
    let recoverPages: boolean;
    if (isMassUndo) {
      records = this.markedRecordsForMassAction;
      messageText = TYPO3.lang['modal.massundo.text'];
      recoverPages = true;
    } else {
      const uid = $tr.data('uid');
      const table = $tr.data('table');
      const recordTitle = $tr.data('recordtitle');

      records = [table + ':' + uid];
      recoverPages = table === 'pages';
      messageText = recoverPages ? TYPO3.lang['modal.undopage.text'] : TYPO3.lang['modal.undocontent.text'];
      messageText = this.createMessage(messageText, [recordTitle, '[' + records[0] + ']']);

      if (recoverPages && $tr.data('parentDeleted')) {
        messageText += TYPO3.lang['modal.undo.parentpages'];
      }
    }

    let $message: JQuery = null;
    if (recoverPages) {
      $message = $('<div />').append(
        $('<p />').text(messageText),
        $('<div />', {class: 'checkbox'}).append(
          $('<label />').append(TYPO3.lang['modal.undo.recursive']).prepend($('<input />', {
            id: 'undo-recursive',
            type: 'checkbox',
          })),
        ),
      );
    } else {
      $message = $('<p />').text(messageText);
    }

    Modal.confirm(TYPO3.lang['modal.undo.header'], $message, Severity.ok, [
      {
        text: TYPO3.lang['button.cancel'],
        btnClass: 'btn-default',
        trigger: function(): void {
          Modal.dismiss();
        },
      }, {
        text: TYPO3.lang['button.undo'],
        btnClass: 'btn-success',
        action: new DeferredAction(() => {
          return Promise.resolve(this.callAjaxAction(
            'undo',
            typeof records === 'object' ? records : [records],
            isMassUndo,
            $message.find('#undo-recursive').prop('checked'),
          ));
        }),
      },
    ]);
  }

  /**
   * @param {string} action
   * @param {Object} records
   * @param {boolean} isMassAction
   * @param {boolean} recursive
   */
  private callAjaxAction(action: string, records: Object, isMassAction: boolean, recursive: boolean = false): JQueryXHR|void {
    let data: any = {
      records: records,
      action: '',
    };
    let reloadPageTree: boolean = false;
    if (action === 'undo') {
      data.action = 'undoRecords';
      data.recursive = recursive ? 1 : 0;
      reloadPageTree = true;
    } else if (action === 'delete') {
      data.action = 'deleteRecords';
    } else {
      return;
    }

    return $.ajax({
      url: TYPO3.settings.ajaxUrls.recycler,
      type: 'POST',
      dataType: 'json',
      data: data,
      beforeSend: () => {
        NProgress.start();
      },
      success: (responseData: any) => {
        if (responseData.success) {
          Notification.success('', responseData.message);
        } else {
          Notification.error('', responseData.message);
        }

        // reload recycler data
        this.paging.currentPage = 1;

        $.when(this.loadAvailableTables()).done((): void => {
          this.loadDeletedElements();
          if (isMassAction) {
            this.resetMassActionButtons();
          }

          if (reloadPageTree) {
            Recycler.refreshPageTree();
          }
        });
      },
      complete: () => {
        NProgress.done();
      },
    });
  }

  /**
   * Replaces the placeholders with actual values
   */
  private createMessage(message: string, placeholders: Array<any>): string {
    if (typeof message === 'undefined') {
      return '';
    }

    return message.replace(
      /\{([0-9]+)\}/g,
      function(_: string, index: any): string {
        return placeholders[index];
      },
    );
  }


  /**
   * Build the paginator
   */
  private buildPaginator(totalItems: number): void {
    if (totalItems === 0) {
      this.elements.$paginator.contents().remove();
      return;
    }

    this.paging.totalItems = totalItems;
    this.paging.totalPages = Math.ceil(totalItems / this.paging.itemsPerPage);

    if (this.paging.totalPages === 1) {
      // early abort if only one page is available
      this.elements.$paginator.contents().remove();
      return;
    }

    const $ul = $('<ul />', {class: 'pagination'}),
      liElements = [],
      $controlFirstPage = $('<li />', {class: 'page-item'}).append(
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
        $('<button />', {class: 'page-link', type: 'button', 'data-action': 'page'}).append(
          $('<span />').text(i),
        ),
      );
      liElements.push($li);
    }

    $ul.append($controlFirstPage, liElements, $controlLastPage);
    this.elements.$paginator.html($ul);
  }
}

/**
 * Changes the markup of a pagination action being disabled
 */
$.fn.disablePagingAction = function(): void {
  $(this).addClass('disabled').find('button').prop('disabled', true);
};

export default new Recycler();
