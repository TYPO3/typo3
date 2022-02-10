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
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {Popover as BootstrapPopover} from 'bootstrap';
import Popover from './popover';

interface HelpData {
  title: string;
  content: string;
}

/**
 * Module: @typo3/backend/context-help
 * API for context help.
 * @exports @typo3/backend/context-help
 */
class ContextHelp {
  private ajaxUrl: string = TYPO3.settings.ajaxUrls.context_help;
  private helpModuleUrl: string;
  private trigger: string = 'click';
  private placement: string = 'auto';
  private selector: string = '.help-link';

  /**
   * @return {Window}
   */
  private static resolveBackend(): Window {
    if (typeof window.opener !== 'undefined' && window.opener !== null) {
      return window.opener.top;
    } else {
      return top;
    }
  }

  constructor() {
    this.initialize();
  }

  public initialize(): void {
    const backendWindow = ContextHelp.resolveBackend();
    if (typeof backendWindow.TYPO3.settings.ContextHelp !== 'undefined') {
      this.helpModuleUrl = backendWindow.TYPO3.settings.ContextHelp.moduleUrl;
    }

    if (typeof TYPO3.ShortcutMenu === 'undefined' && typeof backendWindow.TYPO3.ShortcutMenu === 'undefined') {
      // @FIXME: if we are in the popup... remove the bookmark / shortcut button
      // @TODO: make it possible to use the bookmark button also in popup mode
      $('.icon-actions-system-shortcut-new').closest('.btn').hide();
    }

    let title = '&nbsp;';
    if (typeof backendWindow.TYPO3.lang !== 'undefined') {
      title = backendWindow.TYPO3.lang.csh_tooltip_loading;
    }
    const $element = $(this.selector);
    $element
      .attr('data-loaded', 'false')
      .attr('data-bs-html', 'true')
      .attr('data-bs-original-title', title)
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
      } else if ($me.attr('data-loaded') === 'false' && $me.data('table')) {
        this.loadHelp($me);
      }

      // if help icon is in DocHeader, force open to bottom
      if ($me.closest('.t3js-module-docheader').length) {
        Popover.setOption($me, 'placement', 'bottom');
      }
    }).on('click', '.help-has-link', (e: any): void => {
      $('.popover').each((index: number, popover: Element): void => {
        const $popover = $(popover);
        if ($popover.has(e.target).length) {
          this.showHelpPopup($('[aria-describedby="' + $popover.attr('id') + '"]'));
        }
      });
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

  /**
   * Open the help popup
   *
   * @param {JQuery} $trigger
   */
  private showHelpPopup($trigger: JQuery): any {
    try {
      const cshWindow = window.open(
        this.helpModuleUrl +
        '&table=' + $trigger.data('table') +
        '&field=' + $trigger.data('field') +
        '&action=detail',
        'ContextHelpWindow',
        'height=400,width=600,status=0,menubar=0,scrollbars=1',
      );
      cshWindow.focus();
      Popover.hide($trigger);
      return cshWindow;
    } catch {
      // do nothing
    }
  }

  /**
   * Load help data
   *
   * @param {JQuery} $trigger
   */
  private loadHelp($trigger: JQuery): void {
    const table = $trigger.data('table');
    const field = $trigger.data('field');
    // If a table is defined, use ajax call to get the tooltip's content
    if (table) {
      // Load content
      new AjaxRequest(this.ajaxUrl).withQueryArguments({
        params: {
          action: 'getContextHelp',
          table: table,
          field: field,
        }
      }).get().then(async (response: AjaxResponse): Promise<any> => {
        const data: HelpData = await response.resolve();
        const title = data.title || '';
        const content = data.content || '<p></p>';
        const options = <BootstrapPopover.Options>{
          title: title,
          content: content,
        };
        Popover.setOptions($trigger, options);
        Popover.update($trigger);

        $trigger.attr('data-loaded', 'true');
      });
    }
  }
}

export default new ContextHelp();
