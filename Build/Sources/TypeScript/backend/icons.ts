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

import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import ClientStorage from './storage/client';
import { Sizes, States, MarkupIdentifiers } from './enum/icon-types';
import { css, CSSResult, unsafeCSS } from 'lit';

interface PromiseCache {
  [key: string]: Promise<string>;
}

export class IconStyles {
  public static getStyles(): CSSResult[] {
    return [
      css`
        :host {
          --icon-color-primary: currentColor;
          --icon-size-small: 16px;
          --icon-size-medium: 32px;
          --icon-size-large: 48px;
          --icon-size-mega: 64px;
          --icon-unify-modifier: 0.86;
          --icon-opacity-disabled: 0.5

          display: inline-block;
        }

        .icon-wrapper {
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .icon {
          position: relative;
          display: inline-flex;
          overflow: hidden;
          white-space: nowrap;
          height: var(--icon-size, 1em);
          width: var(--icon-size, 1em);
          line-height: var(--icon-size, 1em);
          flex-shrink: 0;
        }

        .icon img, .icon svg {
          display: block;
          height: 100%;
          width: 100%
        }

        .icon * {
          display: block;
          line-height: inherit
        }

        .icon-markup {
          position: absolute;
          display: block;
          text-align: center;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0
        }

        .icon-overlay {
          position: absolute;
          bottom: 0;
          right: 0;
          height: 68.75%;
          width: 68.75%;
          text-align: center
        }

        .icon-color {
          fill: var(--icon-color-primary)
        }

        .icon-spin .icon-markup {
          -webkit-animation: icon-spin 2s infinite linear;
          animation: icon-spin 2s infinite linear
        }

        @keyframes icon-spin {
          0% {
            transform: rotate(0)
          }
          100% {
            transform: rotate(360deg)
          }
        }

        .icon-state-disabled .icon-markup {
          opacity: var(--icon-opacity-disabled)
        }
      `,
      IconStyles.getStyleSizeVariant(Sizes.small),
      IconStyles.getStyleSizeVariant(Sizes.default),
      IconStyles.getStyleSizeVariant(Sizes.medium),
      IconStyles.getStyleSizeVariant(Sizes.large),
      IconStyles.getStyleSizeVariant(Sizes.mega),
    ];
  }

  public static getStyleSizeVariant(variant: string): CSSResult {
    const variantResult = unsafeCSS(variant);
    return css`
      :host([size=${variantResult}]) .icon-size-${variantResult},
      :host([raw]) .icon-size-${variantResult} {
        --icon-size: var(--icon-size-${variantResult})
      }
      :host([size=${variantResult}]) .icon-size-${variantResult} .icon-unify,
      :host([raw]) .icon-size-${variantResult} .icon-unify {
        line-height: var(--icon-size);
        font-size: calc(var(--icon-size) * var(--icon-unify-modifier))
      }
      :host([size=${variantResult}]) .icon-size-${variantResult} .icon-overlay .icon-unify,
      :host([raw]) .icon-size-${variantResult} .icon-overlay .icon-unify {
        line-height: calc(var(--icon-size) / 1.6);
        font-size: calc((var(--icon-size) / 1.6) * var(--icon-unify-modifier))
      }
    `;
  }
}

/**
 * Module: @typo3/backend/icons
 * Uses the icon API of the core to fetch icons via AJAX.
 */
class Icons {
  public readonly sizes: typeof Sizes = Sizes;
  public readonly states: typeof States = States;
  public readonly markupIdentifiers: typeof MarkupIdentifiers = MarkupIdentifiers;
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

    return this.getIconRegistryCache().then((registryCacheIdentifier: string): Promise<string> => {
      if (!ClientStorage.isset('icon_registry_cache_identifier')
        || ClientStorage.get('icon_registry_cache_identifier') !== registryCacheIdentifier
      ) {
        ClientStorage.unsetByPrefix('icon_');
        ClientStorage.set('icon_registry_cache_identifier', registryCacheIdentifier);
      }

      return this.fetchFromLocal(cacheIdentifier).then(null, (): Promise<string> => {
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
            return await response.resolve();
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

export default iconsObject;
