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
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import Severity from '@typo3/backend/severity';
import Tablesort from 'tablesort';
import '@typo3/backend/input/clearable';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import RegularEvent from '@typo3/core/event/regular-event';

class Repository {
  public downloadPath: string = '';

  public initDom(): void {
    NProgress.configure({parent: '.module-loading-indicator', showSpinner: false});

    const terVersionTable = document.getElementById('terVersionTable');
    const terSearchTable = document.getElementById('terSearchTable');

    if (terVersionTable !== null) {
      new Tablesort(terVersionTable);
    }
    if (terSearchTable !== null) {
      new Tablesort(terSearchTable);
    }

    this.bindDownload();
    this.bindSearchFieldResetter();
  }

  private bindDownload(): void {
    const me = this;
    new RegularEvent('click', function (this: HTMLInputElement, e: Event): void {
      e.preventDefault();

      const form = this.closest('form');
      const url = form.dataset.href;
      me.downloadPath = (form.querySelector('input.downloadPath:checked') as HTMLInputElement).value;
      NProgress.start();
      new AjaxRequest(url).get().then(me.getDependencies);

    }).delegateTo(document, '.downloadFromTer form.download button[type=submit]');
  }

  private getDependencies = async(response: AjaxResponse): Promise<void> => {
    const data = await response.resolve();

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
  }

  private getResolveDependenciesAndInstallResult(url: string): void {
    NProgress.start();
    new AjaxRequest(url).get().then(async (response: AjaxResponse): Promise<void> => {
      // FIXME: As of now, the endpoint doesn't set proper headers, thus we have to parse the response text
      // https://review.typo3.org/c/Packages/TYPO3.CMS/+/63438
      const data = await response.raw().json();
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
    }).finally((): void => {
      NProgress.done()
    });
  }

  private bindSearchFieldResetter(): void {
    let searchField: HTMLInputElement;
    if ((searchField = document.querySelector('.typo3-extensionmanager-searchTerForm input[type="text"]')) !== null) {
      const searchResultShown = ('' !== searchField.value);

      // make search field clearable
      searchField.clearable({
        onClear: (input: HTMLInputElement): void => {
          if (searchResultShown) {
            input.closest('form').submit();
          }
        },
      });
    }
  }
}

export default Repository;
