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
define(["require", "exports"], function (require, exports) {
    "use strict";
    /**
     * Module: TYPO3/CMS/Backend/Utility
     */
    var Utility = (function () {
        function Utility() {
            /**
             * Checks if a given number is really a number
             *
             * Taken from:
             * http://dl.dropbox.com/u/35146/js/tests/isNumber.html
             *
             * @param {number} value
             * @returns {boolean}
             */
            this.isNumber = function (value) {
                return !isNaN(parseFloat(value.toString())) && isFinite(value);
            };
            /**
             * Gets a parameter from a given url
             *
             * @param {string} url
             * @param {string} parameter
             * @returns {string}
             */
            this.getParameterFromUrl = function (url, parameter) {
                if (typeof url.split !== 'function') {
                    return '';
                }
                var parts = url.split('?');
                var value = '';
                if (parts.length >= 2) {
                    var queryString = parts.join('?');
                    var prefix = encodeURIComponent(parameter) + '=';
                    var parameters = queryString.split(/[&;]/g);
                    for (var i = parameters.length; i-- > 0;) {
                        if (parameters[i].lastIndexOf(prefix, 0) !== -1) {
                            value = parameters[i].split('=')[1];
                            break;
                        }
                    }
                }
                return value;
            };
            /**
             * Updates a parameter inside of given url
             *
             * @param {string} url
             * @param {string} key
             * @param {string} value
             * @returns {string}
             */
            this.updateQueryStringParameter = function (url, key, value) {
                var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i');
                var separator = url.indexOf('?') !== -1 ? '&' : '?';
                if (url.match(re)) {
                    return url.replace(re, '$1' + key + '=' + value + '$2');
                }
                return url + separator + key + '=' + value;
            };
        }
        return Utility;
    }());
    var utilityObject = new Utility();
    // @deprecated since TYPO3 v9, will be removed in TYPO3 v10. Use the TYPO3/CMS/Backend/Utility module in AMD instead
    TYPO3.Utility = utilityObject;
    return utilityObject;
});
