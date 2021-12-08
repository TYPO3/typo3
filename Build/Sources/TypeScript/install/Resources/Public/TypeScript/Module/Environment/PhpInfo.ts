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

import Notification from 'TYPO3/CMS/Backend/Notification';
import AjaxRequest from 'TYPO3/CMS/Core/Ajax/AjaxRequest';
import Router from '../../Router';
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {AbstractInteractableModule} from '../AbstractInteractableModule';

/**
 * Module: TYPO3/CMS/Install/Module/PhpInfo
 */
class PhpInfo extends AbstractInteractableModule {
  public initialize(currentModal: any): void {
    this.currentModal = currentModal;
    this.getData();
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('phpInfoGetData')))
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
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }
}

export default new PhpInfo();
