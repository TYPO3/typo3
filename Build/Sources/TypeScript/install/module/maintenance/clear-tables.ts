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

import $ from 'jquery';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { AbstractInteractableModule, ModuleLoadedResponseWithButtons } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';

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

/**
 * Module: @typo3/install/module/clear-tables
 */
class ClearTables extends AbstractInteractableModule {
  private readonly selectorClearTrigger: string = '.t3js-clearTables-clear';
  private readonly selectorStatsTrigger: string = '.t3js-clearTables-stats';
  private readonly selectorOutputContainer: string = '.t3js-clearTables-output';
  private readonly selectorStatContainer: string = '.t3js-clearTables-stat-container';
  private readonly selectorStatTemplate: string = '.t3js-clearTables-stat-template';
  private readonly selectorStatDescription: string = '.t3js-clearTables-stat-description';
  private readonly selectorStatRows: string = '.t3js-clearTables-stat-rows';
  private readonly selectorStatName: string = '.t3js-clearTables-stat-name';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getStats();

    currentModal.on('click', this.selectorStatsTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      $(this.selectorOutputContainer).empty();
      this.getStats();
    });

    currentModal.on('click', this.selectorClearTrigger, (e: JQueryEventObject): void => {
      const table = $(e.target).closest(this.selectorClearTrigger).data('table');
      e.preventDefault();
      this.clear(table);
    });
  }

  private getStats(): void {
    this.setModalButtonsState(false);

    const modalContent: JQuery = this.getModalBody();
    (new AjaxRequest(Router.getUrl('clearTablesStats')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: DatabaseTableListResponse = await response.resolve();
          if (data.success === true) {
            modalContent.empty().append(data.html);
            Modal.setButtons(data.buttons);
            if (Array.isArray(data.stats) && data.stats.length > 0) {
              data.stats.forEach((element: any): void => {
                if (element.rowCount > 0) {
                  const aStat = modalContent.find(this.selectorStatTemplate).clone();
                  aStat.find(this.selectorStatDescription).text(element.description);
                  aStat.find(this.selectorStatName).text(element.name);
                  aStat.find(this.selectorStatRows).text(element.rowCount);
                  aStat.find(this.selectorClearTrigger).attr('data-table', element.name);
                  modalContent.find(this.selectorStatContainer).append(aStat.html());
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
    const executeToken = this.getModuleContent().data('clear-tables-clear-token');
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
