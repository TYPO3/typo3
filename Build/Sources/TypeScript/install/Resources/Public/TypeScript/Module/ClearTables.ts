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

import {AbstractInteractableModule} from './AbstractInteractableModule';
import * as $ from 'jquery';
import Router = require('../Router');
import Modal = require('TYPO3/CMS/Backend/Modal');
import Notification = require('TYPO3/CMS/Backend/Notification');

/**
 * Module: TYPO3/CMS/Install/Module/ClearTables
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

  public initialize(currentModal: any): void {
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
    const modalContent: JQuery = this.getModalBody();
    $.ajax({
      url: Router.getUrl('clearTablesStats'),
      cache: false,
      success: (data: any): void => {
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
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }

  private clear(table: string): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().data('clear-tables-clear-token');
    $.ajax({
      url: Router.getUrl(),
      method: 'POST',
      context: this,
      data: {
        'install': {
          'action': 'clearTablesClear',
          'token': executeToken,
          'table': table,
        },
      },
      cache: false,
      success: (data: any): void => {
        if (data.success === true && Array.isArray(data.status)) {
          data.status.forEach((element: any): void => {
            Notification.success(element.message);
          });
        } else {
          Notification.error('Something went wrong');
        }
        this.getStats();
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }
}

export = new ClearTables();
