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
import ModuleMenu = require('TYPO3/CMS/Backend/ModuleMenu');
import Viewport = require('TYPO3/CMS/Backend/Viewport');
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');

enum Identifiers {
  containerSelector = '#typo3-cms-workspaces-backend-toolbaritems-workspaceselectortoolbaritem',
  activeMenuItemLinkSelector = '.dropdown-menu .selected',
  menuItemSelector = '.t3js-workspace-item',
  menuItemLinkSelector = '.t3js-workspaces-switchlink',
  toolbarItemSelector = '.dropdown-toggle',
  workspaceModuleLinkSelector = '.t3js-workspaces-modulelink',
}

enum Classes {
  workspaceBodyClass = 'typo3-in-workspace',
  workspacesTitleInToolbarClass = 'toolbar-item-name',
}

/**
 * Module: TYPO3/CMS/Workspaces/Toolbar/WorkspacesMenu
 * toolbar menu for the workspaces functionality to switch between the workspaces
 * and jump to the workspaces module
 */
class WorkspacesMenu {
  /**
   * Refresh the page tree
   */
  private static refreshPageTree(): void {
    if (Viewport.NavigationContainer && Viewport.NavigationContainer.PageTree) {
      Viewport.NavigationContainer.PageTree.refreshTree();
    }
  }

  private static updateWorkspaceState() {
    // This is a poor-mans state update in case the current active workspace has been renamed
    const selectedWorkspaceLink: HTMLElement = document.querySelector(Identifiers.containerSelector + ' .t3js-workspace-item.selected .t3js-workspaces-switchlink');
    if (selectedWorkspaceLink !== null) {
      const workspaceId = parseInt(selectedWorkspaceLink.dataset.workspaceid, 10);
      const title = selectedWorkspaceLink.innerText.trim();

      top.TYPO3.configuration.inWorkspace = workspaceId !== 0;
      top.TYPO3.Backend.workspaceTitle = top.TYPO3.configuration.inWorkspace ? title : '';
    }
  }

  /**
   * adds the workspace title to the toolbar next to the username
   *
   * @param {String} workspaceTitle
   */
  private static updateTopBar(workspaceTitle: string): void {
    $('.' + Classes.workspacesTitleInToolbarClass, Identifiers.containerSelector).remove();

    if (workspaceTitle && workspaceTitle.length) {
      let title = $('<span>', {
        'class': Classes.workspacesTitleInToolbarClass,
      }).text(workspaceTitle);
      $(Identifiers.toolbarItemSelector, Identifiers.containerSelector).append(title);
    }
  }

  private static updateBackendContext(): void {
    let topBarTitle = '';
    WorkspacesMenu.updateWorkspaceState();

    if (TYPO3.configuration.inWorkspace) {
      $('body').addClass(Classes.workspaceBodyClass);
      topBarTitle = top.TYPO3.Backend.workspaceTitle || TYPO3.lang['Workspaces.workspaceTitle'];
    } else {
      $('body').removeClass(Classes.workspaceBodyClass);
    }

    WorkspacesMenu.updateTopBar(topBarTitle);
  }

  constructor() {
    Viewport.Topbar.Toolbar.registerEvent((): void => {
      this.initializeEvents();
      WorkspacesMenu.updateBackendContext();
    });

    new RegularEvent('typo3:datahandler:process', (e: CustomEvent): void => {
      const payload = e.detail.payload;
      if (payload.table === 'sys_workspace' && payload.action === 'delete' && payload.hasErrors === false) {
        Viewport.Topbar.refresh();
      }
    }).bindTo(document);
  }

  /**
   * Changes the data in the module menu and the updates the backend context
   * This method is also used in the workspaces backend module.
   *
   * @param {Number} id the workspace ID
   */
  public performWorkspaceSwitch(id: number): void {
    // first remove all checks, then set the check in front of the selected workspace
    const stateActiveClass = 'fa fa-check';
    const stateInactiveClass = 'fa fa-empty-empty';

    // remove "selected" class and checkmark
    $(Identifiers.activeMenuItemLinkSelector + ' i', Identifiers.containerSelector)
      .removeClass(stateActiveClass)
      .addClass(stateInactiveClass);
    $(Identifiers.activeMenuItemLinkSelector, Identifiers.containerSelector).removeClass('selected');

    // add "selected" class and checkmark
    const $activeElement = $(Identifiers.menuItemLinkSelector + '[data-workspaceid=' + id + ']', Identifiers.containerSelector);
    const $menuItem = $activeElement.closest(Identifiers.menuItemSelector);
    $menuItem.find('i')
      .removeClass(stateInactiveClass)
      .addClass(stateActiveClass);
    $menuItem.addClass('selected');

    WorkspacesMenu.updateBackendContext();
  }

  private initializeEvents(): void {
    $(Identifiers.containerSelector).on('click', Identifiers.workspaceModuleLinkSelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();
      ModuleMenu.App.showModule((<HTMLAnchorElement>evt.currentTarget).dataset.module);
    });

    $(Identifiers.containerSelector).on('click', Identifiers.menuItemLinkSelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();
      this.switchWorkspace(parseInt((<HTMLAnchorElement>evt.currentTarget).dataset.workspaceid, 10));
    });
  }

  private switchWorkspace(workspaceId: number): void {
    (new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_switch)).post({
      workspaceId: workspaceId,
      pageId: top.fsMod.recentIds.web
    }).then(async (response: AjaxResponse): Promise<any> => {
      const data = await response.resolve();
      if (!data.workspaceId) {
        data.workspaceId = 0;
      }

      this.performWorkspaceSwitch(parseInt(data.workspaceId, 10));

      // append the returned page ID to the current module URL
      if (data.pageId) {
        top.fsMod.recentIds.web = data.pageId;
        let url = TYPO3.Backend.ContentContainer.getUrl();
        url += (!url.includes('?') ? '?' : '&') + '&id=' + data.pageId;
        WorkspacesMenu.refreshPageTree();
        Viewport.ContentContainer.setUrl(url);

        // when in web module reload, otherwise send the user to the web module
      } else if (top.currentModuleLoaded.startsWith('web_')) {
        WorkspacesMenu.refreshPageTree();

        if (top.currentModuleLoaded === 'web_WorkspacesWorkspaces') {
          // Reload the workspace module and override the workspace id
          ModuleMenu.App.showModule(top.currentModuleLoaded, 'workspace=' + workspaceId);
        } else {
          ModuleMenu.App.reloadFrames();
        }
      } else if (TYPO3.configuration.pageModule) {
        ModuleMenu.App.showModule(TYPO3.configuration.pageModule);
      }

      // reload the module menu
      ModuleMenu.App.refreshMenu();
    });
  }
}

const workspacesMenu = new WorkspacesMenu();
// expose the module in a global object
TYPO3.WorkspacesMenu = workspacesMenu;

export = workspacesMenu;
