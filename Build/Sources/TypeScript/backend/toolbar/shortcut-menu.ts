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

import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Icons from '../icons';
import Modal from '../modal';
import Notification from '../notification';
import Viewport from '../viewport';
import SecurityUtility from '@typo3/core/security-utility';
import '@typo3/backend/element/spinner-element';
import { Sizes } from '../enum/icon-types';
import RegularEvent from '@typo3/core/event/regular-event';

enum Identifiers {
  containerSelector = '#typo3-cms-backend-backend-toolbaritems-shortcuttoolbaritem',
  toolbarIconSelector = '.dropdown-toggle span.icon',
  toolbarMenuSelector = '.dropdown-menu',

  shortcutItemSelector = '.t3js-topbar-shortcut',
  shortcutJumpSelector = '.t3js-shortcut-jump',
  shortcutDeleteSelector = '.t3js-shortcut-delete',
  shortcutEditSelector = '.t3js-shortcut-edit',

  shortcutFormSelector = '.t3js-shortcut-form',

  createShortcutSelector = '[data-dispatch-action="TYPO3.ShortcutMenu.createShortcut"]',
}

interface BookmarkData {
  route: string;
  args: string;
}

/**
 * Module: @typo3/backend/toolbar/shortcut-menu
 * shortcut menu logic to add new shortcut, remove a shortcut
 * and edit a shortcut
 */
class ShortcutMenu {
  constructor() {
    Viewport.Topbar.Toolbar.registerEvent(this.initializeEvents);
  }

  /**
   * makes a call to the backend class to create a new shortcut,
   * when finished it reloads the menu
   *
   * @param {String} routeIdentifier
   * @param {String} routeArguments
   * @param {String} displayName
   * @param {String} confirmationText
   * @param {Object} shortcutButton
   */
  public createShortcut(
    routeIdentifier: string,
    routeArguments: string,
    displayName: string,
    confirmationText: string,
    shortcutButton: HTMLElement,
  ): void {
    (new AjaxRequest(TYPO3.settings.ajaxUrls.shortcut_create)).post({
      routeIdentifier: routeIdentifier,
      arguments: routeArguments,
      displayName: displayName,
    }).then(async (response: AjaxResponse): Promise<void> => {
      const jsonResponse = await response.resolve();
      if (jsonResponse.result === 'success') {
        Notification.success(TYPO3.lang['bookmark.savedTitle'], TYPO3.lang['bookmark.savedMessage']);
      } else if (jsonResponse.result === 'alreadyExists') {
        Notification.info(TYPO3.lang['bookmark.alreadyExistsTitle'], TYPO3.lang['bookmark.alreadyExistsMessage']);
      }

      // Always reload the bookmark menu, could be out-of-sync in the "alreadyExists" case
      this.refreshMenu();

      if (typeof shortcutButton !== 'object') {
        // @todo: when does this happen?
        console.warn(`Expected argument shortcutButton to be an object, got ${typeof shortcutButton}`);
        return;
      }

      const isDropdownItem = shortcutButton.classList.contains('dropdown-item');
      const securityUtility = new SecurityUtility();
      Icons.getIcon('actions-system-shortcut-active', Icons.sizes.small).then((icon: string): void => {
        shortcutButton.innerHTML = icon + (isDropdownItem ? ' ' + securityUtility.encodeHtml(TYPO3.lang['labels.alreadyBookmarked']) : '');
      });

      if (isDropdownItem) {
        shortcutButton.setAttribute('disabled', 'disabled');
      } else {
        shortcutButton.classList.add('active');
      }

      shortcutButton.dataset.dispatchDisabled = 'disabled';
      shortcutButton.title = securityUtility.encodeHtml(TYPO3.lang['labels.alreadyBookmarked']);
    }).catch((): void => {
      Notification.error(TYPO3.lang['bookmark.failedTitle'], TYPO3.lang['bookmark.failedMessage']);
    });
  }

  private readonly initializeEvents = (): void => {
    const containerSelector = document.querySelector(Identifiers.containerSelector);
    new RegularEvent('click', (evt: Event, target: HTMLElement): void => {
      evt.preventDefault();
      evt.stopImmediatePropagation();

      const shortcutItem = target.closest(Identifiers.shortcutItemSelector) as HTMLElement;
      this.deleteShortcut(parseInt(shortcutItem.dataset.shortcutid, 10));
    }).delegateTo(containerSelector, Identifiers.shortcutDeleteSelector);

    new RegularEvent('click', (evt: Event, target: HTMLElement): void => {
      evt.preventDefault();
      evt.stopImmediatePropagation();

      const shortcutItem = target.closest(Identifiers.shortcutItemSelector) as HTMLElement;
      this.editShortcut(parseInt(shortcutItem.dataset.shortcutid, 10), shortcutItem.dataset.shortcutgroup);
    }).delegateTo(containerSelector, Identifiers.shortcutEditSelector);

    new RegularEvent('submit', (evt: Event, target: HTMLFormElement): void => {
      evt.preventDefault();
      evt.stopImmediatePropagation();

      this.saveShortcutForm(target);
    }).delegateTo(containerSelector, Identifiers.shortcutFormSelector);

    new RegularEvent('reset', (evt: Event): void => {
      evt.preventDefault();
      evt.stopImmediatePropagation();

      this.refreshMenu();
    }).delegateTo(containerSelector, Identifiers.shortcutFormSelector);

    new RegularEvent('click', (evt: Event, target: HTMLAnchorElement): void => {
      evt.preventDefault();

      const router = document.querySelector('typo3-backend-module-router');
      router.setAttribute('endpoint', target.href);
      router.setAttribute('module', target.dataset.module);
    }).delegateTo(containerSelector, Identifiers.shortcutJumpSelector);
  };

