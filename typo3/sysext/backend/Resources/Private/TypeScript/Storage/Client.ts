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
 * Module: TYPO3/CMS/Backend/Storage/Client
 * Wrapper for localStorage
 * @exports TYPO3/CMS/Backend/Storage/Client
 */
class Client {
  /**
   * Simple localStorage wrapper, to get value from localStorage
   * @param {string} key
   * @returns {string}
   */
  public get = (key: string): string => {
    return localStorage.getItem('t3-' + key);
  }

  /**
   * Simple localStorage wrapper, to set value from localStorage
   *
   * @param {string} key
   * @param {string} value
   * @returns {string}
   */
  public set = (key: string, value: string): void => {
    localStorage.setItem('t3-' + key, value);
  }

  /**
   * Simple localStorage wrapper, to unset value from localStorage
   *
   * @param {string} key
   */
  public unset = (key: string): void => {
    localStorage.removeItem('t3-' + key);
  }

  /**
   * Simple localStorage wrapper, to clear localStorage
   */
  public clear = (): void => {
    localStorage.clear();
  }

  /**
   * Checks if a key was set before, useful to not do all the undefined checks all the time
   *
   * @param {string} key
   * @returns {boolean}
   */
  public isset = (key: string): boolean => {
    const value = this.get(key);
    return (typeof value !== 'undefined' && value !== null);
  }
}

export = new Client();
