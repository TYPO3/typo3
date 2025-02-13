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
import Severity from '../../renderable/severity';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';

enum Identifiers {
  executeTrigger = '.t3js-imageProcessing-execute',
  testContainer = '.t3js-imageProcessing-twinContainer',
  twinImageTemplate = '#t3js-imageProcessing-twinImage-template',
  commandContainer = '.t3js-imageProcessing-command',
  commandText = '.t3js-imageProcessing-command-text',
  twinImages = '.t3js-imageProcessing-images'
}

type ImageProcessResponse = {
  command?: string[],
  fileExists: boolean,
  outputFile: string,
  referenceFile: string,
  success: boolean,
  status?: MessageInterface[]
};

/**
 * Module: @typo3/install/module/image-processing
 */
class ImageProcessing extends AbstractInteractableModule {
  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.loadModuleFrameAgnostic('@typo3/install/renderable/info-box.js').then((): void => {
      this.getData();
    });

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.runTests();
    }).delegateTo(currentModal, Identifiers.executeTrigger);
  }

  private getData(): void {
    const modalContent: HTMLElement = this.getModalBody();
    (new AjaxRequest(Router.getUrl('imageProcessingGetData')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ModuleLoadedResponseWithButtons = await response.resolve();
          if (data.success === true) {
            modalContent.innerHTML = data.html;
            Modal.setButtons(data.buttons);
            this.runTests();
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private runTests(): void {
    const modalContent: HTMLElement = this.getModalBody();
    this.setModalButtonsState(false);

    const twinImageTemplate = this.findInModal(Identifiers.twinImageTemplate) as HTMLTemplateElement;
    const promises: Array<Promise<void>> = [];
    modalContent.querySelectorAll(Identifiers.testContainer).forEach((container: HTMLElement): void => {
      container.replaceChildren(InfoBox.create(Severity.loading, 'Loading...'));
      const request = (new AjaxRequest(Router.getUrl(container.dataset.test)))
        .get({ cache: 'no-cache' })
        .then(
          async (response: AjaxResponse): Promise<void> => {
            const data: ImageProcessResponse = await response.resolve();
            if (data.success === true) {
              container.innerHTML = '';
              if (Array.isArray(data.status)) {
                data.status.forEach((element: MessageInterface): void => {
                  container.append(InfoBox.create(element.severity, element.title, element.message));
                });
              }
              const aTwin: HTMLElement = twinImageTemplate.content.cloneNode(true) as HTMLElement;
              if (data.fileExists === true) {
                aTwin.querySelector('img.reference')?.setAttribute('src', data.referenceFile);
                aTwin.querySelector('img.result')?.setAttribute('src', data.outputFile);
                aTwin.querySelectorAll(Identifiers.twinImages).forEach((image: HTMLElement) => image.hidden = false);
              }
              if (Array.isArray(data.command) && data.command.length > 0) {
                const commandContainer: HTMLElement = aTwin.querySelector(Identifiers.commandContainer);
                if (commandContainer !== null) {
                  commandContainer.hidden = false;
                }
                const commandText: Array<string> = [];
                data.command.forEach((aElement: any): void => {
                  commandText.push('<strong>Command:</strong>\n' + aElement[1]);
                  if (aElement.length === 3) {
                    commandText.push('<strong>Result:</strong>\n' + aElement[2]);
                  }
                });
                const commandTextElement: HTMLElement = aTwin.querySelector(Identifiers.commandText);
                if (commandTextElement !== null) {
                  commandTextElement.innerHTML = commandText.join('\n');
                }
              }
              container.append(aTwin);
            }
          },
          (error: AjaxResponse): void => {
            Router.handleAjaxError(error, modalContent);
          }
        );
      promises.push(request);
    });

    Promise.all(promises).then((): void => {
      this.setModalButtonsState(true);
    });
  }
}

export default new ImageProcessing();
