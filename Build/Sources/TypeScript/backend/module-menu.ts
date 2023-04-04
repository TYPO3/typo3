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
import { ScaffoldIdentifierEnum } from './enum/viewport/scaffold-identifier';
import { Module, ModuleSelector, ModuleState, ModuleUtility } from '@typo3/backend/module';
import $ from 'jquery';
import PersistentStorage from './storage/persistent';
import Viewport from './viewport';
import ClientRequest from './event/client-request';
import TriggerRequest from './event/trigger-request';
import InteractionRequest from './event/interaction-request';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import RegularEvent from '@typo3/core/event/regular-event';
import { ModuleStateStorage } from './storage/module-state-storage';

enum ModuleMenuSelector {
  menu = '[data-modulemenu]',
  item = '[data-modulemenu-identifier]',
  collapsible = '[data-modulemenu-collapsible="true"]',
}

interface ModuleMenuItem {
  identifier: string,
  collapsible: boolean,
  expanded: boolean,
  level: number | null,
  element: HTMLElement
}

/**
 * Class to render the module menu and handle the BE navigation
 * Module: @typo3/backend/module-menu
 */
class ModuleMenu {
  private loadedModule: string = null;

  constructor() {
    // @todo: DocumentService.ready() doesn't work here as it apparently is too fast or whatever.
    //        It keeps breaking acceptance tests. Bonkers.
    $((): void => this.initialize());
  }

  private static getModuleMenuItemFromElement(element: HTMLElement): ModuleMenuItem {
    const item: ModuleMenuItem = {
      identifier: element.dataset.modulemenuIdentifier,
      level: element.parentElement.dataset.modulemenuLevel ? parseInt(element.parentElement.dataset.modulemenuLevel, 10) : null,
      collapsible: element.dataset.modulemenuCollapsible === 'true',
      expanded: element.attributes.getNamedItem('aria-expanded')?.value === 'true',
      element: element,
    };

    return item;
  }

  /**
   * Fetches all module menu elements in the local storage that should be collapsed
   *
   * @returns {Object}
   */
  private static getCollapsedMainMenuItems(): { [key: string]: boolean } {
    if (PersistentStorage.isset('modulemenu')) {
      return JSON.parse(PersistentStorage.get('modulemenu'));
    } else {
      return {};
    }
  }

  /**
   * Adds a module menu item to the local storage
   *
   * @param {string} item
   */
  private static addCollapsedMainMenuItem(item: string): void {
    const existingItems = ModuleMenu.getCollapsedMainMenuItems();
    existingItems[item] = true;
    PersistentStorage.set('modulemenu', JSON.stringify(existingItems));
  }

  /**
   * Removes a module menu item from the local storage
   *
   * @param {string} item
   */
  private static removeCollapseMainMenuItem(item: string): void {
    const existingItems = this.getCollapsedMainMenuItems();
    delete existingItems[item];
    PersistentStorage.set('modulemenu', JSON.stringify(existingItems));
  }

  /**
   * Prepends previously saved record id to the url params
   *
   * @param {Object} moduleData
   * @param {string} params query string parameters for module url
   * @return {string}
   */
  private static includeId(moduleData: Module, params: string): string {
    if (!moduleData.navigationComponentId) {
      return params;
    }
    // get id
    let section = '';
    if (moduleData.navigationComponentId === '@typo3/backend/page-tree/page-tree-element') {
      section = 'web';
    } else {
      section = moduleData.name.split('_')[0];
    }
    const moduleStateStorage = ModuleStateStorage.current(section);
    if (moduleStateStorage.selection) {
      params = 'id=' + moduleStateStorage.selection + '&' + params;
    }
    return params;
  }

  private static toggleMenu(collapse?: boolean): void {
    const scaffold = document.querySelector(ScaffoldIdentifierEnum.scaffold);
    const expandedClass = 'scaffold-modulemenu-expanded';

    if (typeof collapse === 'undefined') {
      collapse = scaffold.classList.contains(expandedClass);
    }
    scaffold.classList.toggle(expandedClass, !collapse);

    if (!collapse) {
      scaffold.classList.remove('scaffold-toolbar-expanded');
    }

    // Persist collapsed state in the UC of the current user
    PersistentStorage.set(
      'BackendComponents.States.typo3-module-menu',
      {
        collapsed: collapse,
      },
    );
  }

