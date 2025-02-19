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
import l from"@typo3/core/document-service.js";class r{constructor(s){this.controlElement=null,this.assignedFormField=null,this.registerChangeHandler=()=>{this.controlElement.classList.toggle("disabled",this.assignedFormField.options.selectedIndex===-1)},this.registerClickHandler=n=>{n.preventDefault();const t=[];for(let e=0;e<this.assignedFormField.selectedOptions.length;++e){const o=this.assignedFormField.selectedOptions.item(e);t.push(o.value)}const i=this.controlElement.getAttribute("href")+"&P[currentValue]="+encodeURIComponent(this.assignedFormField.value)+"&P[currentSelectedValues]="+t.join(",");window.open(i,"",this.controlElement.dataset.windowParameters).focus()},l.ready().then(()=>{this.controlElement=document.querySelector(s),this.assignedFormField=document.querySelector('select[data-formengine-input-name="'+this.controlElement.dataset.element+'"]'),this.assignedFormField.options.selectedIndex===-1&&this.controlElement.classList.add("disabled"),this.assignedFormField.addEventListener("change",this.registerChangeHandler),this.controlElement.addEventListener("click",this.registerClickHandler)})}}export{r as default};
