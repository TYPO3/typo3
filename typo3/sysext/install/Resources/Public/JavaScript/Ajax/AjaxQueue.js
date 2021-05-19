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
define(["require","exports","TYPO3/CMS/Core/Ajax/AjaxRequest"],(function(e,t,s){"use strict";return new class{constructor(){this.requests=[],this.requestCount=0,this.threshold=5,this.queue=[]}add(e){this.queue.push(e),this.handleNext()}flush(){this.queue=[],this.requests.forEach(e=>e.abort()),this.requests=[]}handleNext(){this.queue.length>0&&this.requestCount<this.threshold&&(this.incrementRequestCount(),this.sendRequest(this.queue.shift()).finally(()=>{this.decrementRequestCount(),this.handleNext()}))}async sendRequest(e){const t=new s(e.url);let u;return u=void 0!==e.method&&"POST"===e.method.toUpperCase()?t.post(e.data):t.withQueryArguments(e.data||{}).get(),this.requests.push(t),u.then(e.onfulfilled,e.onrejected).then(()=>{const e=this.requests.indexOf(t);delete this.requests[e]})}incrementRequestCount(){this.requestCount++}decrementRequestCount(){this.requestCount>0&&this.requestCount--}}}));