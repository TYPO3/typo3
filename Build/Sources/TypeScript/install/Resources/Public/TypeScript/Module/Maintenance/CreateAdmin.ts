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

import {AbstractInteractableModule} from '../AbstractInteractableModule';
import * as $ from 'jquery';
import Router = require('../../Router');
import PasswordStrength = require('../PasswordStrength');
import Modal = require('TYPO3/CMS/Backend/Modal');
import Notification = require('TYPO3/CMS/Backend/Notification');

/**
 * Module: TYPO3/CMS/Install/Module/CreateAdmin
 */
class CreateAdmin extends AbstractInteractableModule {
  private selectorAdminCreateButton: string = '.t3js-createAdmin-create';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getData();

    currentModal.on('click', this.selectorAdminCreateButton, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.create();
    });

    currentModal.on('click', '.t3-install-form-password-strength', (): void => {
      PasswordStrength.initialize('.t3-install-form-password-strength');
    });
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    $.ajax({
      url: Router.getUrl('createAdminGetData'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          modalContent.empty().append(data.html);
          Modal.setButtons(data.buttons);
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }

  private create(): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().data('create-admin-token');
    $.ajax({
      url: Router.getUrl(),
      method: 'POST',
      data: {
        'install': {
          'action': 'createAdmin',
          'token': executeToken,
          'userName': this.findInModal('.t3js-createAdmin-user').val(),
          'userPassword': this.findInModal('.t3js-createAdmin-password').val(),
          'userPasswordCheck': this.findInModal('.t3js-createAdmin-password-check').val(),
          'userEmail': this.findInModal('.t3js-createAdmin-email').val(),
          'userSystemMaintainer': (this.findInModal('.t3js-createAdmin-system-maintainer').is(':checked')) ? 1 : 0,
        },
      },
      cache: false,
      success: (data: any): void => {
        if (data.success === true && Array.isArray(data.status)) {
          data.status.forEach((element: any): void => {
            if (element.severity === 2) {
              Notification.error(element.message);
            } else {
              Notification.success(element.title);
            }
          });
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
    this.findInModal('.t3js-createAdmin-user').val('');
    this.findInModal('.t3js-createAdmin-password').val('');
    this.findInModal('.t3js-createAdmin-password-check').val('');
    this.findInModal('.t3js-createAdmin-email').val('');
    this.findInModal('.t3js-createAdmin-system-maintainer').prop('checked', false);
  }
}

export = new CreateAdmin();
