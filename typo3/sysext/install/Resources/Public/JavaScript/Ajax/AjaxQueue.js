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
define(["require","exports","jquery"],function(e,t,u){"use strict";return new(function(){function e(){this.requestCount=0,this.threshold=10,this.queue=[]}return e.prototype.add=function(e){var t=this,n=e.complete;e.complete=function(e,o){t.queue.length>0&&t.requestCount<=t.threshold?u.ajax(t.queue.shift()).always(function(){t.decrementRequestCount()}):t.decrementRequestCount(),n&&n(e,o)},this.requestCount>=this.threshold?this.queue.push(e):(this.incrementRequestCount(),u.ajax(e))},e.prototype.incrementRequestCount=function(){this.requestCount++},e.prototype.decrementRequestCount=function(){this.requestCount>0&&this.requestCount--},e}())});