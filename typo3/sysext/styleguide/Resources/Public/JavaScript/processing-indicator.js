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
import NProgress from"nprogress";import Notification from"@typo3/backend/notification.js";import RegularEvent from"@typo3/core/event/regular-event.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";let itemProcessing=0;function setButtonStates(e,t){for(const o of e.children){if(1!==o.nodeType||"BUTTON"!==o.nodeName)continue;const e=o;e.dataset.generatorAction===t?(e.classList.remove("disabled"),e.hidden=!1,e.querySelector("typo3-backend-icon").identifier="actions-"+t):(e.classList.add("disabled"),e.hidden=!0)}}new RegularEvent("click",((e,t)=>{e.preventDefault();for(const e of t.parentElement.children)1===e.nodeType&&e.classList.add("disabled");t.querySelector("typo3-backend-icon").identifier="spinner-circle",NProgress.start(),itemProcessing++,new AjaxRequest(t.dataset.href).get().then((async e=>{const o=await e.resolve("application/json");itemProcessing--,Notification.showMessage(o.title,o.body,o.status,5),0===itemProcessing&&NProgress.done(),setButtonStates(t.parentElement,"plus"===t.dataset.generatorAction?"delete":"plus")})).catch((e=>{NProgress.done(),Notification.error("",e.response.status+" "+e.response.statusText,5),t.querySelector("typo3-backend-icon").identifier=t.dataset.generatorAction,t.classList.remove("disabled")}))})).delegateTo(document,".t3js-generator-action");