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
 * Module: TYPO3/CMS/Lang/LanguageModule
 * Language module class
 */
define(['jquery',
  'moment',
  'TYPO3/CMS/Backend/Icons',
  'TYPO3/CMS/Backend/Notification',
  'datatables',
  'TYPO3/CMS/Backend/jquery.clearable'
], function($, moment, Icons, Notification) {
  'use strict';

  /**
   *
   * @type {{me: *, context: null, table: null, topMenu: null, currentRequest: null, settings: {}, icons: {}, labels: {}, identifiers: {searchField: string, topMenu: string, activateIcon: string, deactivateIcon: string, downloadIcon: string, loadingIcon: string, completeIcon: string, progressBar: string, progressBarText: string, progressBarInner: string, lastUpdate: string, languagePrefix: string, extensionPrefix: string}, classes: {enabled: string, disabled: string, processing: string, complete: string, extension: string, actions: string, progressBar: string, loading: string, lastUpdate: string}}}
   * @exports TYPO3/CMS/Lang/LanguageModule
   */
  var LanguageModule = {
    me: this,
    context: null,
    table: null,
    topMenu: null,
    currentRequest: null,
    userAbortRequest: false,
    settings: {},
    icons: {},
    labels: {},
    buttons: {
      update: null,
      cancel: null
    },
    identifiers: {
      searchField: '.t3js-language-searchfield',
      topMenu: 'div.t3js-module-docheader',
      activateIcon: 'span.activateIcon',
      deactivateIcon: 'span.deactivateIcon',
      downloadIcon: 'span.downloadIcon',
      removeIcon: 'span.removeIcon',
      loadingIcon: 'span.loadingIcon',
      completeIcon: 'span.completeIcon',
      progressBar: 'div.progressBar',
      progressBarText: 'div.progress-text',
      progressBarInner: 'div.progress-bar',
      lastUpdate: 'td.lastUpdate',
      languagePrefix: 'language-',
      extensionPrefix: 'extension-'
    },
    classes: {
      enabled: 'enabled',
      disabled: 'disabled',
      processing: 'processing',
      complete: 'complete',
      extension: 'extensionName',
      actions: 'actions',
      progressBar: 'progressBar',
      loading: 'loading',
      lastUpdate: 'lastUpdate'
    }
  };

  /**
   * Initialize language table
   *
   * @param {HTMLElement} contextElement
   * @param {HTMLElement} tableElement
   */
  LanguageModule.initializeLanguageTable = function(contextElement, tableElement) {
    LanguageModule.context = $(contextElement);
    LanguageModule.topMenu = $(LanguageModule.identifiers.topMenu);
    LanguageModule.settings = LanguageModule.context.data();
    LanguageModule.icons = LanguageModule.buildIcons();
    LanguageModule.labels = LanguageModule.buildLabels();
    LanguageModule.table = LanguageModule.buildLanguageTable(tableElement);
    LanguageModule.initializeSearchField();
    LanguageModule.initializeEventHandler();
    LanguageModule.initializeButtons();
  };

  /**
   * Initialize translation table
   *
   * @param {HTMLElement} contextElement
   * @param {HTMLElement} tableElement
   */
  LanguageModule.initializeTranslationTable = function(contextElement, tableElement) {
    LanguageModule.context = $(contextElement);
    LanguageModule.topMenu = $(LanguageModule.identifiers.topMenu);
    LanguageModule.settings = LanguageModule.context.data();
    LanguageModule.icons = LanguageModule.buildIcons();
    LanguageModule.labels = LanguageModule.buildLabels();
    LanguageModule.table = LanguageModule.buildTranslationTable(tableElement);
    LanguageModule.initializeSearchField();
    LanguageModule.initializeEventHandler();
  };

  /**
   * Activate a language
   *
   * @param {HTMLElement} triggerElement
   * @param {Object} parameters
   */
  LanguageModule.activateLanguageAction = function(triggerElement, parameters) {
    var $row = $(triggerElement).closest('tr'),
      locale = $row.data('locale');

    if ($row.hasClass(LanguageModule.classes.processing)) {
      LanguageModule.abortAjaxRequest();
    }
    LanguageModule.executeAjaxRequest(LanguageModule.settings.activateLanguageUri, {locale: locale}, function(response, status) {
      if (status === 'success' && response.success) {
        $row.removeClass(LanguageModule.classes.disabled).addClass(LanguageModule.classes.enabled);
        LanguageModule.displaySuccess(LanguageModule.labels.languageActivated);
      } else {
        LanguageModule.displayError(LanguageModule.labels.errorOccurred);
      }
    });
  };

  /**
   * Deactivate a language
   *
   * @param {HTMLElement} triggerElement
   * @param {Object} parameters
   */
  LanguageModule.deactivateLanguageAction = function(triggerElement, parameters) {
    var $row = $(triggerElement).closest('tr'),
      locale = $row.data('locale');

    if ($row.hasClass(LanguageModule.classes.processing)) {
      LanguageModule.abortAjaxRequest();
    }
    LanguageModule.executeAjaxRequest(LanguageModule.settings.deactivateLanguageUri, {locale: locale}, function(response, status) {
      if (status === 'success' && response.success) {
        $row.removeClass(LanguageModule.classes.enabled).removeClass(LanguageModule.classes.complete).addClass(LanguageModule.classes.disabled);
        LanguageModule.displaySuccess(LanguageModule.labels.languageDeactivated);
      } else {
        LanguageModule.displayError(LanguageModule.labels.errorOccurred);
      }
    });
  };

  /**
   * Remove a language
   *
   * @param {HTMLElement} triggerElement
   * @param {Object} parameters
   */
  LanguageModule.removeLanguageAction = function(triggerElement, parameters) {
    var $row = $(triggerElement).closest('tr'),
      locale = $row.data('locale'),
      $lastUpdate = $(LanguageModule.identifiers.lastUpdate, $row);

    if ($row.hasClass(LanguageModule.classes.processing)) {
      LanguageModule.abortAjaxRequest();
    }
    LanguageModule.executeAjaxRequest(LanguageModule.settings.removeLanguageUri, {locale: locale}, function(response, status) {
      if (status === 'success' && response.success) {
        $row.removeClass(LanguageModule.classes.enabled).removeClass(LanguageModule.classes.complete).addClass(LanguageModule.classes.disabled);
        $lastUpdate.html('');
        LanguageModule.displaySuccess(LanguageModule.labels.languageRemoved);
      } else {
        LanguageModule.displayError(LanguageModule.labels.errorOccurred);
      }
    });
  };

  /**
   * Update a language
   *
   * @param {HTMLElement} triggerElement
   * @param {Object} parameters
   */
  LanguageModule.updateLanguageAction = function(triggerElement, parameters) {
    var $row = $(triggerElement).closest('tr'),
      locale = $row.data('locale'),
      $progressBar = $(LanguageModule.identifiers.progressBar, $row),
      $lastUpdate = $(LanguageModule.identifiers.lastUpdate, $row);

    $row.addClass(LanguageModule.classes.processing);
    LanguageModule.loadTranslationsByLocale(locale, function(status, data, response) {
      if (status === 'success') {
        LanguageModule.setProgress($progressBar, 100);
        LanguageModule.displaySuccess(LanguageModule.labels.updateComplete);
        $row.removeClass(LanguageModule.classes.processing).addClass(LanguageModule.classes.complete);
        $lastUpdate.html(LanguageModule.formatDate(response.timestamp));
      } else if (status === 'progress') {
        LanguageModule.setProgress($progressBar, parseFloat(response.progress));
      } else if (status === 'error') {
        LanguageModule.displayError(LanguageModule.labels.errorOccurred);
      }
    });
  };

  /**
   * Update all active languages
   *
   * @param {HTMLElement} triggerElement
   * @param {Object} parameters
   */
  LanguageModule.updateActiveLanguagesAction = function(triggerElement, parameters) {
    var $activeRows = $('tr.' + LanguageModule.classes.enabled, LanguageModule.table.table().container());
    if ($activeRows.length > 0) {
      LanguageModule.updateButtonStatus('update');
      LanguageModule.topMenu.addClass(LanguageModule.classes.processing);
      $activeRows.addClass(LanguageModule.classes.processing);
      LanguageModule.loadTranslationsByRows($activeRows, function(row, status, data, response) {
        var $progressBar = $(LanguageModule.identifiers.progressBar, row),
          $lastUpdate = $(LanguageModule.identifiers.lastUpdate, row);

        if (status === 'success') {
          LanguageModule.setProgress($progressBar, 100);
          row.removeClass(LanguageModule.classes.processing).addClass(LanguageModule.classes.complete);
          $lastUpdate.html(LanguageModule.formatDate(response.timestamp));
        } else if (status === 'progress') {
          LanguageModule.setProgress($progressBar, parseFloat(response.progress));
        } else if (status === 'error') {
          LanguageModule.displayError(LanguageModule.labels.errorOccurred);
        } else if (status === 'finished') {
          LanguageModule.updateButtonStatus('cancel');
          LanguageModule.displaySuccess(LanguageModule.labels.updateComplete);
          LanguageModule.topMenu.removeClass(LanguageModule.classes.processing);
        }
      });
    } else {
      LanguageModule.displayError(LanguageModule.labels.noLanguageActivated);
    }
  };

  /**
   * Cancel language update
   *
   * @param {HTMLElement} triggerElement
   * @param {Object} parameters
   */
  LanguageModule.cancelLanguageUpdateAction = function(triggerElement, parameters) {
    var $activeRows = $('tr.' + LanguageModule.classes.enabled, LanguageModule.table.table().container());
    LanguageModule.updateButtonStatus('cancel');
    LanguageModule.topMenu.removeClass(LanguageModule.classes.processing);
    $activeRows.removeClass(LanguageModule.classes.processing);
    LanguageModule.abortAjaxRequest();
  };

  /**
   * Update an extension translation
   *
   * @param {HTMLElement} triggerElement
   * @param {Object} parameters
   */
  LanguageModule.updateTranslationAction = function(triggerElement, parameters) {
    var $row = $(triggerElement).closest('tr'),
      $cell = $(triggerElement).closest('td'),
      extension = $row.data('extension'),
      locale = LanguageModule.table.cell($cell).data().locale;

    $cell.addClass(LanguageModule.classes.processing);
    LanguageModule.loadTranslationByExtensionAndLocale(extension, locale, function(status, data, response) {
      if (status === 'success') {
        LanguageModule.displaySuccess(LanguageModule.labels.updateComplete);
        $cell.removeClass(LanguageModule.classes.processing).addClass(LanguageModule.classes.complete);
      } else if (status === 'error') {
        LanguageModule.displayError(LanguageModule.labels.errorOccurred);
      }
    });
  };

  /**
   * Build icons
   *
   * @returns {{activate: (*|jQuery), deactivate: (*|jQuery), download: (*|jQuery), loading: (*|jQuery), complete: (*|jQuery), progressBar: (*|jQuery)}}
   */
  LanguageModule.buildIcons = function() {
    return {
      activate: $(LanguageModule.identifiers.activateIcon, LanguageModule.context).html(),
      deactivate: $(LanguageModule.identifiers.deactivateIcon, LanguageModule.context).html(),
      download: $(LanguageModule.identifiers.downloadIcon, LanguageModule.context).html(),
      remove: $(LanguageModule.identifiers.removeIcon, LanguageModule.context).html(),
      loading: $(LanguageModule.identifiers.loadingIcon, LanguageModule.context).html(),
      complete: $(LanguageModule.identifiers.completeIcon, LanguageModule.context).html(),
      progressBar: $(LanguageModule.identifiers.progressBar, LanguageModule.context).html()
    }
  };

  /**
   * Build labels
   *
   * @returns {{processing: *, search: *, loadingRecords: *, zeroRecords: *, emptyTable: *, dateFormat: *, errorHeader: *, infoHeader: *, successHeader: *, languageActivated: *, errorOccurred: *, languageDeactivated: *, languageRemoved: *, updateComplete: *}}
   */
  LanguageModule.buildLabels = function() {
    return {
      processing: TYPO3.lang['table.processing'],
      search: TYPO3.lang['table.search'],
      loadingRecords: TYPO3.lang['table.loadingRecords'],
      zeroRecords: TYPO3.lang['table.zeroRecords'],
      emptyTable: TYPO3.lang['table.emptyTable'],
      dateFormat: TYPO3.lang['table.dateFormat'],
      errorHeader: TYPO3.lang['flashmessage.error'],
      infoHeader: TYPO3.lang['flashmessage.information'],
      successHeader: TYPO3.lang['flashmessage.success'],
      languageActivated: TYPO3.lang['flashmessage.languageActivated'],
      errorOccurred: TYPO3.lang['flashmessage.errorOccurred'],
      languageDeactivated: TYPO3.lang['flashmessage.languageDeactivated'],
      languageRemoved: TYPO3.lang['flashmessage.languageRemoved'],
      noLanguageActivated: TYPO3.lang['flashmessage.noLanguageActivated'],
      updateComplete: TYPO3.lang['flashmessage.updateComplete'],
      canceled: TYPO3.lang['flashmessage.canceled']
    }
  };

  /**
   * Build language table
   *
   * @param {HTMLElement} tableElement
   * @returns {Object}
   */
  LanguageModule.buildLanguageTable = function(tableElement) {
    return $(tableElement).DataTable({
      dom: 'lrtip',
      serverSide: false,
      stateSave: true,
      paging: false,
      info: false,
      ordering: true,
      columnDefs: [{targets: 4, orderable: false}],
      language: LanguageModule.labels,
      order: [[1, 'asc']]
    });
  };

  /**
   * Initialize translation table
   *
   * @param {HTMLElement} tableElement
   * @returns {Object}
   */
  LanguageModule.buildTranslationTable = function(tableElement) {
    var languageCount = $(tableElement).data('languageCount'),
      columns = [
        {
          render: function(data, type, row) {
            return LanguageModule.buildImage(data.icon, data.title, data.title, data.width, data.height);
          },
          width: '20px',
          orderable: false,
          targets: 0
        }, {
          render: function(data, type, row) {
            return data.title;
          },
          className: LanguageModule.classes.extension,
          targets: 1
        }
      ];

    for (var i = 0; i < languageCount; i++) {
      columns.push({
        render: function(data, type, row) {
          var links = [
            LanguageModule.buildActionLink('updateTranslation', data, LanguageModule.icons.download),
            LanguageModule.buildActionLink('removeTranslation', data, LanguageModule.icons.remove),
            LanguageModule.buildLoadingIndicator(),
            LanguageModule.buildCompleteIndicator()
          ];
          return links.join('');
        },
        className: 'dt-center',
        targets: (i + 2)
      });
    }

    return $(tableElement).DataTable({
      dom: 'lrtip',
      serverSide: false,
      stateSave: true,
      paging: false,
      info: false,
      ordering: true,
      language: LanguageModule.labels,
      ajax: LanguageModule.settings.listTranslationsUri,
      order: [[1, 'asc']],
      columnDefs: columns,
      createdRow: function(row, data, index) {
        var $row = $(row);
        $row.attr('id', LanguageModule.identifiers.extensionPrefix + data[1].key);
        $row.attr('data-extension', data[1].key);
      }
    });
  };

  /**
   * Initialize search field
   */
  LanguageModule.initializeSearchField = function() {
    var getVars = LanguageModule.getUrlVars();
    var currentSearch = (getVars['search'] ? getVars['search'] : LanguageModule.table.search());
    $(LanguageModule.identifiers.searchField)
      .val(currentSearch)
      .on('input', function() {
        LanguageModule.table.search($(this).val()).draw();
      })
      .clearable({
        onClear: function() {
          if (LanguageModule.table !== null) {
            LanguageModule.table.search('').draw();
          }
        }
      })
      .parents('form').on('submit', function() {
      return false;
    });
  };

  /**
   * Initialize event handler, redirect clicks to controller actions
   */
  LanguageModule.initializeEventHandler = function() {
    $(document).on('click', function(event) {
      var $element = $(event.target);
      var $parent = $element.closest('[data-action]');

      if ($element.data('action') !== undefined) {
        LanguageModule.handleActionEvent($element, event);
      } else if ($parent.data('action') !== undefined) {
        LanguageModule.handleActionEvent($parent, event);
      }
    });
  };

  /**
   * Initialize buttons
   */
  LanguageModule.initializeButtons = function() {
    LanguageModule.buttons.update = LanguageModule.topMenu.find('.t3js-button-update');
    LanguageModule.buttons.cancel = LanguageModule.topMenu.find('.t3js-button-cancel');
  };

  /**
   * Update buttons in top menu
   *
   * @param {String} action
   */
  LanguageModule.updateButtonStatus = function(action) {
    switch (action) {
      case 'update':
        LanguageModule.buttons.update.data('action', 'cancelLanguageUpdate');
        LanguageModule.buttons.cancel.removeClass('disabled');
        Icons.getIcon('spinner-circle-dark', Icons.sizes.small).done(function(spinner) {
          LanguageModule.buttons.update.find('span.icon').replaceWith(spinner);
        });
        break;
      case 'cancel':
        LanguageModule.buttons.update.data('action', 'updateActiveLanguages');
        LanguageModule.buttons.cancel.addClass('disabled');
        Icons.getIcon('actions-system-extension-download', Icons.sizes.small).done(function(download) {
          LanguageModule.buttons.update.find('span.icon').replaceWith(download);
        });
        break;
    }
  };

  /**
   * Handler for "action" events
   *
   * @param {Object} element
   * @param {Event} event
   */
  LanguageModule.handleActionEvent = function(element, event) {
    event.preventDefault();
    var data = element.data();
    var actionName = data.action + 'Action';
    if (actionName in LanguageModule) {
      LanguageModule[actionName](element, data);
    }
  };

  /**
   * Load translations for all extensions by given locale
   *
   * @param {String} locale
   * @param {function} callback
   * @param {Number} counter
   */
  LanguageModule.loadTranslationsByLocale = function(locale, callback, counter) {
    counter = counter || 0;
    var data = {locale: locale, count: counter};
    LanguageModule.executeAjaxRequest(LanguageModule.settings.updateLanguageUri, data, function(response, status) {
      if (status === 'success' && response.success) {
        if (parseFloat(response.progress) < 100) {
          callback('progress', data, response);
          counter++;
          LanguageModule.loadTranslationsByLocale(locale, callback, counter);
        } else {
          callback('success', data, response);
        }
      } else {
        callback('error', data, response);
      }
    });
  };

  /**
   * Load translations for all extensions by given rows
   *
   * @param {Object} rows
   * @param {function} callback
   */
  LanguageModule.loadTranslationsByRows = function(rows, callback) {
    if (rows) {
      rows = $(rows).toArray();
      var $row = $(rows.shift()),
        locale = $row.data('locale');

      LanguageModule.loadTranslationsByLocale(locale, function(status, data, response) {
        callback($row, status, data, response);
        if (status === 'success') {
          if (rows.length) {
            LanguageModule.loadTranslationsByRows(rows, callback);
          } else {
            callback($row, 'finished', data, response);
          }
        }
      });
    }
  };

  /**
   * Load translation for one extension by given locale
   *
   * @param {String} extension
   * @param {String} locale
   * @param {function} callback
   */
  LanguageModule.loadTranslationByExtensionAndLocale = function(extension, locale, callback) {
    var data = {extension: extension, locale: locale};
    LanguageModule.executeAjaxRequest(LanguageModule.settings.updateTranslationUri, data, function(response, status) {
      if (status === 'success' && response.success) {
        callback('success', data, response);
      } else {
        callback('error', data, response);
      }
    });
  };

  /**
   * Execute AJAX request
   *
   * @param {String} uri
   * @param {Object} data
   * @param {function} callback
   */
  LanguageModule.executeAjaxRequest = function(uri, data, callback) {
    var newData = {};
    newData[LanguageModule.settings.prefix] = {
      data: data
    };
    LanguageModule.currentRequest = $.ajax({
      type: 'POST',
      cache: false,
      url: uri,
      data: newData,
      dataType: 'json',
      success: function(response, status) {
        if (typeof callback === 'function') {
          callback(response, status, '');
        }
      },
      error: function(response, status, error) {
        if (typeof callback === 'function') {
          callback(response, status, error);
        }
      }
    });
  };

  /**
   * Abort current AJAX request
   */
  LanguageModule.abortAjaxRequest = function() {
    if (LanguageModule.currentRequest) {
      LanguageModule.userAbortRequest = true;
      LanguageModule.currentRequest.abort();
    }
  };

  /**
   * Display error flash message
   *
   * @param {String} label
   */
  LanguageModule.displayError = function(label) {
    if (LanguageModule.userAbortRequest) {
      LanguageModule.displaySuccess(LanguageModule.labels.canceled);
    } else if (typeof label === 'string' && label !== '') {
      Notification.error(LanguageModule.labels.errorHeader, label);
    }
  };

  /**
   * Display information flash message
   *
   * @param {String} label
   */
  LanguageModule.displayInformation = function(label) {
    if (typeof label === 'string' && label !== '') {
      Notification.info(LanguageModule.labels.infoHeader, label);
    }
  };

  /**
   * Display success flash message
   *
   * @param {String} label
   */
  LanguageModule.displaySuccess = function(label) {
    if (typeof label === 'string' && label !== '') {
      Notification.success(LanguageModule.labels.successHeader, label);
    }
  };

  /**
   * Build action link
   *
   * @param {String} action
   * @param {Object} parameters
   * @param {String} content
   * @returns {Object}
   */
  LanguageModule.buildActionLink = function(action, parameters, content) {
    var $link = $('<a>');

    $link.addClass(action + 'Link');
    $link.attr('data-action', action);
    for (var name in parameters) {
      if (parameters.hasOwnProperty(name)) {
        $link.attr('data-' + name, parameters[name]);
      }
    }
    $link.html(content);
    return $link.wrap('<span>').parent().html();
  };

  /**
   * Build progress bar
   *
   * @returns {Object}
   */
  LanguageModule.buildProgressBar = function() {
    var $span = $('<span>');
    $span.addClass(LanguageModule.classes.progressBar);
    $span.html(LanguageModule.icons.progressBar);
    return $span.wrap('<span>').parent().html();
  };

  /**
   * Build loading indicator
   *
   * @returns {Object}
   */
  LanguageModule.buildLoadingIndicator = function() {
    var $span = $('<span>');
    $span.addClass(LanguageModule.classes.loading);
    $span.html(LanguageModule.icons.loading);
    return $span.wrap('<span>').parent().html();
  };

  /**
   * Build complete state indicator
   *
   * @returns {Object}
   */
  LanguageModule.buildCompleteIndicator = function() {
    var $span = $('<span>');
    $span.addClass(LanguageModule.classes.complete);
    $span.html(LanguageModule.icons.complete);
    return $span.wrap('<span>').parent().html();
  };

  /**
   * Build image
   *
   * @param {String} uri
   * @param {String} alt
   * @param {String} title
   * @param {Number} width
   * @param {Number} heigth
   * @returns {Object}
   */
  LanguageModule.buildImage = function(uri, alt, title, width, heigth) {
    var $image = $('<img>');
    $image.attr('src', uri);
    $image.attr('alt', alt ? alt : '');
    $image.attr('title', title ? title : '');
    $image.attr('style', 'width: ' + width + 'px; height: ' + heigth + 'px;');
    var $span = $('<span>');
    $span.addClass('typo3-app-icon');
    $span.attr('style', 'background: none; text-align: center;');
    $span.html($image);
    return $span.wrap('<span>').parent().html();
  };

  /**
   * Format date
   *
   * @param {Number} timestamp
   * @returns {*}
   */
  LanguageModule.formatDate = function(timestamp) {
    return moment.unix(timestamp).format(LanguageModule.labels.dateFormat);
  };

  /**
   * Set progress bar progress
   *
   * @param {Object} progressBar
   * @param {String} progress
   */
  LanguageModule.setProgress = function(progressBar, progress) {
    var $inner = $(LanguageModule.identifiers.progressBarInner, progressBar),
      $text = $(LanguageModule.identifiers.progressBarText, progressBar);
    $inner.css({width: progress + '%'});
    $inner.attr('aria-valuenow', progress);
    $text.text(Math.round(progress) + '%');
  };

  /**
   * Utility method to retrieve query parameters
   *
   * @returns {Array}
   */
  LanguageModule.getUrlVars = function getUrlVars() {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
      hash = hashes[i].split('=');
      vars.push(hash[0]);
      vars[hash[0]] = hash[1];
    }
    return vars;
  };

  $(function() {
    if ($('#typo3-language-list').length) {
      LanguageModule.initializeLanguageTable('div.typo3-module-lang', '#typo3-language-list');
    } else if ($('#typo3-translation-list').length) {
      LanguageModule.initializeTranslationTable('div.typo3-module-lang', '#typo3-translation-list');
    }
  });

  return LanguageModule;
});
