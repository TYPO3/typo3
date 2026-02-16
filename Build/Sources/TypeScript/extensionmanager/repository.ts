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

import { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import Severity from '@typo3/backend/severity';
import SortableTable from '@typo3/backend/sortable-table';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import RegularEvent from '@typo3/core/event/regular-event';
import coreCommonLabels from '~labels/core.common';

interface ResultItems {
  [key: string]: string
}

interface ExtensionInstallResult {
  errorCount: number,
  errorMessage: string,
  errorTitle: string,
  extension: string,
  installationTypeLanguageKey: string,
  result: false | {dependencies?: ResultItems, installed?: ResultItems, updated?: ResultItems },
  skipDependencyUri: string
}

class Repository {
  private progressBar: ProgressBarElement;

  public initDom(): void {

    const terVersionTable = document.getElementById('terVersionTable');
    const terSearchTable = document.getElementById('terSearchTable');

    if (terVersionTable instanceof HTMLTableElement) {
      new SortableTable(terVersionTable);
    }
    if (terSearchTable instanceof HTMLTableElement) {
      new SortableTable(terSearchTable);
    }

    this.bindDownload();
    this.bindSearchFieldResetter();
  }

  private bindDownload(): void {
    new RegularEvent('click', (e: Event, target: HTMLInputElement): void => {
      e.preventDefault();

      const form = target.closest('form');
      const url = form.dataset.href;
      this.getProgress().start();
      new AjaxRequest(url).get().then(this.getDependencies);
    }).delegateTo(document, '.downloadFromTer form.download button[type=submit]');
  }

  private readonly getDependencies = async(response: AjaxResponse): Promise<void> => {
    const data = await response.resolve();
    const messageElement = document.createElement('div');
    messageElement.innerHTML = data.message;

    this.progressBar?.done();
    if (data.hasDependencies) {
      Modal.confirm(data.title, messageElement, Severity.info, [
        {
          text: coreCommonLabels.get('cancel'),
          active: true,
          btnClass: 'btn-default',
          trigger: (): void => {
            Modal.dismiss();
          },
        }, {
          text: TYPO3.lang['button.resolveDependencies'],
          btnClass: 'btn-primary',
          trigger: (): void => {
            this.getResolveDependenciesAndInstallResult(data.url);
            Modal.dismiss();
          },
        },
      ]);
    } else {
      if (data.hasErrors) {
        Notification.error(data.title, data.message, 15);
      } else {
        this.getResolveDependenciesAndInstallResult(data.url);
      }
    }
  };

  private getResolveDependenciesAndInstallResult(url: string): void {
    this.getProgress().start();
    new AjaxRequest(url).post({}).then(async (response: AjaxResponse): Promise<void> => {
      try {
        // FIXME: As of now, the endpoint doesn't set proper headers, thus we have to parse the response text
        // https://review.typo3.org/c/Packages/TYPO3.CMS/+/63438
        const data: ExtensionInstallResult = await response.raw().json();
        const errorMessageElement = document.createElement('div');
        errorMessageElement.innerHTML = data.errorMessage;

        if (data.errorCount > 0) {
          const modal = Modal.confirm(data.errorTitle, errorMessageElement, Severity.error, [
            {
              text: coreCommonLabels.get('cancel'),
              active: true,
              btnClass: 'btn-default',
              trigger: (): void => {
                Modal.dismiss();
              },
            }, {
              text: TYPO3.lang['button.resolveDependenciesIgnore'],
              btnClass: 'btn-danger disabled t3js-dependencies',
              trigger: (e: Event): void => {
                if (!(e.currentTarget as HTMLElement).classList.contains('disabled')) {
                  this.getResolveDependenciesAndInstallResult(data.skipDependencyUri);
                  Modal.dismiss();
                }
              },
            },
          ]);
          modal.addEventListener('typo3-modal-shown', (): void => {
            const actionButton = modal.querySelector('.t3js-dependencies');
            modal.querySelector('input[name="unlockDependencyIgnoreButton"]').addEventListener('change', (e: Event): void => {
              if ((e.currentTarget as HTMLInputElement).checked) {
                actionButton?.classList.remove('disabled');
              } else {
                actionButton?.classList.add('disabled');
              }
            });
          });
        } else {
          let successMessage = TYPO3.lang['extensionList.dependenciesResolveDownloadSuccess.message'
          + data.installationTypeLanguageKey].replace(/\{0\}/g, data.extension);

          successMessage += '\n' + TYPO3.lang['extensionList.dependenciesResolveDownloadSuccess.header'] + ': ';
          for (const [index, value] of Object.entries(data.result)) {
            successMessage += '\n\n' + TYPO3.lang['extensionList.dependenciesResolveDownloadSuccess.item'] + ' ' + index + ': ';
            for (const extkey of Object.keys(value)) {
              successMessage += '\n* ' + extkey;
            }
          }
          Notification.info(
            TYPO3.lang['extensionList.dependenciesResolveFlashMessage.title' + data.installationTypeLanguageKey]
              .replace(/\{0\}/g, data.extension),
            successMessage,
            15,
          );
          top.TYPO3.ModuleMenu.App.refreshMenu();
        }
      } catch {
        // Catching errors on resolving the response. One case is that an extensions might lead to
        // the PHP request being aborted, which results in an empty response body. Calling .json()
        // on this, results in a SyntaxError. Therefore catch such errors and display a flash message.
        Notification.error(
          TYPO3.lang['extensionList.dependenciesResolveInstallError.title'] || 'Install error',
          TYPO3.lang['extensionList.dependenciesResolveInstallError.message'] || 'Your installation failed while resolving dependencies.'
        );
      }
    }, (): void => {
      Notification.error(
        TYPO3.lang['extensionList.dependenciesResolveInstallError.title'] || 'Install error',
        TYPO3.lang['extensionList.dependenciesResolveInstallError.message'] || 'Your installation failed while resolving dependencies.'
      );
    }).finally((): void => {
      this.progressBar?.done();
    });
  }

  private getProgress(): ProgressBarElement {
    if (!this.progressBar || !this.progressBar.isConnected) {
      this.progressBar = document.createElement('typo3-backend-progress-bar');
      document.querySelector('.module-loading-indicator').appendChild(this.progressBar);
    }
    return this.progressBar;
  }

  private bindSearchFieldResetter(): void {
    let searchField: HTMLInputElement;
    if ((searchField = document.querySelector('.typo3-extensionmanager-searchTerForm input[type="search"]')) !== null) {
      const searchResultShown = ('' !== searchField.value);

      new RegularEvent('search', (): void => {
        if (searchField.value === '' && searchResultShown) {
          searchField.closest('form').submit();
        }
      }).bindTo(searchField);
    }
  }
}

export default Repository;
