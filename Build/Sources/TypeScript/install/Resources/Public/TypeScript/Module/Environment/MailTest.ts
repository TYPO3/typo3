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
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {ResponseError} from 'TYPO3/CMS/Core/Ajax/ResponseError';
import {AbstractInteractableModule} from '../AbstractInteractableModule';
import Modal = require('TYPO3/CMS/Backend/Modal');
import Notification = require('TYPO3/CMS/Backend/Notification');
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import InfoBox = require('../../Renderable/InfoBox');
import ProgressBar = require('../../Renderable/ProgressBar');
import Severity = require('../../Renderable/Severity');
import Router = require('../../Router');

/**
 * Module: TYPO3/CMS/Install/Module/CreateAdmin
 */
class MailTest extends AbstractInteractableModule {
  private selectorOutputContainer: string = '.t3js-mailTest-output';
  private selectorMailTestButton: string = '.t3js-mailTest-execute';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getData();
    currentModal.on('click', this.selectorMailTestButton, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.send();
    });
    currentModal.on('submit', 'form', (e: JQueryEventObject): void => {
      e.preventDefault();
      this.send();
    });
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('mailTestGetData')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.empty().append(data.html);
            Modal.setButtons(data.buttons);
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, modalContent);
        },
      );
  }

  private send(): void {
    this.setModalButtonsState(false);

    const executeToken: string = this.getModuleContent().data('mail-test-token');
    const $outputContainer: JQuery = this.findInModal(this.selectorOutputContainer);
    const message: any = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().html(message);
    (new AjaxRequest(Router.getUrl())).post({
      install: {
        action: 'mailTest',
        token: executeToken,
        email: this.findInModal('.t3js-mailTest-email').val(),
      },
    }).then(
      async (response: AjaxResponse): Promise<any> => {
        const data = await response.resolve();
        $outputContainer.empty();
        if (Array.isArray(data.status)) {
          data.status.forEach((element: any): void => {
            const aMessage: any = InfoBox.render(element.severity, element.title, element.message);
            $outputContainer.html(aMessage);
          });
        } else {
          Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
        }
      },
      (): void => {
        // 500 can happen here if the mail configuration is broken
        Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
      },
    ).finally((): void => {
      this.setModalButtonsState(true);
    });
  }
}

export = new MailTest();
