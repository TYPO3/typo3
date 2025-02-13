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
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { AbstractInteractableModule, ModuleLoadedResponseWithButtons } from '../abstract-interactable-module';
import MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';

enum Identifiers {
  adminCreateButton = '.t3js-createAdmin-create',
  adminUserInput = '.t3js-createAdmin-user',
  adminRealNameInput = '.t3js-createAdmin-realname',
  adminPasswordInput = '.t3js-createAdmin-password',
  adminPasswordCheckInput = '.t3js-createAdmin-password-check',
  adminEmailInput = '.t3js-createAdmin-email',
  adminSysMaintainterInput = '.t3js-createAdmin-system-maintainer'
}

type BackendUserCreatedResponse = {
  status: MessageInterface[],
  success: boolean,
  userCreated: boolean,
}

/**
 * Module: @typo3/install/module/create-admin
 */
class CreateAdmin extends AbstractInteractableModule {
  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.getData();

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.create();
    }).delegateTo(currentModal, Identifiers.adminCreateButton);
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('createAdminGetData')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ModuleLoadedResponseWithButtons = await response.resolve();
          if (data.success === true) {
            modalContent.innerHTML = data.html;

            PasswordStrength.initialize(modalContent.querySelector('.t3-install-form-password-strength'));
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
    const executeToken = this.getModuleContent().dataset.createAdminToken;
    const payload = {
      install: {
        action: 'createAdmin',
        token: executeToken,
        userName: (this.findInModal(Identifiers.adminUserInput) as HTMLInputElement).value,
        userPassword: (this.findInModal(Identifiers.adminPasswordInput) as HTMLInputElement).value,
        userPasswordCheck: (this.findInModal(Identifiers.adminPasswordCheckInput) as HTMLInputElement).value,
        userEmail: (this.findInModal(Identifiers.adminEmailInput) as HTMLInputElement).value,
        realName: (this.findInModal(Identifiers.adminRealNameInput) as HTMLInputElement).value,
        userSystemMaintainer: (this.findInModal(Identifiers.adminSysMaintainterInput) as HTMLInputElement).checked ? 1 : 0,
      },
    };
    this.getModuleContent().querySelectorAll('input').forEach((input: HTMLInputElement): void => {
      input.disabled = true;
    });

    (new AjaxRequest(Router.getUrl())).post(payload).then(async (response: AjaxResponse): Promise<void> => {
      const data: BackendUserCreatedResponse = await response.resolve();
      if (data.success === true && Array.isArray(data.status)) {
        data.status.forEach((element: MessageInterface): void => {
          Notification.showMessage(element.title, element.message, element.severity);
        });
        if (data.userCreated) {
          (this.findInModal(Identifiers.adminUserInput) as HTMLInputElement).value = '';
          (this.findInModal(Identifiers.adminPasswordInput) as HTMLInputElement).value = '';
          (this.findInModal(Identifiers.adminPasswordCheckInput) as HTMLInputElement).value = '';
          (this.findInModal(Identifiers.adminEmailInput) as HTMLInputElement).value = '';
          (this.findInModal(Identifiers.adminSysMaintainterInput) as HTMLInputElement).checked = false;
        }
      } else {
        Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
      }
    }, (error: AjaxResponse): void => {
      Router.handleAjaxError(error, modalContent);
    }).finally((): void => {
      this.setModalButtonsState(true);

      this.getModuleContent().querySelectorAll('input').forEach((input: HTMLInputElement): void => {
        input.disabled = false;
      });
    });
  }
}

export default new CreateAdmin();
