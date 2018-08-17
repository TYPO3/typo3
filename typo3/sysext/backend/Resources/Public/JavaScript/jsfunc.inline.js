/*<![CDATA[*/

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
 *  Inline-Relational-Record Editing
 */

var inline = {
  classVisible: 'panel-visible',
  classCollapsed: 'panel-collapsed',
  structureSeparator: '-',
  flexFormSeparator: '---',
  flexFormSubstitute: ':',
  noTitleString: (TYPO3.lang ? TYPO3.lang['FormEngine.noRecordTitle'] : '[No title]'),
  lockedAjaxMethod: {},
  sourcesLoaded: {},
  data: {},
  isLoading: false,
  methodRequestMap: {
    'details': 'record_inline_details',
    'create': 'record_inline_create',
    'synchronizelocalize': 'record_inline_synchronizelocalize',
    'expandcollapse': 'record_inline_expandcollapse'
  },
  addMethod: function(method, route) {
    this.methodRequestMap[method] = route;
  },
  addToDataArray: function(object) {
    $.each(object, function(key, value) {
      if (!inline.data[key]) {
        inline.data[key] = {};
      }
      $.extend(inline.data[key], value);
    });
  },
  toggleEvent: function(event) {
    var $triggerElement = $(event.target);
    if ($triggerElement.parents('.t3js-formengine-irre-control').length == 1) {
      return;
    }

    var $recordHeader = $triggerElement.closest('.panel-heading');
    inline.expandCollapseRecord(
      $recordHeader.attr('id').replace(/_header$/, ''),
      $recordHeader.attr('data-expandSingle')
    );
  },
  expandCollapseRecord: function(objectId, expandSingle) {
    var currentUid = this.parseObjectId('none', objectId, 1);
    var objectPrefix = this.parseObjectId('full', objectId, 0, 1);
    var escapedObjectId = this.escapeObjectId(objectId);

    var $currentObject = $('#' + escapedObjectId + '_div');
    // if content is not loaded yet, get it now from server
    if (inline.isLoading) {
      return false;
    } else if ($('#' + escapedObjectId + '_fields').length > 0 && $('#' + escapedObjectId + '_fields').html().substr(0, 16) === '<!--notloaded-->') {
      inline.isLoading = true;
      var headerIdentifier = '#' + escapedObjectId + '_header';
      // add loading-indicator
      require(['nprogress'], function(NProgress) {
        inline.progress = NProgress;
        inline.progress.configure({parent: headerIdentifier, showSpinner: false});
        inline.progress.start();
      });
      return this.getRecordDetails(objectId);
    }

    var isCollapsed = $currentObject.hasClass(this.classCollapsed);
    var collapse = [];
    var expand = [];

    // if only a single record should be visibly for that set of records
    // and the record clicked itself is no visible, collapse all others
    if (expandSingle && $currentObject.hasClass(this.classCollapsed)) {
      collapse = this.collapseAllRecords(objectId, objectPrefix, currentUid);
    }

    inline.toggleElement(objectId);

    if (this.isNewRecord(objectId)) {
      this.updateExpandedCollapsedStateLocally(objectId, isCollapsed);
    } else if (isCollapsed) {
      expand.push(currentUid);
    } else if (!isCollapsed) {
      collapse.push(currentUid);
    }

    this.setExpandedCollapsedState(objectId, expand.join(','), collapse.join(','));

    return false;
  },

  toggleElement: function(objectId) {
    var escapedObjectId = this.escapeObjectId(objectId);
    var $jQueryObject = $('#' + escapedObjectId + '_div');

    if ($jQueryObject.hasClass(this.classCollapsed)) {
      $jQueryObject.removeClass(this.classCollapsed).addClass(this.classVisible);
      $jQueryObject.find('#' + escapedObjectId + '_header .t3-icon-irre-collapsed').removeClass('t3-icon-irre-collapsed').addClass('t3-icon-irre-expanded');
    } else {
      $jQueryObject.removeClass(this.classVisible).addClass(this.classCollapsed);
      $jQueryObject.find('#' + escapedObjectId + '_header .t3-icon-irre-expanded').addClass('t3-icon-irre-collapsed').removeClass('t3-icon-irre-expanded');
    }
  },
  collapseAllRecords: function(objectId, objectPrefix, callingUid) {
    // get the form field, where all records are stored
    var objectName = 'data' + this.parseObjectId('parts', objectId, 3, 2, true);
    var formObj = document.getElementsByName(objectName);
    var collapse = [];

    if (formObj.length) {
      // the uid of the calling object (last part in objectId)
      var recObjectId = '', escapedRecordObjectId;

      var records = this.trimExplode(',', formObj[0].value);
      for (var i = 0; i < records.length; i++) {
        recObjectId = objectPrefix + this.structureSeparator + records[i];
        escapedRecordObjectId = this.escapeObjectId(recObjectId);

        var $recordEntry = $('#' + escapedRecordObjectId + '_div');
        if (records[i] != callingUid && $recordEntry.hasClass(this.classVisible)) {
          $recordEntry.removeClass(this.classVisible).addClass(this.classCollapsed);
          if (this.isNewRecord(recObjectId)) {
            this.updateExpandedCollapsedStateLocally(recObjectId, 0);
          } else {
            collapse.push(records[i]);
          }
        }
      }
    }

    return collapse;
  },

  updateExpandedCollapsedStateLocally: function(objectId, value) {
    var ucName = 'uc[inlineView]' + this.parseObjectId('parts', objectId, 3, 2, true);
    var ucFormObj = document.getElementsByName(ucName);
    if (ucFormObj.length) {
      ucFormObj[0].value = value;
    }
  },

  getRecordDetails: function(objectId) {
    var context = this.getContext(this.parseObjectId('full', objectId, 0, 1));
    inline.makeAjaxCall(this.methodRequestMap.details, [objectId], true, context);
    return false;
  },

  createNewRecord: function(objectId, recordUid) {
    if (this.isBelowMax(objectId)) {
      var context = this.getContext(objectId);
      if (recordUid) {
        objectId += this.structureSeparator + recordUid;
      }
      this.makeAjaxCall(this.methodRequestMap.create, [objectId], true, context);
    } else {
      var message = TYPO3.lang['FormEngine.maxItemsAllowed'].replace('{0}', this.data.config[objectId].max);
      var matches = objectId.match(/^(data-\d+-.*?-\d+-.*?)-(.*?)$/);
      var title = '';
      if (matches) {
        title = $('#' + matches[1] + '_records').data('title');
      }
      top.TYPO3.Notification.error(title, message, 5);
    }
    return false;
  },

  synchronizeLocalizeRecords: function(objectId, type) {
    var context = this.getContext(objectId);
    this.makeAjaxCall(this.methodRequestMap.synchronizelocalize, [objectId, type], true, context);
    return false;
  },

  setExpandedCollapsedState: function(objectId, expand, collapse) {
    var context = this.getContext(objectId);
    this.makeAjaxCall(this.methodRequestMap.expandcollapse, [objectId, expand, collapse], false, context);
  },

  makeAjaxCall: function(method, params, lock, context) {
    var url = '', urlParams = '', options = {};
    if (method && params && params.length && this.lockAjaxMethod(method, lock)) {
      url = TYPO3.settings.ajaxUrls[method];
      urlParams = '';
      for (var i = 0; i < params.length; i++) {
        urlParams += '&ajax[' + i + ']=' + encodeURIComponent(params[i]);
      }
      if (context) {
        urlParams += '&ajax[context]=' + encodeURIComponent(JSON.stringify(context));
      }
      options = {
        type: 'POST',
        data: urlParams,
        success: function(data, message, jqXHR) {
          inline.isLoading = false;
          inline.processAjaxResponse(method, jqXHR);
          if (inline.progress) {
            inline.progress.done();
          }
        },
        error: function(jqXHR) {
          inline.isLoading = false;
          inline.showAjaxFailure(method, jqXHR);
          if (inline.progress) {
            inline.progress.done();
          }
        }
      };

      $.ajax(url, options);
    }
  },

  lockAjaxMethod: function(method, lock) {
    if (!lock || !inline.lockedAjaxMethod[method]) {
      inline.lockedAjaxMethod[method] = true;
      return true;
    } else {
      return false;
    }
  },

  unlockAjaxMethod: function(method) {
    inline.lockedAjaxMethod[method] = false;
  },

  processAjaxResponse: function(method, xhr, json) {
    var processedCount = 0, sourcesWaiting = [];
    if (!json && xhr) {
      json = xhr.responseJSON;
    }
    if (json.hasErrors) {
      $.each(json.messages, function(position, message) {
        top.TYPO3.Notification.error(message.title, message.message);
      });
    }
    // If there are elements the should be added to the <HEAD> tag (e.g. for RTEhtmlarea):
    if (json.stylesheetFiles) {
      $.each(json.stylesheetFiles, function(index, stylesheetFile) {
        if (!stylesheetFile) {
          return;
        }
        var element = document.createElement('link');
        element['rel'] = 'stylesheet';
        element['type'] = 'text/css';
        element['href'] = stylesheetFile;
        $('head').get(0).appendChild(element);
        processedCount++;
        delete(json.stylesheetFiles[index]);
      });
    }
    if (processedCount) {
      window.setTimeout(function() {
        inline.reprocessAjaxResponse(method, json, sourcesWaiting);
      }, 40);
    } else {
      if (method) {
        inline.unlockAjaxMethod(method);
      }
      if (json.scriptCall && json.scriptCall.length > 0) {
        $.each(json.scriptCall, function(index, value) {
          eval(value);
        });
      }
      TYPO3.FormEngine.reinitialize();
      TYPO3.FormEngine.Validation.initializeInputFields();
      TYPO3.FormEngine.Validation.validate();
    }
  },

  // Check if dynamically added scripts are loaded and restart inline.processAjaxResponse():
  reprocessAjaxResponse: function(method, json, sourcesWaiting) {
    var sourcesLoaded = true;
    if (sourcesWaiting && sourcesWaiting.length) {
      $.each(sourcesWaiting, function(index, source) {
        if (!inline.sourcesLoaded[source]) {
          sourcesLoaded = false;
          return false;
        }
      });
    }
    if (sourcesLoaded) {
      $.each(sourcesWaiting, function(index, source) {
        delete(inline.sourcesLoaded[source]);
      });
      window.setTimeout(function() {
        inline.processAjaxResponse(method, null, json);
      }, 80);
    } else {
      window.setTimeout(function() {
        inline.reprocessAjaxResponse(method, json, sourcesWaiting);
      }, 40);
    }
  },

  sourceLoadedHandler: function(element) {
    if (element && element.src) {
      inline.sourcesLoaded[element.src] = true;
    }
  },

  showAjaxFailure: function(method, xhr) {
    inline.unlockAjaxMethod(method);
    top.TYPO3.Notification.error('Error ' + xhr.status, xhr.statusText, 0);
  },

  // foreign_selector: used by selector box (type='select')
  importNewRecord: function(objectId) {
    var $selector = $('#' + this.escapeObjectId(objectId) + '_selector');
    var selectedIndex = $selector.prop('selectedIndex');
    if (selectedIndex != -1) {
      var context = this.getContext(objectId);
      var selectedValue = $selector.val();
      if (!this.data.unique || !this.data.unique[objectId]) {
        $selector.find('option').eq(selectedIndex).prop('selected', false);
      }
      this.makeAjaxCall(this.methodRequestMap.create, [objectId, selectedValue], true, context);
    }
    return false;
  },

  // foreign_selector: used by element browser (type='group/db')
  importElement: function(objectId, table, uid) {
    var context = this.getContext(objectId);
    inline.makeAjaxCall(this.methodRequestMap.create, [objectId, uid], true, context);
  },

  importElementMultiple: function(objectId, table, uidArray, type) {
    $.each(uidArray, function(index, uid) {
      inline.delayedImportElement(objectId, table, uid, type);
    });
  },
  delayedImportElement: function(objectId, table, uid, type) {
    if (inline.lockedAjaxMethod[this.methodRequestMap.create] == true) {
      window.setTimeout("inline.delayedImportElement('" + objectId + "','" + table + "'," + uid + ", null );",
        300);
    } else {
      inline.importElement(objectId, table, uid, type);
    }
  },
  // Check uniqueness for element browser:
  checkUniqueElement: function(objectId, table, uid) {
    if (this.checkUniqueUsed(objectId, uid, table)) {
      return {passed: false, message: 'There is already a relation to the selected element!'};
    } else {
      return {passed: true};
    }
  },

  // Checks if a record was used and should be unique:
  checkUniqueUsed: function(objectId, uid, table) {
    if (!this.data.unique || !this.data.unique[objectId]) {
      return false;
    }

    var unique = this.data.unique[objectId];
    var values = this.getValuesFromHashMap(unique.used);

    // for select: only the uid is stored
    if (unique['type'] == 'select') {
      if (values.indexOf(uid) != -1) {
        return true;
      }

      // for group/db: table and uid is stored in an assoc array
    } else if (unique.type == 'groupdb') {
      for (var i = values.length - 1; i >= 0; i--) {
        // if the pair table:uid is already used:
        if (values[i].table == table && values[i].uid == uid) {
          return true;
        }
      }
    }

    return false;
  },

  setUniqueElement: function(objectId, table, uid, type, elName) {
    var recordUid = this.parseFormElementName('none', elName, 1, 1);
    this.setUnique(objectId, recordUid, uid);
  },

  getValuesFromHashMap: function(hashMap) {
    return $.map(hashMap, function(value, key) {
      return value;
    });
  },

  // Remove all select items already used
  // from a newly retrieved/expanded record
  removeUsed: function(objectId, recordUid) {
    if (!this.data.unique || !this.data.unique[objectId]) {
      return;
    }

    var unique = this.data.unique[objectId];
    if (unique.type != 'select') {
      return;
    }

    var recordObj = document.getElementsByName('data[' + unique.table + '][' + recordUid + '][' + unique.field + ']');
    var values = this.getValuesFromHashMap(unique.used);
    if (recordObj.length) {
      if (recordObj[0].hasOwnProperty('options')) {
        var selectedValue = recordObj[0].options[recordObj[0].selectedIndex].value;
        for (var i = 0; i < values.length; i++) {
          if (values[i] != selectedValue) {
            var $recordObject = $(recordObj[0]);
            this.removeSelectOption($recordObject, values[i]);
          }
        }
      }
    }
  },
  // this function is applied to a newly inserted record by AJAX
  // it removes the used select items, that should be unique
  setUnique: function(objectId, recordUid, selectedValue) {
    if (!this.data.unique || !this.data.unique[objectId]) {
      return;
    }
    var $selector = $('#' + this.escapeObjectId(objectId) + '_selector');

    var unique = this.data.unique[objectId];
    if (unique.type == 'select') {
      if (!(unique.selector && unique.max == -1)) {
        var formName = 'data' + this.parseObjectId('parts', objectId, 3, 1, true);
        var formObj = document.getElementsByName(formName);
        var recordObj = document.getElementsByName('data[' + unique.table + '][' + recordUid + '][' + unique.field + ']');
        var values = this.getValuesFromHashMap(unique.used);
        if ($selector.length) {
          // remove all items from the new select-item which are already used in other children
          if (recordObj.length) {
            var $recordObject = $(recordObj[0]);
            for (var i = 0; i < values.length; i++) {
              this.removeSelectOption($recordObject, values[i]);
            }
            // set the selected item automatically to the first of the remaining items if no selector is used
            if (!unique.selector) {
              selectedValue = recordObj[0].options[0].value;
              recordObj[0].options[0].selected = true;
              this.updateUnique(recordObj[0], objectId, formName, recordUid);
              this.handleChangedField(recordObj[0], objectId + '[' + recordUid + ']');
            }
          }
          for (var i = 0; i < values.length; i++) {
            this.removeSelectOption($selector, values[i]);
          }
          if (typeof this.data.unique[objectId].used.length != 'undefined') {
            this.data.unique[objectId].used = {};
          }
          this.data.unique[objectId].used[recordUid] = selectedValue;
        }
        // remove the newly used item from each select-field of the child records
        if (formObj.length && selectedValue) {
          var records = this.trimExplode(',', formObj[0].value);
          for (var i = 0; i < records.length; i++) {
            recordObj = document.getElementsByName('data[' + unique.table + '][' + records[i] + '][' + unique.field + ']');
            if (recordObj.length && records[i] != recordUid) {
              var $recordObject = $(recordObj[0]);
              this.removeSelectOption($recordObject, selectedValue);
            }
          }
        }
      }
    } else if (unique.type == 'groupdb') {
      // add the new record to the used items:
      this.data.unique[objectId].used[recordUid] = {'table': unique.elTable, 'uid': selectedValue};
    }

    // remove used items from a selector-box
    if (unique.selector == 'select' && selectedValue) {
      this.removeSelectOption($selector, selectedValue);
      this.data.unique[objectId]['used'][recordUid] = selectedValue;
    }
  },

  domAddNewRecord: function(method, insertObjectId, objectPrefix, htmlData) {
    var $insertObject = $('#' + this.escapeObjectId(insertObjectId));
    if (this.isBelowMax(objectPrefix)) {
      if (method == 'bottom') {
        $insertObject.append(htmlData);
      } else if (method == 'after') {
        $insertObject.after(htmlData);
      }
    } else {
      var message = TYPO3.lang['FormEngine.maxItemsAllowed'].replace('{0}', this.data.config[objectPrefix].max);
      var title = $insertObject.data('title');
      top.TYPO3.Notification.error(title, message);
    }
  },

  domAddRecordDetails: function(objectId, objectPrefix, expandSingle, htmlData) {
    var hiddenValue, formObj, valueObj;
    var escapeObjectId = this.escapeObjectId(objectId);
    var $objectDiv = $('#' + escapeObjectId + '_fields');
    if ($objectDiv.length == 0 || $objectDiv.html().substr(0, 16) !== '<!--notloaded-->') {
      return;
    }

    var elName = this.parseObjectId('full', objectId, 2, 0, true);

    var $formObj = $('[data-formengine-input-name="' + elName + '[hidden]"]');
    var $valueObj = $('[name="' + elName + '[hidden]"]');

    // It might be the case that a child record
    // cannot be hidden at all (no hidden field)
    if ($formObj.length && $valueObj.length) {
      hiddenValue = $formObj[0].checked;
      $formObj.first().remove();
      $valueObj.first().remove();
    }

    // Update DOM
    $objectDiv.html(htmlData);

    formObj = document.querySelector('[data-formengine-input-name="' + elName + '[hidden]"]');
    valueObj = document.getElementsByName(elName + '[hidden]');

    // Set the hidden value again
    if (typeof formObj !== 'undefined' && formObj !== null && valueObj.length) {
      valueObj[0].value = hiddenValue ? 1 : 0;
      formObj.checked = hiddenValue;
    }

    // now that the content is loaded, set the expandState
    this.expandCollapseRecord(objectId, expandSingle);
  },

  changeSorting: function(objectId, direction) {
    var objectName = 'data' + this.parseObjectId('parts', objectId, 3, 2, true);
    var objectPrefix = this.parseObjectId('full', objectId, 0, 1);
    var formObj = document.getElementsByName(objectName);

    if (!formObj.length) {
      return false;
    }

    // the uid of the calling object (last part in objectId)
    var callingUid = this.parseObjectId('none', objectId, 1);
    var records = this.trimExplode(',', formObj[0].value);
    var current = records.indexOf(callingUid);
    var changed = false;

    // move up
    if (direction > 0 && current > 0) {
      records[current] = records[current - 1];
      records[current - 1] = callingUid;
      changed = true;

      // move down
    } else if (direction < 0 && current < records.length - 1) {
      records[current] = records[current + 1];
      records[current + 1] = callingUid;
      changed = true;
    }

    if (changed) {
      formObj[0].value = records.join(',');
      var cAdj = direction > 0 ? 1 : 0; // adjustment
      var objectIdPrefix = '#' + this.escapeObjectId(objectPrefix) + this.structureSeparator;
      $(objectIdPrefix + records[current - cAdj] + '_div').insertBefore(
        $(objectIdPrefix + records[current + 1 - cAdj] + '_div')
      );
      this.redrawSortingButtons(objectPrefix, records);
      $(formObj).addClass('has-change');
      $(document).trigger('inline:sorting-changed');
      $(document).trigger('change');
    }

    return false;
  },

  dragAndDropSorting: function(element) {
    var objectId = element.getAttribute('id').replace(/_records$/, '');
    var objectName = 'data' + inline.parseObjectId('parts', objectId, 3, 0, true);
    var formObj = document.getElementsByName(objectName);
    var $element = $(element);

    if (!formObj.length) {
      return;
    }

    var checked = [];
    var order = [];
    $element.find('.sortableHandle').each(function(i, e) {
      order.push($(e).data('id').toString());
    });
    var records = this.trimExplode(',', formObj[0].value);

    // check if ordered uid is really part of the records
    // virtually deleted items might still be there but ordering shouldn't saved at all on them
    for (var i = 0; i < order.length; i++) {
      if (records.indexOf(order[i]) != -1) {
        checked.push(order[i]);
      }
    }

    formObj[0].value = checked.join(',');

    if (inline.data.config && inline.data.config[objectId]) {
      var table = inline.data.config[objectId].table;
      inline.redrawSortingButtons(objectId + inline.structureSeparator + table, checked);
      $(formObj).addClass('has-change');
      $(document).trigger('change');
    }
  },

  createDragAndDropSorting: function(objectId) {
    require(['jquery', 'jquery-ui/sortable'], function($) {
      var $sortingContainer = $('#' + inline.escapeObjectId(objectId));

      if ($sortingContainer.hasClass('ui-sortable')) {
        $sortingContainer.sortable('enable');
        return;
      }

      $sortingContainer.sortable({
        containment: 'parent',
        handle: '.sortableHandle',
        zIndex: '4000',
        axis: 'y',
        tolerance: 'pointer',
        stop: function() {
          inline.dragAndDropSorting($sortingContainer[0]);
        }
      });
    });
  },

  destroyDragAndDropSorting: function(objectId) {
    require(['jquery', 'jquery-ui/sortable'], function($) {
      var $sortingContainer = $('#' + inline.escapeObjectId(objectId));
      if (!$sortingContainer.hasClass('ui-sortable')) {
        return;
      }
      $sortingContainer.sortable('disable');
    });
  },

  redrawSortingButtons: function(objectPrefix, records) {
    var i, $headerObj, sortUp, sortDown, partOfHeaderObj, iconIdentifier;

    // if no records were passed, fetch them from form field
    if (typeof records == 'undefined') {
      records = [];
      var objectName = 'data' + this.parseObjectId('parts', objectPrefix, 3, 1, true);
      var formObj = document.getElementsByName(objectName);
      if (formObj.length) {
        records = this.trimExplode(',', formObj[0].value);
      }
    }
    partOfHeaderObj = this.escapeObjectId(objectPrefix) + this.structureSeparator;
    require(['TYPO3/CMS/Backend/Icons'], function(Icons) {
      for (i = 0; i < records.length; i++) {
        if (!records[i].length) {
          continue;
        }
        $headerObj = $('#' + partOfHeaderObj + records[i] + '_header');
        sortUp = $headerObj.find('.sortingUp');
        iconIdentifier = 'actions-move-up';
        if (sortUp) {
          if (i == 0) {
            sortUp.addClass('disabled');
            iconIdentifier = 'empty-empty';
          } else {
            sortUp.removeClass('disabled');
          }
          Icons.getIcon(iconIdentifier, Icons.sizes.small).done(function(markup) {
            sortUp.find('.t3js-icon').replaceWith(markup);
          });
        }
        sortDown = $headerObj.find('.sortingDown');
        iconIdentifier = 'actions-move-down';
        if (sortDown) {
          if (i == records.length - 1) {
            sortDown.addClass('disabled');
            iconIdentifier = 'empty-empty';
          } else {
            sortDown.removeClass('disabled');
          }
          Icons.getIcon(iconIdentifier, Icons.sizes.small).done(function(markup) {
            sortDown.find('.t3js-icon').replaceWith(markup);
          });
        }
      }
    });
  },

  memorizeAddRecord: function(objectPrefix, newUid, afterUid, selectedValue) {
    if (this.isBelowMax(objectPrefix)) {
      var objectName = 'data' + this.parseObjectId('parts', objectPrefix, 3, 1, true);
      var formObj = document.getElementsByName(objectName);

      if (formObj.length) {
        var records = [];
        if (formObj[0].value.length) {
          records = this.trimExplode(',', formObj[0].value);
        }

        if (afterUid) {
          var newRecords = [];
          for (var i = 0; i < records.length; i++) {
            if (records[i].length) {
              newRecords.push(records[i]);
            }
            if (afterUid == records[i]) {
              newRecords.push(newUid);
            }
          }
          records = newRecords;
        } else {
          records.push(newUid);
        }
        formObj[0].value = records.join(',');

        $(formObj).addClass('has-change');
        $(document).trigger('change');
      }

      this.redrawSortingButtons(objectPrefix, records);

      if (this.data.unique && this.data.unique[objectPrefix]) {
        this.setUnique(objectPrefix, newUid, selectedValue);
      }
    }

    // if we reached the maximum of possible records after this action, hide the new buttons
    if (!this.isBelowMax(objectPrefix)) {
      var objectParent = this.parseObjectId('full', objectPrefix, 0, 1);
      var md5 = this.getObjectMD5(objectParent);
      this.hideElementsWithClassName('.inlineNewButton' + (md5 ? '.' + md5 : ''), objectParent);
      this.hideElementsWithClassName('.inlineNewRelationButton' + (md5 ? '.' + md5 : ''), objectParent);
      this.hideElementsWithClassName('.inlineNewFileUploadButton' + (md5 ? '.' + md5 : ''), objectParent);
      this.hideElementsWithClassName('.t3js-online-media-add-btn' + (md5 ? '.' + md5 : ''), objectParent);
      this.hideElementsWithClassName('.inlineForeignSelector' + (md5 ? '.' + md5 : ''), 't3-form-field-item');
    }

    if (TBE_EDITOR) {
      TBE_EDITOR.fieldChanged_fName(objectName, formObj);
    }
  },

  memorizeRemoveRecord: function(objectName, removeUid) {
    var formObj = document.getElementsByName(objectName);
    if (formObj.length) {
      var parts = [],
        indexOfRemoveUid = -1;
      if (formObj[0].value.length) {
        parts = this.trimExplode(',', formObj[0].value);
        indexOfRemoveUid = parts.indexOf(removeUid);
        if (indexOfRemoveUid !== -1) {
          delete parts[indexOfRemoveUid];
        }
        formObj[0].value = parts.join(',');
        if (TBE_EDITOR) {
          TBE_EDITOR.fieldChanged_fName(objectName, formObj);
        }

        $(formObj).addClass('has-change');
        $(document).trigger('change');
        return parts.length;
      }
    }
    return false;
  },

  updateUnique: function(srcElement, objectPrefix, formName, recordUid) {
    if (!this.data.unique || !this.data.unique[objectPrefix]) {
      return;
    }

    var unique = this.data.unique[objectPrefix];
    var oldValue = unique.used[recordUid];

    if (unique.selector == 'select') {
      var selector = $(objectPrefix + '_selector');
      this.removeSelectOption(selector, srcElement.value);
      if (typeof oldValue != 'undefined') {
        this.readdSelectOption(selector, oldValue, unique);
      }
    }

    if (unique.selector && unique.max == -1) {
      return;
    }

    var formObj = document.getElementsByName(formName);
    if (!unique || !formObj.length) {
      return;
    }

    var records = this.trimExplode(',', formObj[0].value);
    var recordObj;
    for (var i = 0; i < records.length; i++) {
      recordObj = document.getElementsByName('data[' + unique.table + '][' + records[i] + '][' + unique.field + ']');
      if (recordObj.length && recordObj[0] != srcElement) {
        var $recordObject = $(recordObj[0]);
        this.removeSelectOption($recordObject, srcElement.value);
        if (typeof oldValue != 'undefined') {
          this.readdSelectOption($recordObject, oldValue, unique);
        }
      }
    }
    this.data.unique[objectPrefix].used[recordUid] = srcElement.value;
  },

  revertUnique: function(objectPrefix, elName, recordUid) {
    if (!this.data.unique || !this.data.unique[objectPrefix]) {
      return;
    }

    var unique = this.data.unique[objectPrefix];
    var fieldObj = elName ? document.getElementsByName(elName + '[' + unique.field + ']') : null;

    if (unique.type == 'select') {
      if (!fieldObj || !fieldObj.length) {
        return;
      }

      delete(this.data.unique[objectPrefix].used[recordUid]);

      if (unique.selector == 'select') {
        if (!isNaN(fieldObj[0].value)) {
          var $selector = $('#' + this.escapeObjectId(objectPrefix) + '_selector');
          this.readdSelectOption($selector, fieldObj[0].value, unique);
        }
      }

      if (unique.selector && unique.max == -1) {
        return;
      }

      var formName = 'data' + this.parseObjectId('parts', objectPrefix, 3, 1, true);
      var formObj = document.getElementsByName(formName);
      if (!formObj.length) {
        return;
      }

      var records = this.trimExplode(',', formObj[0].value);
      var recordObj;
      // walk through all inline records on that level and get the select field
      for (var i = 0; i < records.length; i++) {
        recordObj = document.getElementsByName('data[' + unique.table + '][' + records[i] + '][' + unique.field + ']');
        if (recordObj.length) {
          var $recordObject = $(recordObj[0]);
          this.readdSelectOption($recordObject, fieldObj[0].value, unique);
        }
      }
    } else if (unique.type == 'groupdb') {
      delete(this.data.unique[objectPrefix].used[recordUid])
    }
  },

  enableDisableRecord: function(objectIdentifier, fieldName) {
    var elName = this.parseObjectId('full', objectIdentifier, 2, 0, true) + '[' + fieldName + ']';
    var formObj = document.querySelector('[data-formengine-input-name="' + elName + '"]');
    var valueObj = document.getElementsByName(elName);
    var escapedObjectIdentifier = this.escapeObjectId(objectIdentifier);
    var $container = $('#' + escapedObjectIdentifier + '_div');
    var $icon = $container.find('.t3js-' + escapedObjectIdentifier + '_disabled .t3js-icon');

    // It might be the case that there's no hidden field
    if (typeof formObj !== 'undefined' && formObj !== null && valueObj.length) {
      formObj.click();
      valueObj[0].value = formObj.checked ? 1 : 0;
      TBE_EDITOR.fieldChanged_fName(elName, elName);
    }

    if ($icon.length) {
      require(['TYPO3/CMS/Backend/Icons'], function(Icons) {
        var hiddenClass = 't3-form-field-container-inline-hidden',
          isHidden = $container.hasClass(hiddenClass),
          toggleIcon;

        if (isHidden) {
          toggleIcon = 'actions-edit-hide';
          $container.removeClass(hiddenClass);
        } else {
          toggleIcon = 'actions-edit-unhide';
          $container.addClass(hiddenClass);
        }

        Icons.getIcon(toggleIcon, Icons.sizes.small).done(function(markup) {
          $icon.replaceWith(markup);
        });
      });
    }

    return false;
  },

  deleteRecord: function(objectId, options) {
    var i, j, inlineRecords, records, childObjectId, childTable;
    var objectPrefix = this.parseObjectId('full', objectId, 0, 1);
    var elName = this.parseObjectId('full', objectId, 2, 0, true);
    var shortName = this.parseObjectId('parts', objectId, 2, 0, true);
    var recordUid = this.parseObjectId('none', objectId, 1);
    var beforeDeleteIsBelowMax = this.isBelowMax(objectPrefix);

    // revert the unique settings if available
    this.revertUnique(objectPrefix, elName, recordUid);

    // Remove from TBE_EDITOR (required fields, required range, etc.):
    if (TBE_EDITOR && TBE_EDITOR.removeElement) {
      var removeStack = [];
      // Iterate over all child records:
      inlineRecords = $('.inlineRecord', '#' + objectId + '_div');
      // Remove nested child records from TBE_EDITOR required/range checks:
      for (i = inlineRecords.length - 1; i >= 0; i--) {
        if (inlineRecords.get(i).value.length) {
          records = this.trimExplode(',', inlineRecords.get(i).value);
          childObjectId = this.data.map[inlineRecords.get(i).name];
          childTable = this.data.config[childObjectId].table;
          for (j = records.length - 1; j >= 0; j--) {
            removeStack.push('data[' + childTable + '][' + records[j] + ']');
          }
        }
      }
      removeStack.push('data' + shortName);
      TBE_EDITOR.removeElementArray(removeStack);
    }

    // Mark this container as deleted
    $('#' + this.escapeObjectId(objectId) + '_div')
      .addClass('inlineIsDeletedRecord')
      .addClass('t3js-inline-record-deleted');

    // If the record is new and was never saved before, just remove it from DOM:
    if (this.isNewRecord(objectId) || options && options.forceDirectRemoval) {
      this.fadeAndRemove(objectId + '_div');
      // If the record already exists in storage, mark it to be deleted on clicking the save button:
    } else {
      document.getElementsByName('cmd' + shortName + '[delete]')[0].disabled = false;
      $('#' + this.escapeObjectId(objectId) + '_div').fadeOut(200);
    }

    var recordCount = this.memorizeRemoveRecord(
      'data' + this.parseObjectId('parts', objectId, 3, 2, true),
      recordUid
    );

    if (recordCount <= 1) {
      this.destroyDragAndDropSorting(this.parseObjectId('full', objectId, 0, 2) + '_records');
    }
    this.redrawSortingButtons(objectPrefix);

    // if the NEW-button was hidden and now we can add again new children, show the button
    if (!beforeDeleteIsBelowMax && this.isBelowMax(objectPrefix)) {
      var objectParent = this.parseObjectId('full', objectPrefix, 0, 1);
      var md5 = this.getObjectMD5(objectParent);
      this.showElementsWithClassName('.inlineNewButton' + (md5 ? '.' + md5 : ''), objectParent);
      this.showElementsWithClassName('.inlineNewRelationButton' + (md5 ? '.' + md5 : ''), objectParent);
      this.showElementsWithClassName('.inlineNewFileUploadButton' + (md5 ? '.' + md5 : ''), objectParent);
      this.showElementsWithClassName('.t3js-online-media-add-btn' + (md5 ? '.' + md5 : ''), objectParent);
      this.showElementsWithClassName('.inlineForeignSelector' + (md5 ? '.' + md5 : ''), 't3-form-field-item');
    }
    TYPO3.FormEngine.Validation.validate();
    return false;
  },

  parseFormElementName: function(wrap, formElementName, rightCount, skipRight) {
    var idParts = this.splitFormElementName(formElementName);

    if (!wrap) {
      wrap = 'full';
    }
    if (!skipRight) {
      skipRight = 0;
    }

    var elParts = [];
    for (var i = 0; i < skipRight; i++) {
      idParts.pop();
    }

    if (rightCount > 0) {
      for (var i = 0; i < rightCount; i++) {
        elParts.unshift(idParts.pop());
      }
    } else {
      for (var i = 0; i < -rightCount; i++) {
        idParts.shift();
      }
      elParts = idParts;
    }

    return this.constructFormElementName(wrap, elParts);
  },

  splitFormElementName: function(formElementName) {
    // remove left and right side "data[...|...]" -> '...|...'
    formElementName = formElementName.substr(0, formElementName.lastIndexOf(']')).substr(formElementName.indexOf('[') + 1);
    return formElementName.split('][');
  },

  splitObjectId: function(objectId) {
    objectId = objectId.substr(objectId.indexOf(this.structureSeparator) + 1);
    objectId = objectId.split(this.flexFormSeparator).join(this.flexFormSubstitute);
    return objectId.split(this.structureSeparator);
  },

  constructFormElementName: function(wrap, parts) {
    var elReturn;

    if (wrap == 'full') {
      elReturn = 'data[' + parts.join('][') + ']';
      elReturn = elReturn.split(this.flexFormSubstitute).join('][');
    } else if (wrap == 'parts') {
      elReturn = '[' + parts.join('][') + ']';
      elReturn = elReturn.split(this.flexFormSubstitute).join('][');
    } else if (wrap == 'none') {
      elReturn = parts.length > 1 ? parts : parts.join('');
    }

    return elReturn;
  },

  constructObjectId: function(wrap, parts) {
    var elReturn;

    if (wrap == 'full') {
      elReturn = 'data' + this.structureSeparator + parts.join(this.structureSeparator);
      elReturn = elReturn.split(this.flexFormSubstitute).join(this.flexFormSeparator);
    } else if (wrap == 'parts') {
      elReturn = this.structureSeparator + parts.join(this.structureSeparator);
      elReturn = elReturn.split(this.flexFormSubstitute).join(this.flexFormSeparator);
    } else if (wrap == 'none') {
      elReturn = parts.length > 1 ? parts : parts.join('');
    }

    return elReturn;
  },

  parseObjectId: function(wrap, objectId, rightCount, skipRight, returnAsFormElementName) {
    var idParts = this.splitObjectId(objectId);

    if (!wrap) {
      wrap = 'full';
    }
    if (!skipRight) {
      skipRight = 0;
    }

    var elParts = [];
    for (var i = 0; i < skipRight; i++) {
      idParts.pop();
    }

    if (rightCount > 0) {
      for (var i = 0; i < rightCount; i++) {
        elParts.unshift(idParts.pop());
      }
    } else {
      for (var i = 0; i < -rightCount; i++) {
        idParts.shift();
      }
      elParts = idParts;
    }

    return returnAsFormElementName
      ? this.constructFormElementName(wrap, elParts)
      : this.constructObjectId(wrap, elParts);
  },

  handleChangedField: function(formField, objectId) {
    var formObj;
    if (typeof formField == 'object') {
      formObj = formField;
    } else {
      formObj = document.getElementsByName(formField);
      if (formObj.length) {
        formObj = formObj[0];
      }
    }

    if (formObj != undefined) {
      var value;
      if (formObj.nodeName == 'SELECT') {
        value = formObj.options[formObj.selectedIndex].text;
      } else {
        value = formObj.value;
      }
      $('#' + this.escapeObjectId(objectId) + '_label').text(value.length ? value : this.noTitleString);
    }
    return true;
  },

  arrayAssocCount: function(object) {
    var count = 0;
    if (typeof object.length != 'undefined') {
      count = object.length;
    } else {
      for (var i in object) {
        count++;
      }
    }
    return count;
  },

  isBelowMax: function(objectPrefix) {
    var isBelowMax = true;
    var objectName = 'data' + this.parseObjectId('parts', objectPrefix, 3, 1, true);
    var formObj = document.getElementsByName(objectName);

    if (this.data.config && this.data.config[objectPrefix] && formObj.length) {
      var recordCount = formObj[0].value ? this.trimExplode(',', formObj[0].value).length : 0;
      if (recordCount >= this.data.config[objectPrefix].max) {
        isBelowMax = false;
      }
    }
    if (isBelowMax && this.data.unique && this.data.unique[objectPrefix]) {
      var unique = this.data.unique[objectPrefix];
      if (this.arrayAssocCount(unique.used) >= unique.max && unique.max >= 0) {
        isBelowMax = false;
      }
    }
    return isBelowMax;
  },

  getOptionsHash: function($selectObj) {
    var optionsHash = {};
    $selectObj.find('option').each(function(i, option) {
      optionsHash[option.value] = i;
    });
    return optionsHash;
  },

  removeSelectOption: function($selectObj, value) {
    var optionsHash = this.getOptionsHash($selectObj);
    if (optionsHash[value] != undefined) {
      $selectObj.find('option').eq(optionsHash[value]).remove();
    }
  },

  readdSelectOption: function($selectObj, value, unique) {
    if (!$selectObj.length) {
      return;
    }

    var index = null;
    var optionsHash = this.getOptionsHash($selectObj);

    for (var possibleValue in unique.possible) {
      if (possibleValue == value) {
        break;
      }
      if (optionsHash[possibleValue] != undefined) {
        index = optionsHash[possibleValue];
      }
    }

    if (index == null) {
      index = 0;
    } else if (index < $selectObj.find('option').length) {
      index++;
    }
    // recreate the <option> tag
    var readdOption = document.createElement('option');
    readdOption.text = unique.possible[value];
    readdOption.value = value;
    // add the <option> at the right position
    // I didn't find a possibility to add an option to a predefined position
    // with help of an index in jQuery. So we realized it the "old" style
    var selectObj = $selectObj.get(0);
    selectObj.add(readdOption, document.all ? index : selectObj.options[index]);
  },

  hideElementsWithClassName: function(selector, parentElement) {
    $('#' + this.escapeObjectId(parentElement)).find(selector).fadeOut(200);
  },

  showElementsWithClassName: function(selector, parentElement) {
    $('#' + this.escapeObjectId(parentElement)).find(selector).fadeIn(200);
  },

  // sets the opacity to 0.2 and then fades in to opacity 1
  fadeOutFadeIn: function(objectId) {
    $('#' + this.escapeObjectId(objectId)).css({opacity: 0.2}).fadeTo(200, 1, 'linear');
  },

  isNewRecord: function(objectId) {
    var $selector = $('#' + this.escapeObjectId(objectId) + '_div');
    return $selector.length && $selector.hasClass('inlineIsNewRecord');
  },

  // Find and fix nested of inline and tab levels if a new element was created dynamically (it doesn't know about its nesting):
  findContinuedNestedLevel: function(nested, objectId) {
    if (this.data.nested && this.data.nested[objectId]) {
      // Remove the first element from the new nested stack, it's just a hint:
      nested.shift();
      nested = this.data.nested[objectId].concat(nested);
    }
    return nested;
  },

  getObjectMD5: function(objectPrefix) {
    var md5 = false;
    if (this.data.config && this.data.config[objectPrefix] && this.data.config[objectPrefix].md5) {
      md5 = this.data.config[objectPrefix].md5;
    }
    return md5
  },

  fadeAndRemove: function(element) {
    $('#' + this.escapeObjectId(element)).fadeOut(200, function() {
      $(this).remove();
      TYPO3.FormEngine.Validation.validate();
    });
  },

  getContext: function(objectId) {
    var result = null;

    if (objectId !== '' && typeof this.data.config[objectId] !== 'undefined' && typeof this.data.config[objectId].context !== 'undefined') {
      result = this.data.config[objectId].context;
    }

    return result;
  },

  /**
   * Escapes object identifiers to be used in jQuery.
   *
   * @param {String} objectId
   * @return string
   */
  escapeObjectId: function(objectId) {
    var escapedObjectId;
    escapedObjectId = objectId.replace(/:/g, '\\:');
    escapedObjectId = escapedObjectId.replace(/\./g, '\\.');
    return escapedObjectId;
  },

  /**
   * Helper function to get clean trimmed array from comma list
   *
   * @param {String} delimiter
   * @param {String} string
   * @returns {Array}
   */
  trimExplode: function(delimiter, string) {
    var result = [];
    var items = string.split(delimiter);
    for (var i = 0; i < items.length; i++) {
      var item = items[i].trim();
      if (item.length > 0) {
        result.push(item);
      }
    }
    return result;
  }
};

/*]]>*/
$(function() {
  $(document).on('click', '[data-toggle="formengine-inline"]', function(event) {
    inline.toggleEvent(event);
  });
});

// Expose to public
// @internal
TYPO3.inline = inline;
