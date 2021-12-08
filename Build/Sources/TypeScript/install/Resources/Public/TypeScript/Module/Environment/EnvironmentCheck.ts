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
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {AbstractInteractableModule} from '../AbstractInteractableModule';
import Modal from 'TYPO3/CMS/Backend/Modal';
import Notification from 'TYPO3/CMS/Backend/Notification';
import AjaxRequest from 'TYPO3/CMS/Core/Ajax/AjaxRequest';
import InfoBox from '../../Renderable/InfoBox';
import ProgressBar from '../../Renderable/ProgressBar';
import Severity from '../../Renderable/Severity';
import Router from '../../Router';

/**
 * Module: TYPO3/CMS/Install/EnvironmentCheck
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
          const data = await response.resolve();
          modalContent.empty().append(data.html);
          Modal.setButtons(data.buttons);
          let warningCount = 0;
          let errorCount = 0;
          if (data.success === true && typeof (data.status) === 'object') {
            $.each(data.status, (i: number, element: any): void => {
              if (Array.isArray(element) && element.length > 0) {
                element.forEach((aStatus: any): void => {
                  if (aStatus.severity === 1) {
                    warningCount++;
                  }
                  if (aStatus.severity === 2) {
                    errorCount++;
                  }
                  const aMessage = InfoBox.render(aStatus.severity, aStatus.title, aStatus.message);
                  modalContent.find(this.selectorOutputContainer).append(aMessage);
                });
              }
            });
            if (errorCount > 0) {
              $errorBadge.removeClass('label-warning').addClass('label-danger').text(errorCount).show();
            } else if (warningCount > 0) {
              $errorBadge.removeClass('label-error').addClass('label-warning').text(warningCount).show();
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
