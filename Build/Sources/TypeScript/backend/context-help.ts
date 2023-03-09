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

import 'bootstrap';
import { Popover as BootstrapPopover } from 'bootstrap';
import Popover from './popover';
import RegularEvent from '@typo3/core/event/regular-event';

/**
 * Module: @typo3/backend/context-help
 * API for context help.
 * @exports @typo3/backend/context-help
 */
class ContextHelp {
  private trigger: string = 'click';
  private placement: string = 'auto';
  private selector: string = '.help-link';

  constructor() {
    this.initialize();
  }

  public initialize(): void {
    const elements = document.querySelectorAll(this.selector);
    elements.forEach((element: HTMLElement): void => {
      element.dataset.bsHtml = 'true';
      element.dataset.bsPlacement = this.placement;
      element.dataset.bsTrigger = this.trigger;

      Popover.popover(element);
    });

    new RegularEvent('show.bs.popover', (e: Event): void => {
      const me = e.target as HTMLElement;
      const description = me.dataset.description;

      if (description) {
        const options = <BootstrapPopover.Options>{
          title: me.dataset.title || '',
          content: description,
        };
        Popover.setOptions(me, options);
      }
    }).delegateTo(document, this.selector);

    new RegularEvent('click', (e: Event): void => {
      const me = e.target as HTMLElement;
      const elements = document.querySelectorAll(this.selector);
      elements.forEach((element: HTMLElement): void => {
        if (!element.isEqualNode(me)) {
          Popover.hide(element);
        }
      });
    }).delegateTo(document, 'body');
  }
}

export default new ContextHelp();
