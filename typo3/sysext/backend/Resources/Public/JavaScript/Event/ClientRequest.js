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
var __extends = (this && this.__extends) || (function () {
    var extendStatics = Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
        function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
define(["require", "exports", "./InteractionRequest"], function (require, exports, InteractionRequest) {
    "use strict";
    var ClientRequest = (function (_super) {
        __extends(ClientRequest, _super);
        function ClientRequest(type, clientEvent) {
            if (clientEvent === void 0) { clientEvent = null; }
            var _this = _super.call(this, type) || this;
            _this.clientEvent = clientEvent;
            return _this;
        }
        return ClientRequest;
    }(InteractionRequest));
    return ClientRequest;
});
