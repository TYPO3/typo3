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
 * Module: TYPO3/CMS/Install/Module/TcaExtTablesCheck
 */
class TcaExtTablesCheck extends AbstractInteractableModule {
  private selectorCheckTrigger: string = '.t3js-tcaExtTablesCheck-check';
  private selectorOutputContainer: string = '.t3js-tcaExtTablesCheck-output';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.check();
    currentModal.on('click', this.selectorCheckTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.check();
    });
  }

  private check(): void {
    this.setModalButtonsState(false);

    const modalContent = this.getModalBody();
    const $outputContainer = $(this.selectorOutputContainer);
    const m: any = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().html(m);
    (new AjaxRequest(Router.getUrl('tcaExtTablesCheck')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          modalContent.empty().append(data.html);
          Modal.setButtons(data.buttons);
          if (data.success === true && Array.isArray(data.status)) {
            if (data.status.length > 0) {
              const aMessage: any = InfoBox.render(
                Severity.warning,
                'Following extensions change TCA in ext_tables.php',
                'Check ext_tables.php files, look for ExtensionManagementUtility calls and $GLOBALS[\'TCA\'] modifications',
              );
              modalContent.find(this.selectorOutputContainer).append(aMessage);
              data.status.forEach((element: any): void => {
                const m2: any = InfoBox.render(element.severity, element.title, element.message);
                $outputContainer.append(m2);
                modalContent.append(m2);
              });
            } else {
              const aMessage: any = InfoBox.render(Severity.ok, 'No TCA changes in ext_tables.php files. Good job!', '');
              modalContent.find(this.selectorOutputContainer).append(aMessage);
            }
          } else {
            Notification.error('Something went wrong', 'Please use the module "Check for broken extensions" to find a possible extension causing this issue.');
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }
}

export = new TcaExtTablesCheck();
