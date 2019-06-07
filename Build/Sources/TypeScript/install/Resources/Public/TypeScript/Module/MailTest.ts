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

import {AbstractInteractableModule} from './AbstractInteractableModule';
import * as $ from 'jquery';
import 'bootstrap';
import Router = require('../Router');
import ProgressBar = require('../Renderable/ProgressBar');
import Severity = require('../Renderable/Severity');
import InfoBox = require('../Renderable/InfoBox');
import Modal = require('TYPO3/CMS/Backend/Modal');
import Notification = require('TYPO3/CMS/Backend/Notification');

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
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    $.ajax({
      url: Router.getUrl('mailTestGetData'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          modalContent.empty().append(data.html);
          Modal.setButtons(data.buttons);
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }

  private send(): void {
    const executeToken: string = this.getModuleContent().data('mail-test-token');
    const $outputContainer: JQuery = this.findInModal(this.selectorOutputContainer);
    const message: any = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().html(message);
    $.ajax({
      url: Router.getUrl(),
      method: 'POST',
      data: {
        'install': {
          'action': 'mailTest',
          'token': executeToken,
          'email': this.findInModal('.t3js-mailTest-email').val(),
        },
      },
      cache: false,
      success: (data: any): void => {
        $outputContainer.empty();
        if (data.success === true && Array.isArray(data.status)) {
          data.status.forEach((element: any): void => {
            const aMessage: any = InfoBox.render(element.severity, element.title, element.message);
            $outputContainer.html(aMessage);
          });
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (): void => {
        // 500 can happen here if the mail configuration is broken
        Notification.error('Something went wrong');
      },
    });
  }
}

export = new MailTest();
