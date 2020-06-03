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

import InfoWindow = require('TYPO3/CMS/Backend/InfoWindow');
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');
import shortcutMenu = require('TYPO3/CMS/Backend/Toolbar/ShortcutMenu');
import documentService = require('TYPO3/CMS/Core/DocumentService');

/**
 * Module: TYPO3/CMS/Backend/ActionDispatcher
 *
 * @example
 * <a class="btn btn-default" href="#"
 *  data-dispatch-action="TYPO3.InfoWindow.showItem"
 *  data-dispatch-args-list="tt_content,123"
 *  ...
 *  data-dispatch-args="[$quot;tt_content&quot;,123]"
 */
class ActionDispatcher {
  private delegates: {[key: string]: Function} = {};

  private static resolveArguments(element: HTMLElement): null | string[] {
    if (element.dataset.dispatchArgs) {
      // `&quot;` is the only literal of a PHP `json_encode` that needs to be substituted
      // all other payload values are expected to be serialized to unicode literals
      const json = element.dataset.dispatchArgs.replace(/&quot;/g, '"');
      const args = JSON.parse(json);
      return args instanceof Array ? ActionDispatcher.trimItems(args) : null;
    } else if (element.dataset.dispatchArgsList) {
      const args = element.dataset.dispatchArgsList.split(',');
      return ActionDispatcher.trimItems(args);
    }
    return null;
  }

  private static trimItems(items: any[]): any[] {
    return items.map((item: any) => {
      if (item instanceof String) {
        return item.trim();
      }
      return item;
    });
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
    };
  }

  private registerEvents(): void {
    new RegularEvent('click', this.handleClickEvent.bind(this))
      .delegateTo(document, '[data-dispatch-action]');
  }

  private handleClickEvent(evt: Event, target: HTMLElement): void {
    evt.preventDefault();
    this.delegateTo(target);
  }

  private delegateTo(target: HTMLElement): void {
    const action = target.dataset.dispatchAction;
    const args = ActionDispatcher.resolveArguments(target);
    if (this.delegates[action]) {
      this.delegates[action].apply(null, args || []);
    }
  }
}

export = new ActionDispatcher();
