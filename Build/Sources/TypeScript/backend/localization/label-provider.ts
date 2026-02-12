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

export class LabelProvider<Labels extends Record<string, string> = Record<string, string>> {
  constructor(
    private readonly labels: Labels
  ) {}

  public get(key: keyof Labels, ...args: Array<string|number>): string {
    if (!(key in this.labels)) {
      throw new Error('Label is not defined: ' + String(key));
    }
    let index = 0;
    // code taken from lit-helper
    return this.labels[key].replace(/%[sdf]/g, (match) => {
      const arg = args[index++];
      switch (match) {
        case '%s':
          return String(arg);
        case '%d':
          return String(typeof arg === 'number' ? arg : parseInt(String(arg), 10));
        case '%f':
          return String(typeof arg === 'number' ? arg : parseFloat(arg).toFixed(2));
        default:
          return match;
      }
    });
  }
}
