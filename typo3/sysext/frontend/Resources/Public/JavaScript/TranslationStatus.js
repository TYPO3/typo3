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
     * Module: TYPO3/CMS/Frontend/TranslationStatus
     */
    var TranslationStatus = (function () {
        function TranslationStatus() {
            this.registerEvents();
        }
        TranslationStatus.prototype.registerEvents = function () {
            $('input[type="checkbox"][data-lang]').on('change', this.toggleNewButton);
        };
        /**
         * @param {JQueryEventObject} e
         */
        TranslationStatus.prototype.toggleNewButton = function (e) {
            var $me = $(e.currentTarget);
            var languageId = parseInt($me.data('lang'), 10);
            var $newButton = $('.t3js-language-new-' + languageId);
            var $selected = $('input[type="checkbox"][data-lang="' + languageId + '"]:checked');
            $newButton.toggleClass('disabled', $selected.length === 0);
        };
        return TranslationStatus;
    }());
    return new TranslationStatus();
});
