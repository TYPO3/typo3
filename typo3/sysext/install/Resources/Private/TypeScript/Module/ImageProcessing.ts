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
import 'bootstrap';
import Router = require('../Router');
import InfoBox = require('../Renderable/InfoBox');
import Severity = require('../Renderable/Severity');
import Notification = require('TYPO3/CMS/Backend/Notification');

/**
 * Module: TYPO3/CMS/Install/Module/ImageProcessing
 */
class ImageProcessing implements InteractableModuleInterface {
  private selectorModalBody: string = '.t3js-modal-body';
  private selectorExecuteTrigger: string = '.t3js-imageProcessing-execute';
  private selectorTestContainer: string = '.t3js-imageProcessing-twinContainer';
  private selectorTwinImageTemplate: string = '.t3js-imageProcessing-twinImage-template';
  private selectorCommandContainer: string = '.t3js-imageProcessing-command';
  private selectorCommandText: string = '.t3js-imageProcessing-command-text';
  private selectorTwinImages: string = '.t3js-imageProcessing-images';
  private currentModal: JQuery;

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getData();

    currentModal.on('click', this.selectorExecuteTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.runTests();
    });
  }

  private getData(): void {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    $.ajax({
      url: Router.getUrl('imageProcessingGetData'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          modalContent.empty().append(data.html);
          this.runTests();
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }

  private runTests(): void {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const $twinImageTemplate = this.currentModal.find(this.selectorTwinImageTemplate);
    modalContent.find(this.selectorTestContainer).each((index: number, element: any): void => {
      const $container: JQuery = $(element);
      const testType: string = $container.data('test');
      const message: any = InfoBox.render(Severity.loading, 'Loading...', '');
      $container.empty().html(message);
      $.ajax({
        url: Router.getUrl(testType),
        cache: false,
        success: (data: any): void => {
          if (data.success === true) {
            $container.empty();
            if (Array.isArray(data.status)) {
              data.status.forEach((aElement: any): void => {
                const aMessage = InfoBox.render(element.severity, element.title, element.message);
                $container.append(aMessage);
              });
            }
            const $aTwin = $twinImageTemplate.clone();
            $aTwin.removeClass('t3js-imageProcessing-twinImage-template');
            if (data.fileExists === true) {
              $aTwin.find('img.reference').attr('src', data.referenceFile);
              $aTwin.find('img.result').attr('src', data.outputFile);
              $aTwin.find(this.selectorTwinImages).show();
            }
            if (Array.isArray(data.command) && data.command.length > 0) {
              $aTwin.find(this.selectorCommandContainer).show();
              const commandText: Array<string> = [];
              data.command.forEach((aElement: any): void => {
                commandText.push('<strong>Command:</strong>\n' + aElement[1]);
                if (aElement.length === 3) {
                  commandText.push('<strong>Result:</strong>\n' + aElement[2]);
                }
              });
              $aTwin.find(this.selectorCommandText).html(commandText.join('\n'));
            }
            $container.append($aTwin);
          }
        },
        error: (xhr: XMLHttpRequest): void => {
          Router.handleAjaxError(xhr, modalContent);
        },
      });
    });
  }
}

export = new ImageProcessing();
