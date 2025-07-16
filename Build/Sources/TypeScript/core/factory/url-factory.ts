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

type NestedURLSearchParams = {[key: string]: NestedURLSearchParams} | string | number | boolean | Array<NestedURLSearchParams> | undefined | null;
export type URLSearchParamsFeedable = Record<string, NestedURLSearchParams> | string | URLSearchParams;

/**
 * Module: @typo3/core/factory/url-factory
 */
export class UrlFactory {
  public static createUrl(url: string | URL, parameters?: URLSearchParamsFeedable): URL {
    const urlObject = new URL(url, window.origin);

    if (parameters !== undefined) {
      const urlSearchParams = UrlFactory.createSearchParams(parameters);

      for (const [key, value] of urlSearchParams) {
        // @todo: handle conflicting parameter types (e.g. string vs. objects), even in nested scenarios
        urlObject.searchParams.set(key, value);
      }
    }

    return urlObject;
  }

  public static createSearchParams(parameters: URLSearchParamsFeedable): URLSearchParams {
    if (parameters instanceof URLSearchParams) {
      return parameters;
    }
    if (typeof parameters === 'string') {
      return new URLSearchParams(parameters);
    }
    return new URLSearchParams(UrlFactory.flattenObject(parameters));
  }

  private static flattenObject(obj: Record<string, NestedURLSearchParams>, prefix: string = ''): Record<string, string> {
    return Object.keys(obj).reduce<Record<string, string>>(
      (accumulator, currentValue) => {
        if (obj[currentValue] === undefined || obj[currentValue] === null) {
          return accumulator;
        }
        const objPrefix = prefix.length ? prefix + '[' : '';
        const objSuffix = prefix.length ? ']' : '';
        if (typeof obj[currentValue] === 'object') {
          return {
            ...accumulator,
            ...UrlFactory.flattenObject(
              Array.isArray(obj[currentValue])
                ? Object.fromEntries(obj[currentValue].map((value, index) => [index, value]))
                : obj[currentValue],
              objPrefix + currentValue + objSuffix
            ),
          };
        }
        return {
          ...accumulator,
          [objPrefix + currentValue + objSuffix]: String(obj[currentValue]),
        };
      },
      {}
    );
  }
}
