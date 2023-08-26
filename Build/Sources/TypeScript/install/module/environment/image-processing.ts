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
import { AbstractInteractableModule } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { InfoBox } from '../../renderable/info-box';
import Severity from '../../renderable/severity';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';

/**
 * Module: @typo3/install/module/image-processing
 */
class ImageProcessing extends AbstractInteractableModule {
  private readonly selectorExecuteTrigger: string = '.t3js-imageProcessing-execute';
  private readonly selectorTestContainer: string = '.t3js-imageProcessing-twinContainer';
  private readonly selectorTwinImageTemplate: string = '.t3js-imageProcessing-twinImage-template';
  private readonly selectorCommandContainer: string = '.t3js-imageProcessing-command';
  private readonly selectorCommandText: string = '.t3js-imageProcessing-command-text';
  private readonly selectorTwinImages: string = '.t3js-imageProcessing-images';

  public initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.getData();

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.runTests();
    }).delegateTo(currentModal, this.selectorExecuteTrigger);
  }

  private getData(): void {
    const modalContent: HTMLElement = this.getModalBody();
    (new AjaxRequest(Router.getUrl('imageProcessingGetData')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
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

    const twinImageTemplate = this.findInModal(this.selectorTwinImageTemplate);
    const promises: Array<Promise<void>> = [];
    modalContent.querySelectorAll(this.selectorTestContainer).forEach((container: HTMLElement): void => {
      container.replaceChildren(InfoBox.create(Severity.loading, 'Loading...'));
      const request = (new AjaxRequest(Router.getUrl(container.dataset.test)))
        .get({ cache: 'no-cache' })
        .then(
          async (response: AjaxResponse): Promise<void> => {
            const data = await response.resolve();
            if (data.success === true) {
              container.innerHTML = '';
              if (Array.isArray(data.status)) {
                data.status.forEach((element: MessageInterface): void => {
                  container.append(InfoBox.create(element.severity, element.title, element.message));
                });
              }
              const aTwin: HTMLElement = twinImageTemplate.cloneNode(true) as HTMLElement;
              aTwin.classList.remove('t3js-imageProcessing-twinImage-template');
              if (data.fileExists === true) {
                aTwin.querySelector('img.reference')?.setAttribute('src', data.referenceFile);
                aTwin.querySelector('img.result')?.setAttribute('src', data.outputFile);
                aTwin.querySelectorAll(this.selectorTwinImages).forEach((image: HTMLElement) => image.hidden = false);
              }
              if (Array.isArray(data.command) && data.command.length > 0) {
                const commandContainer: HTMLElement = aTwin.querySelector(this.selectorCommandContainer);
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
                const commandTextElement: HTMLElement = aTwin.querySelector(this.selectorCommandText);
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
      const triggerButton: HTMLElement = this.findInModal(this.selectorExecuteTrigger);
      if (triggerButton !== null) {
        triggerButton.classList.remove('disabled');
        triggerButton.removeAttribute('disabled');
      }
    });
  }
}

export default new ImageProcessing();
