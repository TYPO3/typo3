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

import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import RegularEvent from '@typo3/core/event/regular-event';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Tooltip from '@typo3/backend/tooltip';

/**
 * Module: @typo3/beuser/permissions
 * Javascript functions regarding the permissions module
 */
class Permissions {
  private options: any = {
    containerSelector: '#typo3-permissionList',
    editControllerSelector: '#PermissionControllerEdit',
  };

  private ajaxUrl: string = TYPO3.settings.ajaxUrls.user_access_permissions;

  /**
   * Changes the value of the permissions in the form
   */
  private static setPermissionCheckboxes(checknames: string, permissionValue: number): void {
    const permissionCheckboxes: NodeListOf<HTMLInputElement> = document.querySelectorAll(`input[type="checkbox"][name^="${checknames}"]`);
    for (let permissionCheckbox of permissionCheckboxes) {
      const value = parseInt(permissionCheckbox.value, 10);
      permissionCheckbox.checked = (permissionValue & value) === value;
    }
  }

  /**
   * checks for a change of the permissions in the form
   */
  private static updatePermissionValue(checknames: string, varname: string): void {
    let permissionValue = 0;
    const checkedPermissionCheckboxes: NodeListOf<HTMLInputElement> = document.querySelectorAll(`input[type="checkbox"][name^="${checknames}"]:checked`);
    for (let permissionCheckbox of checkedPermissionCheckboxes) {
      permissionValue |= parseInt(permissionCheckbox.value, 10);
    }
    document.forms.namedItem('editform')[varname].value = permissionValue | (checknames === 'check[perms_user]' ? 1 : 0);
  }

  constructor() {
    this.initializeCheckboxGroups();
    this.initializeEvents();
  }

