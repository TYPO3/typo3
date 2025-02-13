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
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { AbstractInteractableModule, ModuleLoadedResponseWithButtons } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { InfoBox } from '../../renderable/info-box';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';

enum Identifiers {
  outputContainer = '.t3js-mailTest-output',
  mailTestButton = '.t3js-mailTest-execute'
}

type MailGetDataResponse = ModuleLoadedResponseWithButtons & {
  messages: MessageInterface[],
  sendPossible: boolean,
};

type SendTestMailResponse = {
  success: boolean,
  status: MessageInterface[],
}

/**
 * Module: @typo3/install/module/create-admin
 */
class MailTest extends AbstractInteractableModule {

  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.loadModuleFrameAgnostic('@typo3/install/renderable/info-box.js').then((): void => {
      this.getData();
    });

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.send();
    }).delegateTo(currentModal, Identifiers.mailTestButton);

    new RegularEvent('submit', (event: Event): void => {
      event.preventDefault();
      this.send();
    }).delegateTo(currentModal, 'form');
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('mailTestGetData')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: MailGetDataResponse = await response.resolve();
          if (data.success === true) {
            modalContent.innerHTML = data.html;
            const outputContainer: HTMLElement = this.findInModal(Identifiers.outputContainer);
            if (data.messages && Array.isArray(data.messages)) {
              data.messages.forEach((element: MessageInterface): void => {
                outputContainer.append(InfoBox.create(element.severity, element.title, element.message));
              });
            }
            if (data.sendPossible) {
              Modal.setButtons(data.buttons);
            }
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }

        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        },
      );
  }

  private send(): void {
    this.setModalButtonsState(false);

    const executeToken: string = this.getModuleContent().dataset.mailTestToken;
    const outputContainer: HTMLElement = this.findInModal(Identifiers.outputContainer);
    this.renderProgressBar(outputContainer);
    (new AjaxRequest(Router.getUrl())).post({
      install: {
        action: 'mailTest',
        token: executeToken,
        email: (this.findInModal('.t3js-mailTest-email') as HTMLInputElement).value,
      },
    }).then(
      async (response: AjaxResponse): Promise<void> => {
        const data: SendTestMailResponse = await response.resolve();
        outputContainer.innerHTML = '';
        if (Array.isArray(data.status)) {
          data.status.forEach((element: MessageInterface): void => {
            outputContainer.innerHTML = '';
            outputContainer.append(InfoBox.create(element.severity, element.title, element.message));
          });
        } else {
          Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
        }
      },
      (): void => {
        // 500 can happen here if the mail configuration is broken
        Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
      },
    ).finally((): void => {
      this.setModalButtonsState(true);
    });
  }
}

export default new MailTest();