  private static toggleModuleGroup(element: HTMLElement): void {
    const menuItem = ModuleMenu.getModuleMenuItemFromElement(element);
    const moduleGroup = menuItem.element.closest('.modulemenu-group');
    const moduleGroupContainer = moduleGroup.querySelector('.modulemenu-group-container');

    if (menuItem.expanded) {
      ModuleMenu.addCollapsedMainMenuItem(menuItem.identifier);
    } else {
      ModuleMenu.removeCollapseMainMenuItem(menuItem.identifier);
    }

    moduleGroup.classList.toggle('modulemenu-group-collapsed', menuItem.expanded);
    moduleGroup.classList.toggle('modulemenu-group-expanded', !menuItem.expanded);

    element.setAttribute('aria-expanded', (!menuItem.expanded).toString());

    $(moduleGroupContainer).stop().slideToggle();
  }

  private static highlightModule(identifier: string): void {
    // Handle modulemenu
    const menu = document.querySelector(ModuleMenuSelector.menu);
    menu.querySelectorAll(ModuleMenuSelector.item).forEach((element: Element) => {
      element.classList.remove('modulemenu-action-active');
      element.removeAttribute('aria-current');
    });

    // Handle toolbar
    //
    // This is a workaround, to ensure the toolbar module links are handled.
    // There is no dedicated module rendering in the toolbar, so we rely on this
    // workaround until this changes. Even the code matches the handling of
    // module-menu-items we keep this separate to show the problem here.
    const toolbar = document.querySelector('.t3js-scaffold-toolbar');
    toolbar.querySelectorAll(ModuleSelector.link + '.dropdown-item').forEach((element: Element) => {
      element.classList.remove('active');
      element.removeAttribute('aria-current');
    });

    const module = ModuleUtility.getFromName(identifier);
    this.highlightModuleMenuItem(module, true);
  }

  private static highlightModuleMenuItem(module: Module, current: boolean = true): void {
    // Handle modulemenu
    const menu = document.querySelector(ModuleMenuSelector.menu);
    const menuElements = menu.querySelectorAll(ModuleMenuSelector.item + '[data-modulemenu-identifier="' + module.name + '"]');
    menuElements.forEach((element: HTMLElement) => {
      element.classList.add('modulemenu-action-active');
      if (current) {
        element.setAttribute('aria-current', 'location');
      }
    });

    // Handle toolbar
    //
    // This is a workaround, to ensure the toolbar module links are handled.
    // There is no dedicated module rendering in the toolbar, so we rely on this
    // workaround until this changes. Even the code matches the handling of
    // module-menu-items we keep this separate to show the problem here.
    const toolbar = document.querySelector('.t3js-scaffold-toolbar');
    const toolbarElements = toolbar.querySelectorAll(ModuleSelector.link + '[data-moduleroute-identifier="' + module.name + '"].dropdown-item');
    toolbarElements.forEach((element: HTMLElement) => {
      element.classList.add('active');
      if (current) {
        element.setAttribute('aria-current', 'location');
      }
    });

    if (menuElements.length > 0 || toolbarElements.length > 0) {
      current = false;
    }

    if (module.parent !== '') {
      this.highlightModuleMenuItem(ModuleUtility.getFromName(module.parent), current);
    }
  }

  private static getPreviousItem(item: HTMLElement): HTMLElement {
    const previousParent = item.parentElement.previousElementSibling; // previous <li>
    if (previousParent === null) {
      return ModuleMenu.getLastItem(item);
    }
    return previousParent.firstElementChild as HTMLElement; // the <element>
  }

  private static getNextItem(item: HTMLElement): HTMLElement {
    const nextParent = item.parentElement.nextElementSibling; // next <li>
    if (nextParent === null) {
      return ModuleMenu.getFirstItem(item);
    }
    return nextParent.firstElementChild as HTMLElement; // the <element>
  }

  private static getFirstItem(item: HTMLElement): HTMLElement {
    // from <element> up to <ul> and down to <element> of first <li>
    return item.parentElement.parentElement.firstElementChild.firstElementChild as HTMLElement;
  }

  private static getLastItem(item: HTMLElement): HTMLElement {
    // from <element> up to <ul> and down to <element> of first <li>
    return item.parentElement.parentElement.lastElementChild.firstElementChild as HTMLElement;
  }

  private static getParentItem(item: HTMLElement): HTMLElement {
    // from <element> up to <ul> and the <li> above and down down its <element>
    return item.parentElement.parentElement.parentElement.firstElementChild as HTMLElement;
  }

