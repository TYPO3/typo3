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
import { AbstractInteractableModule, type ModuleLoadedResponseWithButtons } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { InfoBox } from '../../renderable/info-box';
import Severity from '../../renderable/severity';
import Router from '../../router';
import RegularEvent from '@typo3/core/event/regular-event';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { ModalElement } from '@typo3/backend/modal';
import type MessageInterface from '@typo3/install/message-interface';

enum Identifiers {
  checkTrigger = '.t3js-extensionCompatTester-check',
  uninstallTrigger = '.t3js-extensionCompatTester-uninstall',
  outputContainer = '.t3js-extensionCompatTester-output'
}

interface BrokenExtension {
  name: string;
  isProtected: boolean;
}

/**
 * Module: @typo3/install/module/extension-compat-tester
 */
class ExtensionCompatTester extends AbstractInteractableModule {
  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.loadModuleFrameAgnostic('@typo3/install/renderable/info-box.js').then((): void => {
      this.getLoadedExtensionList();
    });

    new RegularEvent('click', (): void => {
      this.findInModal(Identifiers.uninstallTrigger)?.classList?.add('hidden');

      const outputContainer = this.findInModal(Identifiers.outputContainer);
      if (outputContainer) {
        outputContainer.innerHTML = '';
      }
      this.getLoadedExtensionList();
    }).delegateTo(currentModal, Identifiers.checkTrigger);

    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      this.uninstallExtension(target.dataset.extension);
    }).delegateTo(currentModal, Identifiers.uninstallTrigger);
  }

  private getLoadedExtensionList(): void {
    this.setModalButtonsState(false);
    const modalContent = this.getModalBody();
    const outputContainer = this.findInModal(Identifiers.outputContainer);
    if (outputContainer) {
      this.renderProgressBar(outputContainer, {}, 'append');
    }

    (new AjaxRequest(Router.getUrl('extensionCompatTesterLoadedExtensionList')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ModuleLoadedResponseWithButtons = await response.resolve();
          modalContent.innerHTML = data.html;
          Modal.setButtons(data.buttons);
          const innerOutputContainer = this.findInModal(Identifiers.outputContainer);
          this.renderProgressBar(innerOutputContainer, {}, 'append');

          if (data.success === true) {
            this.loadExtLocalconf().then((): void => {
              innerOutputContainer.append(InfoBox.create(Severity.ok, 'ext_localconf.php of all loaded extensions successfully loaded'));
              this.loadExtTables().then((): void => {
                innerOutputContainer.append(InfoBox.create(Severity.ok, 'ext_tables.php of all loaded extensions successfully loaded'));
              }, async (error: AjaxResponse): Promise<void> => {
                this.renderFailureMessages('ext_tables.php', (await error.response.json()).brokenExtensions, innerOutputContainer);
              }).finally((): void => {
                this.unlockModal();
              });
            }, async (error: AjaxResponse): Promise<void> => {
              this.renderFailureMessages('ext_localconf.php', (await error.response.json()).brokenExtensions, innerOutputContainer);
              innerOutputContainer.append(InfoBox.create(Severity.notice, 'Skipped scanning ext_tables.php files due to previous errors'));
              this.unlockModal();
            });
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
            this.unlockModal();
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
          this.unlockModal();
        }
      );
  }

  private unlockModal(): void {
    this.findInModal(Identifiers.outputContainer)?.querySelector('typo3-backend-progress-bar')?.remove();
    this.setModalButtonsState(true);
  }

  private renderFailureMessages(scope: string, brokenExtensions: Array<BrokenExtension>, outputContainer: HTMLElement): void {
    for (const extension of brokenExtensions) {
      let uninstallAction;
      if (!extension.isProtected) {
        uninstallAction = document.createElement('button');
        uninstallAction.classList.add('btn', 'btn-danger', 't3js-extensionCompatTester-uninstall');
        uninstallAction.dataset.extension = extension.name;
        uninstallAction.innerText = 'Uninstall extension "' + extension.name + '"';
      }
      outputContainer.append(
        InfoBox.create(
          Severity.error,
          'Loading ' + scope + ' of extension "' + extension.name + '" failed',
          extension.isProtected ? 'Extension is mandatory and cannot be uninstalled.' : ''
        ),
        uninstallAction,
      );
    }

    this.unlockModal();
  }

  private loadExtLocalconf(): Promise<AjaxResponse> {
    const executeToken = this.getModuleContent().dataset.extensionCompatTesterLoadExt_localconfToken;
    return new AjaxRequest(Router.getUrl()).post({
      'install': {
        'action': 'extensionCompatTesterLoadExtLocalconf',
        'token': executeToken,
      },
    });
  }

  private loadExtTables(): Promise<AjaxResponse> {
    const executeToken = this.getModuleContent().dataset.extensionCompatTesterLoadExt_tablesToken;
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
    const executeToken = this.getModuleContent().dataset.extensionCompatTesterUninstallExtensionToken;
    const modalContent = this.getModalBody();
    const outputContainer = this.findInModal(Identifiers.outputContainer);
    this.renderProgressBar(outputContainer, {}, 'append');
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          action: 'extensionCompatTesterUninstallExtension',
          token: executeToken,
          extension: extension,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.success) {
            if (Array.isArray(data.status)) {
              data.status.forEach((element: MessageInterface): void => {
                modalContent.querySelector(Identifiers.outputContainer).replaceChildren(InfoBox.create(element.severity, element.title, element.message));
              });
            }
            this.findInModal(Identifiers.uninstallTrigger).classList.add('hidden');
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
