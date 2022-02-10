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

import $ from 'jquery';
import {Popover as BootstrapPopover} from 'bootstrap';

/**
 * Module: @typo3/backend/popover
 * API for popover windows powered by Twitter Bootstrap.
 * @exports @typo3/backend/popover
 */
class Popover {

  /**
   * Default selector string.
   *
   * @return {string}
   */
  private readonly DEFAULT_SELECTOR: string = '[data-bs-toggle="popover"]';

  constructor() {
    this.initialize();
  }

  /**
   * Initialize
   */
  public initialize(selector?: string): void {
    selector = selector || this.DEFAULT_SELECTOR;
    $(selector).each((i, el) => {
      const popover = new BootstrapPopover(el);
      $(el).data('typo3.bs.popover', popover);
    });
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Popover wrapper function
   *
   * @param {JQuery} $element
   */
  public popover($element: JQuery) {
    $element.each((i, el) => {
      const popover = new BootstrapPopover(el);
      $(el).data('typo3.bs.popover', popover);
    });
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Set popover options on $element
   *
   * @param {JQuery} $element
   * @param {PopoverOptions} options
   */
  public setOptions($element: JQuery, options?: BootstrapPopover.Options): void {
    options = options || <BootstrapPopover.Options>{};
    options.html = true;
    const title: string|(() => void) = options.title || $element.data('title') || '';
    const content: string|(() => void) = options.content || $element.data('bs-content') || '';
    $element
      .attr('data-bs-original-title', (title as string))
      .attr('data-bs-content', (content as string))
      .attr('data-bs-placement', 'auto')

    $.each(options, (key, value) => {
      this.setOption($element, key, value);
    });
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Set popover option on $element
   *
   * @param {JQuery} $element
   * @param {String} key
   * @param {String} value
   */
  public setOption($element: JQuery, key: string, value: string): void {
    if (key === 'content') {
      const popover = $element.data('typo3.bs.popover');
      popover._config.content = value;
      popover.setContent(popover.tip);
    } else {
      $element.each((i, el) => {
        const popover = $(el).data('typo3.bs.popover');
        if (popover) {
          popover._config[key] = value;
        }
      });
    }
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Show popover with title and content on $element
   *
   * @param {JQuery} $element
   */
  public show($element: JQuery): void {
    $element.each((i, el) => {
      const popover = $(el).data('typo3.bs.popover');
      if (popover) {
        popover.show();
      }
    });
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Hide popover on $element
   *
   * @param {JQuery} $element
   */
  public hide($element: JQuery): void {
    $element.each((i, el) => {
      const popover = $(el).data('typo3.bs.popover');
      if (popover) {
        popover.hide();
      }
    });
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Destroy popover on $element
   *
   * @param {Object} $element
   */
  public destroy($element: JQuery): void {
    $element.each((i, el) => {
      const popover = $(el).data('typo3.bs.popover');
      if (popover) {
        popover.dispose();
      }
    });
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Toggle popover on $element
   *
   * @param {Object} $element
   */
  public toggle($element: JQuery): void {
    $element.each((i, el) => {
      const popover = $(el).data('typo3.bs.popover');
      if (popover) {
        popover.toggle();
      }
    });
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Update popover with new content
   *
   * @param $element
   */
  public update($element: JQuery): void {
    $element.data('typo3.bs.popover')._popper.update();
  }
}

export default new Popover();
