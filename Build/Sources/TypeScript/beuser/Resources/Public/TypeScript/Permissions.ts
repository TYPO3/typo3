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

declare global {
  interface Document { editform: any; }
}

/**
 * Module: TYPO3/CMS/Beuser/Permissions
 * Javascript functions regarding the permissions module
 */
class Permissions {
  private options: any = {
    containerSelector: '#typo3-permissionList',
    editControllerSelector: '#PermissionControllerEdit',
  };

  private ajaxUrl: string = TYPO3.settings.ajaxUrls.user_access_permissions;

  constructor() {
    this.initializeEvents();
  }

  /**
   * Changes the value of the permissions in the form
   */
  public setCheck(checknames: string, varname: string): void {
    if (document.editform[varname]) {
      let res = document.editform[varname].value;
      for (let a = 1; a <= 5; a++) {
        document.editform[checknames + '[' + a + ']'].checked = (res & Math.pow(2, a - 1));
      }
    }
  }

  /**
   * checks for a change of the permissions in the form
   */
  public checkChange(checknames: string, varname: string): void {
    let res = 0;
    for (let a = 1; a <= 5; a++) {
      if (document.editform[checknames + '[' + a + ']'].checked) {
        res |= Math.pow(2, a - 1);
      }
    }
    document.editform[varname].value = res | (checknames === 'tx_beuser_system_beusertxpermission[check][perms_user]' ? 1 : 0);
    this.setCheck(checknames, varname);
  }

