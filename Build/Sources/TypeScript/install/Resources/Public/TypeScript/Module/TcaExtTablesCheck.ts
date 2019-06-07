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
import ProgressBar = require('../Renderable/ProgressBar');
import Severity = require('../Renderable/Severity');
import InfoBox = require('../Renderable/InfoBox');
import Notification = require('TYPO3/CMS/Backend/Notification');

/**
 * Module: TYPO3/CMS/Install/Module/TcaExtTablesCheck
 */
class TcaExtTablesCheck extends AbstractInteractableModule {
  private selectorCheckTrigger: string = '.t3js-tcaExtTablesCheck-check';
  private selectorOutputContainer: string = '.t3js-tcaExtTablesCheck-output';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.check();
    currentModal.on('click',  this.selectorCheckTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.check();
    });
  }

  private check(): void {
    const modalContent = this.getModalBody();
    const $outputContainer = $(this.selectorOutputContainer);
    const m: any = ProgressBar.render(Severity.loading, 'Loading...', '');
    $outputContainer.empty().html(m);
    $.ajax({
      url: Router.getUrl('tcaExtTablesCheck'),
      cache: false,
      success: (data: any): void => {
        modalContent.empty().append(data.html);
        if (data.success === true && Array.isArray(data.status)) {
          if (data.status.length > 0) {
            const aMessage: any = InfoBox.render(
              Severity.warning,
              'Extensions change TCA in ext_tables.php',
              'Check for ExtensionManagementUtility and $GLOBALS["TCA"]',
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
          Notification.error('Something went wrong', 'Use "Check for broken extensions"');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }
}

export = new TcaExtTablesCheck();
