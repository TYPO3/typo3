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
import Modal, { ModalElement } from '@typo3/backend/modal';
import { ActionEventDetails } from '@typo3/backend/multi-record-selection-action';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import { MultiRecordSelectionSelectors } from '@typo3/backend/multi-record-selection';
import AjaxDataHandler from '@typo3/backend/ajax-data-handler';
import Severity from '@typo3/backend/severity';
import Notification from '@typo3/backend/notification';
import ResponseInterface from '@typo3/backend/ajax-data-handler/response-interface';

/**
 * Module: @typo3/reactions/management-overview
 * @exports @typo3/reactions/management-overview
 */
class ManagementOverview {
  public constructor() {
    this.registerEvents();
  }

  private registerEvents(): void {
    new RegularEvent('multiRecordSelection:action:edit', this.editMultiple.bind(this)).bindTo(document);
    new RegularEvent('multiRecordSelection:action:delete', this.deleteMultiple.bind(this)).bindTo(document);
  }

  private editMultiple (event: CustomEvent): void {
    event.preventDefault();
    const eventDetails: ActionEventDetails = event.detail as ActionEventDetails;
    const returnUrl: string = eventDetails.configuration.returnUrl || '';
    const entityIdentifiers: Array<string> = this.getEntityIdentifiers(eventDetails);

    if (!entityIdentifiers.length) {
      // Return in case no records to edit were found
      return;
    }

    window.location.href = top.TYPO3.settings.FormEngine.moduleUrl
      + '&edit[sys_reaction][' + entityIdentifiers.join(',') + ']=edit'
      + '&returnUrl=' + encodeURIComponent(returnUrl);
  }

  private deleteMultiple (event: CustomEvent): void {
    event.preventDefault();
    const eventDetails: ActionEventDetails = event.detail as ActionEventDetails;
    const returnUrl: string = eventDetails.configuration.returnUrl || '';
    const entityIdentifiers: Array<string> = this.getEntityIdentifiers(eventDetails);

    if (!entityIdentifiers.length) {
      // Return in case no records to delete were found
      return;
    }

    Modal.advanced({
      title: eventDetails.configuration.title || 'Delete',
      content: eventDetails.configuration.content || 'Are you sure you want to delete those records?',
      severity: SeverityEnum.warning,
      buttons: [
        {
          text: eventDetails.configuration.cancel || TYPO3.lang['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (e: Event, modal: ModalElement) => modal.hideModal(),
        },
        {
          text: eventDetails.configuration.ok || TYPO3.lang['button.delete'] || 'OK',
          btnClass: 'btn-' + Severity.getCssClass(SeverityEnum.warning),
          name: 'delete',
          trigger: (e: Event, modal: ModalElement) => {
            modal.hideModal();
            AjaxDataHandler.process('cmd[sys_reaction][' + entityIdentifiers.join(',') + '][delete]=1')
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
                Notification.error('Could not delete reactions');
              });
          }
        }
      ]
    });
  }

  private getEntityIdentifiers(eventDetails: ActionEventDetails): Array<string>
  {
    // Evaluate all checked records and if valid, add their uid to the list
    const entityIdentifiers: Array<string> = [];
    eventDetails.checkboxes.forEach((checkbox: HTMLInputElement): void => {
      const checkboxContainer: HTMLElement = checkbox.closest(MultiRecordSelectionSelectors.elementSelector);
      if (checkboxContainer !== null && checkboxContainer.dataset[eventDetails.configuration.idField]) {
        entityIdentifiers.push(checkboxContainer.dataset[eventDetails.configuration.idField]);
      }
    });
    return entityIdentifiers;
  }
}

export default new ManagementOverview();
