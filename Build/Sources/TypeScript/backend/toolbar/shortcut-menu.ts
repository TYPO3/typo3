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
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Icons from '../icons';
import Modal from '../modal';
import Notification from '../notification';
import Viewport from '../viewport';
import SecurityUtility from '@typo3/core/security-utility';
import {ModuleStateStorage} from '@typo3/backend/storage/module-state-storage';
import '@typo3/backend/element/spinner-element';
import {Sizes} from '../enum/icon-types';

enum Identifiers {
  containerSelector = '#typo3-cms-backend-backend-toolbaritems-shortcuttoolbaritem',
  toolbarIconSelector = '.dropdown-toggle span.icon',
  toolbarMenuSelector = '.dropdown-menu',

  shortcutItemSelector = '.t3js-topbar-shortcut',
  shortcutJumpSelector = '.t3js-shortcut-jump',
  shortcutDeleteSelector = '.t3js-shortcut-delete',
  shortcutEditSelector = '.t3js-shortcut-edit',

  shortcutFormTitleSelector = 'input[name="shortcut-title"]',
  shortcutFormGroupSelector = 'select[name="shortcut-group"]',
  shortcutFormSaveSelector = '.t3js-shortcut-form-save',
  shortcutFormCancelSelector = '.t3js-shortcut-form-cancel',
  shortcutFormSelector = '.t3js-shortcut-form',
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

      const isDropdownItem = $(shortcutButton).hasClass('dropdown-item');
      const securityUtility = new SecurityUtility();
      Icons.getIcon('actions-system-shortcut-active', Icons.sizes.small).then((icon: string): void => {
        $(shortcutButton).html(icon + (isDropdownItem ? ' ' + securityUtility.encodeHtml(TYPO3.lang['labels.alreadyBookmarked']) : ''));
      });
      $(shortcutButton).addClass(isDropdownItem ? 'disabled' : 'active');
      // @todo using plain `disabled` HTML attr would have been better, since it disables events, mouse cursor, etc.
      //       (however, it might make things more complicated in Bootstrap's `button-variant` mixin)
      $(shortcutButton).attr('data-dispatch-disabled', 'disabled');
      $(shortcutButton).attr('title', TYPO3.lang['labels.alreadyBookmarked']);
    }).catch((): void => {
      Notification.error(TYPO3.lang['bookmark.failedTitle'], TYPO3.lang['bookmark.failedMessage']);
    });
  }

  private initializeEvents = (): void => {
    $(Identifiers.containerSelector).on('click', Identifiers.shortcutDeleteSelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();
      evt.stopImmediatePropagation();
      this.deleteShortcut($(evt.currentTarget).closest(Identifiers.shortcutItemSelector).data('shortcutid'));
    }).on('click', Identifiers.shortcutFormGroupSelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();
      evt.stopImmediatePropagation();
    }).on('click', Identifiers.shortcutEditSelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();
      evt.stopImmediatePropagation();
      this.editShortcut($(evt.currentTarget).closest(Identifiers.shortcutItemSelector));
    }).on('click', Identifiers.shortcutFormSaveSelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();
      evt.stopImmediatePropagation();
      this.saveShortcutForm($(evt.currentTarget).closest(Identifiers.shortcutFormSelector));
    }).on('submit', Identifiers.shortcutFormSelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();
      evt.stopImmediatePropagation();
      this.saveShortcutForm($(evt.currentTarget).closest(Identifiers.shortcutFormSelector));
    }).on('click', Identifiers.shortcutFormCancelSelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();
      evt.stopImmediatePropagation();
      this.refreshMenu();
    }).on('click', Identifiers.shortcutJumpSelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();
      evt.stopImmediatePropagation();
      const pageId = $(evt.currentTarget).data('pageid');
      if (pageId) {
        ModuleStateStorage.updateWithCurrentMount('web', pageId, true);
      }
      const router = document.querySelector('typo3-backend-module-router');
      router.setAttribute('endpoint', $(evt.currentTarget).attr('href'))
      router.setAttribute('module', $(evt.currentTarget).data('module'));
    });
  }

  /**
   * Removes an existing short by sending an AJAX call
   *
   * @param shortcutId number
   */
  private deleteShortcut(shortcutId: number): void {
    const modal = Modal.confirm(TYPO3.lang['bookmark.delete'], TYPO3.lang['bookmark.confirmDelete'])
    modal.addEventListener('confirm.button.ok', (): void => {
      (new AjaxRequest(TYPO3.settings.ajaxUrls.shortcut_remove)).post({
        shortcutId,
      }).then((): void => {
        // a reload is used in order to restore the original behaviour
        // e.g. remove groups that are now empty because the last one in the group
        // was removed
        this.refreshMenu();
      });
      modal.hideModal();
    });
    modal.addEventListener('confirm.button.cancel', (): void => {
      modal.hideModal();
    });
  }

  /**
   * Build the in-place-editor for a shortcut
   *
   * @param {JQuery} $shortcutRecord
   */
  private editShortcut($shortcutRecord: JQuery): void {
    // load the form
    (new AjaxRequest(TYPO3.settings.ajaxUrls.shortcut_editform)).withQueryArguments({
      shortcutId: $shortcutRecord.data('shortcutid'),
      shortcutGroup: $shortcutRecord.data('shortcutgroup'),
    }).get({cache: 'no-cache'}).then(async (response: AjaxResponse): Promise<any> => {
      $(Identifiers.containerSelector).find(Identifiers.toolbarMenuSelector).html(await response.resolve());
    });
  }

  /**
   * Save the data from the in-place-editor for a shortcut
   *
   * @param {JQuery} $shortcutForm
   */
  private saveShortcutForm($shortcutForm: JQuery): void {
    (new AjaxRequest(TYPO3.settings.ajaxUrls.shortcut_saveform)).post({
      shortcutId: $shortcutForm.data('shortcutid'),
      shortcutTitle: $shortcutForm.find(Identifiers.shortcutFormTitleSelector).val(),
      shortcutGroup: $shortcutForm.find(Identifiers.shortcutFormGroupSelector).val(),
    }).then((): void => {
      Notification.success(TYPO3.lang['bookmark.savedTitle'], TYPO3.lang['bookmark.savedMessage']);
      this.refreshMenu();
    });
  }

  /**
   * Reloads the menu after an update
   */
  private refreshMenu(): void {
    const $toolbarItemIcon = $(Identifiers.toolbarIconSelector, Identifiers.containerSelector);
    const $existingIcon = $toolbarItemIcon.clone();

    const spinner = document.createElement('typo3-backend-spinner');
    spinner.setAttribute('size', Sizes.small);
    $toolbarItemIcon.replaceWith(spinner);

    (new AjaxRequest(TYPO3.settings.ajaxUrls.shortcut_list)).get({cache: 'no-cache'}).then(async (response: AjaxResponse): Promise<any> => {
      $(Identifiers.toolbarMenuSelector, Identifiers.containerSelector).html(await response.resolve());
    }).finally((): void => {
      $('typo3-backend-spinner', Identifiers.containerSelector).replaceWith($existingIcon);
    });
  }
}

if (!top.TYPO3.ShortcutMenu || typeof top.TYPO3.ShortcutMenu !== 'object') {
  top.TYPO3.ShortcutMenu = new ShortcutMenu();
}

const shortcutMenuObject: ShortcutMenu = top.TYPO3.ShortcutMenu;
export default shortcutMenuObject;
