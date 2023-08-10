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

import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { AbstractInteractableModule } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { FlashMessage } from '../../renderable/flash-message';
import { InfoBox } from '../../renderable/info-box';
import Severity from '../../renderable/severity';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';

/**
 * Module: @typo3/install/module/tca-migrations-check
 */
class TcaMigrationsCheck extends AbstractInteractableModule {
  private selectorCheckTrigger: string = '.t3js-tcaMigrationsCheck-check';
  private selectorOutputContainer: string = '.t3js-tcaMigrationsCheck-output';

  public initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.check();

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.check();
    }).delegateTo(currentModal, this.selectorCheckTrigger);
  }

  private check(): void {
    this.setModalButtonsState(false);
    const outputContainer: HTMLElement = document.querySelector(this.selectorOutputContainer);
    if (outputContainer !== null) {
      this.renderProgressBar(outputContainer, {}, 'append');
    }
    const modalContent: HTMLElement = this.getModalBody();
    (new AjaxRequest(Router.getUrl('tcaMigrationsCheck')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          modalContent.innerHTML = data.html;
          Modal.setButtons(data.buttons);
          if (data.success === true && Array.isArray(data.status)) {
            if (data.status.length > 0) {
              modalContent.querySelector(this.selectorOutputContainer).append(InfoBox.create(
                Severity.warning,
                'TCA migrations need to be applied',
                'Check the following list and apply needed changes.'
              ));
              data.status.forEach((element: MessageInterface): void => {
                modalContent.querySelector(this.selectorOutputContainer).append(InfoBox.create(element.severity, element.title, element.message));
              });
            } else {
              modalContent.querySelector(this.selectorOutputContainer).append(InfoBox.create(
                Severity.ok,
                'No TCA migrations need to be applied',
                'Your TCA looks good.'
              ));
            }
          } else {
            modalContent.querySelector(this.selectorOutputContainer).append(
              FlashMessage.create(Severity.error, 'Something went wrong', 'Use "Check for broken extensions"')
            );
          }
          this.setModalButtonsState(true);
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

}

export default new TcaMigrationsCheck();
