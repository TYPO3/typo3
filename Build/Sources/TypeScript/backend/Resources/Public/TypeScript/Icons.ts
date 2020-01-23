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

import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import ClientStorage = require('./Storage/Client');

enum Sizes {
  small = 'small',
  default = 'default',
  large = 'large',
  overlay = 'overlay',
}

enum States {
  default = 'default',
  disabled = 'disabled',
}

enum MarkupIdentifiers {
  default = 'default',
  inline = 'inline',
}

interface PromiseCache {
  [key: string]: Promise<string>;
}

/**
 * Module: TYPO3/CMS/Backend/Icons
 * Uses the icon API of the core to fetch icons via AJAX.
 */
class Icons {
  public readonly sizes: any = Sizes;
  public readonly states: any = States;
  public readonly markupIdentifiers: any = MarkupIdentifiers;
  private readonly promiseCache: PromiseCache = {};

  /**
   * Get the icon by its identifier
   *
   * @param {string} identifier
   * @param {Sizes} size
   * @param {string} overlayIdentifier
   * @param {string} state
   * @param {MarkupIdentifiers} markupIdentifier
   * @returns {Promise<string>}
   */
  public getIcon(
    identifier: string,
    size: Sizes,
    overlayIdentifier?: string,
    state?: string,
    markupIdentifier?: MarkupIdentifiers,
  ): Promise<string> {

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

    const describedIcon = [identifier, size, overlayIdentifier, state, markupIdentifier];
    const cacheIdentifier = describedIcon.join('_');

    return this.getIconRegistryCache().then((registryCacheIdentifier: string): any => {
      if (!ClientStorage.isset('icon_registry_cache_identifier')
        || ClientStorage.get('icon_registry_cache_identifier') !== registryCacheIdentifier
      ) {
        ClientStorage.unsetByPrefix('icon_');
        ClientStorage.set('icon_registry_cache_identifier', registryCacheIdentifier);
      }

      return this.fetchFromLocal(cacheIdentifier).then(null, (): any => {
        return this.fetchFromRemote(describedIcon, cacheIdentifier);
      });
    });
  }

  private getIconRegistryCache(): Promise<string> {
    const promiseCacheIdentifier = 'icon_registry_cache_identifier';

    if (!this.isPromiseCached(promiseCacheIdentifier)) {
      this.putInPromiseCache(
        promiseCacheIdentifier,
        (new AjaxRequest(TYPO3.settings.ajaxUrls.icons_cache)).get()
          .then(async (response: AjaxResponse): Promise<string> => {
            return await response.resolve()
          })
      );
    }

    return this.getFromPromiseCache(promiseCacheIdentifier);
  }

  /**
   * Performs the AJAX request to fetch the icon
   *
   * @param {Array<string>} icon
   * @param {string} cacheIdentifier
   * @returns {JQueryPromise<any>}
   */
  private fetchFromRemote(icon: Array<string>, cacheIdentifier: string): Promise<string> {
    if (!this.isPromiseCached(cacheIdentifier)) {
      const queryArguments = {
        icon: JSON.stringify(icon),
      };
      this.putInPromiseCache(
        cacheIdentifier,
        (new AjaxRequest(TYPO3.settings.ajaxUrls.icons)).withQueryArguments(queryArguments).get()
          .then(async (response: AjaxResponse): Promise<string> => {
            const markup = await response.resolve();
            if (markup.includes('t3js-icon') && markup.includes('<span class="icon-markup">')) {
              ClientStorage.set('icon_' + cacheIdentifier, markup);
            }
            return markup;
          })
      );
    }
    return this.getFromPromiseCache(cacheIdentifier);
  }

  /**
   * Gets the icon from localStorage
   * @param {string} cacheIdentifier
   * @returns {Promise<string>}
   */
  private fetchFromLocal(cacheIdentifier: string): Promise<string> {
    if (ClientStorage.isset('icon_' + cacheIdentifier)) {
      return Promise.resolve(ClientStorage.get('icon_' + cacheIdentifier));
    }

    return Promise.reject();
  }

  /**
   * Check whether icon was fetched already
   *
   * @param {string} cacheIdentifier
   * @returns {boolean}
   */
  private isPromiseCached(cacheIdentifier: string): boolean {
    return typeof this.promiseCache[cacheIdentifier] !== 'undefined';
  }

  /**
   * Get icon from cache
   *
   * @param {string} cacheIdentifier
   * @returns {Promise<string>}
   */
  private getFromPromiseCache(cacheIdentifier: string): Promise<string> {
    return this.promiseCache[cacheIdentifier];
  }

  /**
   * Put icon into cache
   *
   * @param {string} cacheIdentifier
   * @param {Promise<string>} markup
   */
  private putInPromiseCache(cacheIdentifier: string, markup: Promise<string>): void {
    this.promiseCache[cacheIdentifier] = markup;
  }
}

let iconsObject: Icons;
if (!iconsObject) {
  iconsObject = new Icons();
  TYPO3.Icons = iconsObject;
}

export = iconsObject;
