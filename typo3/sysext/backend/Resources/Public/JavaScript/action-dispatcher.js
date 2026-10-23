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
import c from"@typo3/backend/info-window.js";import p from"@typo3/core/event/regular-event.js";import n from"@typo3/backend/window-manager.js";import o from"@typo3/backend/module-menu.js";import u from"@typo3/core/document-service.js";import d from"@typo3/backend/utility.js";class a{constructor(){this.delegates={},this.createDelegates(),u.ready().then(()=>this.registerEvents())}static resolveArguments(e){if(e.dataset.dispatchArgs){const t=e.dataset.dispatchArgs.replace(/&quot;/g,'"'),s=JSON.parse(t);return s instanceof Array?d.trimItems(s):null}else if(e.dataset.dispatchArgsList){const t=e.dataset.dispatchArgsList.split(",");return d.trimItems(t)}return null}createDelegates(){this.delegates={"TYPO3.InfoWindow.showItem":c.showItem.bind(null),"TYPO3.WindowManager.localOpen":n.localOpen.bind(n),"TYPO3.ModuleMenu.showModule":o.App.showModule.bind(o.App)}}registerEvents(){new p("click",this.handleClickEvent.bind(this)).delegateTo(document,"[data-dispatch-action]")}handleClickEvent(e,t){e.preventDefault(),this.delegateTo(e,t)}delegateTo(e,t){if(t.hasAttribute("data-dispatch-disabled"))return;const i=t.dataset.dispatchAction,l=(a.resolveArguments(t)??[]).map(r=>{switch(r){case"{$target}":return t;case"{$event}":return e;default:return r}});this.delegates[i]&&this.delegates[i].apply(null,l)}}var h=new a;export{h as default};
