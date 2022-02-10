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
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';

/**
 * Module: @typo3/install/module/features
 */
class Features extends AbstractInteractableModule {
  private selectorSaveTrigger: string = '.t3js-features-save';

  public initialize(currentModal: any): void {
    this.currentModal = currentModal;
    this.getContent();

    currentModal.on('click', this.selectorSaveTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.save();
    });
  }

  private getContent(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('featuresGetContent')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
            modalContent.empty().append(data.html);
            Modal.setButtons(data.buttons);
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private save(): void {
    this.setModalButtonsState(false);

    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().data('features-save-token');
    const postData: any = {};
    $(this.findInModal('form').serializeArray()).each((index: number, element: any): void => {
      postData[element.name] = element.value;
    });
    postData['install[action]'] = 'featuresSave';
    postData['install[token]'] = executeToken;
    (new AjaxRequest(Router.getUrl()))
      .post(postData)
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: any): void => {
              Notification.showMessage(element.title, element.message, element.severity);
            });
            this.getContent();
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

export default new Features();
