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
import $ from 'jquery';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import {AbstractInteractableModule} from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import MessageInterface from '../../message-interface';
import InfoBox from '../../renderable/info-box';
import ProgressBar from '../../renderable/progress-bar';
import Severity from '../../renderable/severity';
import Router from '../../router';

interface EnvironmentCheckResponse {
  success: boolean,
  status: {
    error: MessageInterface[],
    warning: MessageInterface[],
    ok: MessageInterface[],
    information: MessageInterface[],
    notice: MessageInterface[]
  },
  html: string,
  buttons: { btnClass: string, text: string }[]
}

/**
 * Module: @typo3/install/environment-check
 */
class EnvironmentCheck extends AbstractInteractableModule {
  private selectorGridderBadge: string = '.t3js-environmentCheck-badge';
  private selectorExecuteTrigger: string = '.t3js-environmentCheck-execute';
  private selectorOutputContainer: string = '.t3js-environmentCheck-output';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;

    // Get status on initialize to have the badge and content ready
    this.runTests();

    currentModal.on('click', this.selectorExecuteTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.runTests();
    });
  }

  private runTests(): void {
    this.setModalButtonsState(false);

    const modalContent = this.getModalBody();
    const $errorBadge = $(this.selectorGridderBadge);
    $errorBadge.text('').hide();
    const message = ProgressBar.render(Severity.loading, 'Loading...', '');
    modalContent.find(this.selectorOutputContainer).empty().append(message);

    (new AjaxRequest(Router.getUrl('environmentCheckGetStatus')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data: EnvironmentCheckResponse = await response.resolve();
          modalContent.empty().append(data.html);
          Modal.setButtons(data.buttons);
          let warningCount = 0;
          let errorCount = 0;
          if (data.success === true && typeof (data.status) === 'object') {
            for (let messages of Object.values(data.status)) {
              for (let status of messages) {
                if (status.severity === 1) {
                  warningCount++;
                }
                if (status.severity === 2) {
                  errorCount++;
                }
                const aMessage = InfoBox.render(status.severity, status.title, status.message);
                modalContent.find(this.selectorOutputContainer).append(aMessage);
              }
            }
            if (errorCount > 0) {
              $errorBadge.removeClass('badge-warning').addClass('badge-danger').text(errorCount).show();
            } else if (warningCount > 0) {
              $errorBadge.removeClass('badge-error').addClass('badge-warning').text(warningCount).show();
            }
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

export default new EnvironmentCheck();
