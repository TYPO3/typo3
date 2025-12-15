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

import { ScaffoldIdentifierEnum } from '../enum/viewport/scaffold-identifier';
import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';
import { type Module, ModuleSelector, type ModuleState, ModuleUtility } from '@typo3/backend/module';
import { selector } from '@typo3/core/literals';
import moduleMenuApp from '@typo3/backend/module-menu';

class Toolbar {
  public static readonly toolbarSelector: string = '.t3js-scaffold-toolbar';

  public constructor() {
    DocumentService.ready().then((): void => {
      this.initializeEvents();
    });
  }

  public registerEvent(callback: () => void): void {
    DocumentService.ready().then(() => {
      callback();
    });
    new RegularEvent('t3-topbar-update', callback).bindTo(document.querySelector(ScaffoldIdentifierEnum.header));
  }

  private initializeEvents(): void {
    const toolbar = document.querySelector(Toolbar.toolbarSelector);
    if (toolbar === null) {
      return;
    }

    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      event.preventDefault();
      const moduleRoute = ModuleUtility.getRouteFromElement(target);
      moduleMenuApp.App.showModule(moduleRoute.identifier, moduleRoute.params, event);
    }).delegateTo(toolbar, ModuleSelector.link);

    const moduleLoadListener = (evt: CustomEvent<ModuleState>) => {
      const moduleName = evt.detail.module;
      if (!moduleName) {
        return;
      }
      const moduleData = ModuleUtility.getFromName(moduleName);
      if (!moduleData.link) {
        return;
      }
      this.highlightModule(moduleName);
    };
    document.addEventListener('typo3-module-load', moduleLoadListener);
    document.addEventListener('typo3-module-loaded', moduleLoadListener);
  }

  private highlightModule(identifier: string): void {
    const toolbar = document.querySelector(Toolbar.toolbarSelector);
    if (toolbar === null) {
      return;
    }

    // Clear existing highlights
    toolbar.querySelectorAll(ModuleSelector.link + '.dropdown-item').forEach((element: Element) => {
      element.classList.remove('active');
      element.removeAttribute('aria-current');
    });

    // Highlight the module and its parents
    const module = ModuleUtility.getFromName(identifier);
    this.highlightModuleItem(toolbar, module, true);
  }

  private highlightModuleItem(toolbar: Element, module: Module, current: boolean): boolean {
    const toolbarElements = toolbar.querySelectorAll(ModuleSelector.link + selector`[data-moduleroute-identifier="${module.name}"].dropdown-item`);
    toolbarElements.forEach((element: HTMLElement) => {
      element.classList.add('active');
      if (current) {
        element.setAttribute('aria-current', 'location');
      }
    });

    if (toolbarElements.length > 0) {
      current = false;
    }

    if (module.parent !== '') {
      this.highlightModuleItem(toolbar, ModuleUtility.getFromName(module.parent), current);
    }

    return toolbarElements.length > 0;
  }
}

export default Toolbar;
