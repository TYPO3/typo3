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
import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";import DebounceEvent from"@typo3/core/event/debounce-event.js";DocumentService.ready().then((()=>{const e=document.getElementById("search-field"),t=document.getElementById("t3js-filter-container");new RegularEvent("click",((n,o)=>{n.preventDefault(),e.value=o.dataset.filter,t.dispatchEvent(new CustomEvent("typo3:styleguide:update-icons",{detail:{searchValue:e.value}}))})).delegateTo(document,".t3js-filter-buttons button"),new DebounceEvent("input",(e=>{t.dispatchEvent(new CustomEvent("typo3:styleguide:update-icons",{detail:{searchValue:e.target.value}}))})).bindTo(e),new RegularEvent("typo3:styleguide:update-icons",(e=>{const n=e.detail.searchValue,o=Array.from(t.querySelectorAll("[data-icon-identifier]"));if(""===n)o.map((e=>e.hidden=!1));else if(n.includes("type:")){const[,e]=n.split(":");switch(e.toLowerCase()){case"bitmap":o.forEach((e=>{const t=null!==e.querySelector('img:not([src$=".svg"])');e.hidden=!t}));break;case"font":o.forEach((e=>{const t=null!==e.querySelector("i.fa");e.hidden=!t}));break;case"vector":o.forEach((e=>{const t=null!==e.querySelector('img[src$=".svg"]');e.hidden=!t}))}}else o.forEach((e=>{e.hidden=!e.matches('[data-icon-identifier*="'+n+'"]')}))})).bindTo(t)}));