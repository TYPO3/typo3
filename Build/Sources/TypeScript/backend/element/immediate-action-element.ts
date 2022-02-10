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

import Utility from '@typo3/backend/utility';
import {EventDispatcher} from '@typo3/backend/event/event-dispatcher';

/**
 * Module: @typo3/backend/element/immediate-action-element
 *
 * @example
 * <typo3-immediate-action action="TYPO3.ModuleMenu.App.refreshMenu"></typo3-immediate-action>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
export class ImmediateActionElement extends HTMLElement {
  private action: string;
  private args: any[] = [];

  private static async getDelegate(action: string): Promise<Function> {
    switch (action) {
      case 'TYPO3.ModuleMenu.App.refreshMenu':
        const {default: moduleMenuApp} = await import('@typo3/backend/module-menu');
        return moduleMenuApp.App.refreshMenu.bind(moduleMenuApp.App);
      case 'TYPO3.Backend.Topbar.refresh':
        const {default: viewportObject} = await import('@typo3/backend/viewport');
        return viewportObject.Topbar.refresh.bind(viewportObject.Topbar);
      case 'TYPO3.WindowManager.localOpen':
        const {default: windowManager} = await import('@typo3/backend/window-manager');
        return windowManager.localOpen.bind(windowManager);
      case 'TYPO3.Backend.Storage.ModuleStateStorage.update':
        return (await import('@typo3/backend/storage/module-state-storage')).ModuleStateStorage.update;
      case 'TYPO3.Backend.Storage.ModuleStateStorage.updateWithCurrentMount':
        return (await import('@typo3/backend/storage/module-state-storage')).ModuleStateStorage.updateWithCurrentMount;
      case 'TYPO3.Backend.Event.EventDispatcher.dispatchCustomEvent':
        return EventDispatcher.dispatchCustomEvent;
      default:
        throw Error('Unknown action "' + action + '"');
    }
  }

  /**
   * Observed attributes handled by `attributeChangedCallback`.
   */
  public static get observedAttributes(): string[] {
    return ['action', 'args', 'args-list'];
  }

  /**
   * Custom element life-cycle callback initializing attributes.
   */
  public attributeChangedCallback(name: string, oldValue: string, newValue: string): void {
    if (name === 'action') {
      this.action = newValue;
    } else if (name === 'args') {
      // `&quot;` is the only literal of a PHP `json_encode` that needs to be substituted
      // all other payload values are expected to be serialized to unicode literals
      const json = newValue.replace(/&quot;/g, '"');
      const args = JSON.parse(json);
      this.args = args instanceof Array ? Utility.trimItems(args) : [];
    } else if (name === 'args-list') {
      const args = newValue.split(',');
      this.args = Utility.trimItems(args);
    }
  }

  /**
   * Custom element life-cycle callback triggered when element
   * becomes available in document ("connected to DOM").
   */
  public connectedCallback(): void {
    if (!this.action) {
      throw new Error('Missing mandatory action attribute');
    }
    ImmediateActionElement.getDelegate(this.action).then((callback: Function): void => callback.apply(null, this.args));
  }
}

window.customElements.define('typo3-immediate-action', ImmediateActionElement);
