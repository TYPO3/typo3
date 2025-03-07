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

import { AbstractInteractableModule, type ModuleLoadedResponseWithButtons } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import RegularEvent from '@typo3/core/event/regular-event';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { ModalElement } from '@typo3/backend/modal';
import type MessageInterface from '@typo3/install/message-interface';

enum Identifiers {
  saveTrigger = '.t3js-features-save'
}

type FeaturesWrittenResponse = {
  status: MessageInterface[],
  success: boolean,
}

/**
 * Module: @typo3/install/module/features
 */
class Features extends AbstractInteractableModule {
  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.getContent();

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.save();
    }).delegateTo(currentModal, Identifiers.saveTrigger);
  }

  private getContent(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('featuresGetContent')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ModuleLoadedResponseWithButtons = await response.resolve();
          if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
            modalContent.innerHTML = data.html;
            Modal.setButtons(data.buttons);
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private save(): void {
    this.setModalButtonsState(false);

    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().dataset.featuresSaveToken;
    const postData: Record<string, string> = {};
    const formData = new FormData(this.findInModal('form'));
    for (const [name, value] of formData) {
      postData[name] = value.toString();
    }
    postData['install[action]'] = 'featuresSave';
    postData['install[token]'] = executeToken;
    (new AjaxRequest(Router.getUrl()))
      .post(postData)
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: FeaturesWrittenResponse = await response.resolve();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: MessageInterface): void => {
              Notification.showMessage(element.title, element.message, element.severity);
            });
            this.getContent();
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

export default new Features();
