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
import Tooltip = require('TYPO3/CMS/Backend/Tooltip');
import Severity = require('TYPO3/CMS/Backend/Severity');
import SecurityUtility = require('TYPO3/CMS/Core/SecurityUtility');
import ExtensionManagerRepository = require('./Repository');
import ExtensionManagerUpdate = require('./Update');
import ExtensionManagerUploadForm = require('./UploadForm');
import 'datatables';
import 'TYPO3/CMS/Backend/Input/Clearable';

const securityUtility = new SecurityUtility();

enum ExtensionManagerIdentifier {
  extensionlist = '#typo3-extension-list',
  searchField = '#Tx_Extensionmanager_extensionkey',
}

/**
 * Module: TYPO3/CMS/Extensionmanager/Main
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

  /**
   * Special sorting for the extension version column
   */
  private static versionCompare(a: string, b: string): number {
    if (a === b) {
      return 0;
    }

    const a_components = a.split('.');
    const b_components = b.split('.');
    const len = Math.min(a_components.length, b_components.length);

    // loop while the components are equal
    for (let i = 0; i < len; i++) {
      // A bigger than B
      if (parseInt(a_components[i], 10) > parseInt(b_components[i], 10)) {
        return 1;
      }

      // B bigger than A
      if (parseInt(a_components[i], 10) < parseInt(b_components[i], 10)) {
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
  }

  /**
   * The extension name column can contain various forms of HTML that
   * break a direct comparison of values
   */
  private static extensionCompare(a: string, b: string): number {
    const div = document.createElement('div');
    div.innerHTML = a;
    const aStr = div.textContent || div.innerText || a;

    div.innerHTML = b;
    const bStr = div.textContent || div.innerText || b;

    return aStr.trim().localeCompare(bStr.trim());
  }

  constructor() {
    $(() => {
      $.fn.dataTableExt.oSort['extension-asc'] = (a: string, b: string) => {
        return ExtensionManager.extensionCompare(a, b);
      };

      $.fn.dataTableExt.oSort['extension-desc'] = (a: string, b: string) => {
        let result = ExtensionManager.extensionCompare(a, b);
        return result * -1;
      };

      $.fn.dataTableExt.oSort['version-asc'] = (a: string, b: string) => {
        let result = ExtensionManager.versionCompare(a, b);
        return result * -1;
      };

      $.fn.dataTableExt.oSort['version-desc'] = (a: string, b: string) => {
        return ExtensionManager.versionCompare(a, b);
      };
      this.Update = new ExtensionManagerUpdate();
      this.UploadForm = new ExtensionManagerUploadForm();
      this.Repository = new ExtensionManagerRepository();

      const dataTable: DataTables.Api = this.manageExtensionListing();
      $(document).on('click', '.onClickMaskExtensionManager', (): void => {
        NProgress.start();
      }).on('click', 'a[data-action=update-extension]', (e: JQueryEventObject): void => {
        e.preventDefault();
        $.ajax({
          url: $(e.currentTarget).attr('href'),
          dataType: 'json',
          beforeSend: (): void => {
            NProgress.start();
          },
          success: this.updateExtension,
        });
      }).on('change', 'input[name=unlockDependencyIgnoreButton]', (e: JQueryEventObject): void => {
        const $actionButton = $('.t3js-dependencies');
        $actionButton.toggleClass('disabled', !$(e.currentTarget).prop('checked'));
      });

      let searchField: HTMLInputElement;
      if ((searchField = document.querySelector(ExtensionManagerIdentifier.searchField)) !== null) {
        searchField.clearable({
          onClear: (): void => {
            dataTable.search('').draw();
          },
        });
      }

      $(document).on('click', '.t3-button-action-installdistribution', (): void => {
        NProgress.start();
      });

      this.Repository.initDom();
      this.Update.initializeEvents();
      this.UploadForm.initializeEvents();

      Tooltip.initialize('#typo3-extension-list [title]', {
        delay: {
          show: 500,
          hide: 100,
        },
        trigger: 'hover',
        container: 'body',
      });
    });
  }

  private manageExtensionListing(): DataTables.Api {
    const $searchField = $(ExtensionManagerIdentifier.searchField);
    const dataTable = $(ExtensionManagerIdentifier.extensionlist).DataTable({
      paging: false,
      dom: 'lrtip',
      lengthChange: false,
      pageLength: 15,
      stateSave: true,
      info: false,
      drawCallback: this.bindExtensionListActions,
      columns: [
        null,
        null,
        { type: 'extension' },
        null,
        { type: 'version' },
        { orderable: false },
        null,
        { orderable: false },
      ],
    });

    $searchField.parents('form').on('submit', () => {
      return false;
    });

    const getVars: any = ExtensionManager.getUrlVars();

    // restore filter
    const currentSearch = (getVars.search ? getVars.search : dataTable.search());
    $searchField.val(currentSearch);

    $searchField.on('input', (e: JQueryEventObject): void => {
      dataTable.search($(e.currentTarget).val()).draw();
    });

    return dataTable;
  }

  private bindExtensionListActions = (): void => {
    $('.removeExtension').not('.transformed').each((index: number, element: any) => {
      const $me = $(element);
      $me.data('href', $me.attr('href'));
      $me.attr('href', '#');
      $me.addClass('transformed');
      $me.click((): void => {
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
                this.removeExtensionFromDisk($me);
                Modal.dismiss();
              },
            },
          ],
        );
      });
    });
  }

  private removeExtensionFromDisk($extension: JQuery): void {
    $.ajax({
      url: $extension.data('href'),
      beforeSend: (): void => {
        NProgress.start();
      },
      success: (): void => {
        location.reload();
      },
      complete: (): void => {
        NProgress.done();
      },
    });
  }

  private updateExtension(data: any): void {
    let i = 0;
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
            $.ajax({
              url: data.url,
              data: {
                tx_extensionmanager_tools_extensionmanagerextensionmanager: {
                  version: $('input:radio[name=version]:checked', Modal.currentModal).val(),
                },
              },
              dataType: 'json',
              beforeSend: (): void => {
                NProgress.start();
              },
              complete: (): void => {
                location.reload();
              },
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

export = extensionManagerObject;
