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

import { ScaffoldContentArea } from '../enum/viewport/scaffold-identifier';
import { AbstractContainer } from './abstract-container';
import TriggerRequest from '../event/trigger-request';
import { selector } from '@typo3/core/literals';
import type InteractionRequest from '../event/interaction-request';
import type { ContentNavigation } from '@typo3/backend/viewport/content-navigation';

class NavigationContainer extends AbstractContainer {
  private activeComponentId: string = '';

  public constructor(consumerScope: any)
  {
    super(consumerScope);
  }

  private get contentNavigation(): ContentNavigation | null
  {
    return ScaffoldContentArea.getContentNavigation();
  }

  private get navigationContainer(): HTMLElement | null
  {
    return ScaffoldContentArea.getNavigationContainer();
  }

  /**
   * Renders registered (non-iframe) navigation component e.g. a page tree
   *
   * @param {string} navigationComponentId
   */
  public showComponent(navigationComponentId: string): void {
    const contentNavigation = this.contentNavigation;
    const navigationContainer = this.navigationContainer;
    if (!contentNavigation || !navigationContainer) {
      return;
    }

    this.show(navigationComponentId);
    // Component is already loaded and active, nothing to do
    if (navigationComponentId === this.activeComponentId) {
      return;
    }
    if (this.activeComponentId !== '') {
      const activeComponentElement = navigationContainer.querySelector('#navigationComponent-' + this.activeComponentId.replace(/[/@]/g, '_')) as HTMLElement;
      if (activeComponentElement) {
        activeComponentElement.style.display = 'none';
      }
    }

    const componentCssName = navigationComponentId.replace(/[/@]/g, '_');
    const navigationComponentElement = 'navigationComponent-' + componentCssName;

    // The component was already set up, so requiring the module again can be excluded.
    if (navigationContainer.querySelectorAll(selector`[data-component="${navigationComponentId}"]`).length === 1) {
      this.show(navigationComponentId);
      this.activeComponentId = navigationComponentId;
      return;
    }

    import(navigationComponentId + '.js').then((__esModule: {navigationComponentName?: string}): void => {
      if (typeof __esModule.navigationComponentName === 'string') {
        const tagName: string = __esModule.navigationComponentName;
        const element = document.createElement(tagName);
        element.setAttribute('id', navigationComponentElement);
        element.dataset.component = navigationComponentId;
        navigationContainer.append(element);
      } else {
        // Because the component does not exist, let's create the div as wrapper
        navigationContainer.insertAdjacentHTML(
          'beforeend',
          '<div data-component="' + navigationComponentId + '" id="' + navigationComponentElement + '"></div>'
        );

        // manual static initialize method, unused but kept for backwards-compatibility until TYPO3 v12
        const navigationComponent = Object.values(__esModule)[0] as any;
        navigationComponent.initialize('#' + navigationComponentElement);
      }
      this.show(navigationComponentId);
      this.activeComponentId = navigationComponentId;
    });
  }

  public hide(): void {
    this.contentNavigation?.hideNavigation();
  }

  public show(component: string): void {
    const contentNavigation = this.contentNavigation;
    const navigationContainer = this.navigationContainer;
    if (!contentNavigation || !navigationContainer) {
      return;
    }
    navigationContainer.querySelectorAll('[data-component]').forEach((el: HTMLElement) => el.style.display = 'none');
    contentNavigation.showNavigation();
    const selectedElement = navigationContainer.querySelector('[data-component="' + component + '"]') as HTMLElement;
    if (selectedElement) {
      // Re-set to the display setting from CSS
      selectedElement.style.display = null;
    }
  }

  public setUrl(urlToLoad: string, interactionRequest: InteractionRequest): Promise<void> {
    const promise = this.consumerScope.invoke(
      new TriggerRequest('typo3.setUrl', interactionRequest),
    );
    promise.then((): void => {
      this.contentNavigation?.showNavigation();
    });
    return promise;
  }
}

export default NavigationContainer;
