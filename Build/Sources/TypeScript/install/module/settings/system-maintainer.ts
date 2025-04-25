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
import { AbstractInteractableModule, type ModuleLoadedResponseWithButtons } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import RegularEvent from '@typo3/core/event/regular-event';
import type { SelectPure } from 'select-pure/lib/components';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { ModalElement } from '@typo3/backend/modal';
import type MessageInterface from '@typo3/install/message-interface';

enum Identifiers {
  writeTrigger = '.t3js-systemMaintainer-write',
  selectPureField = '.t3js-systemMaintainer-select-pure'
}

type SystemMaintainerListResponse = ModuleLoadedResponseWithButtons & {
  users: {
    uid: number;
    username: string;
    disable: boolean;
    isSystemMaintainer: boolean;
  }[];
};

type SystemMaintainersWrittenResponse = {
  status: MessageInterface[],
  success: boolean,
};

/**
 * Module: @typo3/install/module/system-maintainer
 */
class SystemMaintainer extends AbstractInteractableModule {
  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);

    this.loadModuleFrameAgnostic('select-pure').then((): void => {
      this.getList();
    });

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.write();
    }).delegateTo(currentModal, Identifiers.writeTrigger);
  }

  private getList(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('systemMaintainerGetList')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: SystemMaintainerListResponse = await response.resolve();
          if (data.success === true) {
            modalContent.innerHTML = data.html;
            Modal.setButtons(data.buttons);
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private write(): void {
    this.setModalButtonsState(false);

    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().dataset.systemMaintainerWriteToken;
    const selectedUsers: string[] = (this.findInModal(Identifiers.selectPureField) as SelectPure).values;
    (new AjaxRequest(Router.getUrl())).post({
      install: {
        users: selectedUsers,
        token: executeToken,
        action: 'systemMaintainerWrite',
      },
    }).then(async (response: AjaxResponse): Promise<void> => {
      const data: SystemMaintainersWrittenResponse = await response.resolve();
      if (data.success === true) {
        if (Array.isArray(data.status)) {
          data.status.forEach((element: MessageInterface): void => {
            Notification.success(element.title, element.message);
          });
        }
      } else {
        Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
      }
    }, (error: AjaxResponse): void => {
      Router.handleAjaxError(error, modalContent);
    }).finally((): void => {
      this.setModalButtonsState(true);
    });
  }
}

export default new SystemMaintainer();
