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
define(["require","exports","TYPO3/CMS/Backend/InfoWindow","TYPO3/CMS/Core/Event/RegularEvent","TYPO3/CMS/Backend/Toolbar/ShortcutMenu","TYPO3/CMS/Core/DocumentService"],(function(t,e,a,s,i,n){"use strict";class r{constructor(){this.delegates={},this.createDelegates(),n.ready().then(()=>this.registerEvents())}static resolveArguments(t){if(t.dataset.dispatchArgs){const e=t.dataset.dispatchArgs.replace(/&quot;/g,'"'),a=JSON.parse(e);return a instanceof Array?r.trimItems(a):null}if(t.dataset.dispatchArgsList){const e=t.dataset.dispatchArgsList.split(",");return r.trimItems(e)}return null}static trimItems(t){return t.map(t=>t instanceof String?t.trim():t)}static enrichItems(t,e,a){return t.map(t=>t instanceof Object&&t.$event?t.$target?a:t.$event?e:void 0:t)}createDelegates(){this.delegates={"TYPO3.InfoWindow.showItem":a.showItem.bind(null),"TYPO3.ShortcutMenu.createShortcut":i.createShortcut.bind(i)}}registerEvents(){new s("click",this.handleClickEvent.bind(this)).delegateTo(document,"[data-dispatch-action]:not([data-dispatch-immediately])"),document.querySelectorAll("[data-dispatch-action][data-dispatch-immediately]").forEach(this.delegateTo.bind(this))}handleClickEvent(t,e){t.preventDefault(),this.delegateTo(e)}delegateTo(t){const e=t.dataset.dispatchAction,a=r.resolveArguments(t);this.delegates[e]&&this.delegates[e].apply(null,a||[])}}return new r}));