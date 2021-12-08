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
import Router from '../../Router';

/**
 * Module: TYPO3/CMS/Install/Module/SystemMaintainer
 */
class SystemMaintainer extends AbstractInteractableModule {
  private selectorWriteTrigger: string = '.t3js-systemMaintainer-write';
  private selectorChosenContainer: string = '.t3js-systemMaintainer-chosen';
  private selectorChosenField: string = '.t3js-systemMaintainer-chosen-select';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    const isInIframe = window.location !== window.parent.location;
    if (isInIframe) {
      top.require(['TYPO3/CMS/Install/chosen.jquery.min'], (): void => {
        this.getList();
      });
    } else {
      import('TYPO3/CMS/Install/chosen.jquery.min').then((): void => {
        this.getList();
      });
    }

    currentModal.on('click', this.selectorWriteTrigger, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.write();
    });
  }

  private getList(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('systemMaintainerGetList')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.html(data.html);
            Modal.setButtons(data.buttons);
            if (Array.isArray(data.users)) {
              data.users.forEach((element: any): void => {
                let name = element.username;
                if (element.disable) {
                  name = '[DISABLED] ' + name;
                }
                const $option = $('<option>', {'value': element.uid}).text(name);
                if (element.isSystemMaintainer) {
                  $option.attr('selected', 'selected');
                }
                modalContent.find(this.selectorChosenField).append($option);
              });
            }
            const config: any = {
              '.t3js-systemMaintainer-chosen-select': {
                width: '100%',
                placeholder_text_multiple: 'users',
              },
            };

            for (const selector in config) {
              if (config.hasOwnProperty(selector)) {
                modalContent.find(selector).chosen(config[selector]);
              }
            }
            modalContent.find(this.selectorChosenContainer).show();
            modalContent.find(this.selectorChosenField).trigger('chosen:updated');
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
    const executeToken = this.getModuleContent().data('system-maintainer-write-token');
    const selectedUsers = this.findInModal(this.selectorChosenField).val();
    (new AjaxRequest(Router.getUrl())).post({
      install: {
        users: selectedUsers,
        token: executeToken,
        action: 'systemMaintainerWrite',
      },
    }).then(async (response: AjaxResponse): Promise<any> => {
      const data = await response.resolve();
      if (data.success === true) {
        if (Array.isArray(data.status)) {
          data.status.forEach((element: any): void => {
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
