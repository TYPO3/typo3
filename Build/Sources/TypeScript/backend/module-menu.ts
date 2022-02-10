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
import {ScaffoldIdentifierEnum} from './enum/viewport/scaffold-identifier';
import {getRecordFromName, Module, ModuleState} from './module';
import $ from 'jquery';
import PersistentStorage from './storage/persistent';
import Viewport from './viewport';
import ClientRequest from './event/client-request';
import TriggerRequest from './event/trigger-request';
import InteractionRequest from './event/interaction-request';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import RegularEvent from '@typo3/core/event/regular-event';
import {ModuleStateStorage} from './storage/module-state-storage';

/**
 * Class to render the module menu and handle the BE navigation
 * Module: @typo3/backend/module-menu
 */
class ModuleMenu {
  private loadedModule: string = null;
  private spaceKeyPressedOnCollapsible: boolean = false;

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

  /**
   * @param {boolean} collapse
   */
  private static toggleMenu(collapse?: boolean): void {
    const $mainContainer = $(ScaffoldIdentifierEnum.scaffold);
    const expandedClass = 'scaffold-modulemenu-expanded';

    if (typeof collapse === 'undefined') {
      collapse = $mainContainer.hasClass(expandedClass);
    }
    $mainContainer.toggleClass(expandedClass, !collapse);
    if (!collapse) {
      $('.scaffold')
        .removeClass('scaffold-search-expanded')
        .removeClass('scaffold-toolbar-expanded');
    }

    // Persist collapsed state in the UC of the current user
    PersistentStorage.set(
      'BackendComponents.States.typo3-module-menu',
      {
        collapsed: collapse,
      },
    );
  }

  /**
   * @param {HTMLButtonElement} button
   */
  private static toggleModuleGroup(button: HTMLElement): void {
    const moduleGroup = button.closest('.modulemenu-group');
    const moduleGroupContainer = moduleGroup.querySelector('.modulemenu-group-container');
    const ariaExpanded = button.attributes.getNamedItem('aria-expanded').value === 'true';

    if (ariaExpanded) {
      ModuleMenu.addCollapsedMainMenuItem(button.id);
    } else {
      ModuleMenu.removeCollapseMainMenuItem(button.id);
    }

    moduleGroup.classList.toggle('modulemenu-group-collapsed', ariaExpanded);
    moduleGroup.classList.toggle('modulemenu-group-expanded', !ariaExpanded);

    button.attributes.getNamedItem('aria-expanded').value = (!ariaExpanded).toString();

    $(moduleGroupContainer).stop().slideToggle();
  }

  /**
   * @param {string} module
   */
  private static highlightModuleMenuItem(module: string): void {
    $('.modulemenu-action.modulemenu-action-active')
      .removeClass('modulemenu-action-active')
      .removeAttr('aria-current');
    $('#' + module)
      .addClass('modulemenu-action-active')
      .attr('aria-current', 'location');
  }

  private static getPreviousItem(item: HTMLButtonElement): HTMLButtonElement {
    let previousParent = item.parentElement.previousElementSibling; // previous <li>
    if (previousParent === null) {
      return ModuleMenu.getLastItem(item);
    }
    return previousParent.firstElementChild as HTMLButtonElement; // the <button>
  }

  private static getNextItem(item: HTMLButtonElement): HTMLButtonElement {
    let nextParent = item.parentElement.nextElementSibling; // next <li>
    if (nextParent === null) {
      return ModuleMenu.getFirstItem(item);
    }
    return nextParent.firstElementChild as HTMLButtonElement; // the <button>
  }

  private static getFirstItem(item: HTMLButtonElement): HTMLButtonElement {
    // from <button> up to <ul> and down to <button> of first <li>
    return item.parentElement.parentElement.firstElementChild.firstElementChild as HTMLButtonElement;
  }

  private static getLastItem(item: HTMLButtonElement): HTMLButtonElement {
    // from <button> up to <ul> and down to <button> of first <li>
    return item.parentElement.parentElement.lastElementChild.firstElementChild as HTMLButtonElement;
  }

