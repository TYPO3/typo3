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
import ModuleMenu from '@typo3/backend/module-menu';
import Viewport from '@typo3/backend/viewport';
import RegularEvent from '@typo3/core/event/regular-event';
import {ModuleStateStorage} from '@typo3/backend/storage/module-state-storage';

enum Identifiers {
  topbarHeaderSelector = '.t3js-topbar-header',
  containerSelector = '#typo3-cms-workspaces-backend-toolbaritems-workspaceselectortoolbaritem',
  activeMenuItemLinkSelector = '.t3js-workspaces-switchlink.active',
  menuItemLinkSelector = '.t3js-workspaces-switchlink',
  toolbarItemSelector = '.dropdown-toggle',
  workspaceModuleLinkSelector = '.t3js-workspaces-modulelink',
}

enum Classes {
  workspaceBodyClass = 'typo3-in-workspace',
  workspacesTitleInToolbarClass = 'toolbar-item-name',
}

interface WorkspaceState {
  id: number
  title: string
  inWorkspace: boolean
}

/**
 * Module: @typo3/workspaces/toolbar/workspaces-menu
 * toolbar menu for the workspaces functionality to switch between the workspaces
 * and jump to the workspaces module
 */
class WorkspacesMenu {

  /**
   * Refresh the page tree
   */
  private static refreshPageTree(): void {
    document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
  }

  /**
   * Get workspace state from the current active menu item
   */
  private static getWorkspaceState(): null|WorkspaceState {
    const selectedWorkspaceLink: HTMLElement = document.querySelector(
      [Identifiers.containerSelector, Identifiers.activeMenuItemLinkSelector].join(' ')
    );
    if (selectedWorkspaceLink === null) {
      return null;
    }
    const workspaceId = parseInt(selectedWorkspaceLink.dataset.workspaceid || '0', 10);
    return {
      id: workspaceId,
      title: selectedWorkspaceLink.innerText.trim(),
      inWorkspace: workspaceId !== 0
    }
  }

  /**
   * Adds the workspace title to the toolbar next to the username
   * and adds the check icon to the currently active menu items.
   *
   * @param {WorkspaceState} workspaceState
   */
  private static updateTopBar(workspaceState: WorkspaceState): void {
    // Remove the workspace title in toolbar
    $('.' + Classes.workspacesTitleInToolbarClass, Identifiers.containerSelector).remove();

    // If we are in a workspace, add the corresponding title to the toolbar - if available
    if (workspaceState.inWorkspace && workspaceState.title) {
      $(Identifiers.toolbarItemSelector, Identifiers.containerSelector).append(
        $('<span>', {
          'class': Classes.workspacesTitleInToolbarClass,
        }).text(workspaceState.title)
      );
    }
  }

  /**
   * Updates backend context, especially the topbar
   */
  private static updateBackendContext(workspaceState: WorkspaceState = null): void {
    if (workspaceState === null) {
      // Fetch workspace state form workspace toolbar switch
      workspaceState = WorkspacesMenu.getWorkspaceState();
      if (workspaceState === null) {
        // If still no workspace state, return
        return;
      }
    }

    if (workspaceState.inWorkspace) {
      $(Identifiers.topbarHeaderSelector).addClass(Classes.workspaceBodyClass);
      if (!workspaceState.title) {
        workspaceState.title = TYPO3.lang['Workspaces.workspaceTitle'];
      }
    } else {
      $(Identifiers.topbarHeaderSelector).removeClass(Classes.workspaceBodyClass);
    }

    WorkspacesMenu.updateTopBar(workspaceState);
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
   * @param {String} title the workspace title
   */
  public performWorkspaceSwitch(id: number, title: string): void {
    // remove "active" class
    $(Identifiers.activeMenuItemLinkSelector, Identifiers.containerSelector).removeClass('active');

    // add "active" class to currently selected workspace
    $(Identifiers.menuItemLinkSelector + '[data-workspaceid=' + id + ']', Identifiers.containerSelector)
      ?.addClass('active');

    // Initiate backend context update
    WorkspacesMenu.updateBackendContext({id: id, title: title, inWorkspace: id !== 0});
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
      pageId: ModuleStateStorage.current('web').identifier
    }).then(async (response: AjaxResponse): Promise<any> => {
      const data = await response.resolve();
      if (!data.workspaceId) {
        data.workspaceId = 0;
      }

      this.performWorkspaceSwitch(data.workspaceId, data.title || '');

      const currentModule = ModuleMenu.App.getCurrentModule();
      // append the returned page ID to the current module URL
      if (data.pageId) {
        let url = TYPO3.Backend.ContentContainer.getUrl();
        url += (!url.includes('?') ? '?' : '&') + 'id=' + data.pageId;
        Viewport.ContentContainer.setUrl(url);

      } else if (currentModule === 'workspaces_admin') {
        // Reload the workspace module and override the workspace id
        ModuleMenu.App.showModule(currentModule, 'workspace=' + workspaceId);
      } else if (currentModule.startsWith('web_')) {
        // when in web module reload, otherwise send the user to the page module
        ModuleMenu.App.reloadFrames();
      } else if (data.pageModule) {
        ModuleMenu.App.showModule(data.pageModule);
      }

      // Refresh the pagetree if visible
      WorkspacesMenu.refreshPageTree();
      // reload the module menu
      ModuleMenu.App.refreshMenu();
    });
  }
}

const workspacesMenu = new WorkspacesMenu();
// expose the module in a global object
TYPO3.WorkspacesMenu = workspacesMenu;

export default workspacesMenu;
