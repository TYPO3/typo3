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
import"bootstrap";import $ from"jquery";import BrowserSession from"TYPO3/CMS/Backend/Storage/BrowserSession.js";import Client from"TYPO3/CMS/Backend/Storage/Client.js";class Tabs{constructor(){this.storeLastActiveTab=!0;const t=this;$(()=>{$(".t3js-tabs").each((function(){const e=$(this);t.storeLastActiveTab=1===e.data("storeLastTab");const s=t.receiveActiveTab(e.attr("id"));s&&e.find('a[href="'+s+'"]').tab("show"),e.on("show.bs.tab",e=>{if(t.storeLastActiveTab){const s=e.currentTarget.id,r=e.target.hash;t.storeActiveTab(s,r)}})}))}),Client.unsetByPrefix("tabs-")}static getTimestamp(){return Math.round((new Date).getTime()/1e3)}receiveActiveTab(t){return BrowserSession.get(t)||""}storeActiveTab(t,e){BrowserSession.set(t,e)}}export default new Tabs;