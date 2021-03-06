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
import {NavigationComponent} from 'TYPO3/CMS/Backend/Viewport/NavigationComponent';

class NavigationContainer extends AbstractContainer {
  private components: Array<NavigationComponent> = [];
  private readonly parent: HTMLElement;
  private readonly container: HTMLElement;
  private readonly switcher: HTMLElement = null;
  private activeComponentId: string = '';

  public constructor(consumerScope: any, navigationSwitcher?: HTMLElement)
  {
    super(consumerScope);
    this.parent = document.querySelector(ScaffoldIdentifierEnum.scaffold);
    this.container = document.querySelector(ScaffoldIdentifierEnum.contentNavigation);
    this.switcher = navigationSwitcher;
  }

  /**
   * Renders registered (non-iframe) navigation component e.g. a page tree
   *
   * @param {string} navigationComponentId
   */
  public showComponent(navigationComponentId: string): void {
    this.show(navigationComponentId);
    // Component is already loaded and active, nothing to do
    if (navigationComponentId === this.activeComponentId) {
      return;
    }
    if (this.activeComponentId !== '') {
      let activeComponentElement = this.container.querySelector('#navigationComponent-' + this.activeComponentId.replace(/[/]/g, '_')) as HTMLElement;
      if (activeComponentElement) {
        activeComponentElement.style.display = 'none';
      }
    }

    const componentCssName = navigationComponentId.replace(/[/]/g, '_');
    const navigationComponentElement = 'navigationComponent-' + componentCssName;
    // Component does not exist, create the div as wrapper
    if (this.container.querySelectorAll('[data-component="' + navigationComponentId + '"]').length === 0) {
      this.container.insertAdjacentHTML(
        'beforeend',
        '<div class="scaffold-content-navigation-component" data-component="' + navigationComponentId + '" id="' + navigationComponentElement + '"></div>'
      );
    }

    require([navigationComponentId], (__esModule: any): void => {
      // @ts-ignore
      const navigationComponent = (new (Object.values(__esModule)[0])('#' + navigationComponentElement)) as NavigationComponent;
      this.addComponent(navigationComponent);
      this.show(navigationComponentId);
      this.activeComponentId = navigationComponentId;
    });
  }

  public getComponentByName(name: string): NavigationComponent|null {
    let foundComponent = null;
    this.components.forEach((component: NavigationComponent) => {
      if (component.getName() == name) {
        foundComponent = component;
      }
    });
    return foundComponent;
  }

  public toggle(): void {
    this.parent.classList.toggle('scaffold-content-navigation-expanded');
  }

  public hide(hideSwitcher: boolean): void {
    this.parent.classList.remove('scaffold-content-navigation-expanded');
    this.parent.classList.remove('scaffold-content-navigation-available');
    if (hideSwitcher && this.switcher) {
      this.switcher.style.display = 'none';
    }
  }

  public getPosition(): DOMRect {
    return this.container.getBoundingClientRect();
  }

  public getWidth(): number {
    if (this.container) {
      return <number>this.container.offsetWidth;
    }
    return 0;
  }

  public autoWidth(): void {
    if (this.container) {
      this.container.style.width = 'auto';
    }
  }

  public setWidth(width: number): void {
    width = width > 300 ? width : 300;
    if (this.container) {
      this.container.style.width = width + 'px';
    }
  }

  public show(component: string): void {
    this.container.querySelectorAll(ScaffoldIdentifierEnum.contentNavigationDataComponent).forEach((el: HTMLElement) => el.style.display = 'none');
    if (typeof component !== undefined) {
      this.parent.classList.add('scaffold-content-navigation-expanded');
      this.parent.classList.add('scaffold-content-navigation-available');
      const selectedElement = this.container.querySelector('[data-component="' + component + '"]') as HTMLElement;
      if (selectedElement) {
        // Re-set to the display setting from CSS
        selectedElement.style.display = null;
      }
    }
    if (this.switcher) {
      // Re-set to the display setting from CSS
      this.switcher.style.display = null;
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

  private addComponent(component: NavigationComponent): void {
    this.components.push(component);
  }
}

export = NavigationContainer;
