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
import"@typo3/backend/element/progress-bar-element.js";import r from"@typo3/backend/notification.js";import d from"@typo3/core/event/regular-event.js";import c from"@typo3/core/ajax/ajax-request.js";let a=0,o=null;function l(i,t){for(const n of i.children){if(n.nodeType!==1||n.nodeName!=="BUTTON")continue;const e=n;e.dataset.generatorAction===t?(e.classList.remove("disabled"),e.hidden=!1,e.querySelector("typo3-backend-icon").identifier="actions-"+t):(e.classList.add("disabled"),e.hidden=!0)}}new d("click",(i,t)=>{i.preventDefault();const n=t.parentElement;if(n!==null){for(const e of n.children)e.nodeType===1&&e.classList.add("disabled");t.querySelector("typo3-backend-icon").identifier="spinner-circle",o||(o=document.createElement("typo3-backend-progress-bar"),document.body.appendChild(o)),o.start(),a++,new c(t.dataset.href??"").get().then(async e=>{const s=await e.resolve("application/json");if(s.status===!1){o&&o.done(),r.error(s.title,s.body,5),t.querySelector("typo3-backend-icon").identifier="actions-"+t.dataset.generatorAction,t.classList.remove("disabled");return}a--,r.showMessage(s.title,s.body,s.status,5),a===0&&o&&o.done(),l(n,t.dataset.generatorAction==="plus"?"delete":"plus")}).catch(e=>{o&&o.done(),r.error("",e.response.status+" "+e.response.statusText,5),t.querySelector("typo3-backend-icon").identifier="actions-"+t.dataset.generatorAction,t.classList.remove("disabled")})}}).delegateTo(document,".t3js-generator-action");
