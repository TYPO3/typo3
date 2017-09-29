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
define(["require", "exports", "jquery"], function (require, exports, $) {
    "use strict";
    /**
     * Module: TYPO3/CMS/Backend/Storage/Persistent
     * Wrapper for persistent storage in UC
     * @exports TYPO3/CMS/Backend/Storage/Persistent
     */
    var Persistent = (function () {
        function Persistent() {
            var _this = this;
            this.data = false;
            /**
             * Persistent storage, stores everything on the server via AJAX, does a greedy load on read
             * common functions get/set/clear
             *
             * @param {String} key
             * @returns {*}
             */
            this.get = function (key) {
                var me = _this;
                if (_this.data === false) {
                    var value_1;
                    _this.loadFromServer().done(function () {
                        value_1 = me.getRecursiveDataByDeepKey(me.data, key.split('.'));
                    });
                    return value_1;
                }
                return _this.getRecursiveDataByDeepKey(_this.data, key.split('.'));
            };
            /**
             * Store data persistent on server
             *
             * @param {String} key
             * @param {String} value
             * @returns {$}
             */
            this.set = function (key, value) {
                if (_this.data !== false) {
                    _this.data = _this.setRecursiveDataByDeepKey(_this.data, key.split('.'), value);
                }
                return _this.storeOnServer(key, value);
            };
            /**
             * @param {string} key
             * @param {string} value
             * @returns {$}
             */
            this.addToList = function (key, value) {
                var me = _this;
                return $.ajax(TYPO3.settings.ajaxUrls.usersettings_process, {
                    data: {
                        action: 'addToList',
                        key: key,
                        value: value,
                    },
                    method: 'post',
                }).done(function (data) {
                    me.data = data;
                });
            };
            /**
             * @param {string} key
             * @param {string} value
             * @returns {$}
             */
            this.removeFromList = function (key, value) {
                var me = _this;
                return $.ajax(TYPO3.settings.ajaxUrls.usersettings_process, {
                    data: {
                        action: 'removeFromList',
                        key: key,
                        value: value,
                    },
                    method: 'post',
                }).done(function (data) {
                    me.data = data;
                });
            };
            this.unset = function (key) {
                var me = _this;
                return $.ajax(TYPO3.settings.ajaxUrls.usersettings_process, {
                    data: {
                        action: 'unset',
                        key: key,
                    },
                    method: 'post',
                }).done(function (data) {
                    me.data = data;
                });
            };
            /**
             * Clears the UC
             */
            this.clear = function () {
                $.ajax(TYPO3.settings.ajaxUrls.usersettings_process, {
                    data: {
                        action: 'clear',
                    },
                });
                _this.data = false;
            };
            /**
             * Checks if a key was set before, useful to not do all the undefined checks all the time
             *
             * @param {string} key
             * @returns {boolean}
             */
            this.isset = function (key) {
                var value = _this.get(key);
                return (typeof value !== 'undefined' && value !== null);
            };
            /**
             * Loads the data from outside, only used for the initial call from BackendController
             *
             * @param {String} data
             */
            this.load = function (data) {
                _this.data = data;
            };
            /**
             * Loads all data from the server
             *
             * @returns {$}
             */
            this.loadFromServer = function () {
                var me = _this;
                return $.ajax(TYPO3.settings.ajaxUrls.usersettings_process, {
                    async: false,
                    data: {
                        action: 'getAll',
                    },
                }).done(function (data) {
                    me.data = data;
                });
            };
            /**
             * Stores data on the server, and gets the updated data on return
             * to always be up-to-date inside the browser
             *
             * @param {string} key
             * @param {string} value
             * @returns {*}
             */
            this.storeOnServer = function (key, value) {
                var me = _this;
                return $.ajax(TYPO3.settings.ajaxUrls.usersettings_process, {
                    data: {
                        action: 'set',
                        key: key,
                        value: value,
                    },
                    method: 'post',
                }).done(function (data) {
                    me.data = data;
                });
            };
            /**
             * Helper function used to set a value which could have been a flat object key data["my.foo.bar"] to
             * data[my][foo][bar] is called recursively by itself
             *
             * @param {Object} data the data to be uased as base
             * @param {String} keyParts the keyParts for the subtree
             * @returns {Object}
             */
            this.getRecursiveDataByDeepKey = function (data, keyParts) {
                if (keyParts.length === 1) {
                    return (data || {})[keyParts[0]];
                }
                var firstKey = keyParts.shift();
                return _this.getRecursiveDataByDeepKey(data[firstKey] || {}, keyParts);
            };
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
            this.setRecursiveDataByDeepKey = function (data, keyParts, value) {
                if (keyParts.length === 1) {
                    data = data || {};
                    data[keyParts[0]] = value;
                }
                else {
                    var firstKey = keyParts.shift();
                    data[firstKey] = _this.setRecursiveDataByDeepKey(data[firstKey] || {}, keyParts, value);
                }
                return data;
            };
        }
        return Persistent;
    }());
    return new Persistent();
});
