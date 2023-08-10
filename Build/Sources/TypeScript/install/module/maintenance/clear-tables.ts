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
import { AbstractInteractableModule } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';

/**
 * Module: @typo3/install/module/clear-tables
 */
class ClearTables extends AbstractInteractableModule {
  private selectorClearTrigger: string = '.t3js-clearTables-clear';
  private selectorStatsTrigger: string = '.t3js-clearTables-stats';
  private selectorOutputContainer: string = '.t3js-clearTables-output';
  private selectorStatContainer: string = '.t3js-clearTables-stat-container';
  private selectorStatTemplate: string = '.t3js-clearTables-stat-template';
  private selectorStatDescription: string = '.t3js-clearTables-stat-description';
  private selectorStatRows: string = '.t3js-clearTables-stat-rows';
  private selectorStatName: string = '.t3js-clearTables-stat-name';

  public initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.getStats();

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      currentModal.querySelector(this.selectorOutputContainer).innerHTML = '';
      this.getStats();
    }).delegateTo(currentModal, this.selectorStatsTrigger);

    new RegularEvent('click', (event: Event, trigger: HTMLElement): void => {
      const table = trigger.closest<HTMLElement>(this.selectorClearTrigger).dataset.table;
      event.preventDefault();
      this.clear(table);
    }).delegateTo(currentModal, this.selectorClearTrigger);
  }

  private getStats(): void {
    this.setModalButtonsState(false);

    const modalContent: HTMLElement = this.getModalBody();
    (new AjaxRequest(Router.getUrl('clearTablesStats')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.innerHTML = data.html;
            Modal.setButtons(data.buttons);
            if (Array.isArray(data.stats) && data.stats.length > 0) {
              data.stats.forEach((element: any): void => {
                if (element.rowCount > 0) {
                  const aStat = modalContent.querySelector<HTMLElement>(this.selectorStatTemplate).cloneNode(true) as HTMLElement;
                  aStat.querySelector<HTMLElement>(this.selectorStatDescription).innerText = element.description;
                  aStat.querySelector<HTMLElement>(this.selectorStatName).innerText = element.name;
                  aStat.querySelector<HTMLElement>(this.selectorStatRows).innerText = element.rowCount;
                  aStat.querySelector<HTMLElement>(this.selectorClearTrigger).setAttribute('data-table', element.name);
                  modalContent.querySelector(this.selectorStatContainer).append(aStat);
                }
              });
            }
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private clear(table: string): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().dataset.clearTablesClearToken;
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          action: 'clearTablesClear',
          token: executeToken,
          table: table,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: MessageInterface): void => {
              Notification.success(element.title, element.message);
            });
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
          this.getStats();
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }
}

export default new ClearTables();
