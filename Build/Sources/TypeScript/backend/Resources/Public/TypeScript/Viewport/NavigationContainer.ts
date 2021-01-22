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

import {NavigationComponentInterface} from './NavigationComponentInterface';
import {ScaffoldIdentifierEnum} from '../Enum/Viewport/ScaffoldIdentifier';
import {AbstractContainer} from './AbstractContainer';
import $ from 'jquery';
import PageTree = require('./PageTree');
import TriggerRequest = require('../Event/TriggerRequest');
import InteractionRequest = require('../Event/InteractionRequest');

class NavigationContainer extends AbstractContainer {
  public PageTree: PageTree = null;
  private instance: NavigationComponentInterface = null;
  private readonly parent: HTMLElement;
  private readonly container: HTMLElement;
  private readonly switcher: HTMLElement = null;

  public constructor(consumerScope: any, navigationSwitcher?: HTMLElement)
  {
    super(consumerScope);
    this.parent = document.querySelector(ScaffoldIdentifierEnum.scaffold);
    this.container = document.querySelector(ScaffoldIdentifierEnum.contentNavigation);
    this.switcher = navigationSwitcher;
  }
  /**
   * Public method used by Navigation components to register themselves.
   * See TYPO3/CMS/Backend/PageTree/PageTreeElement->initialize
   *
   * @param {NavigationComponentInterface} component
   */
  public setComponentInstance(component: NavigationComponentInterface): void {
    this.instance = component;
    this.PageTree = new PageTree(component);
  }

  public toggle(): void {
    this.parent.classList.toggle('scaffold-content-navigation-expanded');
  }

  public hide(hideSwitcher: boolean): void {
    this.parent.classList.remove('scaffold-content-navigation-expanded');
    this.parent.classList.remove('scaffold-content-navigation-available');
    if (hideSwitcher && this.switcher) {
      $(this.switcher).hide();
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
    $(ScaffoldIdentifierEnum.contentNavigationDataComponent).hide();
    if (typeof component !== undefined) {
      this.parent.classList.add('scaffold-content-navigation-expanded');
      this.parent.classList.add('scaffold-content-navigation-available');
      $(ScaffoldIdentifierEnum.contentNavigation + ' [data-component="' + component + '"]').show();
    }
    if (this.switcher) {
      $(this.switcher).show();
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
      $(ScaffoldIdentifierEnum.contentNavigationIframe).attr('src', urlToLoad);
    });
    return deferred;
  }

  /**
   * @returns {string}
   */
  public getUrl(): string {
    return $(ScaffoldIdentifierEnum.contentNavigationIframe).attr('src');
  }

  public refresh(): any {
    return (<HTMLIFrameElement>$(ScaffoldIdentifierEnum.contentNavigationIframe)[0]).contentWindow.location.reload();
  }
}

export = NavigationContainer;
