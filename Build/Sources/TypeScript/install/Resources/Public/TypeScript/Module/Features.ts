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
import Router = require('../Router');
import Notification = require('TYPO3/CMS/Backend/Notification');

/**
 * Module: TYPO3/CMS/Install/Module/Features
 */
class Features implements InteractableModuleInterface {
  private selectorModalBody: string = '.t3js-modal-body';
  private selectorModuleContent: string = '.t3js-features-content';
  private selectorSaveTrigger: string = '.t3js-features-save';
  private currentModal: any;

  public initialize(currentModal: any): void {
    this.currentModal = currentModal;
    this.getContent();

    currentModal.on('click', this.selectorSaveTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.save();
    });
  }

  private getContent(): void {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    $.ajax({
      url: Router.getUrl('featuresGetContent'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
          modalContent.empty().append(data.html);
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }

  private save(): void {
    const modalContent = this.currentModal.find(this.selectorModalBody);
    const executeToken = this.currentModal.find(this.selectorModuleContent).data('features-save-token');
    const postData: any = {};
    $(this.currentModal.find(this.selectorModuleContent + ' form').serializeArray()).each((index: number, element: any): void => {
      postData[element.name] = element.value;
    });
    postData['install[action]'] = 'featuresSave';
    postData['install[token]'] = executeToken;
    $.ajax({
      url: Router.getUrl(),
      method: 'POST',
      data: postData,
      cache: false,
      success: (data: any): void => {
        if (data.success === true && Array.isArray(data.status)) {
          data.status.forEach((element: any): void => {
            Notification.showMessage(element.title, element.message, element.severity);
          });
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }
}

export = new Features();
