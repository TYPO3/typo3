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
    var ConsumerScope = (function () {
        function ConsumerScope() {
            this.consumers = [];
        }
        ConsumerScope.prototype.getConsumers = function () {
            return this.consumers;
        };
        ConsumerScope.prototype.hasConsumer = function (consumer) {
            return this.consumers.indexOf(consumer) !== -1;
        };
        ConsumerScope.prototype.attach = function (consumer) {
            if (!this.hasConsumer(consumer)) {
                this.consumers.push(consumer);
            }
        };
        ConsumerScope.prototype.detach = function (consumer) {
            this.consumers = this.consumers.filter(function (currentConsumer) { return currentConsumer !== consumer; });
        };
        ConsumerScope.prototype.invoke = function (request) {
            var deferreds = [];
            this.consumers.forEach(function (consumer) {
                var deferred = consumer.consume.call(consumer, request);
                if (deferred) {
                    deferreds.push(deferred);
                }
            });
            return $.when.apply($, deferreds);
        };
        return ConsumerScope;
    }());
    return new ConsumerScope();
});
