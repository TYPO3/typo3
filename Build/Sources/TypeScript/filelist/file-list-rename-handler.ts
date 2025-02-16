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

import RegularEvent from '@typo3/core/event/regular-event';
import { html, type TemplateResult } from 'lit';
import type { ResourceInterface } from '@typo3/backend/resource/resource';
import { FileListActionEvent, type FileListActionDetail } from '@typo3/filelist/file-list-actions';
import { default as Modal, type ModalElement } from '@typo3/backend/modal';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import Notification from '@typo3/backend/notification';
import Viewport from '@typo3/backend/viewport';

interface Message {
  title: string;
  message: string;
}

class FileListRenameHandler {
  constructor() {

    new RegularEvent(FileListActionEvent.rename, (event: CustomEvent): void => {
      const detail: FileListActionDetail = event.detail;
      const resource = detail.resources[0];
      const modal = Modal.advanced({
        title: TYPO3.lang['file_rename.title'] || 'Rename',
        type: Modal.types.default,
        size: Modal.sizes.small,
        content: this.composeEditForm(resource),
        buttons: [
          {
            text: TYPO3.lang['file_rename.button.cancel'] || 'Cancel',
            btnClass: 'btn-default',
            name: 'cancel',
            trigger: (): void => {
              modal.hideModal();
            }
          },
          {
            text: TYPO3.lang['file_rename.button.rename'] || 'Rename',
            btnClass: 'btn-primary',
            name: 'rename',
            trigger: (): void => {
              const form: HTMLFormElement = modal.querySelector('form');
              form?.requestSubmit();
            },
          },
        ],
        callback: function (modal: ModalElement) {
          const form = modal.querySelector('form');
          form.addEventListener('submit', (event: SubmitEvent): void => {
            event.preventDefault();
            const formData = new FormData(event.target as HTMLFormElement);
            const submittedData = Object.fromEntries(formData);
            const resourceName = submittedData.name.toString();
            if (detail.resources[0].name !== resourceName) {

              const request = new AjaxRequest(TYPO3.settings.ajaxUrls.resource_rename);
              request.post({
                identifier: detail.resources[0].identifier,
                resourceName: resourceName,
              }).then(async (success: AjaxResponse): Promise<void> => {

                const data = await success.resolve();
                if (data.status.length > 0) {
                  data.status.forEach((message: Message): void => {
                    if (data.success) {
                      Notification.success(message.title, message.message);
                    } else {
                      Notification.error(message.title, message.message);
                    }
                  });
                }

                if (data.resource?.type === 'folder') {
                  const currentUrl = Viewport.ContentContainer.getUrl();
                  const params = (new URL(currentUrl, window.location.origin)).searchParams;
                  if (params.get('id') === data.origin.identifier) {
                    Viewport.ContentContainer.setUrl(currentUrl + '&id=' + data.resource.identifier);
                  } else {
                    Viewport.ContentContainer.refresh();
                  }
                } else {
                  Viewport.ContentContainer.refresh();
                }

                top.document.dispatchEvent(new CustomEvent('typo3:filestoragetree:refresh'));
                modal.hideModal();

              });
            }
          });

          modal.addEventListener('typo3-modal-shown', (): void => {
            form.querySelector('input')?.focus();
          });
        }
      });

    }).bindTo(document);
  }

  private composeEditForm(resource: ResourceInterface): TemplateResult {
    return html`
      <form>
        <label class="form-label" for="rename_target">
          ${TYPO3.lang['file_rename.label'] ?? 'New filename'}
        </label>
        <input id="rename_target" name="name" class="form-control" value="${resource.name}" required>
      </form>
    `;
  }
}

export default new FileListRenameHandler();
