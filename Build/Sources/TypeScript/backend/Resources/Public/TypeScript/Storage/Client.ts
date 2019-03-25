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
  private keyPrefix: string = 't3-';

  /**
   * @returns {boolean}
   */
  private static isCapable(): boolean {
    return localStorage !== null;
  }

  /**
   * Simple localStorage wrapper, to get value from localStorage
   * @param {string} key
   * @returns {string}
   */
  public get = (key: string): string => {
    return Client.isCapable() ? localStorage.getItem(this.keyPrefix + key) : null;
  }

  /**
   * Simple localStorage wrapper, to set value from localStorage
   *
   * @param {string} key
   * @param {string} value
   * @returns {string}
   */
  public set = (key: string, value: string): void => {
    if (Client.isCapable()) {
      localStorage.setItem(this.keyPrefix + key, value);
    }
  }

  /**
   * Simple localStorage wrapper, to unset value from localStorage
   *
   * @param {string} key
   */
  public unset = (key: string): void => {
    if (Client.isCapable()) {
      localStorage.removeItem(this.keyPrefix + key);
    }
  }

  /**
   * Removes values from localStorage by a specific prefix of the key
   *
   * @param {string} prefix
   */
  public unsetByPrefix = (prefix: string): void => {
    if (!Client.isCapable()) {
      return;
    }

    prefix = this.keyPrefix + prefix;

    const keysToDelete: Array<string> = [];
    for (let i = 0; i < localStorage.length; ++i) {
      if (localStorage.key(i).substring(0, prefix.length) === prefix) {
        // Remove the global key prefix, as it gets prepended in unset again
        const key = localStorage.key(i).substr(this.keyPrefix.length);

        // We can't delete the key here as this interferes with the size of the localStorage
        keysToDelete.push(key);
      }
    }

    for (let key of keysToDelete) {
      this.unset(key);
    }
  }

  /**
   * Simple localStorage wrapper, to clear localStorage
   */
  public clear = (): void => {
    if (Client.isCapable()) {
      localStorage.clear();
    }
  }

  /**
   * Checks if a key was set before, useful to not do all the undefined checks all the time
   *
   * @param {string} key
   * @returns {boolean}
   */
  public isset = (key: string): boolean => {
    if (Client.isCapable()) {
      const value = this.get(key);
      return (typeof value !== 'undefined' && value !== null);
    }

    return false;
  }
}

export = new Client();
