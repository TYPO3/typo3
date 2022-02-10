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

import $ from 'jquery';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import {AbstractInteractableModule} from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import FlashMessage from '../../renderable/flash-message';
import InfoBox from '../../renderable/info-box';
import ProgressBar from '../../renderable/progress-bar';
import Severity from '../../renderable/severity';
import Router from '../../router';

/**
 * Module: @typo3/install/module/tca-migrations-check
 */
class TcaMigrationsCheck extends AbstractInteractableModule {
  private selectorCheckTrigger: string = '.t3js-tcaMigrationsCheck-check';
  private selectorOutputContainer: string = '.t3js-tcaMigrationsCheck-output';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.check();
    currentModal.on('click', this.selectorCheckTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.check();
    });
  }

  private check(): void {
    this.setModalButtonsState(false);

    const $outputContainer: JQuery = $(this.selectorOutputContainer);
    const modalContent: JQuery = this.getModalBody();
    const message: any = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().html(message);
    (new AjaxRequest(Router.getUrl('tcaMigrationsCheck')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          modalContent.empty().append(data.html);
          Modal.setButtons(data.buttons);
          if (data.success === true && Array.isArray(data.status)) {
            if (data.status.length > 0) {
              const m: any = InfoBox.render(
                Severity.warning,
                'TCA migrations need to be applied',
                'Check the following list and apply needed changes.',
              );
              modalContent.find(this.selectorOutputContainer).empty();
              modalContent.find(this.selectorOutputContainer).append(m);
              data.status.forEach((element: any): void => {
                const m2 = InfoBox.render(element.severity, element.title, element.message);
                modalContent.find(this.selectorOutputContainer).append(m2);
              });
            } else {
              const m3 = InfoBox.render(Severity.ok, 'No TCA migrations need to be applied', 'Your TCA looks good.');
              modalContent.find(this.selectorOutputContainer).append(m3);
            }
          } else {
            const m4 = FlashMessage.render(Severity.error, 'Something went wrong', 'Use "Check for broken extensions"');
            modalContent.find(this.selectorOutputContainer).append(m4);
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

}

export default new TcaMigrationsCheck();
