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
import r from"@typo3/core/document-service.js";import m from"@typo3/backend/form-engine.js";import e from"@typo3/backend/modal.js";class c{constructor(n){this.controlElement=null,this.handleControlClick=o=>{o.preventDefault();const t=this.controlElement.dataset.itemName,l=this.controlElement.getAttribute("href")+"&P[currentValue]="+encodeURIComponent(document.forms.namedItem("editform")[t].value)+"&P[currentSelectedValues]="+encodeURIComponent(m.getFieldElement(t).val());e.advanced({type:e.types.iframe,content:l,size:e.sizes.large})},r.ready().then(()=>{this.controlElement=document.querySelector(n),this.controlElement!==null&&this.controlElement.addEventListener("click",this.handleControlClick)})}}export{c as default};
