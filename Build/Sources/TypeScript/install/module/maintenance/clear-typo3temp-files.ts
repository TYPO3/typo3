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
import { AbstractInteractableModule, ModuleLoadedResponseWithButtons } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';

type TemporaryAssetsListResponse = ModuleLoadedResponseWithButtons & {
  stats: {
    description: string,
    name: string,
    rowCount: number,
  }[]
}

type TemporaryAssetsClearedResponse = {
  status: MessageInterface[],
  success: boolean
}

enum Identifiers {
  deleteTrigger = '.t3js-clearTypo3temp-delete',
  statContainer = '.t3js-clearTypo3temp-stat-container',
  statsTrigger = '.t3js-clearTypo3temp-stats',
  statTemplate = '#t3js-clearTypo3temp-stat-template',
  statNumberOfFiles = '.t3js-clearTypo3temp-stat-numberOfFiles',
  statDirectory = '.t3js-clearTypo3temp-stat-directory'
}

/**
 * Module: @typo3/install/module/clear-typo3temp-files
 */
class ClearTypo3tempFiles extends AbstractInteractableModule {
  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.getStats();

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      currentModal.querySelector(Identifiers.statContainer).innerHTML = '';
      this.getStats();
    }).delegateTo(currentModal, Identifiers.statsTrigger);

    new RegularEvent('click', (event: Event, trigger: HTMLElement): void => {
      event.preventDefault();
      const folder = trigger.dataset.folder;
      const storageUid = trigger.dataset.storageUid !== undefined ? parseInt(trigger.dataset.storageUid, 10) : undefined;
      this.delete(folder, storageUid);
    }).delegateTo(currentModal, Identifiers.deleteTrigger);
  }

  private getStats(): void {
    this.setModalButtonsState(false);

    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('clearTypo3tempFilesStats')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: TemporaryAssetsListResponse = await response.resolve();
          if (data.success === true) {
            modalContent.innerHTML = data.html;
            Modal.setButtons(data.buttons);
            if (Array.isArray(data.stats) && data.stats.length > 0) {
              data.stats.forEach((element: any): void => {
                if (element.numberOfFiles > 0) {
                  const aStat = (modalContent.querySelector(Identifiers.statTemplate) as HTMLTemplateElement).content.cloneNode(true) as HTMLElement;
                  aStat.querySelector<HTMLElement>(Identifiers.statNumberOfFiles).innerText = (element.numberOfFiles);
                  aStat.querySelector<HTMLElement>(Identifiers.statDirectory).innerText = (element.directory);
                  aStat.querySelector<HTMLElement>(Identifiers.deleteTrigger).setAttribute('data-folder', element.directory);
                  if (element.storageUid !== undefined) {
                    aStat.querySelector<HTMLElement>(Identifiers.deleteTrigger).setAttribute('data-storage-uid', element.storageUid);
                  }
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

  private delete(folder: string, storageUid: number|undefined): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().dataset.clearTypo3tempDeleteToken;
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
        async (response: AjaxResponse): Promise<void> => {
          const data: TemporaryAssetsClearedResponse = await response.resolve();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: MessageInterface): void => {
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
