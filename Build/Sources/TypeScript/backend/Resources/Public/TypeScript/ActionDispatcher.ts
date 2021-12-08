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

import InfoWindow from 'TYPO3/CMS/Backend/InfoWindow';
import RegularEvent from 'TYPO3/CMS/Core/Event/RegularEvent';
import shortcutMenu from 'TYPO3/CMS/Backend/Toolbar/ShortcutMenu';
import windowManager from 'TYPO3/CMS/Backend/WindowManager';
import moduleMenuApp from 'TYPO3/CMS/Backend/ModuleMenu';
import documentService from 'TYPO3/CMS/Core/DocumentService';
import Utility from 'TYPO3/CMS/Backend/Utility';

declare type ActionDispatchArgument = string | HTMLElement | Event;

/**
 * Module: TYPO3/CMS/Backend/ActionDispatcher
 *
 * @example
 * <a class="btn btn-default" href="#"
 *  data-dispatch-action="TYPO3.InfoWindow.showItem"
 *  data-dispatch-args-list="tt_content,123"
 *  ...
 *  data-dispatch-args="[$quot;tt_content&quot;,123]"
 *  ...
 *  data-dispatch-disabled>
 */
class ActionDispatcher {
  private delegates: {[key: string]: Function} = {};

  private static resolveArguments(element: HTMLElement): null | string[] {
    if (element.dataset.dispatchArgs) {
      // `&quot;` is the only literal of a PHP `json_encode` that needs to be substituted
      // all other payload values are expected to be serialized to unicode literals
      const json = element.dataset.dispatchArgs.replace(/&quot;/g, '"');
      const args = JSON.parse(json);
      return args instanceof Array ? Utility.trimItems(args) : null;
    } else if (element.dataset.dispatchArgsList) {
      const args = element.dataset.dispatchArgsList.split(',');
      return Utility.trimItems(args);
    }
    return null;
  }

  private static enrichItems(items: any[], evt: Event, target: HTMLElement): any[] {
    return items.map((item: any) => {
      if (!(item instanceof Object) || !item.$event) {
        return item;
      }
      if (item.$target) {
        return target;
      }
      if (item.$event) {
        return evt;
      }
    });
  }

  public constructor() {
    this.createDelegates();
    documentService.ready().then((): void => this.registerEvents());
  }

  private createDelegates(): void {
    this.delegates = {
      'TYPO3.InfoWindow.showItem': InfoWindow.showItem.bind(null),
      'TYPO3.ShortcutMenu.createShortcut': shortcutMenu.createShortcut.bind(shortcutMenu),
      'TYPO3.WindowManager.localOpen': windowManager.localOpen.bind(windowManager),
      'TYPO3.ModuleMenu.showModule': moduleMenuApp.App.showModule.bind(moduleMenuApp.App),
    };
  }

  private registerEvents(): void {
    new RegularEvent('click', this.handleClickEvent.bind(this))
      .delegateTo(document, '[data-dispatch-action]');
  }

  private handleClickEvent(evt: Event, target: HTMLElement): void {
    evt.preventDefault();
    this.delegateTo(evt, target);
  }

  private delegateTo(evt: Event, target: HTMLElement): void {
    const disabled = target.hasAttribute('data-dispatch-disabled');
    if (disabled) {
      return;
    }
    const action = target.dataset.dispatchAction;
    let args: ActionDispatchArgument[] = ActionDispatcher.resolveArguments(target);
    if (args instanceof Array) {
      args = args.map((arg: string): ActionDispatchArgument => {
        switch (arg) {
          case '{$target}':
            return target;
          case '{$event}':
            return evt;
          default:
            return arg;
        }
      });
    }
    if (this.delegates[action]) {
      this.delegates[action].apply(null, args || []);
    }
  }
}

export default new ActionDispatcher();
