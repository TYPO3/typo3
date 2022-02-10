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
import InfoWindow from"@typo3/backend/info-window.js";import RegularEvent from"@typo3/core/event/regular-event.js";import shortcutMenu from"@typo3/backend/toolbar/shortcut-menu.js";import windowManager from"@typo3/backend/window-manager.js";import moduleMenuApp from"@typo3/backend/module-menu.js";import documentService from"@typo3/core/document-service.js";import Utility from"@typo3/backend/utility.js";class ActionDispatcher{constructor(){this.delegates={},this.createDelegates(),documentService.ready().then(()=>this.registerEvents())}static resolveArguments(e){if(e.dataset.dispatchArgs){const t=e.dataset.dispatchArgs.replace(/&quot;/g,'"'),n=JSON.parse(t);return n instanceof Array?Utility.trimItems(n):null}if(e.dataset.dispatchArgsList){const t=e.dataset.dispatchArgsList.split(",");return Utility.trimItems(t)}return null}static enrichItems(e,t,n){return e.map(e=>e instanceof Object&&e.$event?e.$target?n:e.$event?t:void 0:e)}createDelegates(){this.delegates={"TYPO3.InfoWindow.showItem":InfoWindow.showItem.bind(null),"TYPO3.ShortcutMenu.createShortcut":shortcutMenu.createShortcut.bind(shortcutMenu),"TYPO3.WindowManager.localOpen":windowManager.localOpen.bind(windowManager),"TYPO3.ModuleMenu.showModule":moduleMenuApp.App.showModule.bind(moduleMenuApp.App)}}registerEvents(){new RegularEvent("click",this.handleClickEvent.bind(this)).delegateTo(document,"[data-dispatch-action]")}handleClickEvent(e,t){e.preventDefault(),this.delegateTo(e,t)}delegateTo(e,t){if(t.hasAttribute("data-dispatch-disabled"))return;const n=t.dataset.dispatchAction;let r=ActionDispatcher.resolveArguments(t);r instanceof Array&&(r=r.map(n=>{switch(n){case"{$target}":return t;case"{$event}":return e;default:return n}})),this.delegates[n]&&this.delegates[n].apply(null,r||[])}}export default new ActionDispatcher;