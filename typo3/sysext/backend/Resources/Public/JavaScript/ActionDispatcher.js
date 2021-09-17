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
define(["require","exports","TYPO3/CMS/Backend/InfoWindow","TYPO3/CMS/Core/Event/RegularEvent","TYPO3/CMS/Backend/Toolbar/ShortcutMenu","TYPO3/CMS/Backend/WindowManager","TYPO3/CMS/Backend/ModuleMenu","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Backend/Utility"],(function(e,t,a,n,s,r,i,c,d){"use strict";class o{constructor(){this.delegates={},this.createDelegates(),c.ready().then(()=>this.registerEvents())}static resolveArguments(e){if(e.dataset.dispatchArgs){const t=e.dataset.dispatchArgs.replace(/&quot;/g,'"'),a=JSON.parse(t);return a instanceof Array?d.trimItems(a):null}if(e.dataset.dispatchArgsList){const t=e.dataset.dispatchArgsList.split(",");return d.trimItems(t)}return null}static enrichItems(e,t,a){return e.map(e=>e instanceof Object&&e.$event?e.$target?a:e.$event?t:void 0:e)}createDelegates(){this.delegates={"TYPO3.InfoWindow.showItem":a.showItem.bind(null),"TYPO3.ShortcutMenu.createShortcut":s.createShortcut.bind(s),"TYPO3.WindowManager.localOpen":r.localOpen.bind(r),"TYPO3.ModuleMenu.showModule":i.App.showModule.bind(i.App)}}registerEvents(){new n("click",this.handleClickEvent.bind(this)).delegateTo(document,"[data-dispatch-action]")}handleClickEvent(e,t){e.preventDefault(),this.delegateTo(e,t)}delegateTo(e,t){if(t.hasAttribute("data-dispatch-disabled"))return;const a=t.dataset.dispatchAction;let n=o.resolveArguments(t);n instanceof Array&&(n=n.map(a=>{switch(a){case"{$target}":return t;case"{$event}":return e;default:return a}})),this.delegates[a]&&this.delegates[a].apply(null,n||[])}}return new o}));