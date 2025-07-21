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

import { html, nothing, type TemplateResult } from 'lit';
import { lll } from '@typo3/core/lit-helper';
import { until } from 'lit/directives/until';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ResourceInterface } from '@typo3/backend/resource/resource';
import { FileListActionEvent, type FileListActionDetail } from '@typo3/filelist/file-list-actions';
import { default as Modal, ModalElement } from '@typo3/backend/modal';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import Notification from '@typo3/backend/notification';
import Viewport from '@typo3/backend/viewport';
import { FormatUtility } from '@typo3/backend/utility/format-utility';
import { ThumbnailSize } from '@typo3/backend/element/thumbnail-element';
import { DateTime } from 'luxon';

interface Message {
  title: string;
  message: string;
}

class FileListReplaceHandler {
  constructor() {
    new RegularEvent(FileListActionEvent.replace, (event: CustomEvent): void => {
      const detail: FileListActionDetail = event.detail;
      const resource = detail.resources[0];
      const modal = Modal.advanced({
        title: lll('file_replace.title', resource.name),
        type: Modal.types.default,
        size: Modal.sizes.small,
        content: html`${until(this.loadEditor(resource.identifier), html`<typo3-backend-spinner></typo3-backend-spinner>`)}`,
        buttons: [
          {
            text: TYPO3.lang['file_replace.button.cancel'] || 'Cancel',
            btnClass: 'btn-default',
            name: 'cancel',
            trigger: (): void => {
              modal.hideModal();
            }
          },
          {
            text: TYPO3.lang['file_replace.button.replace'] || 'Replace',
            btnClass: 'btn-primary',
            name: 'rename',
            trigger: (): void => {
              const form: HTMLFormElement = modal.querySelector('form');
              form?.requestSubmit();
            },
          },
        ],
        callback: function (modal: ModalElement) {
          new RegularEvent('submit', (event: SubmitEvent): void => {
            event.preventDefault();

            const formData = new FormData(event.target as HTMLFormElement);
            formData.set('uid', resource.uid.toString());

            const request = new AjaxRequest(TYPO3.settings.ajaxUrls.resource_replace);
            request.post(formData).then(async (success: AjaxResponse): Promise<void> => {
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
              Viewport.ContentContainer.refresh();

              modal.hideModal();
            });
          }).delegateTo(modal, 'form');
        }
      });
    }).bindTo(document);
  }

  private async loadEditor(identifier: string): Promise<TemplateResult> {
    const request = await new AjaxRequest(TYPO3.settings.ajaxUrls.resource_gather)
      .withQueryArguments({ identifier })
      .get();
    const response: ResourceInterface = await request.resolve();

    return this.composeEditForm(response);
  }

  private composeEditForm(resource: ResourceInterface): TemplateResult {
    const format: string = (typeof opener?.top?.TYPO3 !== 'undefined' ? opener.top : top).TYPO3.settings.DateTimePicker.DateFormat;
    const thumbnailUrl = new URL(top.TYPO3.settings.Resource.thumbnailUrl, window.origin);
    thumbnailUrl.searchParams.set('identifier', resource.uid.toString(10));

    return html`
      <div class="file-replace-dialog">
        ${lll('file_replace.intro', resource.name)}
        <div
          class="file-replace-dialog-summary"
        >
          ${resource.hasPreview ? html`<div class="file-replace-dialog-summary-thumbnail">
            <typo3-backend-thumbnail url=${thumbnailUrl} size=${ThumbnailSize.large} width="96" keepAspectRatio></typo3-backend-thumbnail>
          </div>` : nothing}
          <div class="file-replace-dialog-summary-info">
            <dl>
              <dt>${lll('file_info_filename')}</dt>
              <dd>${resource.name}</dd>
              <dt>${lll('file_info_filesize')}</dt>
              <dd>${FormatUtility.fileSizeAsString(resource.size)}</dd>
              <dt>${lll('file_info_creation_date')}</dt>
              <dd>${DateTime.fromSeconds(resource.createdAt).toFormat(format[1])}</dd>
            </dl>
          </div>
        </div>
        <form>
          <div class="form-group">
            <label class="form-label" for="file_replace">${lll('file_replace.new_file.label')}</label>
            <input id="file_replace" type="file" class="form-control" name="replace_1">
          </div>
          <div class="form-check">
            <input type="checkbox" value="1" id="keepFilename" name="keepFilename" class="form-check-input" checked>
            <label class="form-check-label" for="keepFilename">${lll('file_replace.keepFilename.label', resource.name)}</label>
          </div>
        </form>
      </div>
    `;
  }
}

export default new FileListReplaceHandler();
