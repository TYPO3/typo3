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

import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import PasswordStrength from '../password-strength';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import {AbstractInteractableModule} from '../abstract-interactable-module';

/**
 * Module: @typo3/install/module/create-admin
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
    (new AjaxRequest(Router.getUrl('createAdminGetData')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.empty().append(data.html);
            Modal.setButtons(data.buttons);
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        },
      );
  }

  private create(): void {
    this.setModalButtonsState(false);

    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().data('create-admin-token');
    const payload = {
      install: {
        action: 'createAdmin',
        token: executeToken,
        userName: this.findInModal('.t3js-createAdmin-user').val(),
        userPassword: this.findInModal('.t3js-createAdmin-password').val(),
        userPasswordCheck: this.findInModal('.t3js-createAdmin-password-check').val(),
        userEmail: this.findInModal('.t3js-createAdmin-email').val(),
        userSystemMaintainer: (this.findInModal('.t3js-createAdmin-system-maintainer').is(':checked')) ? 1 : 0,
      },
    };
    this.getModuleContent().find(':input').prop('disabled', true);

    (new AjaxRequest(Router.getUrl())).post(payload).then(async (response: AjaxResponse): Promise<any> => {
      const data = await response.resolve();
      if (data.success === true && Array.isArray(data.status)) {
        data.status.forEach((element: any): void => {
          Notification.showMessage(element.title, element.message, element.severity);
        });
      } else {
        Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
      }
    }, (error: AjaxResponse): void => {
      Router.handleAjaxError(error, modalContent);
    }).finally((): void => {
      this.setModalButtonsState(true);

      this.getModuleContent().find(':input').prop('disabled', false);
      this.findInModal('.t3js-createAdmin-user').val('');
      this.findInModal('.t3js-createAdmin-password').val('');
      this.findInModal('.t3js-createAdmin-password-check').val('');
      this.findInModal('.t3js-createAdmin-email').val('');
      this.findInModal('.t3js-createAdmin-system-maintainer').prop('checked', false);
    });
  }
}

export default new CreateAdmin();
