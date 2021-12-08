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

import Modal from 'TYPO3/CMS/Backend/Modal';
import Notification from 'TYPO3/CMS/Backend/Notification';
import AjaxRequest from 'TYPO3/CMS/Core/Ajax/AjaxRequest';
import Router from '../../Router';
import PasswordStrength from '../PasswordStrength';
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {AbstractInteractableModule} from '../AbstractInteractableModule';

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
