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

export type GenericKeyValue = { [key: string]: any};

export class InputTransformer {
  /**
   * Transforms data by its incoming headers
   *
   * @param data
   * @param headers
   */
  public static byHeader(data: GenericKeyValue, headers: GenericKeyValue = {}): FormData | string {
    if (headers.hasOwnProperty('Content-Type') && headers['Content-Type'].includes('application/json')) {
      return JSON.stringify(data);
    }

    return InputTransformer.toFormData(data);
  }

  /**
   * Transforms the incoming object to a flat FormData object used for POST and PUT
   *
   * @param {GenericKeyValue} data
   * @return {FormData}
   */
  public static toFormData(data: GenericKeyValue): FormData {
    const flattenedData = InputTransformer.filter(InputTransformer.flattenObject(data));
    const formData = new FormData();
    for (const [key, value] of Object.entries(flattenedData)) {
      formData.set(key, value);
    }

    return formData;
  }

  /**
   * Transforms the incoming object to a flat URLSearchParams object used for GET
   *
   * @param {string | Array<string> | GenericKeyValue} data
   * @return {string}
   */
  public static toSearchParams(data: string | Array<string> | GenericKeyValue): string {
    if (typeof data === 'string') {
      return data;
    }

    if (data instanceof Array) {
      return data.join('&');
    }

    const flattenedData = InputTransformer.filter(InputTransformer.flattenObject(data));
    const searchParams = new URLSearchParams();
    for (const [key, value] of Object.entries(flattenedData)) {
      searchParams.set(key, value);
    }

    return decodeURI(searchParams.toString());
  }

  private static flattenObject(obj: GenericKeyValue, prefix: string = ''): GenericKeyValue {
    return Object.keys(obj).reduce((accumulator: GenericKeyValue, currentValue: any) => {
      const objPrefix = prefix.length ? prefix + '[' : '';
      const objSuffix = prefix.length ? ']' : '';
      if (typeof obj[currentValue] === 'object') {
        Object.assign(accumulator, InputTransformer.flattenObject(obj[currentValue], objPrefix + currentValue + objSuffix))
      } else {
        accumulator[objPrefix + currentValue + objSuffix] = obj[currentValue]
      }
      return accumulator;
    }, {});
  }

  private static filter(obj: GenericKeyValue): GenericKeyValue {
    Object.keys(obj).forEach((key: string): void => {
      if (typeof obj[key] === 'undefined') {
        delete obj[key];
      }
    });
    return obj;
  }
}