  /**
   * Changes permissions by sending an AJAX request to the server
   */
  public setPermissions($element: JQuery): void {
    let page = $element.data('page');
    let who = $element.data('who');
    let elementSelector = '#' + page + '_' + who;

    (new AjaxRequest(this.ajaxUrl)).post({
      page: page,
      who: who,
      permissions: $element.data('permissions'),
      mode: $element.data('mode'),
      bits: $element.data('bits'),
    }).then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
      // Replace content
      $(elementSelector).replaceWith(data);
      // Reinitialize tooltip
      $(elementSelector).find('button').tooltip();
    });
  }

  /**
   * changes the flag to lock the editing on a page by sending an AJAX request
   */
  public toggleEditLock($element: JQuery): void {
    let page = $element.data('page');
    (new AjaxRequest(this.ajaxUrl)).post({
      action: 'toggle_edit_lock',
      page: page,
      editLockState: $element.data('lockstate'),
    }).then(async (response: AjaxResponse): Promise<void> => {
      // Replace content
      $('#el_' + page).replaceWith(await response.resolve());
    });
  }

  /**
   * Owner-related: Set the new owner of a page by executing an ajax call
   */
  public changeOwner($element: JQuery): void {
    let page = $element.data('page');

    (new AjaxRequest(this.ajaxUrl)).post({
      action: 'change_owner',
      page: page,
      ownerUid: $element.data('owner'),
      newOwnerUid: $('#new_page_owner').val(),
    }).then(async (response: AjaxResponse): Promise<void> => {
      // Replace content
      $('#o_' + page).replaceWith(await response.resolve());
    });
  }

  /**
   * Owner-related: load the selector for selecting
   * the owner of a page by executing an ajax call
   */
  public showChangeOwnerSelector($element: JQuery): void {
    let page = $element.data('page');

    (new AjaxRequest(this.ajaxUrl)).post({
      action: 'show_change_owner_selector',
      page: page,
      ownerUid: $element.data('owner'),
      username: $element.data('username'),
    }).then(async (response: AjaxResponse): Promise<void> => {
      // Replace content
      $('#o_' + page).replaceWith(await response.resolve());
    });
  }

  /**
   * Owner-related: Update the HTML view and show the original owner
   */
  public restoreOwner($element: JQuery): void {
    let page = $element.data('page');
    let username = $element.data('username');
    let usernameHtml = username;
    if (typeof username === 'undefined') {
      username = $('<span>', {
        'class': 'not_set',
        'text': '[not set]',
      });
      usernameHtml = username.html();
      username = username.text();
    }

    let html = $('<span/>', {
      'id': 'o_' + page,
    });
    let aSelector = $('<a/>', {
      'class': 'ug_selector changeowner',
      'data-page': page,
      'data-owner': $element.data('owner'),
      'data-username': usernameHtml,
      'text': username,
    });
    html.append(aSelector);

    // Replace content
    $('#o_' + page).replaceWith(html);
  }

  /**
   * Group-related: Set the new group by executing an ajax call
   */
  public changeGroup($element: JQuery): void {
    let page = $element.data('page');

    (new AjaxRequest(this.ajaxUrl)).post({
      action: 'change_group',
      page: page,
      groupUid: $element.data('groupId'),
      newGroupUid: $('#new_page_group').val(),
    }).then(async (response: AjaxResponse): Promise<void> => {
      // Replace content
      $('#g_' + page).replaceWith(await response.resolve());
    });
  }

  /**
   * Group-related: Load the selector by executing an ajax call
   */
  public showChangeGroupSelector($element: JQuery): void {
    let page = $element.data('page');

    (new AjaxRequest(this.ajaxUrl)).post({
      action: 'show_change_group_selector',
      page: page,
      groupUid: $element.data('groupId'),
      groupname: $element.data('groupname'),
    }).then(async (response: AjaxResponse): Promise<void> => {
      // Replace content
      $('#g_' + page).replaceWith(await response.resolve());
    });
  }

  /**
   * Group-related: Update the HTML view and show the original group
   */
  public restoreGroup($element: JQuery): void {
    let page = $element.data('page');
    let groupname = $element.data('groupname');
    let groupnameHtml = groupname;
    if (typeof groupname === 'undefined') {
      groupname = $('<span>', {
        'class': 'not_set',
        'text': '[not set]',
      });
      groupnameHtml = groupname.html();
      groupname = groupname.text();
    }
    let html = $('<span/>', {
      'id': 'g_' + page,
    });
    let aSelector = $('<a/>', {
      'class': 'ug_selector changegroup',
      'data-page': page,
      'data-group': $element.data('groupId'),
      'data-groupname': groupnameHtml,
      'text': groupname,
    });
    html.append(aSelector);

    // Replace content
    $('#g_' + page).replaceWith(html);
  }

  /**
   * initializes events using deferred bound to document
   * so AJAX reloads are no problem
   */
  public initializeEvents(): void {
    // Click events to change permissions (in template Index.html)
    $(this.options.containerSelector).on('click', '.change-permission', (evt: JQueryEventObject): void => {
      evt.preventDefault();
      this.setPermissions($(evt.currentTarget));
    }).on('click', '.editlock', (evt: JQueryEventObject): void => {
      // Click event for lock state
      evt.preventDefault();
      this.toggleEditLock($(evt.currentTarget));
    }).on('click', '.changeowner', (evt: JQueryEventObject): void => {
      // Click event to change owner
      evt.preventDefault();
      this.showChangeOwnerSelector($(evt.currentTarget));
    }).on('click', '.changegroup', (evt: JQueryEventObject): void => {
      // click event to change group
      evt.preventDefault();
      this.showChangeGroupSelector($(evt.currentTarget));
    }).on('click', '.restoreowner', (evt: JQueryEventObject): void => {
      // Add click handler for restoring previous owner
      evt.preventDefault();
      this.restoreOwner($(evt.currentTarget));
    }).on('click', '.saveowner', (evt: JQueryEventObject): void => {
      // Add click handler for saving owner
      evt.preventDefault();
      this.changeOwner($(evt.currentTarget));
    }).on('click', '.restoregroup', (evt: JQueryEventObject): void => {
      // Add click handler for restoring previous group
      evt.preventDefault();
      this.restoreGroup($(evt.currentTarget));
    }).on('click', '.savegroup', (evt: JQueryEventObject): void => {
      // Add click handler for saving group
      evt.preventDefault();
      this.changeGroup($(evt.currentTarget));
    });
    // Click events to change permissions (in template Edit.html)
    $(this.options.editControllerSelector).on('click', '[data-check-change-permissions]', (evt: JQueryEventObject): void => {
      const $target: JQuery = $(evt.currentTarget);
      const args = $target.data('checkChangePermissions').split(',').map((item: string) => item.trim());
      this.checkChange.apply(this, args);
    });
  }
}

let permissionObject: Permissions = new Permissions();
// expose to global
TYPO3.Permissions = permissionObject;

export = permissionObject;
