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
import{Tab}from"bootstrap";import BrowserSession from"@typo3/backend/storage/browser-session.js";import Client from"@typo3/backend/storage/client.js";import DocumentService from"@typo3/core/document-service.js";class Tabs{constructor(){DocumentService.ready().then((()=>{document.querySelectorAll(".t3js-tabs").forEach((e=>{const t=Tabs.receiveActiveTab(e.id);t&&new Tab(document.querySelector('a[href="'+t+'"]')).show();"1"===e.dataset.storeLastTab&&e.addEventListener("show.bs.tab",(e=>{const t=e.currentTarget.id,r=e.target.hash;Tabs.storeActiveTab(t,r)}))}))})),Client.unsetByPrefix("tabs-")}static receiveActiveTab(e){return BrowserSession.get(e)||""}static storeActiveTab(e,t){BrowserSession.set(e,t)}}export default new Tabs;