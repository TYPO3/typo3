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

import DocumentService from '@typo3/core/document-service';
import { MessageUtility } from '@typo3/backend/utility/message-utility';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import NProgress from 'nprogress';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Modal, { type ModalElement, Types } from './modal';
import Notification from './notification';
import Severity from './severity';
import RegularEvent from '@typo3/core/event/regular-event';
import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';

interface Response {
  file?: number;
  error?: string;
}

/**
 * Module: @typo3/backend/online-media
 * Javascript for show the online media dialog
 */
class OnlineMedia {
  constructor() {
    DocumentService.ready().then(async (): Promise<void> => {
      // Since the web component is used in a modal and therefore in outer frames, we have to import the module in the
      // top level scope. Not doing so causes issues in at least Firefox.
      await topLevelModuleImport('@typo3/backend/form-engine/element/online-media-form-element.js');
      this.registerEvents();
    });
  }

  private registerEvents(): void {
    new RegularEvent('click', (e: Event, target: HTMLButtonElement): void => {
      this.triggerModal(target);
    }).delegateTo(document, '.t3js-online-media-add-btn');
  }

  private addOnlineMedia(trigger: HTMLButtonElement, modalElement: ModalElement, url: string): void {
    const target = trigger.dataset.targetFolder;
    const allowed = trigger.dataset.onlineMediaAllowed;
    const irreObjectUid = trigger.dataset.fileIrreObject;

    NProgress.start();
    new AjaxRequest(TYPO3.settings.ajaxUrls.online_media_create).post({
      url: url,
      targetFolder: target,
      allowed: allowed,
    }).then(async (response: AjaxResponse): Promise<void> => {
      const data: Response = await response.resolve();
      if (data.file) {
        const message = {
          actionName: 'typo3:foreignRelation:insert',
          objectGroup: irreObjectUid,
          table: 'sys_file',
          uid: data.file,
        };
        MessageUtility.send(message);
        modalElement.hideModal();
      } else {
        Notification.error(top.TYPO3.lang['online_media.error.new_media.failed'], data.error);
      }
      NProgress.done();
    });
  }

  private triggerModal(trigger: HTMLButtonElement): void {
    const btnSubmit = trigger.dataset.btnSubmit || 'Add';
    const placeholder = trigger.dataset.placeholder || 'Paste media url here...';
    const allowedHelpText = trigger.dataset.onlineMediaAllowedHelpText || 'Allow to embed from sources:';

    const onlineMediaForm = document.createElement('typo3-backend-formengine-online-media-form');
    onlineMediaForm.placeholder = placeholder;
    onlineMediaForm.setAttribute('help-text', allowedHelpText);
    onlineMediaForm.setAttribute('extensions', trigger.dataset.onlineMediaAllowed);

    Modal.advanced({
      type: Types.default,
      title: trigger.title,
      content: onlineMediaForm,
      severity: Severity.notice,
      callback: (modalElement: ModalElement): void => {
        modalElement.querySelector('typo3-backend-formengine-online-media-form').addEventListener('typo3:formengine:online-media-added', (e: CustomEvent): void => {
          this.addOnlineMedia(trigger, modalElement, e.detail['online-media-url']);
        });
      },
      buttons: [{
        text: btnSubmit,
        btnClass: 'btn btn-primary',
        name: 'ok',
        trigger: (): void => {
          onlineMediaForm.querySelector('form').requestSubmit();
        },
      }],
    });
  }
}

export default new OnlineMedia();
