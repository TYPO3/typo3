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
 * Module: @typo3/backend/utility/dom-helper
 *
 * @internal
 */
export default class DomHelper {
  /**
   * Get all parent elements matching the passed `selector`
   */
  public static parents(el: Element, selector: string): Element[] {
    const parents = [];
    let closest;
    while ((closest = el.parentElement.closest(selector)) !== null) {
      el = closest;
      parents.push(closest);
    }

    return parents;
  }
}
