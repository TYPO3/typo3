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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","./AbstractSortableSelectItems","jquery","TYPO3/CMS/Backend/FormEngine","./Extra/SelectBoxFilter"],(function(e,t,l,n,r,s){"use strict";n=__importDefault(n);class i extends l.AbstractSortableSelectItems{constructor(e,t){super(),this.selectedOptionsElement=null,this.availableOptionsElement=null,n.default(()=>{this.selectedOptionsElement=document.getElementById(e),this.availableOptionsElement=document.getElementById(t),this.registerEventHandler()})}registerEventHandler(){this.registerSortableEventHandler(this.selectedOptionsElement),this.availableOptionsElement.addEventListener("click",e=>{const t=e.currentTarget,l=t.dataset.relatedfieldname;if(l){const e=t.dataset.exclusivevalues,s=t.querySelectorAll("option:checked");s.length>0&&s.forEach(t=>{r.setSelectOptionFromExternalSource(l,t.value,t.textContent,t.getAttribute("title"),e,n.default(t))})}}),new s(this.availableOptionsElement)}}return i}));