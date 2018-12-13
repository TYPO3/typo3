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
 * Module: TYPO3/CMS/Extensionmanager/Main
 * main logic holding everything together, consists of multiple parts
 * ExtensionManager => Various functions for displaying the extension list / sorting
 * Repository => Various AJAX functions for TER downloads
 * ExtensionManager.Update => Various AJAX functions to display updates
 * ExtensionManager.uploadForm => helper to show the upload form
 */
define([
  'jquery',
  'nprogress',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/SplitButtons',
  'TYPO3/CMS/Backend/Tooltip',
  'TYPO3/CMS/Backend/Notification',
  'TYPO3/CMS/Backend/Severity',
  'TYPO3/CMS/Core/SecurityUtility',
  'datatables',
  'TYPO3/CMS/Backend/jquery.clearable'
], function($, NProgress, Modal, SplitButtons, Tooltip, Notification, Severity, SecurityUtility) {

  var securityUtility = new SecurityUtility();

  /**
   *
   * @type {{identifier: {extensionlist: string, searchField: string, extensionManager: string}}}
   * @exports TYPO3/CMS/Extensionmanager/Main
   */
  var ExtensionManager = {
    identifier: {
      extensionlist: '#typo3-extension-list',
      searchField: '#Tx_Extensionmanager_extensionkey'
    }
  };

  /**
   *
   * @returns {Object}
   */
  ExtensionManager.manageExtensionListing = function() {
    var $searchField = $(this.identifier.searchField),
      dataTable = $(this.identifier.extensionlist).DataTable({
        paging: false,
        dom: 'lrtip',
        lengthChange: false,
        pageLength: 15,
        stateSave: true,
        drawCallback: this.bindExtensionListActions,
        columns: [
          null,
          null,
          {
            type: 'extension'
          },
          null,
          {
            type: 'version'
          }, {
            orderable: false
          },
          null,
          null
        ]
      });

    $searchField.parents('form').on('submit', function() {
      return false;
    });

    var getVars = ExtensionManager.getUrlVars();

    // restore filter
    var currentSearch = (getVars['search'] ? getVars['search'] : dataTable.search());
    $searchField.val(currentSearch);

    $searchField.on('input', function(e) {
      dataTable.search($(this).val()).draw();
    });

    return dataTable;
  };

  /**
   *
   */
  ExtensionManager.bindExtensionListActions = function() {
    $('.removeExtension').not('.transformed').each(function() {
      var $me = $(this);
      $me.data('href', $me.attr('href'));
      $me.attr('href', '#');
      $me.addClass('transformed');
      $me.click(function() {
        Modal.confirm(
          TYPO3.lang['extensionList.removalConfirmation.title'],
          TYPO3.lang['extensionList.removalConfirmation.question'],
          Severity.error,
          [
            {
              text: TYPO3.lang['button.cancel'],
              active: true,
              btnClass: 'btn-default',
              trigger: function() {
                Modal.dismiss();
              }
            }, {
            text: TYPO3.lang['button.remove'],
            btnClass: 'btn-danger',
            trigger: function() {
              ExtensionManager.removeExtensionFromDisk($me);
              Modal.dismiss();
            }
          }
          ]
        );
      });
    });
  };

  /**
   *
   * @param {Object} $extension
   */
  ExtensionManager.removeExtensionFromDisk = function($extension) {
    $.ajax({
      url: $extension.data('href'),
      beforeSend: function() {
        NProgress.start();
      },
      success: function() {
        location.reload();
      },
      complete: function() {
        NProgress.done();
      }
    });
  };

  /**
   *
   * @returns {Array}
   */
  ExtensionManager.getUrlVars = function() {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
      hash = hashes[i].split('=');
      vars.push(hash[0]);
      vars[hash[0]] = hash[1];
    }
    return vars;
  };

  $.fn.dataTableExt.oSort['extension-asc'] = function(a, b) {
    return ExtensionManager.extensionCompare(a, b);
  };

  $.fn.dataTableExt.oSort['extension-desc'] = function(a, b) {
    var result = ExtensionManager.extensionCompare(a, b);
    return result * -1;
  };

  $.fn.dataTableExt.oSort['version-asc'] = function(a, b) {
    var result = ExtensionManager.versionCompare(a, b);
    return result * -1;
  };

  $.fn.dataTableExt.oSort['version-desc'] = function(a, b) {
    return ExtensionManager.versionCompare(a, b);
  };

  /**
   * Special sorting for the extension version column
   *
   * @param {String} a
   * @param {String} b
   * @returns {Number}
   */
  ExtensionManager.versionCompare = function(a, b) {
    if (a === b) {
      return 0;
    }

    var a_components = a.split(".");
    var b_components = b.split(".");

    var len = Math.min(a_components.length, b_components.length);

    // loop while the components are equal
    for (var i = 0; i < len; i++) {
      // A bigger than B
      if (parseInt(a_components[i]) > parseInt(b_components[i])) {
        return 1;
      }

      // B bigger than A
      if (parseInt(a_components[i]) < parseInt(b_components[i])) {
        return -1;
      }
    }

    // If one's a prefix of the other, the longer one is greaRepository.
    if (a_components.length > b_components.length) {
      return 1;
    }

    if (a_components.length < b_components.length) {
      return -1;
    }
    // Otherwise they are the same.
    return 0;
  };

  /**
   * The extension name column can contain various forms of HTML that
   * break a direct comparison of values
   *
   * @param {String} a
   * @param {String} b
   * @returns {Number}
   */
  ExtensionManager.extensionCompare = function(a, b) {
    var div = document.createElement("div");
    div.innerHTML = a;
    var aStr = div.textContent || div.innerText || a;

    div.innerHTML = b;
    var bStr = div.textContent || div.innerText || b;

    return aStr.trim().localeCompare(bStr.trim());
  }

  /**
   *
   * @param {Object} data
   */
  ExtensionManager.updateExtension = function(data) {
    var i = 0;
    var $form = $('<form>');
    $.each(data.updateComments, function(version, comment) {
      var $input = $('<input>').attr({type: 'radio', name: 'version'}).val(version);
      if (i === 0) {
        $input.attr('checked', 'checked');
      }
      $form.append([
        $('<h3>').append([
          $input,
          ' ' + securityUtility.encodeHtml(version)
        ]),
        $('<div>')
          .append(
            comment
              .replace(/(\r\n|\n\r|\r|\n)/g, '\n')
              .split(/\n/).map(function(line) {
                return securityUtility.encodeHtml(line);
              })
              .join('<br>')
          )
      ]);
      i++;
    });
    var $container = $('<div>').append([
      $('<h1>').text(TYPO3.lang['extensionList.updateConfirmation.title']),
      $('<h2>').text(TYPO3.lang['extensionList.updateConfirmation.message']),
      $form
    ]);

    NProgress.done();

    Modal.confirm(
      TYPO3.lang['extensionList.updateConfirmation.questionVersionComments'],
      $container,
      Severity.warning,
      [
        {
          text: TYPO3.lang['button.cancel'],
          active: true,
          btnClass: 'btn-default',
          trigger: function() {
            Modal.dismiss();
          }
        }, {
        text: TYPO3.lang['button.updateExtension'],
        btnClass: 'btn-warning',
        trigger: function() {
          $.ajax({
            url: data.url,
            data: {
              tx_extensionmanager_tools_extensionmanagerextensionmanager: {
                version: $('input:radio[name=version]:checked', Modal.currentModal).val()
              }
            },
            dataType: 'json',
            beforeSend: function() {
              NProgress.start();
            },
            complete: function() {
              location.reload();
            }
          });
          Modal.dismiss();
        }
      }
      ]
    );
  };

  /**
   *
   * @type {{downloadPath: string}}
   */
  var Repository = {
    downloadPath: ''
  };

  /**
   *
   */
  Repository.initDom = function() {
    NProgress.configure({parent: '.module-loading-indicator', showSpinner: false});

    $('#terTable').DataTable({
      lengthChange: false,
      pageLength: 15,
      stateSave: false,
      info: false,
      paging: false,
      searching: false,
      ordering: false,
      drawCallback: Repository.bindDownload
    });

    $('#terVersionTable').DataTable({
      lengthChange: false,
      pageLength: 15,
      stateSave: false,
      info: false,
      paging: false,
      searching: false,
      drawCallback: Repository.bindDownload,
      order: [
        [2, 'asc']
      ],
      columns: [
        {orderable: false},
        null,
        {type: 'version'},
        null,
        null,
        null
      ]
    });

    $('#terSearchTable').DataTable({
      paging: false,
      lengthChange: false,
      stateSave: false,
      searching: false,
      language: {
        search: 'Filter results:'
      },
      ordering: false,
      drawCallback: Repository.bindDownload
    });

    Repository.bindDownload();
    Repository.bindSearchFieldResetter();
  };

  /**
   *
   */
  Repository.bindDownload = function() {
    var installButtons = $('.downloadFromTer form.download button[type=submit]');
    installButtons.off('click');
    installButtons.on('click', function(event) {
      event.preventDefault();
      var url = $(event.currentTarget.form).attr('data-href');
      Repository.downloadPath = $(event.currentTarget.form).find('input.downloadPath:checked').val();
      $.ajax({
        url: url,
        dataType: 'json',
        beforeSend: function() {
          NProgress.start();
        },
        success: Repository.getDependencies
      });
    });
  };

  /**
   *
   * @param {Object} data
   * @returns {Boolean}
   */
  Repository.getDependencies = function(data) {
    NProgress.done();
    if (data.hasDependencies) {
      Modal.confirm(data.title, $(data.message), Severity.info, [
        {
          text: TYPO3.lang['button.cancel'],
          active: true,
          btnClass: 'btn-default',
          trigger: function() {
            Modal.dismiss();
          }
        }, {
          text: TYPO3.lang['button.resolveDependencies'],
          btnClass: 'btn-info',
          trigger: function() {
            Repository.getResolveDependenciesAndInstallResult(data.url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager[downloadPath]=' + Repository.downloadPath);
            Modal.dismiss();
          }
        }
      ]);
    } else {
      if (data.hasErrors) {
        Notification.error(data.title, data.message, 15);
      } else {
        Repository.getResolveDependenciesAndInstallResult(data.url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager[downloadPath]=' + Repository.downloadPath);
      }
    }
    return false;
  };

  /**
   *
   * @param {String} url
   */
  Repository.getResolveDependenciesAndInstallResult = function(url) {
    $.ajax({
      url: url,
      dataType: 'json',
      beforeSend: function() {
        NProgress.start();
      },
      success: function(data) {
        if (data.errorCount > 0) {
          Modal.confirm(data.errorTitle, $(data.errorMessage), Severity.error, [
            {
              text: TYPO3.lang['button.cancel'],
              active: true,
              btnClass: 'btn-default',
              trigger: function() {
                Modal.dismiss();
              }
            }, {
              text: TYPO3.lang['button.resolveDependenciesIgnore'],
              btnClass: 'btn-danger disabled t3js-dependencies',
              trigger: function() {
                if (!$(this).hasClass('disabled')) {
                  Repository.getResolveDependenciesAndInstallResult(data.skipDependencyUri);
                  Modal.dismiss();
                }
              }
            }
          ]);
          Modal.currentModal.on('shown.bs.modal', function() {
            var $actionButton = Modal.currentModal.find('.t3js-dependencies');
            $('input[name="unlockDependencyIgnoreButton"]', Modal.currentModal).on('change', function() {
              $actionButton.toggleClass('disabled', !$(this).prop('checked'));
            });
          });
        } else {
          var successMessage = TYPO3.lang['extensionList.dependenciesResolveDownloadSuccess.message' + data.installationTypeLanguageKey].replace(/\{0\}/g, data.extension);

          successMessage += '\n' + TYPO3.lang['extensionList.dependenciesResolveDownloadSuccess.header'] + ': ';
          $.each(data.result, function(index, value) {
            successMessage += '\n\n' + TYPO3.lang['extensionList.dependenciesResolveDownloadSuccess.item'] + ' ' + index + ': ';
            $.each(value, function(extkey) {
              successMessage += '\n* ' + extkey
            });
          });
          Notification.info(TYPO3.lang['extensionList.dependenciesResolveFlashMessage.title' + data.installationTypeLanguageKey].replace(/\{0\}/g, data.extension), successMessage, 15);
          top.TYPO3.ModuleMenu.App.refreshMenu();
        }
      },
      complete: function() {
        NProgress.done();
      }
    });
  };

  /**
   *
   */
  Repository.bindSearchFieldResetter = function() {
    var $searchFields = $('.typo3-extensionmanager-searchTerForm input[type="text"]');
    var searchResultShown = ('' !== $searchFields.first().val());

    $searchFields.clearable(
      {
        onClear: function() {
          if (searchResultShown) {
            $(this).closest('form').submit();
          }
        }
      }
    );
  };

  /**
   *
   * @type {{identifier: {extensionTable: string, terUpdateAction: string, pagination: string, splashscreen: string, terTableWrapper: string, terTableDataTableWrapper: string}}}
   */
  ExtensionManager.Update = {
    identifier: {
      extensionTable: '#terTable',
      terUpdateAction: '.update-from-ter',
      pagination: '.pagination-wrap',
      splashscreen: '.splash-receivedata',
      terTableWrapper: '#terTableWrapper',
      terTableDataTableWrapper: '#terTableWrapper .dataTables_wrapper'
    }
  };

  /**
   * Register "update from ter" action
   */
  ExtensionManager.Update.initializeEvents = function() {
    $(ExtensionManager.Update.identifier.terUpdateAction).each(function() {

      // "this" is the form which updates the extension list from
      // TER on submit
      var $me = $(this),
        updateURL = $(this).attr('action');

      $me.attr('action', '#');
      $me.submit(function() {
        // Force update on click.
        ExtensionManager.Update.updateFromTer(updateURL, true);

        // Prevent normal submit action.
        return false;
      });

      // This might give problems when there are more "update"-buttons,
      // each one would trigger a TER-ExtensionManager.Update.
      ExtensionManager.Update.updateFromTer(updateURL, false);
    });
  };

  /**
   *
   * @param {String} url
   * @param {Boolean} forceUpdate
   */
  ExtensionManager.Update.updateFromTer = function(url, forceUpdate) {
    if (forceUpdate) {
      url = url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager%5BforceUpdateCheck%5D=1';
    }

    // Hide triggers for TER update
    $(ExtensionManager.Update.identifier.terUpdateAction).addClass('is-hidden');

    // Hide extension table
    $(ExtensionManager.Update.identifier.extensionTable).hide();

    // Show loaders
    $(ExtensionManager.Update.identifier.splashscreen).addClass('is-shown');
    $(ExtensionManager.Update.identifier.terTableDataTableWrapper).addClass('is-loading');
    $(ExtensionManager.Update.identifier.pagination).addClass('is-loading');

    var reload = false;

    $.ajax({
      url: url,
      dataType: 'json',
      cache: false,
      beforeSend: function() {
        NProgress.start();
      },
      success: function(data) {
        // Something went wrong, show message
        if (data.errorMessage.length) {
          Notification.error(TYPO3.lang['extensionList.updateFromTerFlashMessage.title'], data.errorMessage, 10);
        }

        // Message with latest updates
        var $lastUpdate = $(ExtensionManager.Update.identifier.terUpdateAction + ' .time-since-last-update');
        $lastUpdate.text(data.timeSinceLastUpdate);
        $lastUpdate.attr(
          'title',
          TYPO3.lang['extensionList.updateFromTer.lastUpdate.timeOfLastUpdate'] + data.lastUpdateTime
        );

        if (data.updated) {
          // Reload page
          reload = true;
          window.location.replace(window.location.href);
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        // Create an error message with diagnosis info.
        var errorMessage = textStatus + '(' + errorThrown + '): ' + jqXHR.responseText;

        Notification.warning(
          TYPO3.lang['extensionList.updateFromTerFlashMessage.title'],
          errorMessage,
          10
        );
      },
      complete: function() {
        NProgress.done();

        if (!reload) {
          // Hide loaders
          $(ExtensionManager.Update.identifier.splashscreen).removeClass('is-shown');
          $(ExtensionManager.Update.identifier.terTableDataTableWrapper).removeClass('is-loading');
          $(ExtensionManager.Update.identifier.pagination).removeClass('is-loading');

          // Show triggers for TER-update
          $(ExtensionManager.Update.identifier.terUpdateAction).removeClass('is-hidden');

          // Show extension table
          $(ExtensionManager.Update.identifier.extensionTable).show();
        }
      }
    });
  };

  /**
   *
   */
  ExtensionManager.Update.transformPaginatorToAjax = function() {
    $(ExtensionManager.Update.identifier.pagination + ' a').each(function() {
      var $me = $(this);
      $me.data('href', $(this).attr('href'));
      $me.attr('href', '#');
      $me.click(function() {
        var $terTableWrapper = $(ExtensionManager.Update.identifier.terTableWrapper);
        NProgress.start();
        $.ajax({
          url: $(this).data('href'),
          dataType: 'json',
          success: function(data) {
            $terTableWrapper.html(data);
            ExtensionManager.Update.transformPaginatorToAjax();
          },
          complete: function() {
            NProgress.done();
          }
        });
      });
    });
  };

  /**
   * show the uploading form
   */
  ExtensionManager.UploadForm = {
    expandedUploadFormClass: 'transformed'
  };

  /**
   *
   */
  ExtensionManager.UploadForm.initializeEvents = function() {
    // Show upload form
    $(document).on('click', '.t3js-upload', function(event) {
      var $me = $(this),
        $uploadForm = $('.uploadForm');

      event.preventDefault();
      if ($me.hasClass(ExtensionManager.UploadForm.expandedUploadFormClass)) {
        $uploadForm.stop().slideUp();
        $me.removeClass(ExtensionManager.UploadForm.expandedUploadFormClass);
      } else {
        $me.addClass(ExtensionManager.UploadForm.expandedUploadFormClass);
        $uploadForm.stop().slideDown();

        $.ajax({
          url: $me.attr('href'),
          dataType: 'html',
          success: function(data) {
            $uploadForm.html(data);
          }
        });
      }
    });
  };

  $(function() {
    var dataTable = ExtensionManager.manageExtensionListing();

    $(document).on('click', '.onClickMaskExtensionManager', function() {
      NProgress.start();
    }).on('click', 'a[data-action=update-extension]', function(e) {
      e.preventDefault();
      $.ajax({
        url: $(this).attr('href'),
        dataType: 'json',
        beforeSend: function() {
          NProgress.start();
        },
        success: ExtensionManager.updateExtension
      });
    }).on('change', 'input[name=unlockDependencyIgnoreButton]', function() {
      var $actionButton = $('.t3js-dependencies');
      $actionButton.toggleClass('disabled', !$(this).prop('checked'));
    });

    $(ExtensionManager.identifier.searchField).clearable({
      onClear: function() {
        dataTable.search('').draw();
      }
    });

    $(document).on('click', '.t3-button-action-installdistribution', function() {
      NProgress.start();
    });

    SplitButtons.addPreSubmitCallback(function(e) {
      if ($(e.target).hasClass('t3js-save-close')) {
        $('#configurationform').append($('<input />', {
          type: 'hidden',
          name: 'tx_extensionmanager_tools_extensionmanagerextensionmanager[action]',
          value: 'saveAndClose'
        }));
      }
    });

    // initialize the repository
    Repository.initDom();

    ExtensionManager.Update.initializeEvents();
    ExtensionManager.UploadForm.initializeEvents();

    Tooltip.initialize('#typo3-extension-list [title]', {
      delay: {
        show: 500,
        hide: 100
      },
      trigger: 'hover',
      container: 'body'
    });
  });

  if (typeof TYPO3.ExtensionManager === 'undefined') {
    TYPO3.ExtensionManager = ExtensionManager;
  }

  return ExtensionManager;
});
