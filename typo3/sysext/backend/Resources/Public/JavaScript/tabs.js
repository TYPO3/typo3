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
import{Tab as n}from"bootstrap";import o from"@typo3/backend/storage/browser-session.js";import b from"@typo3/backend/storage/client.js";import d from"@typo3/core/document-service.js";class s{constructor(){d.ready().then(()=>{document.querySelectorAll(".t3js-tabs").forEach(t=>{const a=s.receiveActiveTab(t.id);if(a){const e=document.querySelector('[data-bs-target="#'+a+'"]');e&&new n(e).show()}t.dataset.storeLastTab==="1"&&t.addEventListener("show.bs.tab",e=>{const c=e.currentTarget.id,i=e.target.dataset.bsTarget.slice(1);s.storeActiveTab(c,i)})})}),b.unsetByPrefix("tabs-")}static receiveActiveTab(r){return o.get(r)||""}static storeActiveTab(r,t){o.set(r,t)}}var u=new s;export{u as default};
