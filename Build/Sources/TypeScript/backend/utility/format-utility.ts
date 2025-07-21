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

/**
 * Module: @typo3/backend/utility/format-utility
 *
 * @internal
 */
export class FormatUtility {
  public static fileSizeAsString(size: number, unit: 'iec' | 'si' = 'iec'): string {
    const formats = {
      iec: { base: 1024, labels: [' ', ' KiB', ' MiB', ' GiB', ' TiB', ' PiB', ' EiB', ' ZiB', ' YiB'] },
      si: { base: 1000, 'labels': [' ', ' kB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'] },
    };
    const format = formats[unit];

    const i = size === 0 ? 0 : Math.floor(Math.log(size) / Math.log(format.base));
    return +((size / Math.pow(format.base, i)).toFixed(2)) + format.labels[i];
  }
}
