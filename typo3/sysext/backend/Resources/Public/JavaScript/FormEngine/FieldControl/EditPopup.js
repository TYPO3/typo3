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
define(["require","exports","TYPO3/CMS/Core/DocumentService"],(function(e,t,s){"use strict";return class{constructor(e){this.controlElement=null,this.assignedFormField=null,this.registerChangeHandler=()=>{this.controlElement.classList.toggle("disabled",-1===this.assignedFormField.options.selectedIndex)},this.registerClickHandler=e=>{e.preventDefault();const t=[];for(let e=0;e<this.assignedFormField.selectedOptions.length;++e){const s=this.assignedFormField.selectedOptions.item(e);t.push(s.value)}const s=this.controlElement.getAttribute("href")+"&P[currentValue]="+encodeURIComponent(this.assignedFormField.value)+"&P[currentSelectedValues]="+t.join(",");window.open(s,"",this.controlElement.dataset.windowParameters).focus()},s.ready().then(()=>{this.controlElement=document.querySelector(e),this.assignedFormField=document.querySelector('select[data-formengine-input-name="'+this.controlElement.dataset.element+'"]'),-1===this.assignedFormField.options.selectedIndex&&this.controlElement.classList.add("disabled"),this.assignedFormField.addEventListener("change",this.registerChangeHandler),this.controlElement.addEventListener("click",this.registerClickHandler)})}}}));