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
define(["require","exports","jquery","TYPO3/CMS/Backend/Utility/MessageUtility","./ElementBrowser","nprogress","TYPO3/CMS/Backend/LegacyTree","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,n,i,l,s,r,c){"use strict";var o=TYPO3.Icons;class a{constructor(){r.noop(),a.File=new d,a.Selector=new g,n(()=>{a.elements=n("body").data("elements"),n("[data-close]").on("click",e=>{e.preventDefault(),a.File.insertElement("file_"+n(e.currentTarget).data("fileIndex"),1===parseInt(n(e.currentTarget).data("close"),10))}),n("#t3js-importSelection").on("click",a.Selector.handle),n("#t3js-toggleSelection").on("click",a.Selector.toggle)})}}class d{insertElement(e,t){let n=!1;if(void 0!==a.elements[e]){const i=a.elements[e];n=l.insertElement(i.table,i.uid,i.type,i.fileName,i.filePath,i.fileExt,i.fileIcon,"",t)}return n}}class g{constructor(){this.toggle=e=>{e.preventDefault();const t=this.getItems();t.length&&t.each((e,t)=>{t.checked=t.checked?null:"checked"})},this.handle=e=>{e.preventDefault();const t=this.getItems(),n=[];t.length&&(t.each((e,t)=>{t.checked&&t.name&&n.unshift(t.name)}),o.getIcon("spinner-circle",o.sizes.small,null,null,o.markupIdentifiers.inline).then(t=>{e.currentTarget.classList.add("disabled"),e.currentTarget.innerHTML=t}),this.handleSelection(n))}}getItems(){return n("#typo3-filelist").find(".typo3-bulk-item")}handleSelection(e){s.configure({parent:"#typo3-filelist",showSpinner:!1}),s.start();const t=1/e.length;this.handleNext(e),new c("message",n=>{if(!i.MessageUtility.verifyOrigin(n.origin))throw"Denied message sent by "+n.origin;"typo3:foreignRelation:inserted"===n.data.actionName&&(e.length>0?(s.inc(t),this.handleNext(e)):(s.done(),l.focusOpenerAndClose()))}).bindTo(window)}handleNext(e){if(e.length>0){const t=e.pop();a.File.insertElement(t)}}}return new a}));