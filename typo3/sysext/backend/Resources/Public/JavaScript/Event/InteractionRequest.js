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
define(["require","exports"],function(a,b){"use strict";var c=function(){function a(a,b){void 0===b&&(b=null),this.processed=!1,this.processedData=null,this.type=a,this.parentRequest=b}return Object.defineProperty(a.prototype,"outerMostRequest",{get:function(){for(var b=this;b.parentRequest instanceof a;)b=b.parentRequest;return b},enumerable:!0,configurable:!0}),a.prototype.isProcessed=function(){return this.processed},a.prototype.getProcessedData=function(){return this.processedData},a.prototype.setProcessedData=function(a){void 0===a&&(a=null),this.processed=!0,this.processedData=a},a}();return c});