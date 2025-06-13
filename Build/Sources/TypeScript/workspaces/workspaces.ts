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

import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import NProgress from 'nprogress';
import { default as Modal, type ModalElement } from '@typo3/backend/modal';
import { html } from 'lit';

export default class Workspaces {
  /**
   * Renders the send to stage window
   * @param {Object} response
   * @return {$}
   */
  protected renderSendToStageWindow(response: Array<any>): ModalElement {
    const result = response[0].result;

    const modal = Modal.advanced({
      title: TYPO3.lang.actionSendToStage,
      content: html`<div class="modal-loading"><typo3-backend-spinner size="large"></typo3-backend-spinner></div>`,
      severity: SeverityEnum.info,
      buttons: [
        {
          text: TYPO3.lang.cancel,
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (): void => {
            modal.hideModal();
          },
        },
        {
          text: TYPO3.lang.ok,
          btnClass: 'btn-primary',
          name: 'ok',
        },
      ],
      callback: (currentModal: ModalElement): void => {
        const form = currentModal.ownerDocument.createElement('typo3-workspaces-send-to-stage-form');
        form.data = result;
        // Required to get the frame-scoped module locales to the custom element
        form.TYPO3lang = TYPO3.lang;

        currentModal.querySelector('.t3js-modal-body').replaceChildren(form);
      }
    });

    return modal;
  }

  /**
   * Sends an AJAX request
   *
   * @param {Object} payload
   * @param {String} progressContainer
   * @return {$}
   */
  protected sendRemoteRequest(payload: object, progressContainer: string = '#workspace-content-wrapper'): Promise<AjaxResponse> {
    NProgress.configure({ parent: progressContainer, showSpinner: false });
    NProgress.start();
    return (new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)).post(
      payload,
      {
        headers: {
          'Content-Type': 'application/json; charset=utf-8'
        }
      }
    ).finally(() => NProgress.done());
  }

  /**
   * Generates the payload for a remote call
   *
   * @param {String} method
   * @param {Object} data
   * @return {{action, data, method}}
   */
  protected generateRemotePayload(method: string, data: object = {}): object {
    return this.generateRemotePayloadBody('RemoteServer', method, data);
  }

  /**
   * Generates the payload for MassActions
   *
   * @param {String} method
   * @param {Object} data
   * @return {{action, data, method}}
   */
  protected generateRemoteMassActionsPayload(method: string, data: object = {}): object {
    return this.generateRemotePayloadBody('MassActions', method, data);
  }

  /**
   * Generates the payload for Actions
   *
   * @param {String} method
   * @param {Object} data
   * @return {{action, data, method}}
   */
  protected generateRemoteActionsPayload(method: string, data: object = {}): object {
    return this.generateRemotePayloadBody('Actions', method, data);
  }

  /**
   * Generates the payload body
   *
   * @param {String} action
   * @param {String} method
   * @param {Object} data
   * @return {{action: String, data: Object, method: String}}
   */
  private generateRemotePayloadBody(action: string, method: string, data: object): object {
    if (!(data instanceof Array)) {
      data = [
        data,
      ];
    }
    return {
      action: action,
      data: data,
      method: method,
    };
  }
}
