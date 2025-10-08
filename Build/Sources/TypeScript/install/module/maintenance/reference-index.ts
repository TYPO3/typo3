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

import { AbstractInteractableModule } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import type MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { ModalElement } from '@typo3/backend/modal';
import '@typo3/backend/element/icon-element';

type ReferenceIndexResponse = {
  status: MessageInterface[],
  success: boolean,
  result: {
    errors?: string[],
    resultText?: string
  }
};

enum Identifiers {
  checkButton = '.t3js-referenceIndex-check',
  updateButton = '.t3js-referenceIndex-update',
  resultContainer = '.t3js-referenceIndex-result'
}

/**
 * Module: @typo3/install/module/maintenance/reference-index
 */
class ReferenceIndex extends AbstractInteractableModule {
  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.loadContent();

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.check();
    }).delegateTo(currentModal, Identifiers.checkButton);

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.update();
    }).delegateTo(currentModal, Identifiers.updateButton);
  }

  private loadContent(): void {
    const modalContent: HTMLElement = this.getModalBody();
    (new AjaxRequest(Router.getUrl('referenceIndex')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.success === true && data.html !== undefined) {
            modalContent.innerHTML = data.html;
            if (data.buttons !== undefined) {
              Modal.setButtons(data.buttons);
            }
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private check(): void {
    this.processReferenceIndex(true);
  }

  private update(): void {
    this.processReferenceIndex(false);
  }

  private processReferenceIndex(checkOnly: boolean): void {
    this.setModalButtonsState(false);

    const modalContent: HTMLElement = this.getModalBody();
    const resultContainer = modalContent.querySelector<HTMLElement>(Identifiers.resultContainer);

    const progressBar = this.renderProgressBar(resultContainer, {
      label: checkOnly ? 'Checking reference index...' : 'Updating reference index...'
    });

    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          action: 'referenceIndexUpdate',
          token: this.getModuleContent().dataset.referenceIndexToken,
          checkOnly: checkOnly ? '1' : '0',
        },
      })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ReferenceIndexResponse = await response.resolve();

          progressBar.remove();

          if (data.success === true && data.result && resultContainer) {
            const hasErrors = data.result.errors && data.result.errors.length > 0;
            const state = hasErrors ? 'warning' : 'success';
            const iconIdentifier = hasErrors ? 'actions-exclamation' : 'actions-check';
            const title = data.result.resultText || (hasErrors ? 'Issues Found' : 'Reference index is up to date');

            // Build error list or success message
            let bodyContent = '';
            if (hasErrors) {
              const errorItems = data.result.errors.map((error: string) => `<li>${error}</li>`).join('');
              bodyContent = `<ul class="list-unstyled">${errorItems}</ul>`;
            } else {
              bodyContent = 'Index integrity was perfect!';
            }

            // Create complete callout markup with icon element
            const calloutHtml = `
              <div class="callout callout-${state}">
                <div class="callout-icon">
                  <span class="icon-emphasized">
                    <typo3-backend-icon identifier="${iconIdentifier}" size="small"></typo3-backend-icon>
                  </span>
                </div>
                <div class="callout-content">
                  <div class="callout-title">${title}</div>
                  <div class="callout-body">${bodyContent}</div>
                </div>
              </div>
            `;
            resultContainer.innerHTML = calloutHtml;
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

export default new ReferenceIndex();
