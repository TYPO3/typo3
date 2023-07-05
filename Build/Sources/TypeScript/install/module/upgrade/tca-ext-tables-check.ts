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
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import InfoBox from '../../renderable/info-box';
import ProgressBar from '../../renderable/progress-bar';
import Severity from '../../renderable/severity';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';

/**
 * Module: @typo3/install/module/tca-ext-tables-check
 */
class TcaExtTablesCheck extends AbstractInteractableModule {
  private selectorCheckTrigger: string = '.t3js-tcaExtTablesCheck-check';
  private selectorOutputContainer: string = '.t3js-tcaExtTablesCheck-output';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.check();

    new RegularEvent('click', (event: Event) => {
      event.preventDefault();
      this.check();
    }).delegateTo(currentModal.get(0), this.selectorCheckTrigger);
  }

  private check(): void {
    this.setModalButtonsState(false);

    const outputContainer: HTMLElement = document.querySelector(this.selectorOutputContainer);
    if (outputContainer !== null) {
      outputContainer.append(ProgressBar.render(Severity.loading, 'Loading...', '').get(0));
    }
    const modalContent: HTMLElement = this.getModalBody().get(0);
    (new AjaxRequest(Router.getUrl('tcaExtTablesCheck')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          modalContent.innerHTML = data.html;
          Modal.setButtons(data.buttons);
          if (data.success === true && Array.isArray(data.status)) {
            if (data.status.length > 0) {
              modalContent.querySelector(this.selectorOutputContainer).append(
                InfoBox.render(
                  Severity.warning,
                  'Following extensions change TCA in ext_tables.php',
                  'Check ext_tables.php files, look for ExtensionManagementUtility calls and $GLOBALS[\'TCA\'] modifications',
                ).get(0)
              );
              data.status.forEach((element: MessageInterface): void => {
                const infobox: HTMLElement = InfoBox.render(element.severity, element.title, element.message).get(0);
                modalContent.append(infobox);
                modalContent.querySelector(this.selectorOutputContainer).append(infobox);
              });
            } else {
              modalContent.querySelector(this.selectorOutputContainer).append(
                InfoBox.render(Severity.ok, 'No TCA changes in ext_tables.php files. Good job!', '').get(0)
              );
            }
          } else {
            Notification.error('Something went wrong', 'Please use the module "Check for broken extensions" to find a possible extension causing this issue.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }
}

export default new TcaExtTablesCheck();
