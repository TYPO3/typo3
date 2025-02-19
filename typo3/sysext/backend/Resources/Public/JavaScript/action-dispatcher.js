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
import u from"@typo3/backend/info-window.js";import h from"@typo3/core/event/regular-event.js";import o from"@typo3/backend/toolbar/shortcut-menu.js";import d from"@typo3/backend/window-manager.js";import c from"@typo3/backend/module-menu.js";import p from"@typo3/core/document-service.js";import l from"@typo3/backend/utility.js";class r{constructor(){this.delegates={},this.createDelegates(),p.ready().then(()=>this.registerEvents())}static resolveArguments(e){if(e.dataset.dispatchArgs){const t=e.dataset.dispatchArgs.replace(/&quot;/g,'"'),a=JSON.parse(t);return a instanceof Array?l.trimItems(a):null}else if(e.dataset.dispatchArgsList){const t=e.dataset.dispatchArgsList.split(",");return l.trimItems(t)}return null}createDelegates(){this.delegates={"TYPO3.InfoWindow.showItem":u.showItem.bind(null),"TYPO3.ShortcutMenu.createShortcut":o.createShortcut.bind(o),"TYPO3.WindowManager.localOpen":d.localOpen.bind(d),"TYPO3.ModuleMenu.showModule":c.App.showModule.bind(c.App)}}registerEvents(){new h("click",this.handleClickEvent.bind(this)).delegateTo(document,"[data-dispatch-action]")}handleClickEvent(e,t){e.preventDefault(),this.delegateTo(e,t)}delegateTo(e,t){if(t.hasAttribute("data-dispatch-disabled"))return;const i=t.dataset.dispatchAction;let s=r.resolveArguments(t);s instanceof Array&&(s=s.map(n=>{switch(n){case"{$target}":return t;case"{$event}":return e;default:return n}})),this.delegates[i]&&this.delegates[i].apply(null,s||[])}}var m=new r;export{m as default};
