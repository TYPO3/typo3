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
import $ from 'jquery';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { AbstractInteractableModule, ModuleLoadedResponseWithButtons } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import InfoBox from '../../renderable/info-box';
import ProgressBar from '../../renderable/progress-bar';
import Severity from '../../renderable/severity';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';

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
  private readonly selectorGridderBadge: string = '.t3js-folderStructure-badge';
  private readonly selectorOutputContainer: string = '.t3js-folderStructure-output';
  private readonly selectorErrorContainer: string = '.t3js-folderStructure-errors';
  private readonly selectorErrorList: string = '.t3js-folderStructure-errors-list';
  private readonly selectorErrorFixTrigger: string = '.t3js-folderStructure-errors-fix';
  private readonly selectorOkContainer: string = '.t3js-folderStructure-ok';
  private readonly selectorOkList: string = '.t3js-folderStructure-ok-list';
  private readonly selectorPermissionContainer: string = '.t3js-folderStructure-permissions';

  private static removeLoadingMessage($container: JQuery): void {
    $container.find('.t3js-progressbar').remove();
  }

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;

    // Get status on initialize to have the badge and content ready
    this.getStatus();

    currentModal.on('click', this.selectorErrorFixTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.fix();
    });
  }

  private getStatus(): void {
    const modalContent = this.getModalBody();
    const $errorBadge = $(this.selectorGridderBadge);
    $errorBadge.text('').hide();
    (new AjaxRequest(Router.getUrl('folderStructureGetStatus')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: FolderStructureResponse = await response.resolve();
          modalContent.empty().append(data.html);
          Modal.setButtons(data.buttons);
          if (data.success === true && Array.isArray(data.errorStatus)) {
            let errorCount = 0;
            if (data.errorStatus.length > 0) {
              modalContent.find(this.selectorErrorContainer).show();
              modalContent.find(this.selectorErrorList).empty();
              data.errorStatus.forEach(((aElement: any): void => {
                errorCount++;
                $errorBadge.text(errorCount).show();
                const message = InfoBox.render(aElement.severity, aElement.title, aElement.message);
                modalContent.find(this.selectorErrorList).append(message);
              }));
            } else {
              modalContent.find(this.selectorErrorContainer).hide();
            }
          }
          if (data.success === true && Array.isArray(data.okStatus)) {
            if (data.okStatus.length > 0) {
              modalContent.find(this.selectorOkContainer).show();
              modalContent.find(this.selectorOkList).empty();
              data.okStatus.forEach(((aElement: any): void => {
                const message = InfoBox.render(aElement.severity, aElement.title, aElement.message);
                modalContent.find(this.selectorOkList).append(message);
              }));
            } else {
              modalContent.find(this.selectorOkContainer).hide();
            }
          }
          let element = data.folderStructureFilePermissionStatus;
          modalContent.find(this.selectorPermissionContainer).empty().append(
            InfoBox.render(element.severity, element.title, element.message),
          );
          element = data.folderStructureDirectoryPermissionStatus;
          modalContent.find(this.selectorPermissionContainer).append(
            InfoBox.render(element.severity, element.title, element.message),
          );
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      ).finally((): void => {
        this.setModalButtonsState(true);
      });
  }

  private fix(): void {
    this.setModalButtonsState(false);

    const modalContent: JQuery = this.getModalBody();
    const $outputContainer: JQuery = this.findInModal(this.selectorOutputContainer);
    const message = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().append(message);
    (new AjaxRequest(Router.getUrl('folderStructureFix')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          FolderStructure.removeLoadingMessage($outputContainer);
          if (data.success === true && Array.isArray(data.fixedStatus)) {
            if (data.fixedStatus.length > 0) {
              data.fixedStatus.forEach((element: any): void => {
                $outputContainer.append(
                  InfoBox.render(element.severity, element.title, element.message),
                );
              });
            } else {
              $outputContainer.append(
                InfoBox.render(Severity.warning, 'Nothing fixed', ''),
              );
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
