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
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { AbstractInteractableModule } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { InfoBox } from '../../renderable/info-box';
import Severity from '../../renderable/severity';
import Router from '../../router';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';

/**
 * Module: @typo3/install/module/folder-structure
 */
class FolderStructure extends AbstractInteractableModule {
  private readonly selectorGridderBadge: string = '.t3js-folderStructure-badge';
  private readonly selectorOutputContainer: string = '.t3js-folderStructure-output';
  private readonly selectorErrorContainer: string = '.t3js-folderStructure-errors';
  private readonly selectorErrorList: string = '.t3js-folderStructure-errors-list';
  private readonly selectorErrorFixTrigger: string = '.t3js-folderStructure-errors-fix';
  private readonly selectorOkContainer: string = '.t3js-folderStructure-ok';
  private readonly selectorOkList: string = '.t3js-folderStructure-ok-list';
  private readonly selectorPermissionContainer: string = '.t3js-folderStructure-permissions';

  private static removeLoadingMessage(container: HTMLElement): void {
    container.querySelector('typo3-install-progress-bar').remove();
  }

  public initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);

    // Get status on initialize to have the badge and content ready
    this.getStatus();

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.fix();
    }).delegateTo(currentModal, this.selectorErrorFixTrigger);
  }

  private getStatus(): void {
    const modalContent = this.getModalBody();

    this.renderProgressBar(modalContent.querySelector(this.selectorOutputContainer));
    (new AjaxRequest(Router.getUrl('folderStructureGetStatus')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          modalContent.innerHTML = data.html;
          Modal.setButtons(data.buttons);
          if (data.success === true && Array.isArray(data.errorStatus)) {
            if (data.errorStatus.length > 0) {
              modalContent.querySelector<HTMLElement>(this.selectorErrorContainer).style.display = 'block';
              modalContent.querySelector(this.selectorErrorList).innerHTML = '';
              data.errorStatus.forEach(((aElement: any): void => {
                modalContent.querySelector(this.selectorErrorList).appendChild(InfoBox.create(aElement.severity, aElement.title, aElement.message));
              }));
            } else {
              modalContent.querySelector<HTMLElement>(this.selectorErrorContainer).style.display = 'none';
            }
          }
          if (data.success === true && Array.isArray(data.okStatus)) {
            if (data.okStatus.length > 0) {
              modalContent.querySelector<HTMLElement>(this.selectorOkContainer).style.display = 'block';
              modalContent.querySelector(this.selectorOkList).innerHTML = '';
              data.okStatus.forEach(((aElement: any): void => {
                modalContent.querySelector(this.selectorOkList).appendChild(InfoBox.create(aElement.severity, aElement.title, aElement.message));
              }));
            } else {
              modalContent.querySelector<HTMLElement>(this.selectorOkContainer).style.display = 'none';
            }
          }
          let element = data.folderStructureFilePermissionStatus;
          const selectorPermissionContainer = modalContent.querySelector(this.selectorPermissionContainer);
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
    const outputContainer = this.findInModal(this.selectorOutputContainer);
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
      );
  }
}

export default new FolderStructure();
