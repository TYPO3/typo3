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
import NProgress from"nprogress";import RegularEvent from"@typo3/core/event/regular-event.js";var Selectors;!function(e){e.actionsContainerSelector=".t3js-reference-index-actions"}(Selectors||(Selectors={}));class ReferenceIndex{constructor(){this.registerActionButtonEvents()}registerActionButtonEvents(){new RegularEvent("click",((e,r)=>{NProgress.configure({showSpinner:!1}),NProgress.start(),Array.from(r.parentNode.querySelectorAll("button")).forEach((e=>{e.classList.add("disabled")}))})).delegateTo(document.querySelector(Selectors.actionsContainerSelector),"button")}}export default new ReferenceIndex;