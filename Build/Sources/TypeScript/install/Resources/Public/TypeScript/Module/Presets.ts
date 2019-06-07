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
import 'bootstrap';
import Router = require('../Router');
import Notification = require('TYPO3/CMS/Backend/Notification');

/**
 * Module: TYPO3/CMS/Install/Module/Presets
 */
class Presets extends AbstractInteractableModule {
  private selectorActivateTrigger: string = '.t3js-presets-activate';
  private selectorImageExecutable: string = '.t3js-presets-image-executable';
  private selectorImageExecutableTrigger: string = '.t3js-presets-image-executable-trigger';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getContent();

    // Load content with post data on click 'custom image executable path'
    currentModal.on('click', this.selectorImageExecutableTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.getCustomImagePathContent();
    });

    // Write out selected preset
    currentModal.on('click', this.selectorActivateTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.activate();
    });

    // Automatically select the custom preset if a value in one of its input fields is changed
    currentModal.find('.t3js-custom-preset').on('input', '.t3js-custom-preset', (e: JQueryEventObject): void => {
      $('#' + $(e.currentTarget).data('radio')).prop('checked', true);
    });
  }

  private getContent(): void {
    const modalContent = this.getModalBody();
    $.ajax({
      url: Router.getUrl('presetsGetContent'),
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

  private getCustomImagePathContent(): void {
    const modalContent = this.getModalBody();
    const presetsContentToken = this.getModuleContent().data('presets-content-token');
    $.ajax({
      url: Router.getUrl(),
      method: 'POST',
      data: {
        'install': {
          'token': presetsContentToken,
          'action': 'presetsGetContent',
          'values': {
            'Image': {
              'additionalSearchPath': this.findInModal(this.selectorImageExecutable).val(),
            },
          },
        },
      },
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

  private activate(): void {
    const modalContent: JQuery = this.getModalBody();
    const executeToken: string = this.getModuleContent().data('presets-activate-token');
    const postData: any = {};
    $(this.findInModal('form').serializeArray()).each((index: number, element: any): void => {
      postData[element.name] = element.value;
    });
    postData['install[action]'] = 'presetsActivate';
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

export = new Presets();
