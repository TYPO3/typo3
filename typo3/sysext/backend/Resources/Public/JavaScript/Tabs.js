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
define(["require", "exports", "jquery", "./Storage/Client", "bootstrap"], function (require, exports, $, Client) {
    "use strict";
    /**
     * Module: TYPO3/CMS/Backend/Tabs
     * @exports TYPO3/CMS/Backend/Tabs
     */
    var Tabs = (function () {
        function Tabs() {
            this.cacheTimeInSeconds = 1800;
            this.storeLastActiveTab = true;
            this.storage = Client;
            var that = this;
            $('.t3js-tabs').each(function () {
                var $tabContainer = $(this);
                that.storeLastActiveTab = $tabContainer.data('storeLastTab') === 1;
                var currentActiveTab = that.receiveActiveTab($tabContainer.attr('id'));
                if (currentActiveTab) {
                    $tabContainer.find('a[href="' + currentActiveTab + '"]').tab('show');
                }
                $tabContainer.on('show.bs.tab', function (e) {
                    if (that.storeLastActiveTab) {
                        var id = e.currentTarget.id;
                        var target = e.target.hash;
                        that.storeActiveTab(id, target);
                    }
                });
            });
        }
        /**
         * Resolve timestamp
         */
        Tabs.getTimestamp = function () {
            return Math.round((new Date()).getTime() / 1000);
        };
        /**
         * Receive active tab from storage
         *
         * @param {string} id
         * @returns {string}
         */
        Tabs.prototype.receiveActiveTab = function (id) {
            var target = this.storage.get(id) || '';
            var expire = this.storage.get(id + '.expire') || 0;
            if (expire > Tabs.getTimestamp()) {
                return target;
            }
            return '';
        };
        /**
         * Set active tab to storage
         *
         * @param {string} id
         * @param {string} target
         */
        Tabs.prototype.storeActiveTab = function (id, target) {
            this.storage.set(id, target);
            this.storage.set(id + '.expire', Tabs.getTimestamp() + this.cacheTimeInSeconds);
        };
        return Tabs;
    }());
    var tabs = new Tabs();
    return tabs;
});
