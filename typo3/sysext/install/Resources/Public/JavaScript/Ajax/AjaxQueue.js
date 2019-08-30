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
define(["require","exports","jquery"],function(e,t,s){"use strict";return new class{constructor(){this.requestCount=0,this.threshold=10,this.queue=[]}add(e){const t=e.complete;e.complete=((e,u)=>{this.queue.length>0&&this.requestCount<=this.threshold?s.ajax(this.queue.shift()).always(()=>{this.decrementRequestCount()}):this.decrementRequestCount(),t&&t(e,u)}),this.requestCount>=this.threshold?this.queue.push(e):(this.incrementRequestCount(),s.ajax(e))}incrementRequestCount(){this.requestCount++}decrementRequestCount(){this.requestCount>0&&this.requestCount--}}});