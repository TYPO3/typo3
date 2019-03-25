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
 * Convert textarea so they grow when it is typed in.
 */
export class Resizable {

  /**
   * @param {HTMLTextAreaElement} textarea
   */
  public static enable(textarea: HTMLTextAreaElement): void {
    if (TYPO3.settings.Textarea && TYPO3.settings.Textarea.autosize) {
      require(['autosize'], (autosize: Function): void => {
        autosize(textarea);
      });
    }
  }
}