  /**
   * Changes permissions by sending an AJAX request to the server
   */
  private setPermissions(element: HTMLElement): void {
    let page = element.dataset.page;
    let who = element.dataset.who;

    // Hide all Tooltips to avoid permanent visible/never hidden Tooltips
    Tooltip.hide(document.querySelectorAll('[data-bs-toggle="tooltip"]'));

    (new AjaxRequest(this.ajaxUrl)).post({
      page: page,
      who: who,
      permissions: element.dataset.permissions,
      mode: element.dataset.mode,
      bits: element.dataset.bits,
    }).then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
      const element = document.getElementById(page + '_' + who);
      // Replace content
      element.outerHTML = data;
      // Reinitialize tooltip
      Tooltip.initialize('[data-bs-toggle="tooltip"]');
    });
  }

  /**
   * changes the flag to lock the editing on a page by sending an AJAX request
   */
  private toggleEditLock(element: HTMLElement): void {
    let page = element.dataset.page;
    (new AjaxRequest(this.ajaxUrl)).post({
      action: 'toggle_edit_lock',
      page: page,
      editLockState: element.dataset.lockstate,
    }).then(async (response: AjaxResponse): Promise<void> => {
      // Replace content
      document.getElementById('el_' + page).outerHTML = await response.resolve();
    });
  }

  /**
   * Owner-related: Set the new owner of a page by executing an ajax call
   */
  private changeOwner(element: HTMLElement): void {
    let page = element.dataset.page;
    const container: HTMLElement = document.getElementById('o_' + page);

    (new AjaxRequest(this.ajaxUrl)).post({
      action: 'change_owner',
      page: page,
      ownerUid: element.dataset.owner,
      newOwnerUid: (container.getElementsByTagName('select')[0] as HTMLSelectElement).value,
    }).then(async (response: AjaxResponse): Promise<void> => {
      // Replace content
      container.outerHTML = await response.resolve();
    });
  }

  /**
   * Owner-related: load the selector for selecting
   * the owner of a page by executing an ajax call
   */
  private showChangeOwnerSelector(element: HTMLElement): void {
    let page = element.dataset.page;

    (new AjaxRequest(this.ajaxUrl)).post({
      action: 'show_change_owner_selector',
      page: page,
      ownerUid: element.dataset.owner,
      username: element.dataset.username,
    }).then(async (response: AjaxResponse): Promise<void> => {
      // Replace content
      document.getElementById('o_' + page).outerHTML = await response.resolve();
    });
  }

  /**
   * Owner-related: Update the HTML view and show the original owner
   */
  private restoreOwner(element: HTMLElement): void {
    const page = element.dataset.page;
    const username = element.dataset.username ?? element.dataset.ifNotSet;
    const span = document.createElement('span');
    span.setAttribute('id', `o_${page}`);

    const buttonSelector = document.createElement('button');
    buttonSelector.classList.add('ug_selector', 'changeowner', 'btn', 'btn-link');
    buttonSelector.setAttribute('type', 'button');
    buttonSelector.setAttribute('data-page', page);
    buttonSelector.setAttribute('data-owner', element.dataset.owner);
    buttonSelector.setAttribute('data-username', username);
    buttonSelector.innerText = username;
    span.appendChild(buttonSelector);

    // Replace content
    const container = document.getElementById('o_' + page);
    container.parentNode.replaceChild(span, container);
  }

  /**
   * Group-related: Update the HTML view and show the original group
   */
  private restoreGroup(element: HTMLElement): void {
    const page = element.dataset.page;
    const groupname = element.dataset.groupname ?? element.dataset.ifNotSet;
    const span = document.createElement('span');
    span.setAttribute('id', `g_${page}`);

    const buttonSelector = document.createElement('button');
    buttonSelector.classList.add('ug_selector', 'changegroup', 'btn', 'btn-link');
    buttonSelector.setAttribute('type', 'button');
    buttonSelector.setAttribute('data-page', page);
    buttonSelector.setAttribute('data-group-id', element.dataset.groupId);
    buttonSelector.setAttribute('data-groupname', groupname);
    buttonSelector.innerText = groupname;
    span.appendChild(buttonSelector);

    // Replace content
    const container = document.getElementById('g_' + page);
    container.parentNode.replaceChild(span, container);
  }

  /**
   * Group-related: Set the new group by executing an ajax call
   */
  private changeGroup(element: HTMLElement): void {
    let page = element.dataset.page;
    const container: HTMLElement = document.getElementById('g_' + page);

    (new AjaxRequest(this.ajaxUrl)).post({
      action: 'change_group',
      page: page,
      groupUid: element.dataset.groupId,
      newGroupUid: (container.getElementsByTagName('select')[0] as HTMLSelectElement).value,
    }).then(async (response: AjaxResponse): Promise<void> => {
      // Replace content
      container.outerHTML = await response.resolve();
    });
  }

  /**
   * Group-related: Load the selector by executing an ajax call
   */
  private showChangeGroupSelector(element: HTMLElement): void {
    let page = element.dataset.page;
    (new AjaxRequest(this.ajaxUrl)).post({
      action: 'show_change_group_selector',
      page: page,
      groupUid: element.dataset.groupId,
      groupname: element.dataset.groupname,
    }).then(async (response: AjaxResponse): Promise<void> => {
      // Replace content
      document.getElementById('g_' + page).outerHTML = await response.resolve();
    });
  }

  private initializeCheckboxGroups(): void {
    const checkboxGroups: NodeListOf<HTMLInputElement> = document.querySelectorAll('[data-checkbox-group]');
    checkboxGroups.forEach((checkboxGroupCheckbox): void => {
      const permissionGroup = checkboxGroupCheckbox.dataset.checkboxGroup;
      const permissionValue = parseInt(checkboxGroupCheckbox.value, 10);
      Permissions.setPermissionCheckboxes(permissionGroup, permissionValue);
    });
  }

  /**
   * initializes events using deferred bound to document
   * so AJAX reloads are no problem
   */
  private initializeEvents(): void {
    const containerSelector = document.querySelector(this.options.containerSelector);
    const editControllerSelector = document.querySelector(this.options.editControllerSelector);

    if (containerSelector !== null) {
      new RegularEvent('click', (e: Event, currentTarget: Element): void => {
        e.preventDefault();
        this.setPermissions(currentTarget as HTMLElement);
      }).delegateTo(containerSelector, '.change-permission');

      // Click event for lock state
      new RegularEvent('click', (e: Event, currentTarget: Element): void => {
        e.preventDefault();
        this.toggleEditLock(currentTarget as HTMLElement);
      }).delegateTo(containerSelector, '.editlock');

      // Click event to change owner
      new RegularEvent('click', (e: Event, currentTarget: Element): void => {
        e.preventDefault();
        this.showChangeOwnerSelector(currentTarget as HTMLElement);
      }).delegateTo(containerSelector, '.changeowner');

      // Click event to change group
      new RegularEvent('click', (e: Event, currentTarget: Element): void => {
        e.preventDefault();
        this.showChangeGroupSelector(currentTarget as HTMLElement);
      }).delegateTo(containerSelector, '.changegroup');

      // Add click handler for restoring previous owner
      new RegularEvent('click', (e: Event, currentTarget: Element): void => {
        e.preventDefault();
        this.restoreOwner(currentTarget as HTMLElement);
      }).delegateTo(containerSelector, '.restoreowner');

      // Add click handler for saving owner
      new RegularEvent('click', (e: Event, currentTarget: Element): void => {
        e.preventDefault();
        this.changeOwner(currentTarget as HTMLElement);
      }).delegateTo(containerSelector, '.saveowner');

      // Add click handler for restoring previous group
      new RegularEvent('click', (e: Event, currentTarget: Element): void => {
        e.preventDefault();
        this.restoreGroup(currentTarget as HTMLElement);
      }).delegateTo(containerSelector, '.restoregroup');

      // Add click handler for saving group
      new RegularEvent('click', (e: Event, currentTarget: Element): void => {
        e.preventDefault();
        this.changeGroup(currentTarget as HTMLElement);
      }).delegateTo(containerSelector, '.savegroup');
    }

    if (editControllerSelector !== null) {
      // Click events to change permissions (in template Edit.html)
      new RegularEvent('click', (e: Event, currentTarget: Element): void => {
        const args = (currentTarget as HTMLElement).dataset.checkChangePermissions.split(',').map((item: string) => item.trim());
        Permissions.updatePermissionValue.apply(this, args);
      }).delegateTo(editControllerSelector, '[data-check-change-permissions]');
    }
  }
}

export default new Permissions();
