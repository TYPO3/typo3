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

import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';
import Notification from '@typo3/backend/notification';
import { default as Modal, type ModalElement } from '@typo3/backend/modal';
import { html } from 'lit';
import labels from '~labels/workspaces.messages';

/**
 * Result of a single call of a dispatched call stack, carrying either
 * `result` if the call succeeded, or `error` if it did not.
 */
interface RemoteCallResult {
  method: string;
  result?: unknown;
  error?: {
    message: string;
    code: number;
  };
}

export default class Workspaces {
  protected readonly ajaxRoute: string = 'workspace_dispatch';
  protected progressBar: ProgressBarElement | null = null;

  /**
   * Renders the send to stage window
   * @param {Object} response
   * @return {$}
   */
  protected renderSendToStageWindow(response: Array<any>): ModalElement {
    const result = response[0].result;

    const modal = Modal.advanced({
      title: labels.get('actionSendToStage'),
      content: html`<div class="modal-loading"><typo3-backend-spinner size="large"></typo3-backend-spinner></div>`,
      severity: SeverityEnum.info,
      buttons: [
        {
          text: labels.get('cancel'),
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (): void => {
            modal.hideModal();
          },
        },
        {
          text: labels.get('ok'),
          btnClass: 'btn-primary',
          name: 'ok',
        },
      ],
      callback: (currentModal: ModalElement): void => {
        const form = currentModal.ownerDocument.createElement('typo3-workspaces-send-to-stage-form');
        form.data = result;

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
    this.progressBar = document.createElement('typo3-backend-progress-bar');
    document.querySelector(progressContainer).prepend(this.progressBar);
    this.progressBar.start();
    const headers = {
      'Content-Type': 'application/json; charset=utf-8'
    };
    return new AjaxRequest(TYPO3.settings.ajaxUrls[this.ajaxRoute])
      .post(payload, { headers })
      .catch(async (reason: unknown): Promise<never> => {
        await this.notifyRequestFailure(reason);
        // Rejection is kept up, so that callers do not continue with a payload
        // that carries errors instead of the results they are written for.
        throw reason;
      })
      .finally(() => {
        if (this.progressBar) {
          this.progressBar.done();
        }
      });
  }

  /**
   * Generates the payload body
   *
   * @param {String} method
   * @param {Object} data
   * @return {{action: String, data: Object, method: String}}
   */
  protected generateRemotePayloadBody(method: string, data: object): object {
    if (!(data instanceof Array)) {
      data = [
        data,
      ];
    }
    return {
      data: data,
      method: method,
    };
  }

  /**
   * Reports a failed request to the user. The server describes failing calls in the
   * JSON body, anything else - an unreachable server, a session timeout redirect or
   * an error page that is no JSON at all - can only be reported in a generic way.
   */
  private async notifyRequestFailure(reason: unknown): Promise<void> {
    const title = labels.get('error.dispatch.title');
    if (!(reason instanceof AjaxResponse)) {
      Notification.error(title, labels.get('error.dispatch.unavailable'));
      return;
    }

    let details: unknown;
    try {
      details = await reason.resolve('json');
    } catch {
      Notification.error(title, labels.get('error.dispatch.unavailable'));
      return;
    }

    const messages: string[] = [];
    if (Array.isArray(details)) {
      for (const item of details as RemoteCallResult[]) {
        if (item?.error?.message) {
          messages.push(item.error.message);
        }
      }
    }
    if (messages.length === 0) {
      Notification.error(title, labels.get('error.dispatch.unknown'));
      return;
    }
    messages.forEach((message: string): void => Notification.error(title, message));
  }
}
