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
import {TopbarIdentifiersEnum} from '../Enum/Viewport/TopbarIdentifiers';
import {AbstractContainer} from './AbstractContainer';
import * as $ from 'jquery';
import PageTree = require('./PageTree');
import Icons = require('./../Icons');
import TriggerRequest = require('../Event/TriggerRequest');
import InteractionRequest = require('../Event/InteractionRequest');

class NavigationContainer extends AbstractContainer {
  public PageTree: PageTree = null;
  private instance: NavigationComponentInterface = null;

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
    $(ScaffoldIdentifierEnum.scaffold).toggleClass('scaffold-content-navigation-expanded');
  }

  public cleanup(): void {
    $(ScaffoldIdentifierEnum.moduleMenu).removeAttr('style');
    $(ScaffoldIdentifierEnum.content).removeAttr('style');
  }

  public hide(): void {
    $(TopbarIdentifiersEnum.buttonNavigationComponent).prop('disabled', true);
    Icons.getIcon(
      'actions-pagetree',
      Icons.sizes.small,
      'overlay-readonly',
      null,
      Icons.markupIdentifiers.inline,
    ).done((icon: string): void => {
      $(TopbarIdentifiersEnum.buttonNavigationComponent).html(icon);
    });
    $(ScaffoldIdentifierEnum.scaffold).removeClass('scaffold-content-navigation-expanded');
    $(ScaffoldIdentifierEnum.contentModule).removeAttr('style');
  }

  public show(component: string): void {
    $(TopbarIdentifiersEnum.buttonNavigationComponent).prop('disabled', false);
    Icons.getIcon('actions-pagetree', Icons.sizes.small, null, null, Icons.markupIdentifiers.inline).done((icon: string): void => {
      $(TopbarIdentifiersEnum.buttonNavigationComponent).html(icon);
    });

    $(ScaffoldIdentifierEnum.contentNavigationDataComponent).hide();
    if (typeof component !== undefined) {
      $(ScaffoldIdentifierEnum.scaffold).addClass('scaffold-content-navigation-expanded');
      $(ScaffoldIdentifierEnum.contentNavigation + ' [data-component="' + component + '"]').show();
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
      $(ScaffoldIdentifierEnum.scaffold).addClass('scaffold-content-navigation-expanded');
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

  public calculateScrollbar(): void {
    this.cleanup();
    const $scaffold = $(ScaffoldIdentifierEnum.scaffold);
    const $moduleMenuContainer = $(ScaffoldIdentifierEnum.moduleMenu);
    const $contentContainer = $(ScaffoldIdentifierEnum.content);
    const $moduleMenu = $('.t3js-modulemenu');
    $moduleMenuContainer.css('overflow', 'auto');
    const moduleMenuContainerWidth = $moduleMenuContainer.outerWidth();
    const moduleMenuWidth = $moduleMenu.outerWidth();
    $moduleMenuContainer.removeAttr('style').css('overflow', 'hidden');
    if ($scaffold.hasClass('scaffold-modulemenu-expanded') === false) {
      $moduleMenuContainer.width(moduleMenuContainerWidth + (moduleMenuContainerWidth - moduleMenuWidth));
      $contentContainer.css('left', moduleMenuContainerWidth + (moduleMenuContainerWidth - moduleMenuWidth));
    } else {
      $moduleMenuContainer.removeAttr('style');
      $contentContainer.removeAttr('style');
    }
    $moduleMenuContainer.css('overflow', 'auto');
  }
}

export = NavigationContainer;
