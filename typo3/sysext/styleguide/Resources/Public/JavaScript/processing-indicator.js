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
import NProgress from"nprogress";import Icons from"@typo3/backend/icons.js";import Notification from"@typo3/backend/notification.js";import RegularEvent from"@typo3/core/event/regular-event.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";let itemProcessing=0;Icons.getIcon("spinner-circle",Icons.sizes.small).then((e=>{new RegularEvent("click",((t,o)=>{t.preventDefault();const s=o.querySelector("span").outerHTML,r=o.parentNode.querySelector("button.disabled");o.querySelector("span").outerHTML=e,o.classList.add("disabled"),NProgress.start(),itemProcessing++,new AjaxRequest(o.dataset.href).get().then((async e=>{const t=await e.resolve("application/json");itemProcessing--,Notification.showMessage(t.title,t.body,t.status,5),0===itemProcessing&&NProgress.done(),o.querySelector(".t3js-icon").outerHTML=s,r.classList.remove("disabled")})).catch((e=>{NProgress.done(),Notification.error("",e.response.status+" "+e.response.statusText,5),o.querySelector(".t3js-icon").outerHTML=s,o.classList.remove("disabled")}))})).delegateTo(document,".t3js-generator-action")}));