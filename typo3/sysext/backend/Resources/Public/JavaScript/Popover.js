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
define(["require", "exports", "jquery", "bootstrap"], function (require, exports, $) {
    "use strict";
    /**
     * Module: TYPO3/CMS/Backend/Popover
     * API for popover windows powered by Twitter Bootstrap.
     * @exports TYPO3/CMS/Backend/Popover
     */
    var Popover = (function () {
        function Popover() {
            /**
             * Default selector string.
             *
             * @return {string}
             */
            this.DEFAULT_SELECTOR = '[data-toggle="popover"]';
        }
        // noinspection JSMethodCanBeStatic
        /**
         * Initialize
         */
        Popover.prototype.initialize = function (selector) {
            selector = selector || this.DEFAULT_SELECTOR;
            $(selector).popover();
        };
        // noinspection JSMethodCanBeStatic
        /**
         * Popover wrapper function
         *
         * @param {JQuery} $element
         */
        Popover.prototype.popover = function ($element) {
            $element.popover();
        };
        // noinspection JSMethodCanBeStatic
        /**
         * Set popover options on $element
         *
         * @param {JQuery} $element
         * @param {PopoverOptions} options
         */
        Popover.prototype.setOptions = function ($element, options) {
            options = options || {};
            var title = options.title || $element.data('title') || '';
            var content = options.content || $element.data('content') || '';
            $element
                .attr('data-original-title', title)
                .attr('data-content', content)
                .attr('data-placement', 'auto')
                .popover(options);
        };
        // noinspection JSMethodCanBeStatic
        /**
         * Set popover option on $element
         *
         * @param {JQuery} $element
         * @param {String} key
         * @param {String} value
         */
        Popover.prototype.setOption = function ($element, key, value) {
            $element.data('bs.popover').options[key] = value;
        };
        // noinspection JSMethodCanBeStatic
        /**
         * Show popover with title and content on $element
         *
         * @param {JQuery} $element
         */
        Popover.prototype.show = function ($element) {
            $element.popover('show');
        };
        // noinspection JSMethodCanBeStatic
        /**
         * Hide popover on $element
         *
         * @param {JQuery} $element
         */
        Popover.prototype.hide = function ($element) {
            $element.popover('hide');
        };
        // noinspection JSMethodCanBeStatic
        /**
         * Destroy popover on $element
         *
         * @param {Object} $element
         */
        Popover.prototype.destroy = function ($element) {
            $element.popover('destroy');
        };
        // noinspection JSMethodCanBeStatic
        /**
         * Toggle popover on $element
         *
         * @param {Object} $element
         */
        Popover.prototype.toggle = function ($element) {
            $element.popover('toggle');
        };
        return Popover;
    }());
    // Create an instance, initialize and return it
    var popover = new Popover();
    popover.initialize();
    TYPO3.Popover = popover;
    return popover;
});
