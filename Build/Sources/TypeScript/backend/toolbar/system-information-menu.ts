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

import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import RegularEvent from '@typo3/core/event/regular-event';
import Icons from '../icons';
import PersistentStorage from '../storage/persistent';
import Viewport from '../viewport';

/**
 * Explicit selectors to avoid nesting queries
 */
enum SystemInformationSelector {
  element = '#typo3-cms-backend-backend-toolbaritems-systeminformationtoolbaritem',
  icon = '#typo3-cms-backend-backend-toolbaritems-systeminformationtoolbaritem .toolbar-item-icon .t3js-icon',
  menu = '#typo3-cms-backend-backend-toolbaritems-systeminformationtoolbaritem .dropdown-menu',
  data = '[data-systeminformation-data]',
  badge = '[data-systeminformation-badge]',
  message = '[data-systeminformation-message-module]',
  messageLink = '[data-systeminformation-message-module] a'
}

interface SystemInformationData {
  count: number,
  severityBadgeClass: string,
}

interface SystemInformationMessageData {
  count: number,
  status: string,
  module: string,
  params: string
}

/**
 * Module: @typo3/backend/toolbar/system-information-menu
 * System information menu handler
 */
class SystemInformationMenu {
  private timer: number = null;

  constructor() {
    new RegularEvent('click', this.handleMessageLinkClick)
      .delegateTo(document, SystemInformationSelector.messageLink);
    Viewport.Topbar.Toolbar.registerEvent(this.updateMenu);
  }

  private static getData(): SystemInformationData {
    const element = document.querySelector(SystemInformationSelector.data) as HTMLElement;
    const data: DOMStringMap = element.dataset;
    return {
      count: data.systeminformationDataCount ? parseInt(data.systeminformationDataCount, 10) : 0,
      severityBadgeClass: data.systeminformationDataSeveritybadgeclass ?? '',
    };
  }

  private static getMessageDataFromElement(element: HTMLElement): SystemInformationMessageData {
    const data: DOMStringMap = element.dataset;
    return {
      count: data.systeminformationMessageCount ? parseInt(data.systeminformationMessageCount, 10) : 0,
      status: data.systeminformationMessageStatus ?? '',
      module: data.systeminformationMessageModule ?? '',
      params: data.systeminformationMessageParams ?? '',
    };
  }

  private static updateBadge(): void {
    const data = SystemInformationMenu.getData();
    const element = document.querySelector(SystemInformationSelector.badge) as HTMLElement;

    // ensure all default classes are available and previous
    // (at this time in processing unknown) class is removed
    element.removeAttribute('class');
    element.classList.add('toolbar-item-badge');
    element.classList.add('badge');
    if (data.severityBadgeClass !== '') {
      element.classList.add(data.severityBadgeClass);
    }

    element.textContent = data.count.toString();
    element.classList.toggle('hidden', !(data.count > 0));
  }

  private updateMenu = (): void => {
    const toolbarItemIcon = document.querySelector(SystemInformationSelector.icon);
    const currentIcon = toolbarItemIcon.cloneNode(true);

    if (this.timer !== null) {
      clearTimeout(this.timer);
      this.timer = null;
    }

    Icons.getIcon('spinner-circle-light', Icons.sizes.small).then((spinner: string): void => {
      toolbarItemIcon.replaceWith(document.createRange().createContextualFragment(spinner));
    });

    (new AjaxRequest(TYPO3.settings.ajaxUrls.systeminformation_render)).get().then(async (response: AjaxResponse): Promise<void> => {
      document.querySelector(SystemInformationSelector.menu).innerHTML = await response.resolve();
      SystemInformationMenu.updateBadge();
    }).finally((): void => {
      document.querySelector(SystemInformationSelector.icon).replaceWith(currentIcon);
      // reload error data every five minutes
      this.timer = setTimeout(this.updateMenu, 1000 * 300);
    });
  };

  /**
   * Updates the UC and opens the linked module
   */
  private handleMessageLinkClick(event: Event, target: HTMLElement): void {
    const messageData = SystemInformationMenu.getMessageDataFromElement(target.closest(SystemInformationSelector.message));
    if (messageData.module === '') {
      return;
    }
    event.preventDefault();
    event.stopPropagation();

    const moduleStorageObject: { [key: string]: Object } = {};
    const timestamp = Math.floor(Date.now() / 1000);
    let storedSystemInformationSettings = {};
    if (PersistentStorage.isset('systeminformation')) {
      storedSystemInformationSettings = JSON.parse(PersistentStorage.get('systeminformation'));
    }

    moduleStorageObject[messageData.module] = { lastAccess: timestamp };
    Object.assign(storedSystemInformationSettings, moduleStorageObject);
    const ajax = PersistentStorage.set('systeminformation', JSON.stringify(storedSystemInformationSettings));
    ajax.then((): void => {
      // finally, open the module now
      TYPO3.ModuleMenu.App.showModule(messageData.module, messageData.params);
      Viewport.Topbar.refresh();
    });
  }
}

export default new SystemInformationMenu();
