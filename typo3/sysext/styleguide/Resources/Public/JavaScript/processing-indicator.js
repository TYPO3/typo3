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
import"@typo3/backend/element/progress-bar-element.js";import i from"@typo3/backend/notification.js";import r from"@typo3/core/event/regular-event.js";import d from"@typo3/core/ajax/ajax-request.js";let a=0,n=null;function c(s,e){for(const o of s.children){if(o.nodeType!==1||o.nodeName!=="BUTTON")continue;const t=o;t.dataset.generatorAction===e?(t.classList.remove("disabled"),t.hidden=!1,t.querySelector("typo3-backend-icon").identifier="actions-"+e):(t.classList.add("disabled"),t.hidden=!0)}}new r("click",(s,e)=>{s.preventDefault();for(const o of e.parentElement.children)o.nodeType===1&&o.classList.add("disabled");e.querySelector("typo3-backend-icon").identifier="spinner-circle",n||(n=document.createElement("typo3-backend-progress-bar"),document.body.appendChild(n)),n.start(),a++,new d(e.dataset.href).get().then(async o=>{const t=await o.resolve("application/json");if(t.status===!1){n&&n.done(),i.error(t.title,t.body,5),e.querySelector("typo3-backend-icon").identifier="actions-"+e.dataset.generatorAction,e.classList.remove("disabled");return}a--,i.showMessage(t.title,t.body,t.status,5),a===0&&n&&n.done(),c(e.parentElement,e.dataset.generatorAction==="plus"?"delete":"plus")}).catch(o=>{n&&n.done(),i.error("",o.response.status+" "+o.response.statusText,5),e.querySelector("typo3-backend-icon").identifier="actions-"+e.dataset.generatorAction,e.classList.remove("disabled")})}).delegateTo(document,".t3js-generator-action");
