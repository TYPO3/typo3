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

import Notification = require('TYPO3/CMS/Backend/Notification');
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import Router = require('../../Router');
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {ResponseError} from 'TYPO3/CMS/Core/Ajax/ResponseError';
import {AbstractInteractableModule} from '../AbstractInteractableModule';

/**
 * Module: TYPO3/CMS/Install/Module/SystemInformation
 */
class SystemInformation extends AbstractInteractableModule {
  public initialize(currentModal: any): void {
    this.currentModal = currentModal;
    this.getData();
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('systemInformationGetData')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.empty().append(data.html);
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }
}

export = new SystemInformation();
