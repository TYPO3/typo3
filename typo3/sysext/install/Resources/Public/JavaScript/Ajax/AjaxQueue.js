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
var __awaiter=this&&this.__awaiter||function(t,e,n,s){return new(n||(n=Promise))((function(u,i){function r(t){try{h(s.next(t))}catch(t){i(t)}}function o(t){try{h(s.throw(t))}catch(t){i(t)}}function h(t){var e;t.done?u(t.value):(e=t.value,e instanceof n?e:new n((function(t){t(e)}))).then(r,o)}h((s=s.apply(t,e||[])).next())}))};define(["require","exports","TYPO3/CMS/Core/Ajax/AjaxRequest"],(function(t,e,n){"use strict";return new class{constructor(){this.requestCount=0,this.threshold=10,this.queue=[]}add(t){return __awaiter(this,arguments,void 0,(function*(){const e=t.finally;this.queue.length>0&&this.requestCount<=this.threshold?this.sendRequest(this.queue.shift()).finally(()=>{this.decrementRequestCount()}):this.decrementRequestCount(),e&&e(...arguments),this.requestCount>=this.threshold?this.queue.push(t):(this.incrementRequestCount(),this.sendRequest(t))}))}sendRequest(t){return __awaiter(this,void 0,void 0,(function*(){const e=new n(t.url);let s;return(s=void 0!==t.method&&"POST"===t.method.toUpperCase()?e.post(t.data):e.withQueryArguments(t.data||{}).get()).then(t.onfulfilled,t.onrejected)}))}incrementRequestCount(){this.requestCount++}decrementRequestCount(){this.requestCount>0&&this.requestCount--}}}));