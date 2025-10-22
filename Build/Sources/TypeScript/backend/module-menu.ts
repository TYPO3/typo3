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

import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { ScaffoldIdentifierEnum } from './enum/viewport/scaffold-identifier';
import { flushModuleCache, type Module, ModuleSelector, type ModuleState, ModuleUtility } from '@typo3/backend/module';
import PersistentStorage from './storage/persistent';
import Viewport from './viewport';
import ClientRequest from './event/client-request';
import TriggerRequest from './event/trigger-request';
import type InteractionRequest from './event/interaction-request';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import RegularEvent from '@typo3/core/event/regular-event';
import { ModuleStateStorage } from './storage/module-state-storage';
import { selector } from '@typo3/core/literals';
import DocumentService from '@typo3/core/document-service';
import { Collapse } from 'bootstrap';
import { KeyTypesEnum } from '@typo3/backend/enum/key-types';

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
    DocumentService.ready().then((): void => {
      this.initialize();
    });
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
    if (params.includes('id')) {
      return params;
    }
    // get id
    let section = '';
    if (moduleData.navigationComponentId === '@typo3/backend/tree/page-tree-element') {
      section = 'web';
    } else {
      section = moduleData.name.split('_')[0];
    }
    const moduleStateStorage = ModuleStateStorage.current(section);
    if (moduleStateStorage.identifier) {
      params = 'id=' + encodeURIComponent(moduleStateStorage.identifier) + '&' + params;
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

  private static toggleModuleGroup(element: HTMLElement, expand?: boolean): void {
    const menuItem = ModuleMenu.getModuleMenuItemFromElement(element);
    const moduleGroup = menuItem.element.closest('.modulemenu-group');
    const moduleGroupContainer = moduleGroup.querySelector('.modulemenu-group-container');
    const collapseInstance = Collapse.getOrCreateInstance(moduleGroupContainer, {
      toggle: false // Do not auto-toggle on init.
    });

    if (expand === undefined) {
      // No intended state given: toggle state.
      expand = !menuItem.expanded;
    } else if (expand === menuItem.expanded) {
      // Intended state is already reached.
      return;
    }

    if (!expand) {
      ModuleMenu.addCollapsedMainMenuItem(menuItem.identifier);
      collapseInstance.hide();
    } else {
      ModuleMenu.removeCollapseMainMenuItem(menuItem.identifier);
      collapseInstance.show();
    }

    moduleGroup.classList.toggle('modulemenu-group-collapsed', !expand);
    moduleGroup.classList.toggle('modulemenu-group-expanded', expand);

    element.setAttribute('aria-expanded', (expand).toString());
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
    const menuElements = menu.querySelectorAll(ModuleMenuSelector.item + selector`[data-modulemenu-identifier="${module.name}"]`);
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
    const toolbarElements = toolbar.querySelectorAll(ModuleSelector.link + selector`[data-moduleroute-identifier="${module.name}"].dropdown-item`);
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
    const nextSibling = item.nextElementSibling;
    if (nextSibling && nextSibling.firstElementChild) {
      return nextSibling.firstElementChild.firstElementChild as HTMLElement;
    }
    return null;
  }

  private static hasExpandableChildren(item: HTMLElement): boolean {
    return item.nextElementSibling !== null && item.nextElementSibling.classList.contains('modulemenu-group-container');
  }

  /**
   * Refresh the HTML by fetching the menu again
   */
  public refreshMenu(): Promise<void> {
    return new AjaxRequest(TYPO3.settings.ajaxUrls.modulemenu).get().then(async (response: AjaxResponse): Promise<void> => {
      const result = await response.resolve();
      document.getElementById('modulemenu').outerHTML = result.menu;
      flushModuleCache();
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
   */
  public showModule(name: string, params?: string, event: Event|null = null, endpoint: string|null = null): Promise<void> {
    params = params || '';
    const moduleData = ModuleUtility.getFromName(name);
    return this.loadModuleComponents(
      moduleData,
      endpoint,
      params,
      new ClientRequest('typo3.showModule', event),
    );
  }

  private initialize(): void {
    if (document.querySelector(ModuleMenuSelector.menu) === null) {
      return;
    }

    this.initializeModuleMenuEvents();
    Viewport.Topbar.Toolbar.registerEvent(() => {
      // Only initialize top bar events when top bar exists.
      // E.g. install tool has no top bar
      if (document.querySelector('.t3js-scaffold-toolbar')) {
        this.initializeTopBarEvents();
      }
    });
  }

  /**
   * Implement the complete keyboard navigation of the menus
   */
  private keyboardNavigation(event: KeyboardEvent, target: HTMLElement): void {
    const menuItem = ModuleMenu.getModuleMenuItemFromElement(target);
    let item = null;

    switch (event.key) {
      case KeyTypesEnum.UP:
        item = ModuleMenu.getPreviousItem(menuItem.element);
        break;
      case KeyTypesEnum.DOWN:
        item = ModuleMenu.getNextItem(menuItem.element);
        break;
      case KeyTypesEnum.LEFT:
        if (menuItem.collapsible && menuItem.expanded) {
          // If expanded and has children, collapse it
          ModuleMenu.toggleModuleGroup(menuItem.element, false);
        } else if (menuItem.level > 1) {
          // Otherwise navigate to parent
          item = ModuleMenu.getParentItem(menuItem.element);
        }
        break;
      case KeyTypesEnum.RIGHT:
        if (menuItem.collapsible) {
          if (!menuItem.expanded) {
            // Expand if collapsed
            ModuleMenu.toggleModuleGroup(menuItem.element, true);
          } else {
            // Already expanded, navigate to first child
            item = ModuleMenu.getFirstChildItem(menuItem.element);
          }
        } else if (menuItem.level === 2 && ModuleMenu.hasExpandableChildren(menuItem.element)) {
          // Level 2 item with level 3 children - expand to show them
          ModuleMenu.toggleModuleGroup(menuItem.element, true);
          item = ModuleMenu.getFirstChildItem(menuItem.element);
        }
        break;
      case KeyTypesEnum.HOME:
        if (event.ctrlKey && menuItem.level > 1) {
          item = document.querySelector(ModuleMenuSelector.menu + ' ' + ModuleMenuSelector.item) as HTMLElement;
          break;
        }
        item = ModuleMenu.getFirstItem(menuItem.element);
        break;
      case KeyTypesEnum.END:
        if (event.ctrlKey && menuItem.level > 1) {
          item = ModuleMenu.getLastItem(document.querySelector(ModuleMenuSelector.menu + ' ' + ModuleMenuSelector.item));
        } else {
          item = ModuleMenu.getLastItem(menuItem.element);
        }
        break;
      case KeyTypesEnum.SPACE:
      case KeyTypesEnum.ENTER:
        // we do not want the click handler to run, need to prevent default immediately
        event.preventDefault();

        if (event.repeat) {
          // Ignore repeated event invocation
          break;
        }

        if (menuItem.collapsible) {
          // Always select the first element of sub-menu on ENTER/SPACE. Open sub-menu if necessary.
          ModuleMenu.toggleModuleGroup(menuItem.element, true);
          item = ModuleMenu.getFirstChildItem(menuItem.element);
        } else if (menuItem.level === 2 && ModuleMenu.hasExpandableChildren(menuItem.element)) {
          // Level 2 item with level 3 children - toggle the expansion
          ModuleMenu.toggleModuleGroup(menuItem.element);
        } else {
          // Regular clickable item without children (level 2 without children or level 3)
          menuItem.element.click();
        }
        break;
      case KeyTypesEnum.ESCAPE:
        // Close sub-menu on ESCAPE either from inside sub-menu or when trigger-button is focused.
        if (menuItem.level > 1) {
          item = ModuleMenu.getParentItem(menuItem.element);
        } else if (menuItem.level === 1 && menuItem.collapsible) {
          item = menuItem.element;
        }
        if (item !== null) {
          ModuleMenu.toggleModuleGroup(item, false);
        }
        break;
      default:
        item = null;
    }
    if (item !== null) {
      // Disable additional scrolling e.g. triggered by arrow-keypress.
      event.preventDefault();
      item.focus();
    }
  }

  private initializeModuleMenuEvents(): void {
    const moduleMenu = document.querySelector(ModuleMenuSelector.menu);

    new RegularEvent('keydown', this.keyboardNavigation)
      .delegateTo(moduleMenu, ModuleMenuSelector.item);

    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      event.preventDefault();

      // Check if this is a level 2 link with level 3 children
      const menuItem = ModuleMenu.getModuleMenuItemFromElement(target);
      if (menuItem.level === 2 && ModuleMenu.hasExpandableChildren(target)) {
        // Level 2 item with level 3 children - check if click was on the indicator
        const clickedElement = event.target as HTMLElement;
        if (clickedElement.classList.contains('modulemenu-indicator') ||
            clickedElement.closest('.modulemenu-indicator')) {
          // Click was on indicator - toggle expansion only
          event.stopPropagation();
          ModuleMenu.toggleModuleGroup(target);
          return;
        }
      }

      // Regular link click - navigate to module
      const moduleRoute = ModuleUtility.getRouteFromElement(target);
      this.showModule(moduleRoute.identifier, moduleRoute.params, event);
    }).delegateTo(moduleMenu, ModuleSelector.link);

    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      event.preventDefault();
      ModuleMenu.toggleModuleGroup(target);
    }).delegateTo(moduleMenu, ModuleMenuSelector.collapsible);

    new RegularEvent('shown.bs.collapse', (event: Event, target: HTMLElement): void => {
      // Wait for collapsible to become fully visible, then scroll module-group into view if necessary.
      target.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
    }).delegateTo(moduleMenu, '.modulemenu-group');
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
   */
  private loadModuleComponents(
    moduleData: Module,
    endpoint: string | null,
    params: string,
    interactionRequest: InteractionRequest,
  ): Promise<void> {
    const moduleName = moduleData.name;

    // Allow other components e.g. Formengine to cancel switching between modules
    // (e.g. you have unsaved changes in the form)
    const promise = Viewport.ContentContainer.beforeSetUrl(interactionRequest);
    promise.then((): void => {
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
        endpoint ?? moduleData.link,
        params,
        new TriggerRequest(
          'typo3.loadModuleComponents',
          interactionRequest,
        ),
      );
    });
    return promise;
  }

  private openInContentContainer(
    module: string,
    url: string,
    params: string,
    interactionRequest: InteractionRequest
  ): Promise<void> {
    const urlToLoad = url + (params ? (url.includes('?') ? '&' : '?') + params : '');
    return Viewport.ContentContainer.setUrl(
      urlToLoad,
      new TriggerRequest('typo3.openInContentFrame', interactionRequest),
      module
    );
  }
}

interface ModuleMenuNamespace {
  App: ModuleMenu;
}

let moduleMenuApp: ModuleMenuNamespace = top?.TYPO3?.ModuleMenu;

if (!moduleMenuApp) {
  moduleMenuApp = {
    App: new ModuleMenu(),
  };
  if (top.TYPO3 !== undefined) {
    top.TYPO3.ModuleMenu = moduleMenuApp;
  }
}

export default moduleMenuApp;
