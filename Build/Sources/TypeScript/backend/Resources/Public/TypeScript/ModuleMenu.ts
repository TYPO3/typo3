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

import {NavigationComponentInterface} from './Viewport/NavigationComponentInterface';
import {ScaffoldIdentifierEnum} from './Enum/Viewport/ScaffoldIdentifier';
import * as $ from 'jquery';
import PersistentStorage = require('./Storage/Persistent');
import Viewport = require('./Viewport');
import ClientRequest = require('./Event/ClientRequest');
import TriggerRequest = require('./Event/TriggerRequest');
import InteractionRequest = require('./Event/InteractionRequest');

interface Module {
  name: string;
  navigationComponentId: string;
  navigationFrameScript: string;
  navigationFrameScriptParam: string;
  link: string;
}

/**
 * Class to render the module menu and handle the BE navigation
 */
class ModuleMenu {
  private loadedModule: string = null;
  private loadedNavigationComponentId: string = '';

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
    if (!moduleData.navigationComponentId && !moduleData.navigationFrameScript) {
      return params;
    }
    // get id
    let section = '';
    if (moduleData.navigationComponentId === 'TYPO3/CMS/Backend/PageTree/PageTreeElement') {
      section = 'web';
    } else {
      section = moduleData.name.split('_')[0];
    }
    if (top.fsMod.recentIds[section]) {
      params = 'id=' + top.fsMod.recentIds[section] + '&' + params;
    }

