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

import * as $ from 'jquery';
import Icons = require('../Icons');
import Modal = require('../Modal');
import Notification = require('../Notification');
import Viewport = require('../Viewport');

enum Identifiers {
  containerSelector = '#typo3-cms-backend-backend-toolbaritems-shortcuttoolbaritem',
  toolbarIconSelector = '.dropdown-toggle span.icon',
  toolbarMenuSelector = '.dropdown-menu',

  shortcutItemSelector = '.t3js-topbar-shortcut',
  shortcutDeleteSelector = '.t3js-shortcut-delete',
  shortcutEditSelector = '.t3js-shortcut-edit',

  shortcutFormTitleSelector = 'input[name="shortcut-title"]',
  shortcutFormGroupSelector = 'select[name="shortcut-group"]',
  shortcutFormSaveSelector = '.shortcut-form-save',
  shortcutFormCancelSelector = '.shortcut-form-cancel',
  shortcutFormSelector = '.shortcut-form',
}

/**
 * Module =TYPO3/CMS/Backend/Toolbar/ShortcutMenu
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
   * @param {String} moduleName
   * @param {String} url
   * @param {String} confirmationText
   * @param {String} motherModule
   * @param {Object} shortcutButton
   * @param {String} displayName
   */
  public createShortcut(
    moduleName: string,
    url: string,
    confirmationText: string,
    motherModule: string,
    shortcutButton: JQuery,
    displayName: string,
  ): void {
    if (typeof confirmationText !== 'undefined') {
      Modal.confirm(TYPO3.lang['bookmark.create'], confirmationText).on('confirm.button.ok', (e: JQueryEventObject): void => {
        const $toolbarItemIcon = $(Identifiers.toolbarIconSelector, Identifiers.containerSelector);
        const $existingIcon = $toolbarItemIcon.clone();

        Icons.getIcon('spinner-circle-light', Icons.sizes.small).done((spinner: string): void => {
          $toolbarItemIcon.replaceWith(spinner);
        });

        $.ajax({
          url: TYPO3.settings.ajaxUrls.shortcut_create,
          type: 'post',
          data: {
            module: moduleName,
            url: url,
            motherModName: motherModule,
            displayName: displayName,
          },
          cache: false,
        }).done((): void => {
          this.refreshMenu();
          $(Identifiers.toolbarIconSelector, Identifiers.containerSelector).replaceWith($existingIcon);
          if (typeof shortcutButton === 'object') {
            Icons.getIcon('actions-system-shortcut-active', Icons.sizes.small).done((icon: string): void => {
              $(shortcutButton).html(icon);
            });
            $(shortcutButton).addClass('active');
            $(shortcutButton).attr('title', null);
            $(shortcutButton).attr('onclick', null);
          }
        });
        $(e.currentTarget).trigger('modal-dismiss');
      })
        .on('confirm.button.cancel', (e: JQueryEventObject): void => {
          $(e.currentTarget).trigger('modal-dismiss');
        });
    }
  }

  private initializeEvents = (): void => {
    $(Identifiers.containerSelector).on('click', Identifiers.shortcutDeleteSelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();
      evt.stopImmediatePropagation();
      this.deleteShortcut($(evt.currentTarget).closest(Identifiers.shortcutItemSelector));
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
    });
  }

  /**
   * Removes an existing short by sending an AJAX call
   *
   * @param {JQuery} $shortcutRecord
   */
  private deleteShortcut($shortcutRecord: JQuery): void {
    Modal.confirm(TYPO3.lang['bookmark.delete'], TYPO3.lang['bookmark.confirmDelete'])
      .on('confirm.button.ok', (e: JQueryEventObject): void => {
        $.ajax({
          url: TYPO3.settings.ajaxUrls.shortcut_remove,
          data: {
            shortcutId: $shortcutRecord.data('shortcutid'),
          },
          type: 'post',
          cache: false,
        }).done((): void => {
          // a reload is used in order to restore the original behaviour
          // e.g. remove groups that are now empty because the last one in the group
          // was removed
          this.refreshMenu();
        });
        $(e.currentTarget).trigger('modal-dismiss');
      })
      .on('confirm.button.cancel', (e: JQueryEventObject): void => {
        $(e.currentTarget).trigger('modal-dismiss');
      });
  }

  /**
   * Build the in-place-editor for a shortcut
   *
   * @param {JQuery} $shortcutRecord
   */
  private editShortcut($shortcutRecord: JQuery): void {
    // load the form
    $.ajax({
      url: TYPO3.settings.ajaxUrls.shortcut_editform,
      data: {
        shortcutId: $shortcutRecord.data('shortcutid'),
        shortcutGroup: $shortcutRecord.data('shortcutgroup'),
      },
      cache: false,
    }).done((data: string): void => {
      $(Identifiers.containerSelector).find(Identifiers.toolbarMenuSelector).html(data);
    });
  }

  /**
   * Save the data from the in-place-editor for a shortcut
   *
   * @param {JQuery} $shortcutForm
   */
  private saveShortcutForm($shortcutForm: JQuery): void {
    $.ajax({
      url: TYPO3.settings.ajaxUrls.shortcut_saveform,
      data: {
        shortcutId: $shortcutForm.data('shortcutid'),
        shortcutTitle: $shortcutForm.find(Identifiers.shortcutFormTitleSelector).val(),
        shortcutGroup: $shortcutForm.find(Identifiers.shortcutFormGroupSelector).val(),
      },
      type: 'post',
      cache: false,
    }).done((): void => {
      Notification.success(TYPO3.lang['bookmark.savedTitle'], TYPO3.lang['bookmark.savedMessage']);
      this.refreshMenu();
    });
  }

  /**
   * Reloads the menu after an update
   */
  private refreshMenu(): void {
    $.ajax({
      url: TYPO3.settings.ajaxUrls.shortcut_list,
      type: 'get',
      cache: false,
    }).done((data: string): void => {
      $(Identifiers.toolbarMenuSelector, Identifiers.containerSelector).html(data);
    });
  }
}

let shortcutMenuObject = new ShortcutMenu();
TYPO3.ShortcutMenu = shortcutMenuObject;

export = shortcutMenuObject;
