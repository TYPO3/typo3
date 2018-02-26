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

import * as $ from 'jquery';

enum Sizes {
  small = 'small',
  default = 'default',
  large = 'large',
  overlay = 'overlay'
}

enum States {
  default = 'default',
  disabled = 'disabled'
}

enum MarkupIdentifiers {
  default = 'default',
  inline = 'inline'
}

interface Cache {
  [key: string]: JQueryPromise<any>;
}

/**
 * Module: TYPO3/CMS/Backend/Icons
 * Uses the icon API of the core to fetch icons via AJAX.
 */
class Icons {
  public readonly sizes: any = Sizes;
  public readonly states: any = States;
  public readonly markupIdentifiers: any = MarkupIdentifiers;
  private readonly cache: Cache = {};

  /**
   * Get the icon by its identifier
   *
   * @param {string} identifier
   * @param {Sizes} size
   * @param {string} overlayIdentifier
   * @param {string} state
   * @param {MarkupIdentifiers} markupIdentifier
   * @returns {JQueryPromise<any>}
   */
  public getIcon(identifier: string,
                 size: Sizes,
                 overlayIdentifier?: string,
                 state?: string,
                 markupIdentifier?: MarkupIdentifiers): JQueryPromise<any> {
    return $.when(this.fetch(identifier, size, overlayIdentifier, state, markupIdentifier));
  }

  /**
   * Performs the AJAX request to fetch the icon
   *
   * @param {string} identifier
   * @param {Sizes} size
   * @param {string} overlayIdentifier
   * @param {string} state
   * @param {MarkupIdentifiers} markupIdentifier
   * @returns {JQueryPromise<any>}
   */
  public fetch(identifier: string,
               size: Sizes,
               overlayIdentifier: string,
               state: string,
               markupIdentifier: MarkupIdentifiers): JQueryPromise<any> {
    /**
     * Icon keys:
     *
     * 0: identifier
     * 1: size
     * 2: overlayIdentifier
     * 3: state
     * 4: markupIdentifier
     */
    size = size || Sizes.default;
    state = state || States.default;
    markupIdentifier = markupIdentifier || MarkupIdentifiers.default;

    const icon = [identifier, size, overlayIdentifier, state, markupIdentifier];
    const cacheIdentifier = icon.join('_');

    if (!this.isCached(cacheIdentifier)) {
      this.putInCache(cacheIdentifier, $.ajax({
        url: TYPO3.settings.ajaxUrls.icons,
        dataType: 'html',
        data: {
          icon: JSON.stringify(icon)
        },
        success: (markup: string) => {
          return markup;
        }
      }).promise());
    }
    return this.getFromCache(cacheIdentifier).done();
  }

  /**
   * Check whether icon was fetched already
   *
   * @param {string} cacheIdentifier
   * @returns {boolean}
   */
  private isCached(cacheIdentifier: string): boolean {
    return typeof this.cache[cacheIdentifier] !== 'undefined';
  }

  /**
   * Get icon from cache
   *
   * @param {string} cacheIdentifier
   * @returns {JQueryPromise<any>}
   */
  private getFromCache(cacheIdentifier: string): JQueryPromise<any> {
    return this.cache[cacheIdentifier];
  }

  /**
   * Put icon into cache
   *
   * @param {string} cacheIdentifier
   * @param {JQueryPromise<any>} markup
   */
  private putInCache(cacheIdentifier: string, markup: JQueryPromise<any>): void {
    this.cache[cacheIdentifier] = markup;
  }
}

let iconsObject: Icons;
try {
  // fetch from opening window
  if (window.opener && window.opener.TYPO3 && window.opener.TYPO3.Icons) {
    iconsObject = window.opener.TYPO3.Icons;
  }

  // fetch from parent
  if (parent && parent.window.TYPO3 && parent.window.TYPO3.Icons) {
    iconsObject = parent.window.TYPO3.Icons;
  }

  // fetch object from outer frame
  if (top && top.TYPO3.Icons) {
    iconsObject = top.TYPO3.Icons;
  }
} catch (e) {
  // This only happens if the opener, parent or top is some other url (eg a local file)
  // which loaded the current window. Then the browser's cross domain policy jumps in
  // and raises an exception.
  // For this case we are safe and we can create our global object below.
}

if (!iconsObject) {
  iconsObject = new Icons();
  TYPO3.Icons = iconsObject;
}

export = iconsObject;
