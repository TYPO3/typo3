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
define(["require","exports","TYPO3/CMS/Backend/InfoWindow","TYPO3/CMS/Core/Event/RegularEvent","TYPO3/CMS/Backend/Toolbar/ShortcutMenu","TYPO3/CMS/Backend/WindowManager","TYPO3/CMS/Backend/ModuleMenu","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Backend/Utility"],(function(e,t,n,s,a,r,i,c,o){"use strict";class d{constructor(){this.delegates={},this.createDelegates(),c.ready().then(()=>this.registerEvents())}static resolveArguments(e){if(e.dataset.dispatchArgs){const t=e.dataset.dispatchArgs.replace(/&quot;/g,'"'),n=JSON.parse(t);return n instanceof Array?o.trimItems(n):null}if(e.dataset.dispatchArgsList){const t=e.dataset.dispatchArgsList.split(",");return o.trimItems(t)}return null}static enrichItems(e,t,n){return e.map(e=>e instanceof Object&&e.$event?e.$target?n:e.$event?t:void 0:e)}createDelegates(){this.delegates={"TYPO3.InfoWindow.showItem":n.showItem.bind(null),"TYPO3.ShortcutMenu.createShortcut":a.createShortcut.bind(a),"TYPO3.WindowManager.localOpen":r.localOpen.bind(r),"TYPO3.ModuleMenu.showModule":i.App.showModule.bind(i.App)}}registerEvents(){new s("click",this.handleClickEvent.bind(this)).delegateTo(document,"[data-dispatch-action]")}handleClickEvent(e,t){e.preventDefault(),this.delegateTo(t)}delegateTo(e){const t=e.dataset.dispatchAction,n=d.resolveArguments(e);this.delegates[t]&&this.delegates[t].apply(null,n||[])}}return new d}));