  private static getFirstChildItem(item: HTMLElement): HTMLElement {
    // the first <li> of the <ul> following the <element>, then down down its <element>
    return item.nextElementSibling.firstElementChild.firstElementChild as HTMLElement;
  }

  /**
   * Refresh the HTML by fetching the menu again
   */
  public refreshMenu(): void {
    new AjaxRequest(TYPO3.settings.ajaxUrls.modulemenu).get().then(async (response: AjaxResponse): Promise<void> => {
      const result = await response.resolve();
      document.getElementById('modulemenu').outerHTML = result.menu;
      this.initializeModuleMenuEvents();
      if (this.loadedModule) {
        ModuleMenu.highlightModule(this.loadedModule);
      }
    });
  }

  public getCurrentModule(): string | null {
    return this.loadedModule;
  }

  /**
   * Reloads the frames
   *
   * Hint: This method can't be static (yet), as this must be bound to the ModuleMenu instance.
   */
  public reloadFrames(): void {
    Viewport.ContentContainer.refresh();
  }

  /**
   * Event handler called after clicking on the module menu item
   *
   * @param {string} name
   * @param {string} params
   * @param {Event} event
   * @returns {JQueryDeferred<TriggerRequest>}
   */
  public showModule(name: string, params?: string, event: Event = null): JQueryDeferred<TriggerRequest> {
    params = params || '';
    const moduleData = ModuleUtility.getFromName(name);
    return this.loadModuleComponents(
      moduleData,
      params,
      new ClientRequest('typo3.showModule', event),
    );
  }

  private initialize(): void {
    if (document.querySelector(ModuleMenuSelector.menu) === null) {
      return;
    }

    const deferred = $.Deferred();
    deferred.resolve();

    deferred.then((): void => {
      this.initializeModuleMenuEvents();
      Viewport.Topbar.Toolbar.registerEvent(() => {
        // Only initialize top bar events when top bar exists.
        // E.g. install tool has no top bar
        if (document.querySelector('.t3js-scaffold-toolbar')) {
          this.initializeTopBarEvents();
        }
      });
    });
  }

  /**
   * Implement the complete keyboard navigation of the menus
   */
  private keyboardNavigation(event: KeyboardEvent, target: HTMLElement): void {
    const menuItem = ModuleMenu.getModuleMenuItemFromElement(target);
    let item = null;

    switch (event.code) {
      case 'ArrowUp':
        item = ModuleMenu.getPreviousItem(menuItem.element);
        break;
      case 'ArrowDown':
        item = ModuleMenu.getNextItem(menuItem.element);
        break;
      case 'ArrowLeft':
        if (menuItem.level > 1) {
          item = ModuleMenu.getParentItem(menuItem.element);
        }
        break;
      case 'ArrowRight':
        if (menuItem.collapsible) {
          if (!menuItem.expanded) {
            ModuleMenu.toggleModuleGroup(menuItem.element);
          }
          item = ModuleMenu.getFirstChildItem(menuItem.element);
        }
        break;
      case 'Home':
        if (event.ctrlKey && menuItem.level > 1) {
          item = document.querySelector(ModuleMenuSelector.menu + ' ' + ModuleMenuSelector.item) as HTMLElement;
          break;
        }
        item = ModuleMenu.getFirstItem(menuItem.element);
        break;
      case 'End':
        if (event.ctrlKey && menuItem.level > 1) {
          item = ModuleMenu.getLastItem(document.querySelector(ModuleMenuSelector.menu + ' ' + ModuleMenuSelector.item));
        } else {
          item = ModuleMenu.getLastItem(menuItem.element);
        }
        break;
      case 'Space':
      case 'Enter':
        if (event.code === 'Space' || menuItem.collapsible) {
          // we do not want the click handler to run, need to prevent default immediately
          event.preventDefault();
        }
        if (menuItem.collapsible) {
          ModuleMenu.toggleModuleGroup(menuItem.element);
          if (menuItem.element.attributes.getNamedItem('aria-expanded').value === 'true') {
            item = ModuleMenu.getFirstChildItem(menuItem.element);
          }
        }
        break;
      case 'Esc':
      case 'Escape':
        if (menuItem.level > 1) {
          item = ModuleMenu.getParentItem(menuItem.element);
          ModuleMenu.toggleModuleGroup(item);
        }
        break;
      default:
        item = null;
    }
    if (item !== null) {
      item.focus();
    }
  }

