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

import { AbstractInteractableModule, type ModuleLoadedResponseWithButtons } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import type MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { ModalElement } from '@typo3/backend/modal';

type DatabaseTableListResponse = ModuleLoadedResponseWithButtons & {
  stats: {
    description: string,
    name: string,
    rowCount: number,
  }[]
};

type DatabaseTableClearedResponse = {
  status: MessageInterface[],
  success: boolean
};

enum Identifiers {
  clearTrigger = '.t3js-clearTables-clear',
  statsTrigger = '.t3js-clearTables-stats',
  statContainer = '.t3js-clearTables-stat-container',
  statTemplate = '#t3js-clearTables-stat-template',
  statDescription = '.t3js-clearTables-stat-description',
  statRows = '.t3js-clearTables-stat-rows',
  statName = '.t3js-clearTables-stat-name'
}

/**
 * Module: @typo3/install/module/clear-tables
 */
class ClearTables extends AbstractInteractableModule {
  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.getStats();

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      currentModal.querySelector(Identifiers.statContainer).innerHTML = '';
      this.getStats();
    }).delegateTo(currentModal, Identifiers.statsTrigger);

    new RegularEvent('click', (event: Event, trigger: HTMLElement): void => {
      const table = trigger.closest<HTMLElement>(Identifiers.clearTrigger).dataset.table;
      event.preventDefault();
      this.clear(table);
    }).delegateTo(currentModal, Identifiers.clearTrigger);
  }

  private getStats(): void {
    this.setModalButtonsState(false);

    const modalContent: HTMLElement = this.getModalBody();
    (new AjaxRequest(Router.getUrl('clearTablesStats')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: DatabaseTableListResponse = await response.resolve();
          if (data.success === true) {
            modalContent.innerHTML = data.html;
            Modal.setButtons(data.buttons);
            if (Array.isArray(data.stats) && data.stats.length > 0) {
              data.stats.forEach((element: any): void => {
                if (element.rowCount > 0) {
                  const aStat = (modalContent.querySelector(Identifiers.statTemplate) as HTMLTemplateElement).content.cloneNode(true) as HTMLElement;
                  aStat.querySelector<HTMLElement>(Identifiers.statDescription).innerText = element.description;
                  aStat.querySelector<HTMLElement>(Identifiers.statName).innerText = element.name;
                  aStat.querySelector<HTMLElement>(Identifiers.statRows).innerText = element.rowCount;
                  aStat.querySelector<HTMLElement>(Identifiers.clearTrigger).setAttribute('data-table', element.name);
                  modalContent.querySelector(Identifiers.statContainer).append(aStat);
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
      ).finally((): void => {
        this.setModalButtonsState(true);
      });
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
          const data: DatabaseTableClearedResponse = await response.resolve();
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
