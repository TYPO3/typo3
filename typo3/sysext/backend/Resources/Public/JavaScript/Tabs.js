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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","./Storage/Client","bootstrap"],(function(t,e,s,a){"use strict";s=__importDefault(s);class i{constructor(){this.cacheTimeInSeconds=1800,this.storeLastActiveTab=!0,this.storage=a;const t=this;s.default(()=>{s.default(".t3js-tabs").each((function(){const e=s.default(this);t.storeLastActiveTab=1===e.data("storeLastTab");const a=t.receiveActiveTab(e.attr("id"));a&&e.find('a[href="'+a+'"]').tab("show"),e.on("show.bs.tab",e=>{if(t.storeLastActiveTab){const s=e.currentTarget.id,a=e.target.hash;t.storeActiveTab(s,a)}})}))})}static getTimestamp(){return Math.round((new Date).getTime()/1e3)}receiveActiveTab(t){const e=this.storage.get(t)||"";return(this.storage.get(t+".expire")||0)>i.getTimestamp()?e:""}storeActiveTab(t,e){this.storage.set(t,e),this.storage.set(t+".expire",i.getTimestamp()+this.cacheTimeInSeconds)}}return new i}));