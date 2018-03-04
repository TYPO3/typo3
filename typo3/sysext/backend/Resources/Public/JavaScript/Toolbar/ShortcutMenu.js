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

/**
 * Module: TYPO3/CMS/Backend/Toolbar/ShortcutMenu
 * shortcut menu logic to add new shortcut, remove a shortcut
 * and edit a shortcut
 */
define(['jquery',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/Icons',
  'TYPO3/CMS/Backend/Notification',
  'TYPO3/CMS/Backend/Viewport'
], function($, Modal, Icons, Notification, Viewport) {
  'use strict';

  /**
   *
   * @type {{options: {containerSelector: string, toolbarIconSelector: string, toolbarMenuSelector: string, shortcutItemSelector: string, shortcutDeleteSelector: string, shortcutEditSelector: string, shortcutFormTitleSelector: string, shortcutFormGroupSelector: string, shortcutFormSaveSelector: string, shortcutFormCancelSelector: string}}}
   * @exports TYPO3/CMS/Backend/Toolbar/ShortcutMenu
   */
  var ShortcutMenu = {
    options: {
      containerSelector: '#typo3-cms-backend-backend-toolbaritems-shortcuttoolbaritem',
      toolbarIconSelector: '.dropdown-toggle span.icon',
      toolbarMenuSelector: '.dropdown-menu',

      shortcutItemSelector: '.t3js-topbar-shortcut',
      shortcutDeleteSelector: '.t3js-shortcut-delete',
      shortcutEditSelector: '.t3js-shortcut-edit',

      shortcutFormTitleSelector: 'input[name="shortcut-title"]',
      shortcutFormGroupSelector: 'select[name="shortcut-group"]',
      shortcutFormSaveSelector: '.shortcut-form-save',
      shortcutFormCancelSelector: '.shortcut-form-cancel',
      shortcutFormSelector: '.shortcut-form'
    }
  };

  /**
   * build the in-place-editor for a shortcut
   *
   * @param {Object} $shortcutRecord
   */
  ShortcutMenu.editShortcut = function($shortcutRecord) {
    // load the form
    $.ajax({
      url: TYPO3.settings.ajaxUrls['shortcut_editform'],
      data: {
        shortcutId: $shortcutRecord.data('shortcutid'),
        shortcutGroup: $shortcutRecord.data('shortcutgroup')
      },
      cache: false
    }).done(function(data) {
      $(ShortcutMenu.options.containerSelector).find(ShortcutMenu.options.toolbarMenuSelector).html(data);
    });
  };

  /**
   * Save the data from the in-place-editor for a shortcut
   *
   * @param {Object} $shortcutForm
   */
  ShortcutMenu.saveShortcutForm = function($shortcutForm) {
    $.ajax({
      url: TYPO3.settings.ajaxUrls['shortcut_saveform'],
      data: {
        shortcutId: $shortcutForm.data('shortcutid'),
        shortcutTitle: $shortcutForm.find(ShortcutMenu.options.shortcutFormTitleSelector).val(),
        shortcutGroup: $shortcutForm.find(ShortcutMenu.options.shortcutFormGroupSelector).val()
      },
      type: 'post',
      cache: false
    }).done(function(data) {
      Notification.success(TYPO3.lang['bookmark.savedTitle'], TYPO3.lang['bookmark.savedMessage']);
      ShortcutMenu.refreshMenu();
    });
  };

  /**
   * removes an existing short by sending an AJAX call
   *
   * @param {Object} $shortcutRecord
   */
  ShortcutMenu.deleteShortcut = function($shortcutRecord) {
    Modal.confirm(TYPO3.lang['bookmark.delete'], TYPO3.lang['bookmark.confirmDelete'])
      .on('confirm.button.ok', function() {
        $.ajax({
          url: TYPO3.settings.ajaxUrls['shortcut_remove'],
          data: {
            shortcutId: $shortcutRecord.data('shortcutid')
          },
          type: 'post',
          cache: false
        }).done(function() {
          // a reload is used in order to restore the original behaviour
          // e.g. remove groups that are now empty because the last one in the group
          // was removed
          ShortcutMenu.refreshMenu();
        });
        $(this).trigger('modal-dismiss');
      })
      .on('confirm.button.cancel', function() {
        $(this).trigger('modal-dismiss');
      });
  };

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
  ShortcutMenu.createShortcut = function(moduleName, url, confirmationText, motherModule, shortcutButton, displayName) {
    if (typeof confirmationText !== 'undefined') {
      Modal.confirm(TYPO3.lang['bookmark.create'], confirmationText)
        .on('confirm.button.ok', function() {
          var $toolbarItemIcon = $(ShortcutMenu.options.toolbarIconSelector, ShortcutMenu.options.containerSelector),
            $existingIcon = $toolbarItemIcon.clone();

          Icons.getIcon('spinner-circle-light', Icons.sizes.small).done(function(spinner) {
            $toolbarItemIcon.replaceWith(spinner);
          });

          $.ajax({
            url: TYPO3.settings.ajaxUrls['shortcut_create'],
            type: 'post',
            data: {
              module: moduleName,
              url: url,
              motherModName: motherModule,
              displayName: displayName
            },
            cache: false
          }).done(function() {
            ShortcutMenu.refreshMenu();
            $(ShortcutMenu.options.toolbarIconSelector, ShortcutMenu.options.containerSelector).replaceWith($existingIcon);
            if (typeof shortcutButton === 'object') {
              Icons.getIcon('actions-system-shortcut-active', Icons.sizes.small).done(function(icon) {
                $(shortcutButton).html(icon);
              });
              $(shortcutButton).addClass('active');
              $(shortcutButton).attr('title', null);
              $(shortcutButton).attr('onclick', null);
            }
          });
          $(this).trigger('modal-dismiss');
        })
        .on('confirm.button.cancel', function() {
          $(this).trigger('modal-dismiss');
        });
    }

  };

  /**
   * reloads the menu after an update
   */
  ShortcutMenu.refreshMenu = function() {
    $.ajax({
      url: TYPO3.settings.ajaxUrls['shortcut_list'],
      type: 'get',
      cache: false
    }).done(function(data) {
      $(ShortcutMenu.options.toolbarMenuSelector, ShortcutMenu.options.containerSelector).html(data);
    });
  };

  /**
   * Registers listeners
   */
  ShortcutMenu.initializeEvents = function() {
    $(ShortcutMenu.options.containerSelector).on('click', ShortcutMenu.options.shortcutDeleteSelector, function(evt) {
      evt.preventDefault();
      evt.stopImmediatePropagation();
      ShortcutMenu.deleteShortcut($(this).closest(ShortcutMenu.options.shortcutItemSelector));
    }).on('click', ShortcutMenu.options.shortcutFormGroupSelector, function(evt) {
      evt.preventDefault();
      evt.stopImmediatePropagation();
    }).on('click', ShortcutMenu.options.shortcutEditSelector, function(evt) {
      evt.preventDefault();
      evt.stopImmediatePropagation();
      ShortcutMenu.editShortcut($(this).closest(ShortcutMenu.options.shortcutItemSelector));
    }).on('click', ShortcutMenu.options.shortcutFormSaveSelector, function(evt) {
      evt.preventDefault();
      evt.stopImmediatePropagation();
      ShortcutMenu.saveShortcutForm($(this).closest(ShortcutMenu.options.shortcutFormSelector));
    }).on('submit', ShortcutMenu.options.shortcutFormSelector, function(evt) {
      evt.preventDefault();
      evt.stopImmediatePropagation();
      ShortcutMenu.saveShortcutForm($(this).closest(ShortcutMenu.options.shortcutFormSelector));
    }).on('click', ShortcutMenu.options.shortcutFormCancelSelector, function(evt) {
      evt.preventDefault();
      evt.stopImmediatePropagation();
      ShortcutMenu.refreshMenu();
    });
  };

  Viewport.Topbar.Toolbar.registerEvent(ShortcutMenu.initializeEvents);

  // expose as global object
  TYPO3.ShortcutMenu = ShortcutMenu;

  return ShortcutMenu;
});
