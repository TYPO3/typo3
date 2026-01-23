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

import DocumentService from '@typo3/core/document-service';
import BrowserSession from '@typo3/backend/storage/browser-session';
import { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';
import { default as Modal, type ModalElement } from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import SecurityUtility from '@typo3/core/security-utility';
import ExtensionManagerRepository from './repository';
import ExtensionManagerUpdate from './update';
import ExtensionManagerUploadForm from './upload-form';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import DebounceEvent from '@typo3/core/event/debounce-event';
import RegularEvent from '@typo3/core/event/regular-event';
import SortableTable from '@typo3/backend/sortable-table';

const securityUtility = new SecurityUtility();

enum ExtensionManagerIdentifier {
  extensionlist = 'typo3-extension-list',
  searchField = '#Tx_Extensionmanager_extensionkey',
}

interface UpdateInformation {
  updateComments: Record<string, string>,
  url: string
}

/**
 * Module: @typo3/extensionmanager/main
 * main logic holding everything together, consists of multiple parts
 * ExtensionManager => Various functions for displaying the extension list / sorting
 * Repository => Various AJAX functions for TER downloads
 * ExtensionManager.Update => Various AJAX functions to display updates
 * ExtensionManager.uploadForm => helper to show the upload form
 */
class ExtensionManager {
  public Update: ExtensionManagerUpdate;
  public UploadForm: ExtensionManagerUploadForm;
  public Repository: ExtensionManagerRepository;
  private readonly searchFilterSessionKey: string = 'tx-extensionmanager-local-filter';
  private progressBar: ProgressBarElement | null = null;

  constructor() {
    DocumentService.ready().then((): void => {
      this.Update = new ExtensionManagerUpdate();
      this.UploadForm = new ExtensionManagerUploadForm();
      this.Repository = new ExtensionManagerRepository();

      const extensionList = document.getElementById(ExtensionManagerIdentifier.extensionlist);
      if (extensionList !== null) {

        if (extensionList instanceof HTMLTableElement) {
          new SortableTable(extensionList);
        }

        new RegularEvent('click', (e: Event, target: HTMLAnchorElement): void => {
          e.preventDefault();

          Modal.confirm(
            TYPO3.lang['extensionList.removalConfirmation.title'],
            TYPO3.lang['extensionList.removalConfirmation.question'],
            Severity.error,
            [
              {
                text: TYPO3.lang['button.cancel'],
                active: true,
                btnClass: 'btn-default',
                trigger: (): void => {
                  Modal.dismiss();
                },
              }, {
                text: TYPO3.lang['button.remove'],
                btnClass: 'btn-danger',
                trigger: (): void => {
                  this.removeExtensionFromDisk(target);
                  Modal.dismiss();
                },
              },
            ],
          );
        }).delegateTo(extensionList, '.removeExtension');

        new RegularEvent('click', (e: Event, target: HTMLAnchorElement): void => {
          e.preventDefault();

          Modal.confirm(
            TYPO3.lang['extensionList.databaseReload.title'],
            TYPO3.lang['extensionList.databaseReload.message'],
            Severity.warning,
            [
              {
                text: TYPO3.lang['button.cancel'],
                active: true,
                btnClass: 'btn-default',
                trigger: (): void => {
                  Modal.dismiss();
                },
              }, {
                text: TYPO3.lang['button.reimport'],
                btnClass: 'btn-warning',
                trigger: (): void => {
                  const progressBar = document.createElement('typo3-backend-progress-bar');
                  document.body.appendChild(progressBar);
                  progressBar.start();
                  new AjaxRequest(target.href).post({}).then((): void => {
                    location.reload();
                  }).finally((): void => {
                    progressBar.done();
                    Modal.dismiss();
                  });
                },
              },
            ],
          );
        }).delegateTo(extensionList, '.reloadSqlData');

      }

      new RegularEvent('click', (): void => {
        this.progressBar = document.createElement('typo3-backend-progress-bar');
        document.body.appendChild(this.progressBar);
        this.progressBar.start();
      }).delegateTo(document, '.onClickMaskExtensionManager');

      new RegularEvent('click', (e: Event, target: HTMLAnchorElement): void => {
        e.preventDefault();

        this.progressBar = document.createElement('typo3-backend-progress-bar');
        document.body.appendChild(this.progressBar);
        this.progressBar.start();
        new AjaxRequest(target.href).get().then((response: AjaxResponse): Promise<void> => this.updateExtension(response));
      }).delegateTo(document, 'a[data-action=update-extension]');

      new RegularEvent('change', (e: Event, target: HTMLInputElement): void => {
        const actionButton = document.querySelector('.t3js-dependencies');

        if (target.checked) {
          actionButton.classList.remove('disabled');
        } else {
          actionButton.classList.add('disabled');
        }
      }).delegateTo(document, 'input[name=unlockDependencyIgnoreButton]');

      new RegularEvent('click', (): void => {
        this.progressBar = document.createElement('typo3-backend-progress-bar');
        document.body.appendChild(this.progressBar);
        this.progressBar.start();
      }).delegateTo(document, '.t3-button-action-installdistribution');

      let searchField: HTMLInputElement;
      if ((searchField = document.querySelector(ExtensionManagerIdentifier.searchField)) !== null) {
        const activeSearchFilter = BrowserSession.get(this.searchFilterSessionKey);
        if (activeSearchFilter !== null) {
          searchField.value = activeSearchFilter;
          this.filterExtensions(activeSearchFilter);
        }

        new RegularEvent('submit', (e: Event): void => {
          e.preventDefault();
        }).bindTo(searchField.closest('form'));

        new DebounceEvent('input', (e: KeyboardEvent): void => {
          const target = e.target as HTMLInputElement;
          BrowserSession.set(this.searchFilterSessionKey, target.value);
          this.filterExtensions(target.value);
        }, 100).bindTo(searchField);

        new RegularEvent('search', (): void => {
          if (searchField.value === '') {
            BrowserSession.unset(this.searchFilterSessionKey);
            this.filterExtensions('');
          }
        }).bindTo(searchField);
      }

      this.Repository.initDom();
      this.Update.initializeEvents();
      this.UploadForm.initializeEvents();
    });
  }

  private filterExtensions(searchText: string): void {
    const filterableColumns = document.querySelectorAll('[data-filterable]');
    const columnIndices: number[] = [];
    filterableColumns.forEach((element: HTMLTableRowElement): void => {
      const children = Array.from(element.parentElement.children);
      columnIndices.push(children.indexOf(element));
    });
    const rows = document.querySelectorAll('#typo3-extension-list tbody tr');
    rows.forEach((row: HTMLTableRowElement): void => {
      const columns = columnIndices.map((index: number) => row.children.item(index));
      const values: string[] = [];
      columns.forEach((column: HTMLTableCellElement): void => {
        values.push(column.textContent.trim().replace(/\s+/g, ' '));
      });
      row.classList.toggle('hidden', searchText !== '' && !RegExp(searchText, 'i').test(values.join(':')));
    });
  }

  private removeExtensionFromDisk(trigger: HTMLAnchorElement): void {
    const progressBar = document.createElement('typo3-backend-progress-bar');
    document.body.appendChild(progressBar);
    progressBar.start();
    new AjaxRequest(trigger.href).post({}).then((): void => {
      location.reload();
    }).finally((): void => {
      progressBar.done();
    });
  }

  private async updateExtension(response: AjaxResponse): Promise<void> {
    const data: UpdateInformation = await response.resolve();
    const versions = Object.entries(data.updateComments);

    const form = document.createElement('form');

    versions.forEach(([version, comment], index) => {
      const formCheck = document.createElement('div');
      formCheck.classList.add('form-check', 'form-check-type-card', 'mb-2');

      const inputId = 'version-' + version.replace(/\./g, '-');

      const versionInput = document.createElement('input');
      versionInput.classList.add('form-check-input');
      versionInput.type = 'radio';
      versionInput.name = 'version';
      versionInput.id = inputId;
      versionInput.value = version;
      if (index === 0) {
        versionInput.checked = true;
      }

      const label = document.createElement('label');
      label.classList.add('form-check-label');
      label.setAttribute('for', inputId);

      const labelHeader = document.createElement('span');
      labelHeader.classList.add('form-check-label-header');
      labelHeader.textContent = version;
      label.append(labelHeader);

      if (comment) {
        const labelBody = document.createElement('span');
        labelBody.classList.add('form-check-label-body');
        labelBody.innerHTML = comment
          .replace(/(\r\n|\n\r|\r|\n)/g, '\n')
          .split(/\n/).map((line: string): string => {
            return securityUtility.encodeHtml(line);
          })
          .join('<br>');
        label.append(labelBody);
      }

      formCheck.append(versionInput, label);
      form.append(formCheck);
    });

    if (this.progressBar) {
      this.progressBar.done();
    }

    Modal.confirm(
      TYPO3.lang['extensionList.updateConfirmation.questionVersionComments'],
      form,
      Severity.notice,
      [
        {
          text: TYPO3.lang['button.cancel'],
          active: true,
          btnClass: 'btn-default',
          trigger: (e: Event, modal: ModalElement): void => modal.hideModal(),
        }, {
          text: TYPO3.lang['button.updateExtension'],
          btnClass: 'btn-warning',
          trigger: (e: Event, modal: ModalElement): void => {
            const progressBar = document.createElement('typo3-backend-progress-bar');
            document.body.appendChild(progressBar);
            progressBar.start();
            new AjaxRequest(data.url).post({
              version: (modal.querySelector('input[name="version"]:checked') as HTMLInputElement)?.value,
            }).finally((): void => {
              location.reload();
            });
            modal.hideModal();
          },
        },
      ],
    );
  }
}

const extensionManagerObject = new ExtensionManager();

if (typeof TYPO3.ExtensionManager === 'undefined') {
  TYPO3.ExtensionManager = extensionManagerObject;
}

export default extensionManagerObject;
