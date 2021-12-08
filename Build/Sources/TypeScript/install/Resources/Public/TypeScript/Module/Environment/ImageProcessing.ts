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
import {AbstractInteractableModule} from '../AbstractInteractableModule';
import Modal from 'TYPO3/CMS/Backend/Modal';
import Notification from 'TYPO3/CMS/Backend/Notification';
import AjaxRequest from 'TYPO3/CMS/Core/Ajax/AjaxRequest';
import InfoBox from '../../Renderable/InfoBox';
import Severity from '../../Renderable/Severity';
import Router from '../../Router';

/**
 * Module: TYPO3/CMS/Install/Module/ImageProcessing
 */
class ImageProcessing extends AbstractInteractableModule {
  private selectorExecuteTrigger: string = '.t3js-imageProcessing-execute';
  private selectorTestContainer: string = '.t3js-imageProcessing-twinContainer';
  private selectorTwinImageTemplate: string = '.t3js-imageProcessing-twinImage-template';
  private selectorCommandContainer: string = '.t3js-imageProcessing-command';
  private selectorCommandText: string = '.t3js-imageProcessing-command-text';
  private selectorTwinImages: string = '.t3js-imageProcessing-images';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getData();

    currentModal.on('click', this.selectorExecuteTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.runTests();
    });
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('imageProcessingGetData')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.empty().append(data.html);
            Modal.setButtons(data.buttons);
            this.runTests();
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private runTests(): void {
    const modalContent = this.getModalBody();
    const $triggerButton = this.findInModal(this.selectorExecuteTrigger);
    this.setModalButtonsState(false);

    const $twinImageTemplate = this.findInModal(this.selectorTwinImageTemplate);
    const promises: Array<Promise<any>> = [];
    modalContent.find(this.selectorTestContainer).each((index: number, container: any): void => {
      const $container: JQuery = $(container);
      const testType: string = $container.data('test');
      const message: any = InfoBox.render(Severity.loading, 'Loading...', '');
      $container.empty().html(message);
      const request = (new AjaxRequest(Router.getUrl(testType)))
        .get({cache: 'no-cache'})
        .then(
          async (response: AjaxResponse): Promise<any> => {
            const data = await response.resolve();
            if (data.success === true) {
              $container.empty();
              if (Array.isArray(data.status)) {
                data.status.forEach((element: any): void => {
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
          (error: AjaxResponse): void => {
            Router.handleAjaxError(error, modalContent);
          }
        );
      promises.push(request);
    });

    Promise.all(promises).then((): void => {
      $triggerButton.removeClass('disabled').prop('disabled', false);
    });
  }
}

export default new ImageProcessing();
