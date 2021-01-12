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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","TYPO3/CMS/Backend/Enum/Severity","TYPO3/CMS/Core/Event/RegularEvent","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Backend/Modal"],(function(e,t,n,a,o,i){"use strict";a=__importDefault(a);return new class{constructor(){o.ready().then(()=>{new a.default("click",(e,t)=>{e.preventDefault();let a=t.dataset.redirectUrl;a=a?encodeURIComponent(a):encodeURIComponent(top.list_frame.document.location.pathname+top.list_frame.document.location.search);const o=t.dataset.identifier,l=t.dataset.deleteType,r=t.dataset.deleteUrl+"&data[delete][0][data]="+encodeURIComponent(o)+"&data[delete][0][redirect]="+a;if(t.dataset.check){i.confirm(t.dataset.title,t.dataset.bsContent,n.SeverityEnum.warning,[{text:TYPO3.lang["buttons.confirm.delete_file.no"]||"Cancel",active:!0,btnClass:"btn-default",name:"no"},{text:TYPO3.lang["buttons.confirm."+l+".yes"]||"Yes, delete this file or folder",btnClass:"btn-warning",name:"yes"}]).on("button.clicked",e=>{const t=e.target.name;"no"===t?i.dismiss():"yes"===t&&(i.dismiss(),top.list_frame.location.href=r)})}else top.list_frame.location.href=r}).delegateTo(document,".t3js-filelist-delete")})}}}));