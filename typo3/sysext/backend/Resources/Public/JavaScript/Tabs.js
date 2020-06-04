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
define(["require","exports","jquery","./Storage/BrowserSession","./Storage/Client","bootstrap"],(function(t,e,s,r,a){"use strict";return new class{constructor(){this.storeLastActiveTab=!0;const t=this;s(()=>{s(".t3js-tabs").each((function(){const e=s(this);t.storeLastActiveTab=1===e.data("storeLastTab");const r=t.receiveActiveTab(e.attr("id"));r&&e.find('a[href="'+r+'"]').tab("show"),e.on("show.bs.tab",e=>{if(t.storeLastActiveTab){const s=e.currentTarget.id,r=e.target.hash;t.storeActiveTab(s,r)}})}))}),a.unsetByPrefix("tabs-")}static getTimestamp(){return Math.round((new Date).getTime()/1e3)}receiveActiveTab(t){return r.get(t)||""}storeActiveTab(t,e){r.set(t,e)}}}));