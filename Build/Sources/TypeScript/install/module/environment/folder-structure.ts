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

import 'bootstrap';
import { AbstractInteractableModule, type ModuleLoadedResponseWithButtons } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { InfoBox } from '../../renderable/info-box';
import Severity from '../../renderable/severity';
import Router from '../../router';
import RegularEvent from '@typo3/core/event/regular-event';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { ModalElement } from '@typo3/backend/modal';
import type MessageInterface from '@typo3/install/message-interface';

enum Identifiers {
  outputContainer = '.t3js-folderStructure-output',
  errorContainer = '.t3js-folderStructure-errors',
  errorList = '.t3js-folderStructure-errors-list',
  errorFixTrigger = '.t3js-folderStructure-errors-fix',
  okContainer = '.t3js-folderStructure-ok',
  okList = '.t3js-folderStructure-ok-list',
  permissionContainer = '.t3js-folderStructure-permissions'
}

type FolderStructureResponse = ModuleLoadedResponseWithButtons & {
  errorStatus: MessageInterface[],
  okStatus: MessageInterface[],
  folderStructureFilePermissionStatus: MessageInterface,
  folderStructureDirectoryPermissionStatus: MessageInterface,
};

/**
 * Module: @typo3/install/module/folder-structure
 */
class FolderStructure extends AbstractInteractableModule {
  private static removeLoadingMessage(container: HTMLElement): void {
    container.querySelector('typo3-backend-progress-bar').remove();
  }

  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);

    this.loadModuleFrameAgnostic('@typo3/install/renderable/info-box.js').then((): void => {
      // Get status on initialize to have the badge and content ready
      this.getStatus();
    });

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.fix();
    }).delegateTo(currentModal, Identifiers.errorFixTrigger);
  }

  private getStatus(): void {
    const modalContent = this.getModalBody();

    (new AjaxRequest(Router.getUrl('folderStructureGetStatus')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: FolderStructureResponse = await response.resolve();
          modalContent.innerHTML = data.html;
          Modal.setButtons(data.buttons);
          if (data.success === true && Array.isArray(data.errorStatus)) {
            if (data.errorStatus.length > 0) {
              modalContent.querySelector<HTMLElement>(Identifiers.errorContainer).style.display = 'block';
              modalContent.querySelector(Identifiers.errorList).innerHTML = '';
              data.errorStatus.forEach(((aElement: any): void => {
                modalContent.querySelector(Identifiers.errorList).appendChild(InfoBox.create(aElement.severity, aElement.title, aElement.message));
              }));
            } else {
              modalContent.querySelector<HTMLElement>(Identifiers.errorContainer).style.display = 'none';
            }
          }
          if (data.success === true && Array.isArray(data.okStatus)) {
            if (data.okStatus.length > 0) {
              modalContent.querySelector<HTMLElement>(Identifiers.okContainer).style.display = 'block';
              modalContent.querySelector(Identifiers.okList).innerHTML = '';
              data.okStatus.forEach(((aElement: any): void => {
                modalContent.querySelector(Identifiers.okList).appendChild(InfoBox.create(aElement.severity, aElement.title, aElement.message));
              }));
            } else {
              modalContent.querySelector<HTMLElement>(Identifiers.okContainer).style.display = 'none';
            }
          }
          let element = data.folderStructureFilePermissionStatus;
          const selectorPermissionContainer = modalContent.querySelector(Identifiers.permissionContainer);
          selectorPermissionContainer.replaceChildren(InfoBox.create(element.severity, element.title, element.message));

          element = data.folderStructureDirectoryPermissionStatus;
          selectorPermissionContainer.appendChild(InfoBox.create(element.severity, element.title, element.message));
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private fix(): void {
    this.setModalButtonsState(false);

    const modalContent = this.getModalBody();
    const outputContainer = this.findInModal(Identifiers.outputContainer);
    this.renderProgressBar(outputContainer);
    (new AjaxRequest(Router.getUrl('folderStructureFix')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          FolderStructure.removeLoadingMessage(outputContainer);
          if (data.success === true && Array.isArray(data.fixedStatus)) {
            if (data.fixedStatus.length > 0) {
              data.fixedStatus.forEach((element: any): void => {
                outputContainer.append(InfoBox.create(element.severity, element.title, element.message));
              });
            } else {
              outputContainer.append(InfoBox.create(Severity.warning, 'Nothing fixed'));
            }
            this.getStatus();
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
}

export default new FolderStructure();
