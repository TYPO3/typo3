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
define(["require","exports","TYPO3/CMS/Backend/Utility/MessageUtility","./ElementBrowser","nprogress","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,n,i,s,a){"use strict";var r=TYPO3.Icons;class l{constructor(){this.importSelection=e=>{e.preventDefault();const t=e.target,o=e.detail.checkboxes;if(!o.length)return;const c=[];o.forEach(e=>{e.checked&&e.name&&e.dataset.fileName&&e.dataset.fileUid&&c.unshift({uid:e.dataset.fileUid,fileName:e.dataset.fileName})}),r.getIcon("spinner-circle",r.sizes.small,null,null,r.markupIdentifiers.inline).then(e=>{t.classList.add("disabled"),t.innerHTML=e}),s.configure({parent:".element-browser-main-content",showSpinner:!1}),s.start();const d=1/c.length;l.handleNext(c),new a("message",e=>{if(!n.MessageUtility.verifyOrigin(e.origin))throw"Denied message sent by "+e.origin;"typo3:foreignRelation:inserted"===e.data.actionName&&(c.length>0?(s.inc(d),l.handleNext(c)):(s.done(),i.focusOpenerAndClose()))}).bindTo(window)},new a("click",(e,t)=>{e.preventDefault(),l.insertElement(t.dataset.fileName,Number(t.dataset.fileUid),1===parseInt(t.dataset.close||"0",10))}).delegateTo(document,"[data-close]"),new a("multiRecordSelection:action:import",this.importSelection).bindTo(document)}static insertElement(e,t,n){return i.insertElement("sys_file",String(t),e,String(t),n)}static handleNext(e){if(e.length>0){const t=e.pop();l.insertElement(t.fileName,Number(t.uid))}}}return new l}));