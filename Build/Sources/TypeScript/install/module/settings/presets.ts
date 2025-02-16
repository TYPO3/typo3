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
import Router from '../../router';
import RegularEvent from '@typo3/core/event/regular-event';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { ModalElement } from '@typo3/backend/modal';
import type MessageInterface from '@typo3/install/message-interface';

enum Identifiers {
  activateTrigger = '.t3js-presets-activate',
  imageExecutable = '.t3js-presets-image-executable',
  imageExecutableTrigger = '.t3js-presets-image-executable-trigger'
}

type PresetsWrittenResponse = {
  status: MessageInterface[],
  success: boolean,
}

/**
 * Module: @typo3/install/module/presets
 */
class Presets extends AbstractInteractableModule {
  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.getContent();

    // Load content with post data on click 'custom image executable path'
    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.getCustomImagePathContent();
    }).delegateTo(currentModal, Identifiers.imageExecutableTrigger);

    // Write out selected preset
    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.activate();
    }).delegateTo(currentModal, Identifiers.activateTrigger);

    // Automatically select the custom preset if a value in one of its input fields is changed

    currentModal.querySelectorAll('.t3js-custom-preset').forEach((element: HTMLInputElement) => {
      new RegularEvent('input', (event: Event, target: HTMLElement): void => {
        currentModal.querySelector<HTMLInputElement>(`#${target.dataset.radio}`).checked = true;
      }).delegateTo(element, '.t3js-custom-preset');
    });
  }

  private getContent(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('presetsGetContent')))
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
        },
      );
  }

  private getCustomImagePathContent(): void {
    const modalContent = this.getModalBody();
    const presetsContentToken = this.getModuleContent().dataset.presetsContentToken;
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          token: presetsContentToken,
          action: 'presetsGetContent',
          values: {
            Image: {
              additionalSearchPath: (this.findInModal(Identifiers.imageExecutable) as HTMLInputElement).value,
            },
          },
        },
      })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
            modalContent.innerHTML = data.html;
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        },
      );
  }

  private activate(): void {
    this.setModalButtonsState(false);

    const modalContent = this.getModalBody();
    const executeToken: string = this.getModuleContent().dataset.presetsActivateToken;
    const postData: Record<string, string> = {};
    const formData = new FormData(this.findInModal('form'));
    for (const [name, value] of formData) {
      postData[name] = value.toString();
    }
    postData['install[action]'] = 'presetsActivate';
    postData['install[token]'] = executeToken;
    (new AjaxRequest(Router.getUrl())).post(postData).then(
      async (response: AjaxResponse): Promise<void> => {
        const data: PresetsWrittenResponse = await response.resolve();
        if (data.success === true && Array.isArray(data.status)) {
          data.status.forEach((element: MessageInterface): void => {
            Notification.showMessage(element.title, element.message, element.severity);
          });
        } else {
          Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
        }
      },
      (error: AjaxResponse): void => {
        Router.handleAjaxError(error, modalContent);
      },
    ).finally((): void => {
      this.setModalButtonsState(true);
    });
  }
}

export default new Presets();
