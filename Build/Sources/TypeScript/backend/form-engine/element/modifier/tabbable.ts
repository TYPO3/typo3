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
 * Convert textarea to enable tab
 */
export class Tabbable {

  /**
   * @param {HTMLTextAreaElement} textarea
   */
  public static enable(textarea: HTMLTextAreaElement): void {
    if (textarea.classList.contains('t3js-enable-tab')) {
      import('taboverride').then(({default: taboverride}): void => {
        taboverride.set(textarea);
      });
    }
  }
}
