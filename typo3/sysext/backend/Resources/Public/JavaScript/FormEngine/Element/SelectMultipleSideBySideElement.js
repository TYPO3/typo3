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
define(["require","exports","./AbstractSortableSelectItems","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Backend/FormEngine","./Extra/SelectBoxFilter"],(function(e,t,l,n,s,r){"use strict";class i extends l.AbstractSortableSelectItems{constructor(e,t){super(),this.selectedOptionsElement=null,this.availableOptionsElement=null,n.ready().then(l=>{this.selectedOptionsElement=l.getElementById(e),this.availableOptionsElement=l.getElementById(t),this.registerEventHandler()})}registerEventHandler(){this.registerSortableEventHandler(this.selectedOptionsElement),this.availableOptionsElement.addEventListener("click",e=>{const t=e.currentTarget,l=t.dataset.relatedfieldname;if(l){const e=t.dataset.exclusivevalues,n=t.querySelectorAll("option:checked");n.length>0&&n.forEach(t=>{s.setSelectOptionFromExternalSource(l,t.value,t.textContent,t.getAttribute("title"),e,t)})}}),new r(this.availableOptionsElement)}}return i}));