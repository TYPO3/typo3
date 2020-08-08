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
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {ResponseError} from 'TYPO3/CMS/Core/Ajax/ResponseError';
import {AbstractInteractableModule} from '../AbstractInteractableModule';
import Modal = require('TYPO3/CMS/Backend/Modal');
import Notification = require('TYPO3/CMS/Backend/Notification');
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import InfoBox = require('../../Renderable/InfoBox');
import ProgressBar = require('../../Renderable/ProgressBar');
import Severity = require('../../Renderable/Severity');
import Router = require('../../Router');

/**
 * Module: TYPO3/CMS/Install/Module/FolderStructure
 */
class FolderStructure extends AbstractInteractableModule {
  private selectorGridderBadge: string = '.t3js-folderStructure-badge';
  private selectorOutputContainer: string = '.t3js-folderStructure-output';
  private selectorErrorContainer: string = '.t3js-folderStructure-errors';
  private selectorErrorList: string = '.t3js-folderStructure-errors-list';
  private selectorErrorFixTrigger: string = '.t3js-folderStructure-errors-fix';
  private selectorOkContainer: string = '.t3js-folderStructure-ok';
  private selectorOkList: string = '.t3js-folderStructure-ok-list';
  private selectorPermissionContainer: string = '.t3js-folderStructure-permissions';

  private static removeLoadingMessage($container: JQuery): void {
    $container.find('.alert-loading').remove();
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
    modalContent.find(this.selectorOutputContainer).empty().append(
      ProgressBar.render(Severity.loading, 'Loading...', ''),
    );
    (new AjaxRequest(Router.getUrl('folderStructureGetStatus')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
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
                const aMessage = InfoBox.render(aElement.severity, aElement.title, aElement.message);
                modalContent.find(this.selectorErrorList).append(aMessage);
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
                const aMessage = InfoBox.render(aElement.severity, aElement.title, aElement.message);
                modalContent.find(this.selectorOkList).append(aMessage);
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
        (error: ResponseError): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private fix(): void {
    this.setModalButtonsState(false);

    const modalContent: JQuery = this.getModalBody();
    const $outputContainer: JQuery = this.findInModal(this.selectorOutputContainer);
    const message: any = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().html(message);
    (new AjaxRequest(Router.getUrl('folderStructureFix')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
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
        (error: ResponseError): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }
}

export = new FolderStructure();
