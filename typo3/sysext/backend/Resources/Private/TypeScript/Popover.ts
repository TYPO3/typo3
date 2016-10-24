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

/// <amd-dependency path="bootstrap">
import $ = require('jquery');

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

  // noinspection JSMethodCanBeStatic
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
    let title: string|Function = options.title || $element.data('title') || '';
    let content: string|Function = options.content || $element.data('content') || '';
    $element
      .attr('data-original-title', (<string> title))
      .attr('data-content', (<string> content))
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

// Create an instance, initialize and return it
let popover: Popover = new Popover();
popover.initialize();

// @deprecated since TYPO3 v9, will be removed in TYPO3 v10 prevent global object usage
declare var TYPO3: any;
TYPO3.Popover = popover;
export = popover;
