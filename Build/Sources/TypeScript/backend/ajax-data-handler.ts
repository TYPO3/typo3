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

import { BroadcastMessage } from '@typo3/backend/broadcast-message';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import ResponseInterface from './ajax-data-handler/response-interface';
import BroadcastService from '@typo3/backend/broadcast-service';
import Notification from './notification';

interface AfterProcessEventDict {
  component: string;
  action: string;
  table: string;
  uid: number;
}

/**
 * Module: @typo3/backend/ajax-data-handler
 * Javascript functions to work with AJAX and interacting with Datahandler
 * through \TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->processAjaxRequest (record_process route)
 */
class AjaxDataHandler {
  /**
   * AJAX call to record_process route (SimpleDataHandlerController->processAjaxRequest)
   * returns a jQuery Promise to work with
   *
   * @param {string | object} params
   * @returns {Promise<ResponseInterface>}
   */
  private static call(params: string | object): Promise<ResponseInterface> {
    return (new AjaxRequest(TYPO3.settings.ajaxUrls.record_process)).withQueryArguments(params).get().then(async (response: AjaxResponse): Promise<ResponseInterface> => {
      return await response.resolve();
    });
  }

  /**
   * Generic function to call from the outside the script and validate directly showing errors
   *
   * @param {string | object} parameters
   * @param {AfterProcessEventDict} eventDict Dictionary used as event detail. This is private API yet.
   * @returns {Promise<ResponseInterface>}
   */
  public async process(parameters: string | object, eventDict?: AfterProcessEventDict): Promise<ResponseInterface> {
    const promise = AjaxDataHandler.call(parameters);
    return promise.then((result: ResponseInterface): ResponseInterface => {
      if (result.hasErrors) {
        this.handleErrors(result);
      }

      if (eventDict) {
        const payload = { ...eventDict, hasErrors: result.hasErrors };
        const message = new BroadcastMessage(
          'datahandler',
          'process',
          payload
        );
        BroadcastService.post(message);

        const event = new CustomEvent('typo3:datahandler:process',{
          detail: {
            payload: payload
          }
        });
        document.dispatchEvent(event);
      }

      return result;
    });
  }

  /**
   * Handle the errors from result object
   *
   * @param {Object} result
   */
  private handleErrors(result: ResponseInterface): void {
    for (const message of result.messages) {
      Notification.error(message.title, message.message);
    }
  }
}

export default new AjaxDataHandler();
