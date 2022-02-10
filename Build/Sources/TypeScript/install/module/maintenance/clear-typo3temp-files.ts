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
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import {AbstractInteractableModule} from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';

/**
 * Module: @typo3/install/module/clear-typo3temp-files
 */
class ClearTypo3tempFiles extends AbstractInteractableModule {
  private selectorDeleteTrigger: string = '.t3js-clearTypo3temp-delete';
  private selectorOutputContainer: string = '.t3js-clearTypo3temp-output';
  private selectorStatContainer: string = '.t3js-clearTypo3temp-stat-container';
  private selectorStatsTrigger: string = '.t3js-clearTypo3temp-stats';
  private selectorStatTemplate: string = '.t3js-clearTypo3temp-stat-template';
  private selectorStatNumberOfFiles: string = '.t3js-clearTypo3temp-stat-numberOfFiles';
  private selectorStatDirectory: string = '.t3js-clearTypo3temp-stat-directory';

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
    this.setModalButtonsState(false);

    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('clearTypo3tempFilesStats')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.empty().append(data.html);
            Modal.setButtons(data.buttons);
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
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private delete(folder: string, storageUid: number): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().data('clear-typo3temp-delete-token');
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          action: 'clearTypo3tempFiles',
          token: executeToken,
          folder: folder,
          storageUid: storageUid,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: any): void => {
              Notification.success(element.title, element.message);
            });
            this.getStats();
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }
}

export default new ClearTypo3tempFiles();
