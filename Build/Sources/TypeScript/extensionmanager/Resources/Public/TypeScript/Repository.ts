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

import * as $ from 'jquery';
import * as NProgress from 'nprogress';
import Modal = require('TYPO3/CMS/Backend/Modal');
import Notification = require('TYPO3/CMS/Backend/Notification');
import Severity = require('TYPO3/CMS/Backend/Severity');
import 'datatables';
import 'TYPO3/CMS/Backend/jquery.clearable';

class Repository {
  public downloadPath: string = '';

  public initDom = (): void => {
    NProgress.configure({parent: '.module-loading-indicator', showSpinner: false});

    $('#terTable').DataTable({
      lengthChange: false,
      pageLength: 15,
      stateSave: false,
      info: false,
      paging: false,
      searching: false,
      ordering: false,
      drawCallback: this.bindDownload,
    });

    $('#terVersionTable').DataTable({
      lengthChange: false,
      pageLength: 15,
      stateSave: false,
      info: false,
      paging: false,
      searching: false,
      drawCallback: this.bindDownload,
      order: [
        [2, 'asc'],
      ],
      columns: [
        {orderable: false},
        null,
        {type: 'version'},
        null,
        null,
        null,
      ],
    });

    $('#terSearchTable').DataTable({
      paging: false,
      lengthChange: false,
      stateSave: false,
      searching: false,
      language: {
        search: 'Filter results:',
      },
      ordering: false,
      drawCallback: this.bindDownload,
    });

    this.bindDownload();
    this.bindSearchFieldResetter();
  }

  private bindDownload = (): void => {
    const installButtons = $('.downloadFromTer form.download button[type=submit]');
    installButtons.off('click');
    installButtons.on('click', (event: JQueryEventObject): void => {
      event.preventDefault();
      const $element: any = $(event.currentTarget);
      const $form = $element.closest('form');
      const url = $form.attr('data-href');
      this.downloadPath = $form.find('input.downloadPath:checked').val();
      $.ajax({
        url: url,
        dataType: 'json',
        beforeSend: (): void => {
          NProgress.start();
        },
        success: this.getDependencies,
      });
    });
  }

  private getDependencies = (data: any): boolean => {
    NProgress.done();
    if (data.hasDependencies) {
      Modal.confirm(data.title, $(data.message), Severity.info, [
        {
          text: TYPO3.lang['button.cancel'],
          active: true,
          btnClass: 'btn-default',
          trigger: (): void => {
            Modal.dismiss();
          },
        }, {
          text: TYPO3.lang['button.resolveDependencies'],
          btnClass: 'btn-info',
          trigger: (): void => {
            this.getResolveDependenciesAndInstallResult(data.url
              + '&tx_extensionmanager_tools_extensionmanagerextensionmanager[downloadPath]=' + this.downloadPath);
            Modal.dismiss();
          },
        },
      ]);
    } else {
      if (data.hasErrors) {
        Notification.error(data.title, data.message, 15);
      } else {
        this.getResolveDependenciesAndInstallResult(data.url
          + '&tx_extensionmanager_tools_extensionmanagerextensionmanager[downloadPath]=' + this.downloadPath);
      }
    }
    return false;
  }

  private getResolveDependenciesAndInstallResult = (url: string) => {
    $.ajax({
      url: url,
      dataType: 'json',
      beforeSend: (): void => {
        NProgress.start();
      },
      success: (data: any): void => {
        if (data.errorCount > 0) {
          Modal.confirm(data.errorTitle, $(data.errorMessage), Severity.error, [
            {
              text: TYPO3.lang['button.cancel'],
              active: true,
              btnClass: 'btn-default',
              trigger: (): void => {
                Modal.dismiss();
              },
            }, {
              text: TYPO3.lang['button.resolveDependenciesIgnore'],
              btnClass: 'btn-danger disabled t3js-dependencies',
              trigger: (e: JQueryEventObject): void => {
                if (!$(e.currentTarget).hasClass('disabled')) {
                  this.getResolveDependenciesAndInstallResult(data.skipDependencyUri);
                  Modal.dismiss();
                }
              },
            },
          ]);
          Modal.currentModal.on('shown.bs.modal', (): void => {
            const $actionButton = Modal.currentModal.find('.t3js-dependencies');
            $('input[name="unlockDependencyIgnoreButton"]', Modal.currentModal).on('change', (e: JQueryEventObject): void => {
              $actionButton.toggleClass('disabled', !$(e.currentTarget).prop('checked'));
            });
          });
        } else {
          let successMessage = TYPO3.lang['extensionList.dependenciesResolveDownloadSuccess.message'
          + data.installationTypeLanguageKey].replace(/\{0\}/g, data.extension);

          successMessage += '\n' + TYPO3.lang['extensionList.dependenciesResolveDownloadSuccess.header'] + ': ';
          $.each(data.result, (index: number, value: any): void => {
            successMessage += '\n\n' + TYPO3.lang['extensionList.dependenciesResolveDownloadSuccess.item'] + ' ' + index + ': ';
            $.each(value, (extkey: string): void => {
              successMessage += '\n* ' + extkey;
            });
          });
          Notification.info(
            TYPO3.lang['extensionList.dependenciesResolveFlashMessage.title' + data.installationTypeLanguageKey]
              .replace(/\{0\}/g, data.extension),
            successMessage,
            15,
          );
          top.TYPO3.ModuleMenu.App.refreshMenu();
        }
      },
      complete: (): void => {
        NProgress.done();
      },
    });
  }

  private bindSearchFieldResetter(): void {
    const $searchFields = $('.typo3-extensionmanager-searchTerForm input[type="text"]');
    const searchResultShown = ('' !== $searchFields.first().val());

    $searchFields.clearable(
      {
        onClear: (e: JQueryEventObject): void => {
          if (searchResultShown) {
            $(e.currentTarget).closest('form').submit();
          }
        },
      },
    );
  }
}

export = Repository;
