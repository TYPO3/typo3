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
import $ from 'jquery';
import { MessageUtility } from '@typo3/backend/utility/message-utility';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { KeyTypesEnum } from './enum/key-types';
import NProgress from 'nprogress';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import SecurityUtility from '@typo3/core/security-utility';
import Modal, { ModalElement } from './modal';
import Notification from './notification';
import Severity from './severity';

interface Response {
  file?: number;
  error?: string;
}

/**
 * Module: @typo3/backend/online-media
 * Javascript for show the online media dialog
 */
class OnlineMedia {
  private readonly securityUtility: SecurityUtility;
  constructor() {
    this.securityUtility = new SecurityUtility();
    DocumentService.ready().then((): void => {
      this.registerEvents();
    });
  }

  private registerEvents(): void {
    $(document).on('click', '.t3js-online-media-add-btn', (e: JQueryEventObject): void => {
      this.triggerModal($(e.currentTarget));
    });
  }

  private addOnlineMedia($trigger: JQuery, modalElement: ModalElement, url: string): void {
    const target = $trigger.data('target-folder');
    const allowed = $trigger.data('online-media-allowed');
    const irreObjectUid = $trigger.data('file-irre-object');

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

  /**
   * @param {JQuery} $currentTarget
   */
  private triggerModal($currentTarget: JQuery): void {
    const btnSubmit = $currentTarget.data('btn-submit') || 'Add';
    const placeholder = $currentTarget.data('placeholder') || 'Paste media url here...';
    const allowedExtMarkup = $.map($currentTarget.data('online-media-allowed').split(','), (ext: string): string => {
      return '<span class="badge badge-success">' + this.securityUtility.encodeHtml(ext.toUpperCase(), false) + '</span>';
    });
    const allowedHelpText = $currentTarget.data('online-media-allowed-help-text') || 'Allow to embed from sources:';

    const $markup = $('<div>')
      .attr('class', 'form-control-wrap')
      .append([
        $('<input>')
          .attr('type', 'text')
          .attr('class', 'form-control online-media-url')
          .attr('placeholder', placeholder),
        $('<div>')
          .attr('class', 'form-text')
          .html(this.securityUtility.encodeHtml(allowedHelpText, false) + '<br>' + allowedExtMarkup.join(' ')),
      ]);
    const modal = Modal.show(
      $currentTarget.attr('title'),
      $markup,
      Severity.notice,
      [{
        text: btnSubmit,
        btnClass: 'btn btn-primary',
        name: 'ok',
        trigger: (): void => {
          const url = $(modal).find('input.online-media-url').val();
          if (url) {
            this.addOnlineMedia($currentTarget, modal, url);
          }
        },
      }],
    );

    modal.addEventListener('typo3-modal-shown', (e: Event): void => {
      // focus the input field
      $(e.currentTarget).find('input.online-media-url').first().focus().on('keydown', (kdEvt: JQueryEventObject): void => {
        if (kdEvt.keyCode === KeyTypesEnum.ENTER) {
          $(modal).find('button[name="ok"]').trigger('click');
        }
      });
    });
  }
}

export default new OnlineMedia();
