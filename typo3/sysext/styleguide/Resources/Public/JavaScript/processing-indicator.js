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
import s from"nprogress";import a from"@typo3/backend/notification.js";import r from"@typo3/core/event/regular-event.js";import d from"@typo3/core/ajax/ajax-request.js";let i=0;function c(n,e){for(const t of n.children){if(t.nodeType!==1||t.nodeName!=="BUTTON")continue;const o=t;o.dataset.generatorAction===e?(o.classList.remove("disabled"),o.hidden=!1,o.querySelector("typo3-backend-icon").identifier="actions-"+e):(o.classList.add("disabled"),o.hidden=!0)}}new r("click",(n,e)=>{n.preventDefault();for(const t of e.parentElement.children)t.nodeType===1&&t.classList.add("disabled");e.querySelector("typo3-backend-icon").identifier="spinner-circle",s.start(),i++,new d(e.dataset.href).get().then(async t=>{const o=await t.resolve("application/json");i--,a.showMessage(o.title,o.body,o.status,5),i===0&&s.done(),c(e.parentElement,e.dataset.generatorAction==="plus"?"delete":"plus")}).catch(t=>{s.done(),a.error("",t.response.status+" "+t.response.statusText,5),e.querySelector("typo3-backend-icon").identifier=e.dataset.generatorAction,e.classList.remove("disabled")})}).delegateTo(document,".t3js-generator-action");
