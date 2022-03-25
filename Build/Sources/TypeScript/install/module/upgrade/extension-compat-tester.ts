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

import 'bootstrap';
import $ from 'jquery';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import {AbstractInteractableModule} from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import InfoBox from '../../renderable/info-box';
import ProgressBar from '../../renderable/progress-bar';
import Severity from '../../renderable/severity';
import Router from '../../router';

interface BrokenExtension {
  name: string;
  isProtected: boolean;
}

/**
 * Module: @typo3/install/module/extension-compat-tester
 */
class ExtensionCompatTester extends AbstractInteractableModule {
  private selectorCheckTrigger: string = '.t3js-extensionCompatTester-check';
  private selectorUninstallTrigger: string = '.t3js-extensionCompatTester-uninstall';
  private selectorOutputContainer: string = '.t3js-extensionCompatTester-output';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getLoadedExtensionList();

    currentModal.on('click', this.selectorCheckTrigger, (): void => {
      this.findInModal(this.selectorUninstallTrigger).addClass('hidden');
      this.findInModal(this.selectorOutputContainer).empty();
      this.getLoadedExtensionList();
    });
    currentModal.on('click', this.selectorUninstallTrigger, (e: JQueryEventObject): void => {
      this.uninstallExtension($(e.target).data('extension'));
    });
  }

  private getLoadedExtensionList(): void {
    this.setModalButtonsState(false);
    const modalContent = this.getModalBody();
    const $outputContainer = this.findInModal(this.selectorOutputContainer);
    if ($outputContainer.length) {
      const message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.append(message);
    }

    (new AjaxRequest(Router.getUrl('extensionCompatTesterLoadedExtensionList')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          modalContent.empty().append(data.html);
          Modal.setButtons(data.buttons);
          const $innerOutputContainer: JQuery = this.findInModal(this.selectorOutputContainer);
          const progressBar = ProgressBar.render(Severity.loading, 'Loading...', '');
          $innerOutputContainer.append(progressBar);

          if (data.success === true) {
            this.loadExtLocalconf().then((): void => {
              $innerOutputContainer.append(
                InfoBox.render(Severity.ok, 'ext_localconf.php of all loaded extensions successfully loaded', ''),
              );
              this.loadExtTables().then((): void => {
                $innerOutputContainer.append(
                  InfoBox.render(Severity.ok, 'ext_tables.php of all loaded extensions successfully loaded', ''),
                );
              }, async (error: AjaxResponse): Promise<void> => {
                this.renderFailureMessages('ext_tables.php', (await error.response.json()).brokenExtensions, $innerOutputContainer);
              }).finally((): void => {
                this.unlockModal();
              })
            }, async (error: AjaxResponse): Promise<void> => {
              this.renderFailureMessages('ext_localconf.php', (await error.response.json()).brokenExtensions, $innerOutputContainer);
              $innerOutputContainer.append(
                InfoBox.render(Severity.notice, 'Skipped scanning ext_tables.php files due to previous errors', ''),
              );
              this.unlockModal();
            });
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private unlockModal(): void {
    this.findInModal(this.selectorOutputContainer).find('.alert-loading').remove();
    this.findInModal(this.selectorCheckTrigger).removeClass('disabled').prop('disabled', false);
  }

  private renderFailureMessages(scope: string, brokenExtensions: Array<BrokenExtension>, $outputContainer: JQuery): void {
    for (let extension of brokenExtensions) {
      let uninstallAction;
      if (!extension.isProtected) {
        uninstallAction = $('<button />', {'class': 'btn btn-danger t3js-extensionCompatTester-uninstall'})
          .attr('data-extension', extension.name)
          .text('Uninstall extension "' + extension.name + '"');
      }
      $outputContainer.append(
        InfoBox.render(
          Severity.error,
          'Loading ' + scope + ' of extension "' + extension.name + '" failed',
          (extension.isProtected ? 'Extension is mandatory and cannot be uninstalled.' : ''),
        ),
        uninstallAction,
      );
    }

    this.unlockModal();
  }

  private loadExtLocalconf(): Promise<AjaxResponse> {
    const executeToken = this.getModuleContent().data('extension-compat-tester-load-ext_localconf-token');
    return new AjaxRequest(Router.getUrl()).post({
      'install': {
        'action': 'extensionCompatTesterLoadExtLocalconf',
        'token': executeToken,
      },
    });
  }

  private loadExtTables(): Promise<AjaxResponse> {
    const executeToken = this.getModuleContent().data('extension-compat-tester-load-ext_tables-token');
    return new AjaxRequest(Router.getUrl()).post({
      'install': {
        'action': 'extensionCompatTesterLoadExtTables',
        'token': executeToken,
      },
    });
  }

  /**
   * Send an ajax request to uninstall an extension (or multiple extensions)
   *
   * @param extension string of extension(s) - may be comma separated
   */
  private uninstallExtension(extension: string): void {
    const executeToken = this.getModuleContent().data('extension-compat-tester-uninstall-extension-token');
    const modalContent = this.getModalBody();
    const $outputContainer = $(this.selectorOutputContainer);
    const message = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.append(message);
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          action: 'extensionCompatTesterUninstallExtension',
          token: executeToken,
          extension: extension,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success) {
            if (Array.isArray(data.status)) {
              data.status.forEach((element: any): void => {
                const aMessage = InfoBox.render(element.severity, element.title, element.message);
                modalContent.find(this.selectorOutputContainer).empty().append(aMessage);
              });
            }
            this.findInModal(this.selectorUninstallTrigger).addClass('hidden');
            this.getLoadedExtensionList();
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }
}

export default new ExtensionCompatTester();
