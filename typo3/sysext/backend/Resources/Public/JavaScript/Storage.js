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
define(["require", "exports", "./Storage/Client", "./Storage/Persistent"], function (require, exports, Client, Persistent) {
    "use strict";
    /**
     * Module: TYPO3/CMS/Backend/Storage
     * Adds a public API for the browsers' localStorage called
     * TYPO3.Storage.Client and the Backend Users "uc",
     * available via TYPO3.Storage.Persistent
     * @exports TYPO3/CMS/Backend/Storage
     * @deprecated
     */
    var Storage = (function () {
        function Storage() {
            var _this = this;
            this.logDeprecated = function (className, methodName) {
                if (console) {
                    console.warn('top.TYPO3.Storage.' + className + '.' + methodName + '() is marked as deprecated since TYPO3 v9 and will be '
                        + 'removed in TYPO3 v10.');
                }
            };
            this.Client = {
                clear: function () {
                    _this.logDeprecated('Client', 'clear');
                    Client.clear();
                },
                get: function (key) {
                    _this.logDeprecated('Client', 'get');
                    return Client.get(key);
                },
                isset: function (key) {
                    _this.logDeprecated('Client', 'isset');
                    return Client.isset(key);
                },
                set: function (key, value) {
                    _this.logDeprecated('Client', 'set');
                    return Client.set(key, value);
                },
                unset: function (key) {
                    _this.logDeprecated('Client', 'unset');
                    return Client.unset(key);
                },
            };
            this.Persistent = {
                addToList: function (key, value) {
                    _this.logDeprecated('Persistent', 'addToList');
                    return Persistent.addToList(key, value);
                },
                clear: function () {
                    _this.logDeprecated('Persistent', 'clear');
                    Persistent.clear();
                },
                get: function (key) {
                    _this.logDeprecated('Persistent', 'get');
                    return Persistent.get(key);
                },
                isset: function (key) {
                    _this.logDeprecated('Persistent', 'isset');
                    return Persistent.isset(key);
                },
                load: function (data) {
                    _this.logDeprecated('Persistent', 'load');
                    return Persistent.load(data);
                },
                removeFromList: function (key, value) {
                    _this.logDeprecated('Persistent', 'removeFromList');
                    return Persistent.removeFromList(key, value);
                },
                set: function (key, value) {
                    _this.logDeprecated('Persistent', 'set');
                    return Persistent.set(key, value);
                },
                unset: function (key) {
                    _this.logDeprecated('Persistent', 'unset');
                    return Persistent.unset(key);
                },
            };
        }
        return Storage;
    }());
    var storageObject;
    try {
        // fetch from opening window
        if (window.opener && window.opener.TYPO3 && window.opener.TYPO3.Storage) {
            storageObject = window.opener.TYPO3.Storage;
        }
        // fetch from parent
        if (parent && parent.window.TYPO3 && parent.window.TYPO3.Storage) {
            storageObject = parent.window.TYPO3.Storage;
        }
        // fetch object from outer frame
        if (top && top.TYPO3.Storage) {
            storageObject = top.TYPO3.Storage;
        }
    }
    catch (e) {
        // this only happens if the opener, parent or top is some other url (eg a local file)
        // which loaded the current window. Then the browser's cross domain policy jumps in
        // and raises an exception.
        // for this case we are safe and we can create our global object below.
    }
    if (!storageObject) {
        storageObject = new Storage();
    }
    TYPO3.Storage = storageObject;
    return storageObject;
});
