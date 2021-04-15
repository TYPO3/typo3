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
define(["require","exports","TYPO3/CMS/Backend/InfoWindow","TYPO3/CMS/Core/Event/RegularEvent","TYPO3/CMS/Backend/Toolbar/ShortcutMenu","TYPO3/CMS/Backend/ModuleMenu","TYPO3/CMS/Core/DocumentService"],(function(e,t,s,n,r,a,i){"use strict";class c{constructor(){this.delegates={},this.createDelegates(),i.ready().then(()=>this.registerEvents())}static resolveArguments(e){if(e.dataset.dispatchArgs){const t=e.dataset.dispatchArgs.replace(/&quot;/g,'"'),s=JSON.parse(t);return s instanceof Array?c.trimItems(s):null}if(e.dataset.dispatchArgsList){const t=e.dataset.dispatchArgsList.split(",");return c.trimItems(t)}return null}static trimItems(e){return e.map(e=>e instanceof String?e.trim():e)}static enrichItems(e,t,s){return e.map(e=>e instanceof Object&&e.$event?e.$target?s:e.$event?t:void 0:e)}createDelegates(){this.delegates={"TYPO3.InfoWindow.showItem":s.showItem.bind(null),"TYPO3.ShortcutMenu.createShortcut":r.createShortcut.bind(r),"TYPO3.ModuleMenu.showModule":a.App.showModule.bind(a.App)}}registerEvents(){new n("click",this.handleClickEvent.bind(this)).delegateTo(document,"[data-dispatch-action]")}handleClickEvent(e,t){e.preventDefault(),this.delegateTo(t)}delegateTo(e){const t=e.dataset.dispatchAction,s=c.resolveArguments(e);this.delegates[t]&&this.delegates[t].apply(null,s||[])}}return new c}));