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
define(["require","exports","jquery","./ElementBrowser","TYPO3/CMS/Backend/LegacyTree"],function(e,t,n,l,c){"use strict";class s{constructor(){c.noop(),s.File=new r,s.Selector=new i,n(()=>{s.elements=n("body").data("elements"),n("[data-close]").on("click",e=>{e.preventDefault(),s.File.insertElement("file_"+n(e.currentTarget).data("fileIndex"),1===parseInt(n(e.currentTarget).data("close"),10))}),n("#t3js-importSelection").on("click",s.Selector.handle),n("#t3js-toggleSelection").on("click",s.Selector.toggle)})}}class r{insertElement(e,t){let n=!1;if(void 0!==s.elements[e]){const c=s.elements[e];n=l.insertElement(c.table,c.uid,c.type,c.fileName,c.filePath,c.fileExt,c.fileIcon,"",t)}return n}}class i{constructor(){this.toggle=(e=>{e.preventDefault();const t=this.getItems();t.length&&t.each((e,t)=>{t.checked=t.checked?null:"checked"})}),this.handle=(e=>{e.preventDefault();const t=this.getItems(),n=[];if(t.length){if(t.each((e,t)=>{t.checked&&t.name&&n.push(t.name)}),n.length>0)for(let e of n)s.File.insertElement(e);l.focusOpenerAndClose()}})}getItems(){return n("#typo3-filelist").find(".typo3-bulk-item")}}return new s});