  /**
   * Removes an existing short by sending an AJAX call
   *
   * @param shortcutId number
   */
  private deleteShortcut(shortcutId: number): void {
    const modal = Modal.confirm(TYPO3.lang['bookmark.delete'], TYPO3.lang['bookmark.confirmDelete']);
    modal.addEventListener('confirm.button.ok', (): void => {
      (new AjaxRequest(TYPO3.settings.ajaxUrls.shortcut_remove)).post({
        shortcutId,
      }).then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        // a reload is used in order to restore the original behaviour
        // e.g. remove groups that are now empty because the last one in the group
        // was removed
        this.refreshMenu();
        // if we remove bookmark entry for current page, we want to enable the bookmark link
        // again in the dropdown menu
        this.checkIfEnableBookmarkLink(data.data);
      });
      modal.hideModal();
    });
    modal.addEventListener('confirm.button.cancel', (): void => {
      modal.hideModal();
    });
  }

  /**
   * Build the in-place-editor for a shortcut
   */
  private editShortcut(shortcutId: number, shortcutGroup: string): void {
    // load the form
    (new AjaxRequest(TYPO3.settings.ajaxUrls.shortcut_editform)).withQueryArguments({
      shortcutId,
      shortcutGroup,
    }).get({ cache: 'no-cache' }).then(async (response: AjaxResponse): Promise<void> => {
      document.querySelector(Identifiers.containerSelector + ' ' + Identifiers.toolbarMenuSelector).innerHTML = await response.resolve();
    });
  }

  /**
   * Save the data from the in-place-editor for a shortcut
   */
  private saveShortcutForm(shortcutForm: HTMLFormElement): void {
    (new AjaxRequest(TYPO3.settings.ajaxUrls.shortcut_saveform)).post(new FormData(shortcutForm)).then((): void => {
      Notification.success(TYPO3.lang['bookmark.savedTitle'], TYPO3.lang['bookmark.savedMessage']);
      this.refreshMenu();
    });
  }

  /**
   * Reloads the menu after an update
   */
  private refreshMenu(): void {
    const toolbarItemIcon = document.querySelector(Identifiers.containerSelector + ' ' + Identifiers.toolbarIconSelector);
    const existingIcon = toolbarItemIcon.cloneNode(true);

    const spinner = document.createElement('typo3-backend-spinner');
    spinner.setAttribute('size', Sizes.small);
    toolbarItemIcon.replaceWith(spinner);

    (new AjaxRequest(TYPO3.settings.ajaxUrls.shortcut_list)).get({ cache: 'no-cache' }).then(async (response: AjaxResponse): Promise<void> => {
      document.querySelector(Identifiers.containerSelector + ' ' + Identifiers.toolbarMenuSelector).innerHTML = await response.resolve();
    }).finally((): void => {
      document.querySelector(Identifiers.containerSelector + ' typo3-backend-spinner').replaceWith(existingIcon);
    });
  }

  private checkIfEnableBookmarkLink(bookmarkData: BookmarkData): void {
    const shortcutButton: HTMLElement|null = top.list_frame?.document.querySelector(Identifiers.createShortcutSelector);
    if (!shortcutButton) {
      return;
    }

    const dispatchArgs = JSON.parse(shortcutButton.dataset.dispatchArgs.replace(/&quot;/g, '"'));
    if (dispatchArgs[0] !== bookmarkData.route || dispatchArgs[1] !== bookmarkData.args) {
      return;
    }

    const securityUtility = new SecurityUtility();
    const isDropdownItem = shortcutButton.classList.contains('dropdown-item');

    Icons.getIcon('actions-system-shortcut-new', Icons.sizes.small).then((icon: string): void => {
      shortcutButton.innerHTML = icon + (isDropdownItem ? ' ' + securityUtility.encodeHtml(TYPO3.lang['labels.makeBookmark']) : '');
    });
    shortcutButton.title = securityUtility.encodeHtml(TYPO3.lang['labels.makeBookmark']);
    delete shortcutButton.dataset.dispatchDisabled;

    if (isDropdownItem) {
      shortcutButton.removeAttribute('disabled');
    } else {
      shortcutButton.classList.remove('active');
    }
  }
}

if (!top.TYPO3.ShortcutMenu || typeof top.TYPO3.ShortcutMenu !== 'object') {
  top.TYPO3.ShortcutMenu = new ShortcutMenu();
}

const shortcutMenuObject: ShortcutMenu = top.TYPO3.ShortcutMenu;
export default shortcutMenuObject;
