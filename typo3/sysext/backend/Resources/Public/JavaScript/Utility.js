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
 * Module: TYPO3/CMS/Backend/Utility
 */
define(['jquery'], function($) {

  /**
   * The main Utility object
   *
   * @type {{}}
   * @exports TYPO3/CMS/Backend/Utility
   */
  var Utility = {};

  /**
   * Checks if a given number is really a number
   *
   * Taken from:
   * http://dl.dropbox.com/u/35146/js/tests/isNumber.html
   *
   * @param {String} number
   * @returns {boolean|*}
   */
  Utility.isNumber = function(number) {
    return !isNaN(parseFloat(number)) && isFinite(number);
  };

  /**
   * Gets a parameter from a given url
   *
   * @param {String} url
   * @param {String} parameter
   * @returns {String}
   */
  Utility.getParameterFromUrl = function(url, parameter) {
    if (typeof url.split !== 'function') {
      return '';
    }
    var parts = url.split('?'),
      value = '';

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
   * @param {String} url
   * @param {String} key
   * @param {String} value
   */
  Utility.updateQueryStringParameter = function(url, key, value) {
    var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i'),
      separator = url.indexOf('?') !== -1 ? '&' : '?';

    if (url.match(re)) {
      return url.replace(re, '$1' + key + '=' + value + '$2');
    }
    return url + separator + key + '=' + value;
  };

  TYPO3.Utility = Utility;
  return Utility;
});
