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
define(["require","exports"],(function(e,t){"use strict";return function(){function e(e,t){void 0===t&&(t=null),this.processed=!1,this.processedData=null,this.type=e,this.parentRequest=t}return Object.defineProperty(e.prototype,"outerMostRequest",{get:function(){for(var t=this;t.parentRequest instanceof e;)t=t.parentRequest;return t},enumerable:!0,configurable:!0}),e.prototype.isProcessed=function(){return this.processed},e.prototype.getProcessedData=function(){return this.processedData},e.prototype.setProcessedData=function(e){void 0===e&&(e=null),this.processed=!0,this.processedData=e},e}()}));