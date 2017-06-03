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
    var InteractionRequestMap = (function () {
        function InteractionRequestMap() {
            this.assignments = [];
        }
        InteractionRequestMap.prototype.attachFor = function (request, deferred) {
            var targetAssignment = this.getFor(request);
            if (targetAssignment === null) {
                targetAssignment = { request: request, deferreds: [] };
                this.assignments.push(targetAssignment);
            }
            targetAssignment.deferreds.push(deferred);
        };
        InteractionRequestMap.prototype.detachFor = function (request) {
            var targetAssignment = this.getFor(request);
            this.assignments = this.assignments.filter(function (assignment) { return assignment === targetAssignment; });
        };
        InteractionRequestMap.prototype.getFor = function (triggerEvent) {
            var targetAssignment = null;
            this.assignments.some(function (assignment) {
                if (assignment.request === triggerEvent) {
                    targetAssignment = assignment;
                    return true;
                }
                return false;
            });
            return targetAssignment;
        };
        InteractionRequestMap.prototype.resolveFor = function (triggerEvent) {
            var targetAssignment = this.getFor(triggerEvent);
            if (targetAssignment === null) {
                return false;
            }
            targetAssignment.deferreds.forEach(function (deferred) { return deferred.resolve(); });
            this.detachFor(triggerEvent);
            return true;
        };
        InteractionRequestMap.prototype.rejectFor = function (triggerEvent) {
            var targetAssignment = this.getFor(triggerEvent);
            if (targetAssignment === null) {
                return false;
            }
            targetAssignment.deferreds.forEach(function (deferred) { return deferred.reject(); });
            this.detachFor(triggerEvent);
            return true;
        };
        return InteractionRequestMap;
    }());
    return new InteractionRequestMap();
});
