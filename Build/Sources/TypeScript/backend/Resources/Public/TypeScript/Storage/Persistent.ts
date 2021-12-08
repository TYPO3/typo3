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

import $ from 'jquery';

/**
 * Module: TYPO3/CMS/Backend/Storage/Persistent
 * Wrapper for persistent storage in UC
 * @exports TYPO3/CMS/Backend/Storage/Persistent
 */
class Persistent {
  private data: any = false;

  /**
   * Persistent storage, stores everything on the server via AJAX, does a greedy load on read
   * common functions get/set/clear
   *
   * @param {String} key
   * @returns {*}
   */
  public get = (key: string): any => {
    const me = this;

    if (this.data === false) {
      let value;
      this.loadFromServer().done(() => {
        value = me.getRecursiveDataByDeepKey(me.data, key.split('.'));
      });
      return value;
    }

    return this.getRecursiveDataByDeepKey(this.data, key.split('.'));
  }

  /**
   * Store data persistent on server
   *
   * @param {String} key
   * @param {String} value
   * @returns {$}
   */
  public set = (key: string, value: string|object): any => {
    if (this.data !== false) {
      this.data = this.setRecursiveDataByDeepKey(this.data, key.split('.'), value);
    }
    return this.storeOnServer(key, value);
  }

  /**
   * @param {string} key
   * @param {string} value
   * @returns {$}
   */
  public addToList = (key: string, value: string): any => {
    const me = this;
    return $.ajax(TYPO3.settings.ajaxUrls.usersettings_process, {
      data: {
        action: 'addToList',
        key,
        value,
      },
      method: 'post',
    }).done((data: any): any => {
      me.data = data;
    });
  }

  /**
   * @param {string} key
   * @param {string} value
   * @returns {$}
   */
  public removeFromList = (key: string, value: string): any => {
    const me = this;
    return $.ajax(TYPO3.settings.ajaxUrls.usersettings_process, {
      data: {
        action: 'removeFromList',
        key,
        value,
      },
      method: 'post',
    }).done((data: any): any => {
      me.data = data;
    });
  }

  public unset = (key: string): any => {
    const me = this;
    return $.ajax(TYPO3.settings.ajaxUrls.usersettings_process, {
      data: {
        action: 'unset',
        key,
      },
      method: 'post',
    }).done((data: any): any => {
      me.data = data;
    });
  }

  /**
   * Clears the UC
   */
  public clear = (): any => {
    $.ajax(TYPO3.settings.ajaxUrls.usersettings_process, {
      data: {
        action: 'clear',
      },
      method: 'post',
    });
    this.data = false;
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

  /**
   * Loads the data from outside, only used for the initial call from BackendController
   *
   * @param {String} data
   */
  public load = (data: any): any => {
    this.data = data;
  }

  /**
   * Loads all data from the server
   *
   * @returns {$}
   */
  private loadFromServer = (): any => {
    const me = this;
    return $.ajax(TYPO3.settings.ajaxUrls.usersettings_process, {
      async: false,
      data: {
        action: 'getAll',
      },
    }).done((data: any) => {
      me.data = data;
    });
  }

  /**
   * Stores data on the server, and gets the updated data on return
   * to always be up-to-date inside the browser
   *
   * @param {string} key
   * @param {string} value
   * @returns {*}
   */
  private storeOnServer = (key: string, value: string|object): any => {
    const me = this;
    return $.ajax(TYPO3.settings.ajaxUrls.usersettings_process, {
      data: {
        action: 'set',
        key,
        value,
      },
      method: 'post',
    }).done((data: any): any => {
      me.data = data;
    });
  }

  /**
   * Helper function used to set a value which could have been a flat object key data["my.foo.bar"] to
   * data[my][foo][bar] is called recursively by itself
   *
   * @param {Object} data the data to be used as base
   * @param {String} keyParts the keyParts for the subtree
   * @returns {Object}
   */
  private getRecursiveDataByDeepKey = (data: any, keyParts: any[]): any => {
    if (keyParts.length === 1) {
      return (data || {})[keyParts[0]];
    }

    const firstKey = keyParts.shift();
    return this.getRecursiveDataByDeepKey(data[firstKey] || {}, keyParts);
  }

  /**
   * helper function used to set a value which could have been a flat object key data["my.foo.bar"] to
   * data[my][foo][bar]
   * is called recursively by itself
   *
   * @param data
   * @param {any[]} keyParts
   * @param {string} value
   * @returns {any[]}
   */
  private setRecursiveDataByDeepKey = (data: any, keyParts: any[], value: string|object): any[] => {
    if (keyParts.length === 1) {
      data = data || {};
      data[keyParts[0]] = value;
    } else {
      const firstKey = keyParts.shift();
      data[firstKey] = this.setRecursiveDataByDeepKey(data[firstKey] || {}, keyParts, value);
    }
    return data;
  }
}

export default new Persistent();
