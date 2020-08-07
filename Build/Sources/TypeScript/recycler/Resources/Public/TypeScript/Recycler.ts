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
import 'TYPO3/CMS/Backend/Input/Clearable';
import DeferredAction = require('TYPO3/CMS/Backend/ActionButton/DeferredAction');
import Modal = require('TYPO3/CMS/Backend/Modal');
import Notification = require('TYPO3/CMS/Backend/Notification');
import Severity = require('TYPO3/CMS/Backend/Severity');

enum RecyclerIdentifiers {
  searchForm = '#recycler-form',
  searchText = '#recycler-form [name=search-text]',
  searchSubmitBtn = '#recycler-form button[type=submit]',
  depthSelector = '#recycler-form [name=depth]',
  tableSelector = '#recycler-form [name=pages]',
  recyclerTable = '#itemsInRecycler',
  paginator = '#recycler-index nav',
  reloadAction = 'a[data-action=reload]',
  massUndo = 'button[data-action=massundo]',
  massDelete = 'button[data-action=massdelete]',
  toggleAll = '.t3js-toggle-all',
}

/**
 * Module: TYPO3/CMS/Recycler/Recycler
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
  public allToggled: boolean = false;

  /**
   * Reloads the page tree
   */
  public static refreshPageTree(): void {
    if (top.TYPO3 && top.TYPO3.Backend && top.TYPO3.Backend.NavigationContainer && top.TYPO3.Backend.NavigationContainer.PageTree) {
      top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
    }
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
      $toggleAll: $(RecyclerIdentifiers.toggleAll),
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
    this.elements.$recyclerTable.on('click', '[data-action=undo]', this.undoRecord);

    // clicking "delete" in single row
    this.elements.$recyclerTable.on('click', '[data-action=delete]', this.deleteRecord);

    this.elements.$reloadAction.on('click', (e: JQueryEventObject): void => {
      e.preventDefault();
      $.when(this.loadAvailableTables()).done((): void => {
        this.loadDeletedElements();
      });
    });

    // clicking an action in the paginator
    this.elements.$paginator.on('click', 'a[data-action]', (e: JQueryEventObject): void => {
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

    this.elements.$recyclerTable.on('show.bs.collapse hide.bs.collapse', 'tr.collapse', (e: JQueryEventObject): void => {
      let $trigger = $(e.currentTarget).prev('tr').find('[data-action=expand]'),
        $iconEl = $trigger.find('.t3-icon'),
        removeClass,
        addClass;

      switch (e.type) {
        case 'show':
          removeClass = 't3-icon-pagetree-collapse';
          addClass = 't3-icon-pagetree-expand';
          break;
        case 'hide':
          removeClass = 't3-icon-pagetree-expand';
          addClass = 't3-icon-pagetree-collapse';
          break;
        default:
      }

      $iconEl.removeClass(removeClass).addClass(addClass);
    });

    // checkboxes in the table
    this.elements.$toggleAll.on('click', (): void => {
      this.allToggled = !this.allToggled;
      $('input[type="checkbox"]').prop('checked', this.allToggled).trigger('change');
    });
    this.elements.$recyclerTable.on('change', 'tr input[type=checkbox]', this.handleCheckboxSelects);

    this.elements.$massUndo.on('click', this.undoRecord);
    this.elements.$massDelete.on('click', this.deleteRecord);
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
  private handleCheckboxSelects = (e: JQueryEventObject): void => {
    const $checkbox = $(e.currentTarget);
    const $tr = $checkbox.parents('tr');
    const table = $tr.data('table');
    const uid = $tr.data('uid');
    const record = table + ':' + uid;

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

    if (this.markedRecordsForMassAction.length > 0) {
      if (this.elements.$massUndo.hasClass('disabled')) {
        this.elements.$massUndo.removeClass('disabled').removeAttr('disabled');
      }
      if (this.elements.$massDelete.hasClass('disabled')) {
        this.elements.$massDelete.removeClass('disabled').removeAttr('disabled');
      }

      const btnTextUndo = this.createMessage(TYPO3.lang['button.undoselected'], [this.markedRecordsForMassAction.length]);
      const btnTextDelete = this.createMessage(TYPO3.lang['button.deleteselected'], [this.markedRecordsForMassAction.length]);

      this.elements.$massUndo.find('span.text').text(btnTextUndo);
      this.elements.$massDelete.find('span.text').text(btnTextDelete);

    } else {
      this.resetMassActionButtons();
    }
  }

  /**
   * Resets the mass action state
   */
  private resetMassActionButtons(): void {
    this.markedRecordsForMassAction = [];
    this.elements.$massUndo.addClass('disabled').attr('disabled', true);
    this.elements.$massUndo.find('span.text').text(TYPO3.lang['button.undo']);
    this.elements.$massDelete.addClass('disabled').attr('disabled', true);
    this.elements.$massDelete.find('span.text').text(TYPO3.lang['button.delete']);
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

  private deleteRecord = (e: JQueryEventObject): void => {
    if (TYPO3.settings.Recycler.deleteDisable) {
      return;
    }

    const $tr = $(e.currentTarget).parents('tr');
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

  private undoRecord = (e: JQueryEventObject): void => {
    const $tr = $(e.currentTarget).parents('tr');
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

          // Reset toggle state
          this.allToggled = false;
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

    const $ul = $('<ul />', {class: 'pagination pagination-block'}),
      liElements = [],
      $controlFirstPage = $('<li />').append(
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
        $('<a />', {'data-action': 'page'}).append(
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
  $(this).addClass('disabled').find('.t3-icon').unwrap().wrap($('<span />'));
};

export = new Recycler();
