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
import type MessageInterface from '../../message-interface';
import { InfoBox } from '../../renderable/info-box';
import Router from '../../router';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';

enum Identifiers {
  executeTrigger = '.t3js-environmentCheck-execute',
  outputContainer = '.t3js-environmentCheck-output'
}

type EnvironmentCheckResponse = ModuleLoadedResponseWithButtons & {
  status: {
    error: MessageInterface[],
    warning: MessageInterface[],
    ok: MessageInterface[],
    information: MessageInterface[],
    notice: MessageInterface[]
  },
}

/**
 * Module: @typo3/install/environment-check
 */
class EnvironmentCheck extends AbstractInteractableModule {
  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);

    this.loadModuleFrameAgnostic('@typo3/install/renderable/info-box.js').then((): void => {
      // Get status on initialize to have the badge and content ready
      this.runTests();
    });

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.runTests();
    }).delegateTo(currentModal, Identifiers.executeTrigger);
  }

  private runTests(): void {
    this.setModalButtonsState(false);
    const modalContent: HTMLElement = this.getModalBody();
    const outputContainer: HTMLElement = modalContent.querySelector(Identifiers.outputContainer);
    if (outputContainer !== null) {
      this.renderProgressBar(outputContainer);
    }
    (new AjaxRequest(Router.getUrl('environmentCheckGetStatus')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: EnvironmentCheckResponse = await response.resolve();
          modalContent.innerHTML = data.html;
          Modal.setButtons(data.buttons);
          if (data.success === true && typeof (data.status) === 'object') {
            for (const messages of Object.values(data.status)) {
              for (const status of messages) {
                modalContent.querySelector(Identifiers.outputContainer).append(InfoBox.create(status.severity, status.title, status.message));
              }
            }
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      ).finally((): void => {
        this.setModalButtonsState(true);
      });
  }
}

export default new EnvironmentCheck();
