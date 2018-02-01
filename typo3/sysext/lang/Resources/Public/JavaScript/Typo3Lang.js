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

Ext.ns('TYPO3.l10n');

TYPO3.l10n = function() {

  /**
   * Protected copy of translationUnits
   * @private
   */
  var lang = [],

    sanitize = function() {
      if (typeof TYPO3.lang !== 'undefined') {
        for (key in TYPO3.lang) {
          lang[key] = TYPO3.lang[key];

          if (!Ext.isString(TYPO3.lang[key])) {
            TYPO3.lang[key] = TYPO3.lang[key][0].target;
          }
        }
      }
    };

  return {

    initialize: function() {
      sanitize();
    },

    localize: function(label, replace, plural) {
      if (typeof lang === 'undefined' || typeof lang[label] === 'undefined') {
        return false;
      }

      var i = plural || 0,
        translationUnit = lang[label],
        label = null, regexp = null;

      // Get localized label
      if (Ext.isString(translationUnit)) {
        label = translationUnit;
      } else {
        label = translationUnit[i]['target'];
      }

      // Replace
      if (typeof replace !== 'undefined') {
        for (key in replace) {
          regexp = new RegExp('%' + key + '|%s');
          label = label.replace(regexp, replace[key]);
        }
      }

      return label;
    }
  };
}();

TYPO3.l10n.initialize();
