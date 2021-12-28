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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","./Storage/BrowserSession","./Storage/Client","bootstrap"],(function(t,e,s,a,r){"use strict";s=__importDefault(s);return new class{constructor(){this.storeLastActiveTab=!0;const t=this;(0,s.default)(()=>{(0,s.default)(".t3js-tabs").each((function(){const e=(0,s.default)(this);t.storeLastActiveTab=1===e.data("storeLastTab");const a=t.receiveActiveTab(e.attr("id"));a&&e.find('a[href="'+a+'"]').tab("show"),e.on("show.bs.tab",e=>{if(t.storeLastActiveTab){const s=e.currentTarget.id,a=e.target.hash;t.storeActiveTab(s,a)}})}))}),r.unsetByPrefix("tabs-")}static getTimestamp(){return Math.round((new Date).getTime()/1e3)}receiveActiveTab(t){return a.get(t)||""}storeActiveTab(t,e){a.set(t,e)}}}));