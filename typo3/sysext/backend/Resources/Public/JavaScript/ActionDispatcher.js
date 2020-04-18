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
define(["require","exports","TYPO3/CMS/Backend/InfoWindow","TYPO3/CMS/Core/Event/RegularEvent","TYPO3/CMS/Backend/Toolbar/ShortcutMenu","TYPO3/CMS/Core/DocumentService"],(function(t,e,n,a,s,i){"use strict";const r={"TYPO3.InfoWindow.showItem":n.showItem.bind(null),"TYPO3.ShortcutMenu.createShortcut":s.createShortcut.bind(s)};class c{static resolveArguments(t){if(t.dataset.dispatchArgs){const e=JSON.parse(t.dataset.dispatchArgs);return e instanceof Array?c.trimItems(e):null}if(t.dataset.dispatchArgsList){const e=t.dataset.dispatchArgsList.split(",");return c.trimItems(e)}return null}static trimItems(t){return t.map(t=>t instanceof String?t.trim():t)}static enrichItems(t,e,n){return t.map(t=>t instanceof Object&&t.$event?t.$target?n:t.$event?e:void 0:t)}constructor(){i.ready().then(()=>this.registerEvents())}registerEvents(){new a("click",this.handleClickEvent.bind(this)).delegateTo(document,"[data-dispatch-action]:not([data-dispatch-immediately])"),document.querySelectorAll("[data-dispatch-action][data-dispatch-immediately]").forEach(this.delegateTo.bind(this))}handleClickEvent(t,e){t.preventDefault(),this.delegateTo(e)}delegateTo(t){const e=t.dataset.dispatchAction,n=c.resolveArguments(t);r[e]&&r[e].apply(null,n||[])}}return new c}));