    return params;
  }

  /**
   * @param {boolean} collapse
   */
  private static toggleMenu(collapse?: boolean): void {
    Viewport.NavigationContainer.cleanup();

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

    Viewport.doLayout();
  }

  /**
   * Gets the module properties from module menu markup (data attributes)
   *
   * @param {string} name
   * @returns {Module}
   */
  private static getRecordFromName(name: string): Module {
    const $subModuleElement = $('#' + name);
    return {
      name: name,
      navigationComponentId: $subModuleElement.data('navigationcomponentid'),
      navigationFrameScript: $subModuleElement.data('navigationframescript'),
      navigationFrameScriptParam: $subModuleElement.data('navigationframescriptparameters'),
      link: $subModuleElement.find('a').data('link'),
    };
  }

  /**
   * @param {string} module
   */
  private static highlightModuleMenuItem(module: string): void {
    $('.modulemenu-item.active').removeClass('active');
    $('#' + module).addClass('active');
  }

  constructor() {
    $((): void => this.initialize());
  }

  /**
   * Refresh the HTML by fetching the menu again
   */
  public refreshMenu(): void {
    $.ajax(TYPO3.settings.ajaxUrls.modulemenu).done((result: { [key: string]: string }): void => {
      $('#menu').replaceWith(result.menu);
      if (top.currentModuleLoaded) {
        ModuleMenu.highlightModuleMenuItem(top.currentModuleLoaded);
      }
      Viewport.doLayout();
    });
  }

  /**
   * Reloads the frames
   *
   * Hint: This method can't be static (yet), as this must be bound to the ModuleMenu instance.
   */
  public reloadFrames(): void {
    Viewport.NavigationContainer.refresh();
    Viewport.ContentContainer.refresh();
  }

  /**
   * Event handler called after clicking on the module menu item
   *
   * @param {string} name
   * @param {string} params
   * @param {JQueryEventObject} event
   * @returns {JQueryDeferred<TriggerRequest>}
   */
  public showModule(name: string, params?: string, event?: JQueryEventObject): JQueryDeferred<TriggerRequest> {
    params = params || '';
    const moduleData = ModuleMenu.getRecordFromName(name);
    return this.loadModuleComponents(
      moduleData,
      params,
      new ClientRequest('typo3.showModule', event ? event.originalEvent : null),
    );
  }

  private initialize(): void {
    const me = this;
    let deferred = $.Deferred();
    deferred.resolve();

    // load the start module
    if (top.startInModule && top.startInModule[0] && $('#' + top.startInModule[0]).length > 0) {
      deferred = this.showModule(
        top.startInModule[0],
        top.startInModule[1],
      );
    } else {
      // fetch first module
      const $firstModule = $('.t3js-mainmodule:first');
      if ($firstModule.attr('id')) {
        deferred = this.showModule(
          $firstModule.attr('id'),
        );
      }
      // else case: the main module has no entries, this is probably a backend
      // user with very little access rights, maybe only the logout button and
      // a user settings module in topbar.
    }

    deferred.then((): void => {
      me.initializeEvents();
    });
  }

  private initializeEvents(): void {
    $(document).on('click', '.modulemenu-group .modulemenu-group-header', (e: JQueryEventObject): void => {
      const $group = $(e.currentTarget).parent('.modulemenu-group');
      const $groupContainer = $group.find('.modulemenu-group-container');

      Viewport.NavigationContainer.cleanup();
      if ($group.hasClass('expanded')) {
        ModuleMenu.addCollapsedMainMenuItem($group.attr('id'));
        $group.addClass('collapsed').removeClass('expanded');
        $groupContainer.stop().slideUp().promise().done((): void => {
          Viewport.doLayout();
        });
      } else {
        ModuleMenu.removeCollapseMainMenuItem($group.attr('id'));
        $group.addClass('expanded').removeClass('collapsed');
        $groupContainer.stop().slideDown().promise().done((): void => {
          Viewport.doLayout();
        });
      }
    });

    // register clicking on sub modules
    $(document).on('click', '.modulemenu-item,.t3-menuitem-submodule', (evt: JQueryEventObject): void => {
      evt.preventDefault();
      this.showModule($(evt.currentTarget).attr('id'), '', evt);
    });
    $(document).on('click', '.t3js-topbar-button-modulemenu', (evt: JQueryEventObject): void => {
        evt.preventDefault();
        ModuleMenu.toggleMenu();
      },
    );
    $(document).on('click', '.t3js-scaffold-content-overlay', (evt: JQueryEventObject): void => {
        evt.preventDefault();
        ModuleMenu.toggleMenu(true);
      },
    );
    $(document).on('click', '.t3js-topbar-button-navigationcomponent', (evt: JQueryEventObject): void => {
      evt.preventDefault();
      Viewport.NavigationContainer.toggle();
    });
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
    deferred.then(
      $.proxy(
        (): void => {
          if (moduleData.navigationComponentId) {
            this.loadNavigationComponent(moduleData.navigationComponentId);
          } else if (moduleData.navigationFrameScript) {
            Viewport.NavigationContainer.show('typo3-navigationIframe');
            this.openInNavFrame(
              moduleData.navigationFrameScript,
              moduleData.navigationFrameScriptParam,
              new TriggerRequest(
                'typo3.loadModuleComponents',
                interactionRequest,
              ),
            );
          } else {
            Viewport.NavigationContainer.hide();
          }

          ModuleMenu.highlightModuleMenuItem(moduleName);
          this.loadedModule = moduleName;
          params = ModuleMenu.includeId(moduleData, params);
          this.openInContentFrame(
            moduleData.link,
            params,
            new TriggerRequest(
              'typo3.loadModuleComponents',
              interactionRequest,
            ),
          );

          // compatibility
          top.currentSubScript = moduleData.link;
          top.currentModuleLoaded = moduleName;

          Viewport.doLayout();
        },
        this,
      ),
    );

    return deferred;
  }

  /**
   * Renders registered (non-iframe) navigation component e.g. a page tree
   *
   * @param {string} navigationComponentId
   */
  private loadNavigationComponent(navigationComponentId: string): void {
    const me = this;

    Viewport.NavigationContainer.show(navigationComponentId);
    if (navigationComponentId === this.loadedNavigationComponentId) {
      return;
    }
    const componentCssName = navigationComponentId.replace(/[/]/g, '_');
    if (this.loadedNavigationComponentId !== '') {
      $('#navigationComponent-' + this.loadedNavigationComponentId.replace(/[/]/g, '_')).hide();
    }
    if ($('.t3js-scaffold-content-navigation [data-component="' + navigationComponentId + '"]').length < 1) {
      $('.t3js-scaffold-content-navigation')
        .append($('<div />', {
          'class': 'scaffold-content-navigation-component',
          'data-component': navigationComponentId,
          id: 'navigationComponent-' + componentCssName,
        }));
    }

    require([navigationComponentId], (NavigationComponent: NavigationComponentInterface): void => {
      NavigationComponent.initialize('#navigationComponent-' + componentCssName);
      Viewport.NavigationContainer.show(navigationComponentId);
      me.loadedNavigationComponentId = navigationComponentId;
    });
  }

  /**
   * @param {string} url
   * @param {string} params
   * @param {InteractionRequest} interactionRequest
   * @returns {JQueryDeferred<TriggerRequest>}
   */
  private openInNavFrame(url: string, params: string, interactionRequest: InteractionRequest): JQueryDeferred<TriggerRequest> {
    const navUrl = url + (params ? (url.indexOf('?') !== -1 ? '&' : '?') + params : '');
    const currentUrl = Viewport.NavigationContainer.getUrl();
    const deferred = Viewport.NavigationContainer.setUrl(
      url,
      new TriggerRequest('typo3.openInNavFrame', interactionRequest),
    );
    if (currentUrl !== navUrl) {
      // if deferred is already resolved, execute directly
      if (deferred.state() === 'resolved') {
        Viewport.NavigationContainer.refresh();
        // otherwise hand in future callback
      } else {
        deferred.then(Viewport.NavigationContainer.refresh);
      }
    }
    return deferred;
  }

  /**
   * @param {string} url
   * @param {string} params
   * @param {InteractionRequest} interactionRequest
   * @returns {JQueryDeferred<TriggerRequest>}
   */
  private openInContentFrame(url: string, params: string, interactionRequest: InteractionRequest):  JQueryDeferred<TriggerRequest> {
    let deferred;

    if (top.nextLoadModuleUrl) {
      deferred = Viewport.ContentContainer.setUrl(
        top.nextLoadModuleUrl,
        new TriggerRequest('typo3.openInContentFrame', interactionRequest),
      );
      top.nextLoadModuleUrl = '';
    } else {
      const urlToLoad = url + (params ? (url.indexOf('?') !== -1 ? '&' : '?') + params : '');
      deferred = Viewport.ContentContainer.setUrl(
        urlToLoad,
        new TriggerRequest('typo3.openInContentFrame', interactionRequest),
      );
    }

    return deferred;
  }
}

if (!top.TYPO3.ModuleMenu) {
  top.TYPO3.ModuleMenu = {
    App: new ModuleMenu(),
  };
}
const moduleMenuApp = top.TYPO3.ModuleMenu;

export = moduleMenuApp;
