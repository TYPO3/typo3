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

interface Message {
  actionName: string;
  [key: string]: any;
}

export class MessageUtility {
  /**
   * Generates an URL for usage in postMessage
   *
   * @return {string}
   */
  public static getOrigin(): string {
    return window.origin;
  }

  /**
   * @param {string} receivedOrigin
   */
  public static verifyOrigin(receivedOrigin: string): boolean {
    const currentDomain = MessageUtility.getOrigin();

    return currentDomain === receivedOrigin;
  }

  /**
   * @param {*} message
   * @param {Window} windowObject
   */
  public static send(message: Message, windowObject: Window = window): void {
    windowObject.postMessage(message, MessageUtility.getOrigin());
  }
}
