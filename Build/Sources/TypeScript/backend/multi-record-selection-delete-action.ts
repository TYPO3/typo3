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
import {
  ActionConfiguration,
  ActionEventDetails,
  MultiRecordSelectionAction
} from '@typo3/backend/multi-record-selection-action';
import Modal, { ModalElement } from '@typo3/backend/modal';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import Severity from '@typo3/backend/severity';
import AjaxDataHandler from '@typo3/backend/ajax-data-handler';
import ResponseInterface from '@typo3/backend/ajax-data-handler/response-interface';
import Notification from '@typo3/backend/notification';

interface DeleteActionConfiguration extends ActionConfiguration {
  tableName: string;
  returnUrl: string;
  title: string;
  content: string;
  ok: string;
  cancel: string;
}

/**
 * Module: @typo3/backend/multi-record-selection-delete-action
 * @exports @typo3/backend/multi-record-selection-delete-action
 */
class MultiRecordSelectionDeleteAction {
  public constructor() {
    new RegularEvent('multiRecordSelection:action:delete', this.delete).bindTo(document);
  }

  private delete(event: CustomEvent): void {
    event.preventDefault();

    const eventDetails: ActionEventDetails = event.detail as ActionEventDetails;
    const entityIdentifiers: Array<string> = MultiRecordSelectionAction.getEntityIdentifiers(eventDetails);
    if (!entityIdentifiers.length) {
      // Return in case no records to delete were found
      return;
    }

    const configuration: DeleteActionConfiguration = eventDetails.configuration;
    const tableName: string = configuration.tableName || '';
    if (tableName === '') {
      return;
    }
    const returnUrl: string = configuration.returnUrl || '';

    Modal.advanced({
      title: configuration.title || 'Delete',
      content: configuration.content || 'Are you sure you want to delete those records?',
      severity: SeverityEnum.warning,
      buttons: [
        {
          text: configuration.cancel || TYPO3.lang['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (e: Event, modal: ModalElement) => modal.hideModal(),
        },
        {
          text: configuration.ok || TYPO3.lang['button.delete'] || 'OK',
          btnClass: 'btn-' + Severity.getCssClass(SeverityEnum.warning),
          name: 'delete',
          trigger: (e: Event, modal: ModalElement) => {
            modal.hideModal();
            AjaxDataHandler.process('cmd[' + tableName + '][' + entityIdentifiers.join(',') + '][delete]=1')
              .then((result: ResponseInterface): void => {
                if (result.hasErrors) {
                  throw result.messages;
                } else if (returnUrl !== '') {
                  window.location.href = returnUrl;
                } else {
                  modal.ownerDocument.location.reload();
                }
              })
              .catch((): void => {
                Notification.error('Could not delete records');
              });
          }
        }
      ]
    });
  }
}

export default new MultiRecordSelectionDeleteAction();
