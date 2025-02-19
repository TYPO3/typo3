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
import l from"@typo3/core/document-service.js";import r from"@typo3/core/event/regular-event.js";import d from"@typo3/core/event/debounce-event.js";l.ready().then(()=>{const i=document.getElementById("search-field"),s=document.getElementById("t3js-filter-container");new r("click",(a,t)=>{a.preventDefault(),i.value=t.dataset.filter,s.dispatchEvent(new CustomEvent("typo3:styleguide:update-icons",{detail:{searchValue:i.value}}))}).delegateTo(document,".t3js-filter-buttons button"),new d("input",a=>{s.dispatchEvent(new CustomEvent("typo3:styleguide:update-icons",{detail:{searchValue:a.target.value}}))}).bindTo(i),new r("typo3:styleguide:update-icons",a=>{const t=a.detail.searchValue,o=Array.from(s.querySelectorAll("[data-icon-identifier]"));if(t==="")o.map(n=>n.hidden=!1);else if(t.includes("type:")){const[,n]=t.split(":");switch(n.toLowerCase()){case"bitmap":o.forEach(e=>{const c=e.querySelector('img:not([src$=".svg"])')!==null;e.hidden=!c});break;case"font":o.forEach(e=>{const c=e.querySelector("i.fa")!==null;e.hidden=!c});break;case"vector":o.forEach(e=>{const c=e.querySelector('img[src$=".svg"]')!==null;e.hidden=!c});break;default:}}else o.forEach(n=>{n.hidden=!n.matches('[data-icon-identifier*="'+t+'"]')})}).bindTo(s)});
