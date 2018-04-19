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
 * Module: TYPO3/CMS/Backend/Storage
 * Adds a public API for the browsers' localStorage called
 * TYPO3.Storage.Client and the Backend Users "uc",
 * available via TYPO3.Storage.Persistent
 */
define(['jquery'], function($) {
  'use strict';

  try {
    // fetch from opening window
    if (window.opener && window.opener.TYPO3 && window.opener.TYPO3.Storage) {
      return window.opener.TYPO3.Storage;
    }

    // fetch from parent
    if (parent && parent.window.TYPO3 && parent.window.TYPO3.Storage) {
      return parent.window.TYPO3.Storage;
    }

    // fetch object from outer frame
    if (top && top.TYPO3.Storage) {
      return top.TYPO3.Storage;
    }
  } catch (e) {
    // This only happens if the opener, parent or top is some other url (eg a local file)
    // which loaded the current window. Then the browser's cross domain policy jumps in
    // and raises an exception.
    // For this case we are safe and we can create our global object below.
  }

  // we didn't find an existing object, so create it
  /**
   *
   * @type {{Client: {}, Persistent: {_data: boolean}}}
   * @exports TYPO3/CMS/Backend/Storage
   */
  var Storage = {
    Client: {},
    Persistent: {
      _data: false
    }
  };

  /**
   * @returns {boolean}
   */
  Storage.Client.isCapable = function() {
    return localStorage !== null;
  };

  /**
   * Simple localStorage wrapper, to get value from localStorage
   * @param {String} key
   * @return {String}
   */
  Storage.Client.get = function(key) {
    return Storage.Client.isCapable() ? localStorage.getItem('t3-' + key) : null;
  };

  /**
   * Simple localStorage wrapper, to set value from localStorage
   * @param {String} key
   * @param {String} value
   */
  Storage.Client.set = function(key, value) {
    if (Storage.Client.isCapable()) {
      localStorage.setItem('t3-' + key, value);
    }
  };

  /**
   * Simple localStorage wrapper, to unset value from localStorage
   * @param {String} key
   */
  Storage.Client.unset = function(key) {
    if (Storage.Client.isCapable()) {
      localStorage.removeItem('t3-' + key);
    }
  };

  /**
   * Simple localStorage wrapper, to clear localStorage
   */
  Storage.Client.clear = function() {
    if (Storage.Client.isCapable()) {
      localStorage.clear();
    }
  };

  /**
   * Checks if a key was set before, useful to not do all the undefined checks all the time
   *
   * @param {String} key
   * @returns {Boolean}
   */
  Storage.Client.isset = function(key) {
    if (Storage.Client.isCapable()) {
      var value = this.get(key);
      return (typeof value !== 'undefined' && value !== null);
    }

    return false;
  };

  /**
   * Persistent storage, stores everything on the server via AJAX, does a greedy load on read
   * common functions get/set/clear
   *
   * @param {String} key
   * @returns {*}
   */
  Storage.Persistent.get = function(key) {
    if (this._data === false) {
      var value;
      this._loadFromServer().done(function() {
        value = Storage.Persistent._getRecursiveDataByDeepKey(Storage.Persistent._data, key.split('.'));
      });
      return value;
    } else {
      return this._getRecursiveDataByDeepKey(this._data, key.split('.'));
    }
  };

  /**
   * Store data persistent on server
   *
   * @param {String} key
   * @param {String} value
   * @returns {jQuery}
   */
  Storage.Persistent.set = function(key, value) {
    if (this._data !== false) {
      this._data = this._setRecursiveDataByDeepKey(this._data, key.split('.'), value);
    }
    return this._storeOnServer(key, value);
  };

  /**
   *
   * @param {String} key
   * @param {String} value
   * @returns {*}
   */
  Storage.Persistent.addToList = function(key, value) {
    return $.ajax(TYPO3.settings.ajaxUrls['usersettings_process'], {
      method: 'post',
      data: {'action': 'addToList', key: key, value: value}
    }).done(function(data) {
      Storage.Persistent._data = data;
    });
  };

  /**
   *
   * @param {String} key
   * @param {String} value
   * @returns {*}
   */
  Storage.Persistent.removeFromList = function(key, value) {
    return $.ajax(TYPO3.settings.ajaxUrls['usersettings_process'], {
      method: 'post',
      data: {'action': 'removeFromList', key: key, value: value}
    }).done(function(data) {
      Storage.Persistent._data = data;
    });
  };

  /**
   *
   * @param {String} key
   * @returns {*}
   */
  Storage.Persistent.unset = function(key) {
    return $.ajax(TYPO3.settings.ajaxUrls['usersettings_process'], {
      method: 'post',
      data: {'action': 'unset', key: key}
    }).done(function(data) {
      Storage.Persistent._data = data;
    });
  };

  /**
   *
   */
  Storage.Persistent.clear = function() {
    $.ajax(TYPO3.settings.ajaxUrls['usersettings_process'], {data: {'action': 'clear'}});
    this._data = false;
  };

  /**
   * Checks if a key was set before, useful to not do all the undefined checks all the time
   *
   * @param {String} key
   * @returns {Boolean}
   */
  Storage.Persistent.isset = function(key) {
    var value = this.get(key);
    return (typeof value !== 'undefined' && typeof value !== 'null' && value != 'undefined');
  };

  /**
   * Loads the data from outside, only used for the initial call from BackendController
   *
   * @param {String} data
   */
  Storage.Persistent.load = function(data) {
    this._data = data;
  };

  /**
   * Loads all data from the server
   *
   * @returns {*}
   * @private
   */
  Storage.Persistent._loadFromServer = function() {
    return $.ajax(TYPO3.settings.ajaxUrls['usersettings_process'], {
      data: {'action': 'getAll'},
      async: false
    }).done(function(data) {
      Storage.Persistent._data = data;
    });
  };

  /**
   * Stores data on the server, and gets the updated data on return
   * to always be up-to-date inside the browser
   *
   * @param {String} key
   * @param {String} value
   * @returns {*}
   * @private
   */
  Storage.Persistent._storeOnServer = function(key, value) {
    return $.ajax(TYPO3.settings.ajaxUrls['usersettings_process'], {
      method: 'post',
      data: {'action': 'set', key: key, value: value}
    }).done(function(data) {
      Storage.Persistent._data = data;
    });
  };

  /**
   * helper function used to set a value which could have been a flat object key data["my.foo.bar"] to
   * data[my][foo][bar]
   * is called recursively by itself
   *
   * @param {Object} data the data to be uased as base
   * @param {String} keyParts the keyParts for the subtree
   * @param {String} value the value to be set
   * @returns {Object} the data object
   * @private
   */
  Storage.Persistent._setRecursiveDataByDeepKey = function(data, keyParts, value) {
    if (keyParts.length === 1) {
      data = data || {};
      data[keyParts[0]] = value;
    } else {
      var firstKey = keyParts.shift();
      data[firstKey] = this._setRecursiveDataByDeepKey(data[firstKey] || {}, keyParts, value);
    }
    return data;
  };

  /**
   * Helper function used to set a value which could have been a flat object key data["my.foo.bar"] to
   * data[my][foo][bar] is called recursively by itself
   *
   * @param {Object} data the data to be uased as base
   * @param {String} keyParts the keyParts for the subtree
   * @returns {Object}
   * @private
   */
  Storage.Persistent._getRecursiveDataByDeepKey = function(data, keyParts) {
    if (keyParts.length === 1) {
      return (data || {})[keyParts[0]];
    } else {
      var firstKey = keyParts.shift();
      return this._getRecursiveDataByDeepKey(data[firstKey] || {}, keyParts);
    }
  };

  // attach to global frame
  TYPO3.Storage = Storage;

  return Storage;
});
