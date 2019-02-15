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

import {InteractableModuleInterface} from './InteractableModuleInterface';
import * as $ from 'jquery';
import Router = require('../Router');
import Notification = require('TYPO3/CMS/Backend/Notification');

/**
 * Module: TYPO3/CMS/Install/Module/ClearTypo3tempFiles
 */
class ClearTypo3tempFiles implements InteractableModuleInterface {
  private selectorModalBody: string = '.t3js-modal-body';
  private selectorModuleContent: string = '.t3js-module-content';
  private selectorDeleteTrigger: string = '.t3js-clearTypo3temp-delete';
  private selectorOutputContainer: string = '.t3js-clearTypo3temp-output';
  private selectorStatContainer: string = '.t3js-clearTypo3temp-stat-container';
  private selectorStatsTrigger: string = '.t3js-clearTypo3temp-stats';
  private selectorStatTemplate: string = '.t3js-clearTypo3temp-stat-template';
  private selectorStatNumberOfFiles: string = '.t3js-clearTypo3temp-stat-numberOfFiles';
  private selectorStatDirectory: string = '.t3js-clearTypo3temp-stat-directory';
  private currentModal: JQuery;

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getStats();

    currentModal.on('click', this.selectorStatsTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      $(this.selectorOutputContainer).empty();
      this.getStats();
    });
    currentModal.on('click', this.selectorDeleteTrigger, (e: JQueryEventObject): void => {
      const folder = $(e.currentTarget).data('folder');
      const storageUid = $(e.currentTarget).data('storage-uid');
      e.preventDefault();
      this.delete(folder, storageUid);
    });
  }

  private getStats(): void {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    $.ajax({
      url: Router.getUrl('clearTypo3tempFilesStats'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          modalContent.empty().append(data.html);
          if (Array.isArray(data.stats) && data.stats.length > 0) {
            data.stats.forEach((element: any): void => {
              if (element.numberOfFiles > 0) {
                const aStat = modalContent.find(this.selectorStatTemplate).clone();
                aStat.find(this.selectorStatNumberOfFiles).text(element.numberOfFiles);
                aStat.find(this.selectorStatDirectory).text(element.directory);
                aStat.find(this.selectorDeleteTrigger).attr('data-folder', element.directory);
                aStat.find(this.selectorDeleteTrigger).attr('data-storage-uid', element.storageUid);
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

  private delete(folder: string, storageUid: number): void {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const executeToken = this.currentModal.find(this.selectorModuleContent).data('clear-typo3temp-delete-token');
    $.ajax({
      method: 'POST',
      url: Router.getUrl(),
      context: this,
      data: {
        'install': {
          'action': 'clearTypo3tempFiles',
          'token': executeToken,
          'folder': folder,
          'storageUid': storageUid,
        },
      },
      cache: false,
      success: (data: any): void => {
        if (data.success === true && Array.isArray(data.status)) {
          data.status.forEach((element: any): void => {
            Notification.success(element.message);
          });
          this.getStats();
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }
}

export = new ClearTypo3tempFiles();
