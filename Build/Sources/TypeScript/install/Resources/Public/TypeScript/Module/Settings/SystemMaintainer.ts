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
import 'bootstrap';
import Router = require('../../Router');
import Modal = require('TYPO3/CMS/Backend/Modal');
import Notification = require('TYPO3/CMS/Backend/Notification');

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
      require(['TYPO3/CMS/Install/chosen.jquery.min'], (): void => {
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
    $.ajax({
      url: Router.getUrl('systemMaintainerGetList'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          if (Array.isArray(data.status)) {
            data.status.forEach((element: any): void => {
              Notification.success(element.title, element.message);
            });
          }
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
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }

  private write(): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().data('system-maintainer-write-token');
    const selectedUsers = this.findInModal(this.selectorChosenField).val();
    $.ajax({
      method: 'POST',
      url: Router.getUrl(),
      data: {
        'install': {
          'users': selectedUsers,
          'token': executeToken,
          'action': 'systemMaintainerWrite',
        },
      },
      success: (data: any): void => {
        if (data.success === true) {
          if (Array.isArray(data.status)) {
            data.status.forEach((element: any): void => {
              Notification.success(element.title, element.message);
            });
          }
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

export = new SystemMaintainer();