  private static getParentItem(item: HTMLButtonElement): HTMLButtonElement {
    // from <button> up to <ul> and the <li> above and down down its <button>
    return item.parentElement.parentElement.parentElement.firstElementChild as HTMLButtonElement;
  }

  private static getFirstChildItem(item: HTMLButtonElement): HTMLButtonElement {
    // the first <li> of the <ul> following the <button>, then down down its <button>
    return item.nextElementSibling.firstElementChild.firstElementChild as HTMLButtonElement;
  }

  private static getLastChildItem(item: HTMLButtonElement): HTMLButtonElement {
    // the first <li> of the <ul> following the <button>, then down down its <button>
    return item.nextElementSibling.lastElementChild.firstElementChild as HTMLButtonElement;
  }

  constructor() {
    $((): void => this.initialize());
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
        ModuleMenu.highlightModuleMenuItem(this.loadedModule);
      }
    });
  }

  public getCurrentModule(): string|null {
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
    const moduleData = getRecordFromName(name);
    return this.loadModuleComponents(
      moduleData,
      params,
      new ClientRequest('typo3.showModule', event),
    );
  }

  private initialize(): void {
    if (document.querySelector('.t3js-modulemenu') === null) {
      return;
    }

    let deferred = $.Deferred();
    deferred.resolve();

    deferred.then((): void => {
      this.initializeModuleMenuEvents();
      Viewport.Topbar.Toolbar.registerEvent(() => {
        // Only initialize top bar events when top bar exists.
        // E.g. install tool has no top bar
        if(document.querySelector('.t3js-scaffold-toolbar')) {
          this.initializeTopBarEvents()
        }
      });
    });
  }

  /**
   * Implement the complete keyboard navigation of the menus
   */
  private keyboardNavigation(e: KeyboardEvent, target: HTMLButtonElement, trackSpaceKey: boolean = false): void {
    const level = target.parentElement.attributes.getNamedItem('data-level').value;
    let item = null;
    if (trackSpaceKey) {
      this.spaceKeyPressedOnCollapsible = false; // only for tracking t3js-modulemenu!!!
    }

    switch (e.code) {
      case 'ArrowUp':
        item = ModuleMenu.getPreviousItem(target);
        break;
      case 'ArrowDown':
        item = ModuleMenu.getNextItem(target);
        break;
      case 'ArrowLeft':
        if (level === '1' && target.classList.contains('t3js-modulemenu-collapsible')) {
          if (target.attributes.getNamedItem('aria-expanded').value === 'false') {
            ModuleMenu.toggleModuleGroup(target);
          }
          item = ModuleMenu.getLastChildItem(target);
        } else if (level === '2') {
          item = ModuleMenu.getPreviousItem(ModuleMenu.getParentItem(target));
        }
        break;
      case 'ArrowRight':
        if (level === '1' && target.classList.contains('t3js-modulemenu-collapsible')) {
          if (target.attributes.getNamedItem('aria-expanded').value === 'false') {
            ModuleMenu.toggleModuleGroup(target);
          }
          item = ModuleMenu.getFirstChildItem(target);
        } else if (level === '2') {
          item = ModuleMenu.getNextItem(ModuleMenu.getParentItem(target));
        }
        break;
      case 'Home':
        if (e.ctrlKey && level === '2') {
          item = ModuleMenu.getFirstItem(ModuleMenu.getParentItem(target));
        } else {
          item = ModuleMenu.getFirstItem(target);
        }
        break;
      case 'End':
        if (e.ctrlKey && level === '2') {
          item = ModuleMenu.getLastItem(ModuleMenu.getParentItem(target));
        } else {
          item = ModuleMenu.getLastItem(target);
        }
        break;
      case 'Space':
      case 'Enter':
        if (level === '1' && target.classList.contains('t3js-modulemenu-collapsible')) {
          if (e.code === 'Enter') {
            e.preventDefault(); // we do not want the click handler to run, need to prevent default immediately
          }
          ModuleMenu.toggleModuleGroup(target);
          if (target.attributes.getNamedItem('aria-expanded').value === 'true') {
            item = ModuleMenu.getFirstChildItem(target);
            if (e.code === 'Space') {
              this.spaceKeyPressedOnCollapsible = true; // focus shifts, so keyup will be sent to submodule
            }
          }
        }
        break;
      case 'Esc':
      case 'Escape':
        if (level === '2') {
          item = ModuleMenu.getParentItem(target);
          ModuleMenu.toggleModuleGroup(item);
        }
        break;
      default:
        item = null;
    }
    if (item !== null) {
      if (!e.defaultPrevented) {
        e.preventDefault();
      }
      item.focus();
    }
  }

  private initializeModuleMenuEvents(): void {
    const moduleMenu = document.querySelector('.t3js-modulemenu');

    const preventSpace = function(this: ModuleMenu, e: KeyboardEvent): void {
      if (e.code === 'Space') {
        if (this.spaceKeyPressedOnCollapsible) { // keydown has been sent to module
          e.preventDefault(); // we do not want the click handler to run
          this.spaceKeyPressedOnCollapsible = false;
        }
      }
    }.bind(this);

    new RegularEvent('keydown', this.keyboardNavigation).delegateTo(moduleMenu, '.t3js-modulemenu-action');

    moduleMenu.querySelectorAll('[data-level="2"] a.t3js-modulemenu-action[href]').forEach( (item: Element): void => {
      item.addEventListener('keyup', preventSpace);
    });

    new RegularEvent('keyup', (e: KeyboardEvent, target: HTMLButtonElement): void => {
      if (e.code === 'Space') {
        e.preventDefault(); // we do not want the click handler to run
      }
    }).delegateTo(moduleMenu, '.t3js-modulemenu-collapsible');

    new RegularEvent('click', (e: Event, target: HTMLElement): void => {
      e.preventDefault();
      this.showModule(target.id, '', e);
    }).delegateTo(moduleMenu, 'a.t3js-modulemenu-action[href]');


    new RegularEvent('click', (e: Event, target: HTMLElement): void => {
      e.preventDefault();
      ModuleMenu.toggleModuleGroup(target);
    }).delegateTo(moduleMenu, '.t3js-modulemenu-collapsible');
  }

  /**
   * Initialize events for label toggle and help menu
   */
  private initializeTopBarEvents(): void {
    const toolbar = document.querySelector('.t3js-scaffold-toolbar');

    new RegularEvent('keydown', (e: KeyboardEvent, target: HTMLButtonElement) => {
      this.keyboardNavigation(e, target);
    }).delegateTo(toolbar, '.t3js-modulemenu-action');

    new RegularEvent('click', (e: Event, target: HTMLElement): void => {
      e.preventDefault();
      this.showModule(target.id, '', e);
    }).delegateTo(toolbar, 'a.t3js-modulemenu-action[href]');

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
      const moduleData = getRecordFromName(moduleName);
      if (!moduleData.link) {
        return;
      }

      ModuleMenu.highlightModuleMenuItem(moduleName);
      $('#' + moduleName).focus();
      this.loadedModule = moduleName;

      // Synchronise navigation container if module is a standalone module (linked via ModuleMenu).
      // Do not hide navigation for intermediate modules like record_edit, which may be used
      // with our without a navigation component, depending on the context.
      if (moduleData.navigationComponentId) {
        Viewport.NavigationContainer.showComponent(moduleData.navigationComponentId);
      } else {
        Viewport.NavigationContainer.hide(false);
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
        Viewport.NavigationContainer.hide(true);
      }

      ModuleMenu.highlightModuleMenuItem(moduleName);
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
  private openInContentContainer(module: string, url: string, params: string, interactionRequest: InteractionRequest):  JQueryDeferred<TriggerRequest> {
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
