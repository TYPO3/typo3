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

const delegates: {[key: string]: Function} = {
  'TYPO3.InfoWindow.showItem': InfoWindow.showItem.bind(null),
  'TYPO3.ShortcutMenu.createShortcut': shortcutMenu.createShortcut.bind(shortcutMenu),
};

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
  private static resolveArguments(element: HTMLElement): null | string[] {
    if (element.dataset.dispatchArgs) {
      const args = JSON.parse(element.dataset.dispatchArgs);
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
    documentService.ready().then((): void => this.registerEvents());
  }

  private registerEvents(): void {
    new RegularEvent('click', this.handleClickEvent.bind(this))
      .delegateTo(document, '[data-dispatch-action]:not([data-dispatch-immediately])');
    document.querySelectorAll('[data-dispatch-action][data-dispatch-immediately]')
      .forEach(this.delegateTo.bind(this));
  }

  private handleClickEvent(evt: Event, target: HTMLElement): void {
    evt.preventDefault();
    this.delegateTo(target);
  }

  private delegateTo(target: HTMLElement): void {
    const action = target.dataset.dispatchAction;
    const args = ActionDispatcher.resolveArguments(target);
    if (delegates[action]) {
      delegates[action].apply(null, args || []);
    }
  }
}

export = new ActionDispatcher();
