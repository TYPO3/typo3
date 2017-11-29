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

/**
 * Module: TYPO3/CMS/Recycler/Recycler
 * RequireJS module for Recycler
 */
define(['jquery',
  'nprogress',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/Notification',
  'TYPO3/CMS/Backend/Severity',
  'TYPO3/CMS/Backend/jquery.clearable'
], function($, NProgress, Modal, Notification, Severity) {
  'use strict';

  /**
   *
   * @type {{identifiers: {searchForm: string, searchText: string, searchSubmitBtn: string, depthSelector: string, tableSelector: string, recyclerTable: string, paginator: string, reloadAction: string, massUndo: string, massDelete: string, toggleAll: string}, elements: {}, paging: {currentPage: number, totalPages: number, totalItems: number, itemsPerPage: number}, markedRecordsForMassAction: Array, allToggled: boolean}}
   * @exports TYPO3/CMS/Recycler/Recycler
   */
  var Recycler = {
    identifiers: {
      searchForm: '#recycler-form',
      searchText: '#recycler-form [name=search-text]',
      searchSubmitBtn: '#recycler-form button[type=submit]',
      depthSelector: '#recycler-form [name=depth]',
      tableSelector: '#recycler-form [name=pages]',
      recyclerTable: '#itemsInRecycler',
      paginator: '#recycler-index nav',
      reloadAction: 'a[data-action=reload]',
      massUndo: 'button[data-action=massundo]',
      massDelete: 'button[data-action=massdelete]',
      selectAll: 'button[data-action=selectall]',
      deselectAll: 'button[data-action=deselectall]',
      toggleAll: '.t3js-toggle-all',
      progressBar: '#recycler-index .progress.progress-bar-notice.alert-loading'
    },
    elements: {}, // filled in getElements()
    paging: {
      currentPage: 1,
      totalPages: 1,
      totalItems: 0,
      itemsPerPage: TYPO3.settings.Recycler.pagingSize
    },
    markedRecordsForMassAction: [],
    allToggled: false
  };

  /**
   * Gets required elements
   */
  Recycler.getElements = function() {
    Recycler.elements = {
      $searchForm: $(Recycler.identifiers.searchForm),
      $searchTextField: $(Recycler.identifiers.searchText),
      $searchSubmitBtn: $(Recycler.identifiers.searchSubmitBtn),
      $depthSelector: $(Recycler.identifiers.depthSelector),
      $tableSelector: $(Recycler.identifiers.tableSelector),
      $recyclerTable: $(Recycler.identifiers.recyclerTable),
      $tableBody: $(Recycler.identifiers.recyclerTable).find('tbody'),
      $paginator: $(Recycler.identifiers.paginator),
      $reloadAction: $(Recycler.identifiers.reloadAction),
      $massUndo: $(Recycler.identifiers.massUndo),
      $massDelete: $(Recycler.identifiers.massDelete),
      $selectAll: $(Recycler.identifiers.selectAll),
      $deselectAll: $(Recycler.identifiers.deselectAll),
      $toggleAll: $(Recycler.identifiers.toggleAll),
      $progressBar: $(Recycler.identifiers.progressBar)
    };
  };

  /**
   * Register events
   */
  Recycler.registerEvents = function() {
    // submitting the form
    Recycler.elements.$searchForm.on('submit', function(e) {
      e.preventDefault();
      if (Recycler.elements.$searchTextField.val() !== '') {
        Recycler.loadDeletedElements();
      }
    });

    // changing the search field
    Recycler.elements.$searchTextField.on('keyup', function() {
      var $me = $(this);

      if ($me.val() !== '') {
        Recycler.elements.$searchSubmitBtn.removeClass('disabled');
      } else {
        Recycler.elements.$searchSubmitBtn.addClass('disabled');
        Recycler.loadDeletedElements();
      }
    }).clearable(
      {
        onClear: function() {
          Recycler.elements.$searchSubmitBtn.addClass('disabled');
          Recycler.loadDeletedElements();
        }
      }
    );

    // changing "depth"
    Recycler.elements.$depthSelector.on('change', function() {
      $.when(Recycler.loadAvailableTables()).done(function() {
        Recycler.clearMarked();
        Recycler.loadDeletedElements();
      });
    });

    // changing "table"
    Recycler.elements.$tableSelector.on('change', function() {
      Recycler.paging.currentPage = 1;
      Recycler.clearMarked();
      Recycler.loadDeletedElements();
    });

    // clicking "recover" in single row
    Recycler.elements.$recyclerTable.on('click', '[data-action=undo]', Recycler.undoRecord);

    // clicking "delete" in single row
    Recycler.elements.$recyclerTable.on('click', '[data-action=delete]', Recycler.deleteRecord);

    Recycler.elements.$reloadAction.on('click', function(e) {
      e.preventDefault();
      $.when(Recycler.loadAvailableTables()).done(function() {
        Recycler.loadDeletedElements();
      });
    });

    // clicking an action in the paginator
    Recycler.elements.$paginator.on('click', 'a[data-action]', function(e) {
      e.preventDefault();

      var $el = $(this),
        reload = false;

      switch ($el.data('action')) {
        case 'previous':
          if (Recycler.paging.currentPage > 1) {
            Recycler.paging.currentPage--;
            reload = true;
          }
          break;
        case 'next':
          if (Recycler.paging.currentPage < Recycler.paging.totalPages) {
            Recycler.paging.currentPage++;
            reload = true;
          }
          break;
        case 'page':
          Recycler.paging.currentPage = parseInt($el.find('span').text());
          reload = true;
          break;
      }

      if (reload) {
        Recycler.loadDeletedElements();
        Recycler.loadMarked();
      }
    });

    if (!TYPO3.settings.Recycler.deleteDisable) {
      Recycler.elements.$massDelete.show();
    } else {
      Recycler.elements.$massDelete.remove();
    }

    Recycler.elements.$recyclerTable.on('show.bs.collapse hide.bs.collapse', 'tr.collapse', function(e) {
      var $trigger = $(e.currentTarget).prev('tr').find('[data-action=expand]'),
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
      }

      $iconEl.removeClass(removeClass).addClass(addClass);
    });

    // checkboxes in the table
    Recycler.elements.$recyclerTable.on('change', 'tr input[type=checkbox]', Recycler.handleCheckboxSelects);

    Recycler.elements.$toggleAll.on('click', Recycler.toggleAll);

    Recycler.elements.$massUndo.on('click', function() {
        if (!$(this).hasClass('disabled')) {
          Recycler.undoRecord();
        }
    });
    Recycler.elements.$massDelete.on('click', function() {
        if (!$(this).hasClass('disabled')) {
          Recycler.deleteRecord();
        }
    });
    Recycler.elements.$selectAll.on('click', function() {
        if (!$(this).hasClass('disabled')) {
          Recycler.selectAll();
        }
    });
    Recycler.elements.$deselectAll.on('click', function() {
        if (!$(this).hasClass('disabled')) {
          Recycler.deselectAll();
        }

    });
  };

  /**
   * Initialize the recycler module
   */
  Recycler.initialize = function() {
    NProgress.configure({parent: '.module-loading-indicator', showSpinner: false});

    Recycler.getElements();
    Recycler.registerEvents();

    if (TYPO3.settings.Recycler.depthSelection > 0) {
      Recycler.elements.$depthSelector.val(TYPO3.settings.Recycler.depthSelection).trigger('change');
    } else {
      $.when(Recycler.loadAvailableTables()).done(function() {
        Recycler.loadDeletedElements();
      });
    }
  };

  /**
   * Handles the clicks on checkboxes in the records table
   */
  Recycler.handleCheckboxSelects = function() {
    var $checkbox = $(this),
      $tr = $checkbox.parents('tr'),
      table = $tr.data('table'),
      uid = $tr.data('uid'),
      record = table + ':' + uid;
    if ($checkbox.prop('checked')) {
      if (!Recycler.markedRecordsForMassAction[record]) {
        Recycler.addRecord(record);
        $tr.addClass('warning');
      }
    } else {
      if (!!Recycler.markedRecordsForMassAction[record]) {
        Recycler.subtractRecord(record);
        $tr.removeClass('warning');
      }
    }
    Recycler.selectAllRefresh();
  };


  /**
   * Resets the mass action state
   */
  Recycler.resetMassActionButtons = function() {
    if (!!Recycler.markedRecordsForMassAction) {
      Recycler.persistMarked(Recycler.markedRecordsForMassAction);
    } else {
      Recycler.markedRecordsForMassAction = {};
    }

    Recycler.elements.$massUndo.addClass('disabled');
    Recycler.elements.$massUndo.find('span.text').text(TYPO3.lang['button.undo']);
    Recycler.elements.$massDelete.addClass('disabled');
    Recycler.elements.$massDelete.find('span.text').text(TYPO3.lang['button.delete']);

    Recycler.elements.$selectAll.addClass('disabled');
    Recycler.elements.$selectAll.find('span.text').text(TYPO3.lang['button.selectall']);
    Recycler.elements.$deselectAll.addClass('disabled');
    Recycler.elements.$deselectAll.find('span.text').text(TYPO3.lang['button.deselectall']);
  };

  /**
   * Loads all tables which contain deleted records.
   *
   * @returns {Promise}
   */
  Recycler.loadAvailableTables = function() {
    return $.ajax({
      url: TYPO3.settings.ajaxUrls['recycler'],
      dataType: 'json',
      data: {
        action: 'getTables',
        startUid: TYPO3.settings.Recycler.startUid,
        depth: Recycler.elements.$depthSelector.find('option:selected').val()
      },
      beforeSend: function() {
        NProgress.start();
        Recycler.elements.$tableSelector.val('');
        Recycler.paging.currentPage = 1;
        Recycler.markedRecordsCounter = 0;
      },
      success: function(data) {
        var tables = [];
        Recycler.elements.$tableSelector.children().remove();
        $.each(data, function(_, value) {
          var tableName = value[0],
            deletedRecords = value[1],
            tableDescription = value[2];

          if (tableDescription === '') {
            tableDescription = TYPO3.lang['label_allrecordtypes'];
          }
          var optionText = tableDescription + ' (' + deletedRecords + ')';
          tables.push($('<option />').val(tableName).text(optionText))
        });

        if (tables.length > 0) {
          Recycler.elements.$tableSelector.append(tables);
          if (TYPO3.settings.Recycler.tableSelection !== '') {
            Recycler.elements.$tableSelector.val(TYPO3.settings.Recycler.tableSelection);
          }
        }
      },
      complete: function() {
        NProgress.done();
      }
    });
  };

  /**
   * Loads the deleted elements, based on the filters
   *
   * @returns {Promise}
   */
  Recycler.loadDeletedElements = function() {
    return $.ajax({
      url: TYPO3.settings.ajaxUrls['recycler'],
      dataType: 'json',
      data: {
        action: 'getDeletedRecords',
        depth: Recycler.elements.$depthSelector.find('option:selected').val(),
        startUid: TYPO3.settings.Recycler.startUid,
        table: Recycler.elements.$tableSelector.find('option:selected').val(),
        filterTxt: Recycler.elements.$searchTextField.val(),
        start: (Recycler.paging.currentPage - 1) * Recycler.paging.itemsPerPage,
        limit: Recycler.paging.itemsPerPage
      },
      beforeSend: function() {
        NProgress.start();
        Recycler.resetMassActionButtons();
        Recycler.selectAllDataShort = [];
        Recycler.currentDataCount = 0;

        /** if there are any checkboxes and corresponding buttons, hide them while new content arrives */
        Recycler.showLoading();
      },
      success: function(data) {
        var totalItems = data.totalItems;

        Recycler.elements.$tableBody.html(data.rows);
        Recycler.buildPaginator(totalItems);
        Recycler.currentDataCount = totalItems;

        Recycler.selectAllDataShort = data.allTheRows;
      },
      complete: function() {
        NProgress.done();
        Recycler.selectAllRefresh();
      }
    });
  };

  /**
   *
   */
  Recycler.deleteRecord = function() {
    if (TYPO3.settings.Recycler.deleteDisable) {
      return;
    }
    var $tr = $(this).parents('tr'),
      isMassDelete = $tr.parent().prop('tagName') !== 'TBODY'; // deleteRecord() was invoked by the mass delete button

    var records, message;
    if (isMassDelete) {
      records = Recycler.returnProperMarkedArray();
      message = TYPO3.lang['modal.massdelete.text'];
    } else {
      var uid = $tr.data('uid'),
        table = $tr.data('table'),
        recordTitle = $tr.data('recordtitle');
      records = table + ':' + uid;
      message = table === 'pages' ? TYPO3.lang['modal.deletepage.text'] : TYPO3.lang['modal.deletecontent.text'];
      message = Recycler.createMessage(message, [recordTitle, '[' + records + ']']);
    }

    Modal.confirm(TYPO3.lang['modal.delete.header'], message, Severity.error, [
      {
        text: TYPO3.lang['button.cancel'],
        btnClass: 'btn-default',
        trigger: function() {
          Modal.dismiss();
        }
      }, {
        text: TYPO3.lang['button.delete'],
        btnClass: 'btn-danger',
        trigger: function() {
          Recycler.callAjaxAction(
            'delete',
            typeof records === 'object' ? records : [records],
            isMassDelete
          )
        }
      }
    ]);
  };

  /**
   *
   */
  Recycler.undoRecord = function() {
    var $tr = $(this).parents('tr'),
      isMassUndo = $tr.parent().prop('tagName') !== 'TBODY'; // undoRecord() was invoked by the mass delete button

    var records, messageText, recoverPages;
    if (isMassUndo) {
      records = Recycler.returnProperMarkedArray();
      messageText = TYPO3.lang['modal.massundo.text'];
      recoverPages = true;
    } else {
      var uid = $tr.data('uid'),
        table = $tr.data('table'),
        recordTitle = $tr.data('recordtitle');

      records = table + ':' + uid;
      recoverPages = table === 'pages';
      messageText = recoverPages ? TYPO3.lang['modal.undopage.text'] : TYPO3.lang['modal.undocontent.text'];
      messageText = Recycler.createMessage(messageText, [recordTitle, '[' + records + ']']);

      if (recoverPages && $tr.data('parentDeleted')) {
        messageText += TYPO3.lang['modal.undo.parentpages'];
      }
    }

    var $message = null;
    if (recoverPages) {
      $message = $('<div />').append(
        $('<p />').text(messageText),
        $('<div />', {class: 'checkbox'}).append(
          $('<label />').append(TYPO3.lang['modal.undo.recursive']).prepend($('<input />', {
            id: 'undo-recursive',
            type: 'checkbox'
          }))
        )
      );
    } else {
      $message = $('<div />').text(messageText);
    }

    Modal.confirm(TYPO3.lang['modal.undo.header'], $message, Severity.ok, [
      {
        text: TYPO3.lang['button.cancel'],
        btnClass: 'btn-default',
        trigger: function() {
          Modal.dismiss();
        }
      }, {
        text: TYPO3.lang['button.undo'],
        btnClass: 'btn-success',
        trigger: function() {
           Recycler.callAjaxAction(
            'undo',
            // typeof records === 'object' ? records : [records],
            records,
            isMassUndo,
            $message.find('#undo-recursive').prop('checked') ? 1 : 0
          );
        }
      }
    ]);
  };

  /**
   *
   * @param {String} action
   * @param {Object} records
   * @param {Boolean} isMassAction
   * @param {Boolean} recursive
   */
  Recycler.callAjaxAction = function(action, records, isMassAction, recursive) {
    var data = {
        records: JSON.stringify(records),
        action: ''
      },
      reloadPageTree = false,
      oldCount = Recycler.markedRecordsCounter,
      error = 0;
    if (action === 'undo') {
      data.action = 'undoRecords';
      data.recursive = recursive ? 1 : 0;
      reloadPageTree = true;
    } else if (action === 'delete') {
      data.action = 'deleteRecords';
    } else {
      return;
    }


    $.ajax({
      url: TYPO3.settings.ajaxUrls['recycler'],
      dataType: 'json',
      data: data,
      method: 'POST',
      beforeSend: function() {
        NProgress.start();
        /** if there are any checkboxes and corresponding buttons, hide them while new content arrives */
        Recycler.showLoading();
      },
      success: function(data) {
        if (data.success) {
          Notification.success('', data.message);
        } else {
          Notification.error('', data.message);
          error = 1;
        }

        // reload recycler data
        Recycler.paging.currentPage = 1;

        $.when(Recycler.loadAvailableTables()).done(function() {
          Recycler.loadDeletedElements();
          if (isMassAction && !error) {
              Recycler.clearMarked();
          } else {
            if (!error) {
              if (!!Recycler.markedRecordsForMassAction[records]) {
                Recycler.subtractRecord(records);
                oldCount--;
              }
              Recycler.markedRecordsCounter = oldCount;
            }
          }

          if (reloadPageTree) {
            Recycler.refreshPageTree();
          }

        });
      },
      complete: function() {
        Modal.dismiss();
        NProgress.done();
      }
    });
  };

  /**
   * Replaces the placeholders with actual values
   *
   * @param {String} message
   * @param {Array} placeholders
   * @returns {*}
   */
  Recycler.createMessage = function(message, placeholders) {
    if (typeof message === 'undefined') {
      return '';
    }

    return message.replace(
      /\{([0-9]+)\}/g,
      function(_, index) {
        return placeholders[index];
      }
    );
  };

  /**
   * Reloads the page tree
   */
  Recycler.refreshPageTree = function() {
    if (top.TYPO3 && top.TYPO3.Backend && top.TYPO3.Backend.NavigationContainer && top.TYPO3.Backend.NavigationContainer.PageTree) {
      top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
    }
  };

  /**
   * Build the paginator
   *
   * @param {Number} totalItems
   */
  Recycler.buildPaginator = function(totalItems) {
    if (totalItems === 0) {
      Recycler.elements.$paginator.contents().remove();
      return;
    }

    Recycler.paging.totalItems = totalItems;
    Recycler.paging.totalPages = Math.ceil(totalItems / Recycler.paging.itemsPerPage);

    if (Recycler.paging.totalPages === 1) {
      // early abort if only one page is available
      Recycler.elements.$paginator.contents().remove();
      return;
    }

    var $ul = $('<ul />', {class: 'pagination pagination-block'}),
      liElements = [],
      $controlFirstPage = $('<li />').append(
        $('<a />', {'data-action': 'previous'}).append(
          $('<span />', {class: 't3-icon fa fa-arrow-left'})
        )
      ),
      $controlLastPage = $('<li />').append(
        $('<a />', {'data-action': 'next'}).append(
          $('<span />', {class: 't3-icon fa fa-arrow-right'})
        )
      );

    if (Recycler.paging.currentPage === 1) {
      $controlFirstPage.disablePagingAction();
    }

    if (Recycler.paging.currentPage === Recycler.paging.totalPages) {
      $controlLastPage.disablePagingAction();
    }

    for (var i = 1; i <= Recycler.paging.totalPages; i++) {
      var $li = $('<li />', {class: Recycler.paging.currentPage === i ? 'active' : ''});
      $li.append(
        $('<a />', {'data-action': 'page'}).append(
          $('<span />').text(i)
        )
      );
      liElements.push($li);
    }

    $ul.append($controlFirstPage, liElements, $controlLastPage);
    Recycler.elements.$paginator.html($ul);
  };

  /**
   * Select all records
   */
  Recycler.selectAll = function() {
    if (Recycler.currentDataCount > 0) {
      Recycler.elements.$selectAll.addClass('disabled');

      Recycler.markedRecordsForMassAction = {};

      Recycler.markedRecordsCounter = Recycler.currentDataCount;
      Recycler.markedRecordsForMassAction = $.extend(true, {}, Recycler.selectAllDataShort);

      Recycler.elements.$selectAll.removeClass('disabled');

      Recycler.selectAllRefresh();
    }
  };

  /**
   * Deselect all records and return everything to clean state
   */
  Recycler.deselectAll = function() {
    Recycler.elements.$deselectAll.addClass('disabled');

    Recycler.clearMarked();
    Recycler.resetMassActionButtons();
    Recycler.selectAllRefresh();

    Recycler.elements.$selectAll.removeClass('disabled');
  };

  /**
   * Adjusts mass action buttons to user's action
   */
  Recycler.selectAllRefresh = function() {
    var totalItems, btnTextSelectAll = '',
      btnDisabledArr = ['$deselectAll', '$massUndo', '$massDelete'];

    Recycler.hideLoading();
    Recycler.persistMarked(Recycler.markedRecordsForMassAction);
    Recycler.refreshCheckboxes();

    /** if any checkboxes are checked change mass action buttons state */
    if (Recycler.markedRecordsCounter > 0) {
      var recordsLength = Recycler.markedRecordsCounter,
        btnTextDelete = Recycler.createMessage(TYPO3.lang['button.deleteselected'], [recordsLength]),
        btnTextUndo = Recycler.createMessage(TYPO3.lang['button.undoselected'], [recordsLength]);

      /** if there are any records unselected show the amount */
      if (!!Recycler.currentDataCount && ( (Recycler.currentDataCount-Recycler.markedRecordsCounter) > 0 )) {
        if (Recycler.markedRecordsCounter === 0) {
          btnTextSelectAll = Recycler.createMessage(TYPO3.lang['button.selectallamount'], [Recycler.currentDataCount]);

        } else {
          var rest = Recycler.currentDataCount - Recycler.markedRecordsCounter;

          btnTextSelectAll = Recycler.createMessage(TYPO3.lang['button.selectallamountrest'], [rest]);
        }
      } else {
        btnTextSelectAll = Recycler.createMessage(TYPO3.lang['button.selectall'])
      }

      /** if total amount of records from ajax is bigger than amount of currently selected records enable selectall */
      if (!!Recycler.currentDataCount && (Recycler.currentDataCount > Recycler.markedRecordsCounter)) {
        if (Recycler.elements.$selectAll.hasClass('disabled')) {
          Recycler.elements.$selectAll.removeClass('disabled');
        }
      } else {
        Recycler.elements.$selectAll.addClass('disabled');
      }

      /** enable mass action buttons (without selectall)*/
      $.each(btnDisabledArr, (function(index, value) {
        if (Recycler.elements[value].hasClass('disabled')) {
          Recycler.elements[value].removeClass('disabled');
        }
      }));

      Recycler.elements.$selectAll.find('span.text').text(btnTextSelectAll);
      Recycler.elements.$massDelete.find('span.text').text(btnTextDelete);
      Recycler.elements.$massUndo.find('span.text').text(btnTextUndo);

    } else {

      /** default states of mass action buttons if none checkboxes are checked */
      if (!!Recycler.currentDataCount) {
        totalItems = Recycler.currentDataCount;
        btnTextSelectAll = Recycler.createMessage(TYPO3.lang['button.selectallamount'], [totalItems])
      } else {
        btnTextSelectAll = Recycler.createMessage(TYPO3.lang['button.selectall'])
      }

      /** disable all action buttons (without selectall) */
      $.each(btnDisabledArr, (function(index, value) {
          if (!Recycler.elements[value].hasClass('disabled')) {
              Recycler.elements[value].addClass('disabled');
          }
      }));

      Recycler.elements.$massUndo.find('span.text').text(TYPO3.lang['button.undo']);
      Recycler.elements.$massDelete.find('span.text').text(TYPO3.lang['button.delete']);
      Recycler.elements.$selectAll.find('span.text').text(btnTextSelectAll);
      Recycler.elements.$selectAll.removeClass('disabled');
    }
  };

  /**
   * Show feedback while loading new content
   */
  Recycler.showLoading = function() {
    Recycler.elements.$recyclerTable.parent().hide();
    Recycler.elements.$progressBar.show();
    Recycler.resetMassActionButtons();
  };

  Recycler.hideLoading = function() {
    Recycler.elements.$recyclerTable.parent().show();
    Recycler.elements.$progressBar.hide();
  };

  /**
   * Check and uncheck checkboxes based on Recycler.markedRecordsForMassAction obj
   */
  Recycler.refreshCheckboxes = function() {
    var $checkboxes = Recycler.elements.$tableBody.find('input[type="checkbox"]');
    $.each($checkboxes, function(index, value) {
      var $checkbox = $(value),
        tableUid = Recycler.createTableUid($checkbox);

      if (!!Recycler.markedRecordsForMassAction[tableUid]) {
        $checkbox.prop('checked', true).parents('tr').addClass('warning');
      } else {
        $checkbox.prop('checked', false).parents('tr').removeClass('warning');
      }
    });
  };

  /**
   * Toggles checkboxes of all records from current page
   */
  Recycler.toggleAll = function() {
    var $checkboxes = Recycler.elements.$tableBody.find('input[type="checkbox"]'),
        markedRecordsOnThisPage = Recycler.countMarkedRecordsOnThisPage(),
        allToggled = (markedRecordsOnThisPage === $checkboxes.length);

    $.each($checkboxes, function(index, value) {
        var tableUid = Recycler.createTableUid($(value));
      if (!Recycler.markedRecordsForMassAction[tableUid]) {
        if (!allToggled) {
          Recycler.addRecord(tableUid);
        }
      } else {
        if (allToggled) {
          Recycler.subtractRecord(tableUid);
        }
      }
    });
    Recycler.selectAllRefresh();
  };

  Recycler.subtractRecord = function(tableUid) {
    delete Recycler.markedRecordsForMassAction[tableUid];
    Recycler.markedRecordsCounter--;
  };

  Recycler.addRecord = function(tableUid) {
    /** it should have truthy value */
    Recycler.markedRecordsForMassAction[tableUid] = 1;
    Recycler.markedRecordsCounter++;
  };

  /**
   * Function to store Recycler.markedRecordsForMassAction
   * @param data
   */
  Recycler.persistMarked = function(data) {
    Recycler.persist = {};
    Recycler.persist = data;
  };
  /**
   * Function to load Recycler.markedRecordsForMassAction from Recycler.persist
   */
  Recycler.loadMarked = function() {
    Recycler.markedRecordsForMassAction = Recycler.persist;
    Recycler.persist = {};
  };

  /**
   *  clear everything about selecting records
   */
  Recycler.clearMarked = function() {
    Recycler.markedRecordsForMassAction = {};
    Recycler.persist = {};
    Recycler.markedRecordsCounter = 0;
  };

  /**
   * Changing obj into proper array for ajax
   * @returns {string[]}
   */
  Recycler.returnProperMarkedArray = function() {
    return Object.keys(Recycler.markedRecordsForMassAction);
  };

  /**
   * Counts checkboxes which have corresponding entry in Recycler.markedRecordsForMassAction
   * @returns {number}
   */
  Recycler.countMarkedRecordsOnThisPage = function() {
    var $checkboxes = Recycler.elements.$tableBody.find('input[type="checkbox"]'),
      countOnPage = 0;
    $.each($checkboxes, function(index,value) {
      var tableUid = Recycler.createTableUid($(value));

      if (!!Recycler.markedRecordsForMassAction[tableUid]) {
      countOnPage++;
      }
    });
    return countOnPage;
  };

  Recycler.createTableUid = function($row) {
    var $checkbox = $($row),
      $tr = $checkbox.parents('tr'),
      table = $tr.data('table'),
      uid = $tr.data('uid');
    return table + ':' + uid;
  };

  /**
   * Changes the markup of a pagination action being disabled
   */
  $.fn.disablePagingAction = function() {
    $(this).addClass('disabled').find('.t3-icon').unwrap().wrap($('<span />'));
  };

  $(Recycler.initialize);
  return Recycler;
});
