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
import PasswordStrength = require('./PasswordStrength');
import Notification = require('TYPO3/CMS/Backend/Notification');

/**
 * Module: TYPO3/CMS/Install/Module/ChangeInstallToolPassword
 */
class ChangeInstallToolPassword extends AbstractInteractableModule {
  private selectorChangeForm: string = '#t3js-changeInstallToolPassword-form';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getData();

    currentModal.on('submit', this.selectorChangeForm, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.change();
    });
    currentModal.on('click', '.t3-install-form-password-strength', (e: JQueryEventObject): void => {
      PasswordStrength.initialize('.t3-install-form-password-strength');
    });
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    $.ajax({
      url: Router.getUrl('changeInstallToolPasswordGetData'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
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

  private change(): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().data('install-tool-token');
    $.ajax({
      url: Router.getUrl(),
      method: 'POST',
      data: {
        'install': {
          'action': 'changeInstallToolPassword',
          'token': executeToken,
          'password': this.findInModal('.t3js-changeInstallToolPassword-password').val(),
          'passwordCheck': this.findInModal('.t3js-changeInstallToolPassword-password-check').val(),
        },
      },
      cache: false,
      success: (data: any): void => {
        if (data.success === true && Array.isArray(data.status)) {
          data.status.forEach((element: any): void => {
            Notification.showMessage('', element.message, element.severity);
          });
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
      complete: (): void => {
        this.findInModal('.t3js-changeInstallToolPassword-password,.t3js-changeInstallToolPassword-password-check').val('');
      },
    });
  }
}

export = new ChangeInstallToolPassword();
