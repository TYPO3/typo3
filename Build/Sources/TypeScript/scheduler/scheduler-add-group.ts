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

import Modal from '@typo3/backend/modal';
import { html, type TemplateResult } from 'lit';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type ResponseInterface from '@typo3/backend/ajax-data-handler/response-interface';
import Notification from '@typo3/backend/notification';
import { lll } from '@typo3/core/lit-helper';

/**
 * Module: @typo3/scheduler/scheduler-add-groups
 * @exports @typo3/scheduler/scheduler-add-groups
 */
class SchedulerAddGroups {
  selector: string = '.t3js-create-group';

  constructor() {
    this.initialize();
  }

  public initialize(): void {
    const element = document.querySelector(this.selector) as HTMLElement;
    if(element) {
      element.addEventListener('click', (button: MouseEvent) => {
        button.preventDefault();
        const content: TemplateResult = html`
          <form name="scheduler-create-group" @submit=${this.createGroup}>
            <label class="form-label" for="actionCreateGroup">Group name</label>
            <input class="form-control" id="actionCreateGroup" required="" name="action[createGroup]" autofocus type="text">
          </form>
        `;

        const modal = Modal.advanced({
          content: content,
          title: lll('scheduler.createGroup') || 'New group',
          size: Modal.sizes.small,
          buttons: [
            {
              trigger: (): void => Modal.dismiss(),
              text: lll('scheduler.modalCancel') || 'Cancel',
              btnClass: 'btn-default',
              name: 'cancel'
            },{
              trigger: (): void => {
                const form: HTMLFormElement = Modal.currentModal.querySelector('form[name="scheduler-create-group"]')
                form.requestSubmit();
              },
              text: lll('scheduler.modalOk') || 'Create group',
              btnClass: 'btn-primary',
              name: 'ok'
            }
          ]
        });

        modal.addEventListener('typo3-modal-shown', (): void => {
          const input: HTMLInputElement = Modal.currentModal.querySelector('input[name="action[createGroup]"]')
          input.focus();
        });
      })
    }
  }

  private createGroup(e: Event): Promise<ResponseInterface | void> {
    e.preventDefault();

    const formData = new FormData(e.target as HTMLFormElement);
    const name = formData.get('action[createGroup]').toString();
    const newUid = 'NEW' + Math.random().toString(36).slice(2, 7);
    const params = '&data[tx_scheduler_task_group][' + newUid + '][pid]=0' +
      '&data[tx_scheduler_task_group][' + newUid + '][groupName]=' + encodeURIComponent(name);

    return (new AjaxRequest(TYPO3.settings.ajaxUrls.record_process)).post(params, {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(async (response: AjaxResponse): Promise<ResponseInterface> => {
        return await response.resolve();
      }).then((result: ResponseInterface): ResponseInterface => {
        if (result.hasErrors) {
          Notification.error(lll('scheduler.group.error.title'), lll('scheduler.group.error.message') + ' "' + name + '"!');
        }

        result.messages.forEach((message) => {
          Notification.info(message.title, message.message);
        });

        return result;
      }).catch(() => {
        Notification.error(lll('scheduler.group.error.title'), lll('scheduler.group.error.message') + ' "' + name + '"!');
      }).finally(() => {
        const select = (document as Document).querySelector('#task_group');
        if(select) {
          // This is an ugly hack to pre-select the created group.
          const form = ((document as Document).forms[0] as HTMLFormElement);
          const selectLatestGroup: HTMLInputElement = form.querySelector('[name="tx_scheduler[select_latest_group]"]');

          selectLatestGroup.value = '1';
          form.submit();
        } else {
          window.location.reload();
        }

        Modal.dismiss();
      })
  }
}

export default new SchedulerAddGroups();
