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
import $ from 'jquery';
import {Popover as BootstrapPopover} from 'bootstrap';
import Popover from './popover';

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
    const $element = $(this.selector);
    $element
      .attr('data-bs-html', 'true')
      .attr('data-bs-placement', this.placement)
      .attr('data-bs-trigger', this.trigger);
    Popover.popover($element);

    $(document).on('show.bs.popover', this.selector, (e: Event): void => {
      const $me = $(e.currentTarget);
      const description = $me.data('description');
      if (typeof description !== 'undefined' && description !== '') {
        const options = <BootstrapPopover.Options>{
          title: $me.data('title') || '',
          content: description,
        };
        Popover.setOptions($me, options);
      }
    }).on('click', 'body', (e: any): void => {
      $(this.selector).each((index: number, triggerElement: Element): void => {
        const $triggerElement = $(triggerElement);
        // the 'is' for buttons that trigger popups
        // the 'has' for icons within a button that triggers a popup
        if (!$triggerElement.is(e.target)
          && $triggerElement.has(e.target).length === 0
          && $('.popover').has(e.target).length === 0
        ) {
          Popover.hide($triggerElement);
        }
      });
    });
  }
}

export default new ContextHelp();
