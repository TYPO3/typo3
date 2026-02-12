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
 * TYPO3 date configuration from backend
 * Available at TYPO3.settings.DateConfiguration
 *
 * Format strings use date format tokens for JavaScript
 * @see https://moment.github.io/luxon/#/formatting?id=table-of-tokens
 */
export interface DateConfiguration {
  timezone: string;
  formats: {
    /** Date format string (e.g., "yyyy-MM-dd") */
    date: string;
    /** DateTime format string (e.g., "yyyy-MM-dd HH:mm") */
    datetime: string;
  };
}
