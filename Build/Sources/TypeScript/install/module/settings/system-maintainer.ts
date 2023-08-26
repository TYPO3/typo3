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
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { AbstractInteractableModule } from '../abstract-interactable-module';
import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import MessageInterface from '@typo3/install/message-interface';
import RegularEvent from '@typo3/core/event/regular-event';
import $ from 'jquery';
import type { ModalElement } from '@typo3/backend/modal';

type SystemMaintainerListResponse = {
  success: boolean;
  users: {
    uid: number;
    username: string;
    disable: boolean;
    isSystemMaintainer: boolean;
  }[];
  html: string;
  buttons: {
    btnClass: string;
    text: string
  }[]
}

/**
 * Module: @typo3/install/module/system-maintainer
 */
class SystemMaintainer extends AbstractInteractableModule {
  private readonly selectorWriteTrigger: string = '.t3js-systemMaintainer-write';
  private readonly selectorChosenContainer: string = '.t3js-systemMaintainer-chosen';
  private readonly selectorChosenField: string = '.t3js-systemMaintainer-chosen-select';

  public initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    const isInIframe = window.location !== window.parent.location;
    if (isInIframe) {
      topLevelModuleImport('@typo3/install/chosen.jquery.min.js').then((): void => {
        this.getList();
      });
    } else {
      import('@typo3/install/chosen.jquery.min').then((): void => {
        this.getList();
      });
    }

    new RegularEvent('click', (event: Event): void => {
      event.preventDefault();
      this.write();
    }).delegateTo(currentModal, this.selectorWriteTrigger);
  }

  // @todo: find replacement for chosen
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
            if (Array.isArray(data.users)) {
              data.users.forEach((element): void => {
                let name = element.username;
                if (element.disable) {
                  name = '[DISABLED] ' + name;
                }
                const option = document.createElement('option');
                option.value = String(element.uid);
                option.innerText = name;
                if (element.isSystemMaintainer) {
                  option.setAttribute('selected', 'selected');
                }
                modalContent.querySelector(this.selectorChosenField).append(option);
              });
            }

            const config: { [key: string]: Record<string, string> } = {
              '.t3js-systemMaintainer-chosen-select': {
                width: '100%',
                placeholder_text_multiple: 'users',
              },
            };

            const configureChosen = ($: JQueryStatic): void => {
              for (const selector in config) {
                if (selector in config) {
                  $(selector).chosen(config[selector]);
                }
              }
              modalContent.querySelector<HTMLElement>(this.selectorChosenContainer).style.display = 'block';
              modalContent.querySelector(this.selectorChosenField).dispatchEvent(new CustomEvent('chosen:updated'));
            };

            const isInIframe = window.location !== window.parent.location;
            if (isInIframe) {
              topLevelModuleImport('jquery').then(({ default: $ }) => configureChosen($));
            } else {
              configureChosen($);
            }
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
    const selectedUsers: Array<string | number> = $(this.findInModal(this.selectorChosenField) as HTMLInputElement).val();
    (new AjaxRequest(Router.getUrl())).post({
      install: {
        users: selectedUsers,
        token: executeToken,
        action: 'systemMaintainerWrite',
      },
    }).then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
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
