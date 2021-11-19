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
 * Module: TYPO3/CMS/Backend/Event/EventDispatcher
 */
export class EventDispatcher {
  static dispatchCustomEvent(name: string, detail: any = null, useTop: boolean = false): void {
    const event = new CustomEvent(name, {detail: detail});
    if (!useTop) {
      document.dispatchEvent(event);
    } else if (typeof top !== 'undefined') {
      top.document.dispatchEvent(event);
    }
  }
}
