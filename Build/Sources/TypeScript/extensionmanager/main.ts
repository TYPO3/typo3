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
import Tooltip from '@typo3/backend/tooltip';
import Severity from '@typo3/backend/severity';
import SecurityUtility from '@typo3/core/security-utility';
import ExtensionManagerRepository from './repository';
import ExtensionManagerUpdate from './update';
import ExtensionManagerUploadForm from './upload-form';
import Tablesort from 'tablesort';
import 'tablesort.dotsep';
import '@typo3/backend/input/clearable';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import DebounceEvent from '@typo3/core/event/debounce-event';
import RegularEvent from '@typo3/core/event/regular-event';

const securityUtility = new SecurityUtility();

enum ExtensionManagerIdentifier {
  extensionlist = 'typo3-extension-list',
  searchField = '#Tx_Extensionmanager_extensionkey',
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

  private static getUrlVars(): any {
    let vars: any = [];
    let hashes: Array<string> = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for (let hash of hashes) {
      const [k, v] = hash.split('=');
      vars.push(k);
      vars[k] = v;
    }
    return vars;
  }

  constructor() {
    const me = this;
    $(() => {
      this.Update = new ExtensionManagerUpdate();
      this.UploadForm = new ExtensionManagerUploadForm();
      this.Repository = new ExtensionManagerRepository();

      const extensionList = document.getElementById(ExtensionManagerIdentifier.extensionlist);
      if (extensionList !== null) {
        new Tablesort(extensionList);

        new RegularEvent('click', function (this: HTMLAnchorElement, e: Event): void {
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
                  me.removeExtensionFromDisk(this);
                  Modal.dismiss();
                },
              },
            ],
          );
        }).delegateTo(extensionList, '.removeExtension');
      }
      $(document).on('click', '.onClickMaskExtensionManager', (): void => {
        NProgress.start();
      }).on('click', 'a[data-action=update-extension]', (e: JQueryEventObject): void => {
        e.preventDefault();

        NProgress.start();
        new AjaxRequest($(e.currentTarget).attr('href')).get().then(this.updateExtension);
      }).on('change', 'input[name=unlockDependencyIgnoreButton]', (e: JQueryEventObject): void => {
        const $actionButton = $('.t3js-dependencies');
        $actionButton.toggleClass('disabled', !$(e.currentTarget).prop('checked'));
      });

      let searchField: HTMLInputElement;
      if ((searchField = document.querySelector(ExtensionManagerIdentifier.searchField)) !== null) {
        new RegularEvent('submit', (e: Event): void => {
          e.preventDefault();
        }).bindTo(searchField.closest('form'));

        new DebounceEvent('keyup', (e: KeyboardEvent): void => {
          this.filterExtensions(e.target as HTMLInputElement);
        }, 100).bindTo(searchField);
        searchField.clearable({
          onClear: (input: HTMLInputElement): void => {
            this.filterExtensions(input);
          },
        });
      }

      $(document).on('click', '.t3-button-action-installdistribution', (): void => {
        NProgress.start();
      });

      this.Repository.initDom();
      this.Update.initializeEvents();
      this.UploadForm.initializeEvents();

      Tooltip.initialize('#typo3-extension-list [title]');
    });
  }

  private filterExtensions(input: HTMLInputElement): void {
    const filterableColumns = document.querySelectorAll('[data-filterable]');
    const columnIndices: number[] = [];
    filterableColumns.forEach((element: HTMLTableRowElement): void => {
      const children = Array.from(element.parentElement.children);
      columnIndices.push(children.indexOf(element));
    });
    const columnQuerySelectors = columnIndices.map((index: number): string => `td:nth-child(${index + 1})`).join(',');
    const rows = document.querySelectorAll('#typo3-extension-list tbody tr');
    rows.forEach((row: HTMLTableRowElement): void => {
      const columns = row.querySelectorAll(columnQuerySelectors);
      const values: string[] = [];
      columns.forEach((column: HTMLTableCellElement): void => {
        values.push(column.textContent.trim().replace(/\s+/g, ' '));
      });
      row.classList.toggle('hidden', input.value !== '' && !RegExp(input.value, 'i').test(values.join(':')));
    });
  }

  private removeExtensionFromDisk(trigger: HTMLAnchorElement): void {
    NProgress.start();
    new AjaxRequest(trigger.href).get().then((): void => {
      location.reload();
    }).finally((): void => {
      NProgress.done();
    });
  }

  private async updateExtension(response: AjaxResponse): Promise<void> {
    let i = 0;
    const data = await response.resolve();
    const $form = $('<form>');
    $.each(data.updateComments, (version: string, comment: string): void => {
      const $input = $('<input>').attr({type: 'radio', name: 'version'}).val(version);
      if (i === 0) {
        $input.attr('checked', 'checked');
      }
      $form.append([
        $('<h3>').append([
          $input,
          ' ' + securityUtility.encodeHtml(version),
        ]),
        $('<div>')
          .append(
            comment
              .replace(/(\r\n|\n\r|\r|\n)/g, '\n')
              .split(/\n/).map((line: string): string => {
                return securityUtility.encodeHtml(line);
              })
              .join('<br>'),
          ),
      ]);
      i++;
    });
    const $container = $('<div>').append([
      $('<h1>').text(TYPO3.lang['extensionList.updateConfirmation.title']),
      $('<h2>').text(TYPO3.lang['extensionList.updateConfirmation.message']),
      $form,
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
          trigger: (): void => {
            Modal.dismiss();
          },
        }, {
          text: TYPO3.lang['button.updateExtension'],
          btnClass: 'btn-warning',
          trigger: (): void => {
            NProgress.start();
            new AjaxRequest(data.url).withQueryArguments({
              tx_extensionmanager_tools_extensionmanagerextensionmanager: {
                version: $('input:radio[name=version]:checked', Modal.currentModal).val(),
              }
            }).get().finally((): void => {
              location.reload();
            });
            Modal.dismiss();
          },
        },
      ],
    );
  }
}

let extensionManagerObject = new ExtensionManager();

if (typeof TYPO3.ExtensionManager === 'undefined') {
  TYPO3.ExtensionManager = extensionManagerObject;
}

export default extensionManagerObject;
