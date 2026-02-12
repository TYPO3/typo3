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
 * Module: @typo3/form/backend/form-editor/utility/string-utility
 *
 * String utility functions for the form editor
 */

/**
 * Strip HTML tags from a string
 *
 * @param value - String potentially containing HTML tags
 * @returns Plain text without HTML tags
 */
export function stripTags(value: string): string {
  if (!value) {
    return value;
  }
  const tempElement = document.createElement('div');
  tempElement.innerHTML = value;
  return tempElement.textContent || tempElement.innerText || '';
}
