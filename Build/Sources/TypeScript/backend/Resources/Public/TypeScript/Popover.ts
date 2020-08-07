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
import {PopoverOptions} from 'bootstrap';
import $ from 'jquery';

/**
 * Module: TYPO3/CMS/Backend/Popover
 * API for popover windows powered by Twitter Bootstrap.
 * @exports TYPO3/CMS/Backend/Popover
 */
class Popover {

  /**
   * Default selector string.
   *
   * @return {string}
   */
  private readonly DEFAULT_SELECTOR: string = '[data-toggle="popover"]';

  constructor() {
    this.initialize();
  }

  /**
   * Initialize
   */
  public initialize(selector?: string): void {
    selector = selector || this.DEFAULT_SELECTOR;
    $(selector).popover();
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Popover wrapper function
   *
   * @param {JQuery} $element
   */
  public popover($element: JQuery): void {
    $element.popover();
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Set popover options on $element
   *
   * @param {JQuery} $element
   * @param {PopoverOptions} options
   */
  public setOptions($element: JQuery, options?: PopoverOptions): void {
    options = options || {};
    const title: string|(() => void) = options.title || $element.data('title') || '';
    const content: string|(() => void) = options.content || $element.data('content') || '';
    $element
      .attr('data-original-title', (title as string))
      .attr('data-content', (content as string))
      .attr('data-placement', 'auto')
      .popover(options);
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
    $element.data('bs.popover').options[key] = value;
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Show popover with title and content on $element
   *
   * @param {JQuery} $element
   */
  public show($element: JQuery): void {
    $element.popover('show');
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Hide popover on $element
   *
   * @param {JQuery} $element
   */
  public hide($element: JQuery): void {
    $element.popover('hide');
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Destroy popover on $element
   *
   * @param {Object} $element
   */
  public destroy($element: JQuery): void {
    $element.popover('destroy');
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Toggle popover on $element
   *
   * @param {Object} $element
   */
  public toggle($element: JQuery): void {
    $element.popover('toggle');
  }
}

export = new Popover();
