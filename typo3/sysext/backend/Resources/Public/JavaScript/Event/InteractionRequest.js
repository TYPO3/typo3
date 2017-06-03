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
    var InteractionRequest = (function () {
        function InteractionRequest(type, parentRequest) {
            if (parentRequest === void 0) { parentRequest = null; }
            this.processed = false;
            this.processedData = null;
            this.type = type;
            this.parentRequest = parentRequest;
        }
        Object.defineProperty(InteractionRequest.prototype, "outerMostRequest", {
            get: function () {
                var request = this;
                while (request.parentRequest instanceof InteractionRequest) {
                    request = request.parentRequest;
                }
                return request;
            },
            enumerable: true,
            configurable: true
        });
        InteractionRequest.prototype.isProcessed = function () {
            return this.processed;
        };
        InteractionRequest.prototype.getProcessedData = function () {
            return this.processedData;
        };
        InteractionRequest.prototype.setProcessedData = function (processedData) {
            if (processedData === void 0) { processedData = null; }
            this.processed = true;
            this.processedData = processedData;
        };
        return InteractionRequest;
    }());
    return InteractionRequest;
});
