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
define(["require","exports","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Backend/Icons","TYPO3/CMS/Core/Event/RegularEvent","TYPO3/CMS/Core/Ajax/AjaxRequest"],(function(e,s,t,c,a,r){"use strict";var n;!function(e){e.clearCache=".t3js-clear-page-cache",e.icon=".t3js-icon"}(n||(n={}));class i{static setDisabled(e,s){e.disabled=s,e.classList.toggle("disabled",s)}static sendClearCacheRequest(e){const s=new r(TYPO3.settings.ajaxUrls.web_list_clearpagecache).withQueryArguments({id:e}).get({cache:"no-cache"});return s.then(async e=>{const s=await e.resolve();!0===s.success?t.success(s.title,s.message,1):t.error(s.title,s.message,1)},()=>{t.error("Clearing page caches went wrong on the server side.")}),s}constructor(){this.registerClickHandler()}registerClickHandler(){const e=document.querySelector(n.clearCache+":not([disabled])");null!==e&&new a("click",e=>{e.preventDefault();const s=e.currentTarget,t=parseInt(s.dataset.id,10);i.setDisabled(s,!0),c.getIcon("spinner-circle-dark",c.sizes.small,null,"disabled").then(e=>{s.querySelector(n.icon).outerHTML=e}),i.sendClearCacheRequest(t).finally(()=>{c.getIcon("actions-system-cache-clear",c.sizes.small).then(e=>{s.querySelector(n.icon).outerHTML=e}),i.setDisabled(s,!1)})}).bindTo(e)}}return new i}));