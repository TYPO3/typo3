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

import $ from 'jquery';
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import Icons = require('../Icons');
import PersistentStorage = require('../Storage/Persistent');
import Viewport = require('../Viewport');

enum Identifiers {
  containerSelector = '#typo3-cms-backend-backend-toolbaritems-systeminformationtoolbaritem',
  toolbarIconSelector = '.toolbar-item-icon .t3js-icon',
  menuContainerSelector = '.dropdown-menu',
  moduleLinks = '.t3js-systeminformation-module',
  counter = '.t3js-systeminformation-counter',
}

/**
 * Module: TYPO3/CMS/Backend/Toolbar/SystemInformationMenu
 * System information menu handler
 */
class SystemInformationMenu {
  private timer: number = null;

  /**
   * Updates the counter
   */
  private static updateCounter(): void {
    const $container = $(Identifiers.containerSelector).find(Identifiers.menuContainerSelector).find('.t3js-systeminformation-container');
    const $counter = $(Identifiers.counter);
    const count = $container.data('count');
    const badgeClass = $container.data('severityclass');

    $counter.text(count).toggle(parseInt(count, 10) > 0);

    // ensure all default classes are available and previous
    // (at this time in processing unknown) class is removed
    $counter.removeClass();
    $counter.addClass('t3js-systeminformation-counter toolbar-item-badge badge');
    // badgeClass e.g. could be 'badge-info', 'badge-danger', ...
    if (badgeClass !== '') {
      $counter.addClass(badgeClass);
    }
  }

  constructor() {
    Viewport.Topbar.Toolbar.registerEvent(this.updateMenu);
  }

  private updateMenu = (): void => {
    const $toolbarItemIcon = $(Identifiers.toolbarIconSelector, Identifiers.containerSelector);
    const $existingIcon = $toolbarItemIcon.clone();
    const $menuContainer = $(Identifiers.containerSelector).find(Identifiers.menuContainerSelector);

    if (this.timer !== null) {
      clearTimeout(this.timer);
      this.timer = null;
    }

    Icons.getIcon('spinner-circle-light', Icons.sizes.small).then((spinner: string): void => {
      $toolbarItemIcon.replaceWith(spinner);
    });

    (new AjaxRequest(TYPO3.settings.ajaxUrls.systeminformation_render)).get().then(async (response: AjaxResponse): Promise<any> => {
      $menuContainer.html(await response.resolve());
      SystemInformationMenu.updateCounter();
      $(Identifiers.moduleLinks).on('click', this.openModule);
    }).finally((): void => {
      $(Identifiers.toolbarIconSelector, Identifiers.containerSelector).replaceWith($existingIcon);
      // reload error data every five minutes
      this.timer = setTimeout(
        this.updateMenu,
        1000 * 300,
      );
    });
  }

  /**
   * Updates the UC and opens the linked module
   *
   * @param {Event} e
   */
  private openModule(e: JQueryEventObject): void {
    e.preventDefault();
    e.stopPropagation();

    let storedSystemInformationSettings = {};
    const moduleStorageObject: { [key: string]: Object } = {};
    const requestedModule: string = $(e.currentTarget).data('modulename');
    const moduleParams = $(e.currentTarget).data('moduleparams');
    const timestamp = Math.floor((new Date()).getTime() / 1000);

    if (PersistentStorage.isset('systeminformation')) {
      storedSystemInformationSettings = JSON.parse(PersistentStorage.get('systeminformation'));
    }

    moduleStorageObject[requestedModule] = {lastAccess: timestamp};
    $.extend(true, storedSystemInformationSettings, moduleStorageObject);
    const $ajax = PersistentStorage.set('systeminformation', JSON.stringify(storedSystemInformationSettings));
    $ajax.done((): void => {
      // finally, open the module now
      TYPO3.ModuleMenu.App.showModule(requestedModule, moduleParams);
      Viewport.Topbar.refresh();
    });
  }
}

export = new SystemInformationMenu();