  private initializeModuleMenuEvents(): void {
    const moduleMenu = document.querySelector(ModuleMenuSelector.menu);

    new RegularEvent('keydown', this.keyboardNavigation)
      .delegateTo(moduleMenu, ModuleMenuSelector.item);

    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      event.preventDefault();
      const moduleRoute = ModuleUtility.getRouteFromElement(target);
      this.showModule(moduleRoute.identifier, moduleRoute.params, event);
    }).delegateTo(moduleMenu, ModuleSelector.link);

    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      event.preventDefault();
      ModuleMenu.toggleModuleGroup(target);
    }).delegateTo(moduleMenu, ModuleMenuSelector.collapsible);
  }

  /**
   * Initialize events for label toggle and help menu
   */
  private initializeTopBarEvents(): void {
    const toolbar = document.querySelector('.t3js-scaffold-toolbar');

    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      event.preventDefault();
      const moduleRoute = ModuleUtility.getRouteFromElement(target);
      this.showModule(moduleRoute.identifier, moduleRoute.params, event);
    }).delegateTo(toolbar, ModuleSelector.link);

    new RegularEvent('click', (e: Event): void => {
      e.preventDefault();
      ModuleMenu.toggleMenu();
    }).bindTo(document.querySelector('.t3js-topbar-button-modulemenu'));

    new RegularEvent('click', (e: Event): void => {
      e.preventDefault();
      ModuleMenu.toggleMenu(true);
    }).bindTo(document.querySelector('.t3js-scaffold-content-overlay'));

    const moduleLoadListener = (evt: CustomEvent<ModuleState>) => {
      const moduleName = evt.detail.module;
      if (!moduleName || this.loadedModule === moduleName) {
        return;
      }
      const moduleData = ModuleUtility.getFromName(moduleName);
      if (!moduleData.link) {
        return;
      }

      ModuleMenu.highlightModule(moduleName);
      this.loadedModule = moduleName;

      // Synchronise navigation container if module is a standalone module (linked via ModuleMenu).
      // Do not hide navigation for intermediate modules like record_edit, which may be used
      // with our without a navigation component, depending on the context.
      if (moduleData.navigationComponentId) {
        Viewport.NavigationContainer.showComponent(moduleData.navigationComponentId);
      } else {
        Viewport.NavigationContainer.hide();
      }
    };
    document.addEventListener('typo3-module-load', moduleLoadListener);
    document.addEventListener('typo3-module-loaded', moduleLoadListener);
  }

  /**
   * Shows requested module (e.g. list/page)
   *
   * @param {Object} moduleData
   * @param {string} params
   * @param {InteractionRequest} [interactionRequest]
   * @return {jQuery.Deferred}
   */
  private loadModuleComponents(
    moduleData: Module,
    params: string,
    interactionRequest: InteractionRequest,
  ): JQueryDeferred<TriggerRequest> {
    const moduleName = moduleData.name;

    // Allow other components e.g. Formengine to cancel switching between modules
    // (e.g. you have unsaved changes in the form)
    const deferred = Viewport.ContentContainer.beforeSetUrl(interactionRequest);
    deferred.then((): void => {
      if (moduleData.navigationComponentId) {
        Viewport.NavigationContainer.showComponent(moduleData.navigationComponentId);
      } else {
        Viewport.NavigationContainer.hide();
      }

      ModuleMenu.highlightModule(moduleName);
      this.loadedModule = moduleName;
      params = ModuleMenu.includeId(moduleData, params);
      this.openInContentContainer(
        moduleName,
        moduleData.link,
        params,
        new TriggerRequest(
          'typo3.loadModuleComponents',
          interactionRequest,
        ),
      );
    });
    return deferred;
  }

  /**
   * @param {string} module
   * @param {string} url
   * @param {string} params
   * @param {InteractionRequest} interactionRequest
   * @returns {JQueryDeferred<TriggerRequest>}
   */
  private openInContentContainer(module: string, url: string, params: string, interactionRequest: InteractionRequest): JQueryDeferred<TriggerRequest> {
    const urlToLoad = url + (params ? (url.includes('?') ? '&' : '?') + params : '');
    return Viewport.ContentContainer.setUrl(
      urlToLoad,
      new TriggerRequest('typo3.openInContentFrame', interactionRequest),
      module
    );
  }
}

if (!top.TYPO3.ModuleMenu) {
  top.TYPO3.ModuleMenu = {
    App: new ModuleMenu(),
  };
}
const moduleMenuApp = top.TYPO3.ModuleMenu;

export default moduleMenuApp;
