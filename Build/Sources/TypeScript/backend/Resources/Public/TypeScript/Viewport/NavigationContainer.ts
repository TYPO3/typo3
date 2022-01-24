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

import {ScaffoldIdentifierEnum} from '../Enum/Viewport/ScaffoldIdentifier';
import {AbstractContainer} from './AbstractContainer';
import TriggerRequest = require('../Event/TriggerRequest');
import InteractionRequest = require('../Event/InteractionRequest');

class NavigationContainer extends AbstractContainer {
  private activeComponentId: string = '';

  private get parent(): HTMLElement
  {
    return document.querySelector(ScaffoldIdentifierEnum.scaffold);
  }

  private get container(): HTMLElement
  {
    return document.querySelector(ScaffoldIdentifierEnum.contentNavigation);
  }

  private get switcher(): HTMLElement|null
  {
    return document.querySelector(ScaffoldIdentifierEnum.contentNavigationSwitcher);
  }

  public constructor(consumerScope: any)
  {
    super(consumerScope);
  }

  /**
   * Renders registered (non-iframe) navigation component e.g. a page tree
   *
   * @param {string} navigationComponentId
   */
  public showComponent(navigationComponentId: string): void {
    const container = this.container;
    this.show(navigationComponentId);
    // Component is already loaded and active, nothing to do
    if (navigationComponentId === this.activeComponentId) {
      return;
    }
    if (this.activeComponentId !== '') {
      let activeComponentElement = container.querySelector('#navigationComponent-' + this.activeComponentId.replace(/[/]/g, '_')) as HTMLElement;
      if (activeComponentElement) {
        activeComponentElement.style.display = 'none';
      }
    }

    const componentCssName = navigationComponentId.replace(/[/]/g, '_');
    const navigationComponentElement = 'navigationComponent-' + componentCssName;

    // The component was already set up, so requiring the module again can be excluded.
    if (container.querySelectorAll('[data-component="' + navigationComponentId + '"]').length === 1) {
      this.show(navigationComponentId);
      this.activeComponentId = navigationComponentId;
      return;
    }

    require([navigationComponentId], (__esModule: any): void => {
      if (typeof __esModule.navigationComponentName === 'string') {
        const tagName: string = __esModule.navigationComponentName;
        const element = document.createElement(tagName);
        element.setAttribute('id', navigationComponentElement);
        element.classList.add('scaffold-content-navigation-component');
        element.dataset.component = navigationComponentId;
        container.append(element);
      } else {
        // Because the component does not exist, let's create the div as wrapper
        container.insertAdjacentHTML(
          'beforeend',
          '<div class="scaffold-content-navigation-component" data-component="' + navigationComponentId + '" id="' + navigationComponentElement + '"></div>'
        );

        // manual static initialize method, unused but kept for backwards-compatibility until TYPO3 v12
        // @ts-ignore
        const navigationComponent = Object.values(__esModule)[0] as any;
        // @ts-ignore
        navigationComponent.initialize('#' + navigationComponentElement);
      }
      this.show(navigationComponentId);
      this.activeComponentId = navigationComponentId;
    });
  }

  public hide(hideSwitcher: boolean): void {
    const parent = this.parent;
    const switcher = this.switcher;
    parent.classList.remove('scaffold-content-navigation-expanded');
    parent.classList.remove('scaffold-content-navigation-available');
    if (hideSwitcher && switcher) {
      switcher.style.display = 'none';
    }
  }

  public show(component: string): void {
    const parent = this.parent;
    const container = this.container;
    const switcher = this.switcher;
    container.querySelectorAll(ScaffoldIdentifierEnum.contentNavigationDataComponent).forEach((el: HTMLElement) => el.style.display = 'none');
    if (typeof component !== undefined) {
      parent.classList.add('scaffold-content-navigation-expanded');
      parent.classList.add('scaffold-content-navigation-available');
      const selectedElement = container.querySelector('[data-component="' + component + '"]') as HTMLElement;
      if (selectedElement) {
        // Re-set to the display setting from CSS
        selectedElement.style.display = null;
      }
    }
    if (switcher) {
      // Re-set to the display setting from CSS
      switcher.style.display = null;
    }
  }

  /**
   * @param {string} urlToLoad
   * @param {InteractionRequest} interactionRequest
   * @returns {JQueryDeferred<TriggerRequest>}
   */
  public setUrl(urlToLoad: string, interactionRequest: InteractionRequest): JQueryDeferred<TriggerRequest> {
    const deferred = this.consumerScope.invoke(
      new TriggerRequest('typo3.setUrl', interactionRequest),
    );
    deferred.then((): void => {
      this.parent.classList.add('scaffold-content-navigation-expanded');
      const iFrameElement = this.getIFrameElement();
      if (iFrameElement) {
        iFrameElement.setAttribute('src', urlToLoad);
      }
    });
    return deferred;
  }

  public getUrl(): string {
    const iFrameElement = this.getIFrameElement();
    if (iFrameElement) {
      return iFrameElement.getAttribute('src');
    }
    return '';
  }

  public refresh(): any {
    const iFrameElement = this.getIFrameElement();
    if (iFrameElement) {
      return iFrameElement.contentWindow.location.reload();
    }
    return undefined;
  }

  private getIFrameElement(): HTMLIFrameElement|null {
    return this.container.querySelector(ScaffoldIdentifierEnum.contentNavigationIframe) as HTMLIFrameElement;
  }
}

export = NavigationContainer;
