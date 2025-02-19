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
import r from"nprogress";import c from"@typo3/core/event/regular-event.js";var e;(function(t){t.actionsContainerSelector=".t3js-reference-index-actions"})(e||(e={}));class s{constructor(){this.registerActionButtonEvents()}registerActionButtonEvents(){new c("click",(i,n)=>{r.configure({showSpinner:!1}),r.start(),Array.from(n.parentNode.querySelectorAll("button")).forEach(o=>{o.classList.add("disabled")})}).delegateTo(document.querySelector(e.actionsContainerSelector),"button")}}var a=new s;export{a as default};
