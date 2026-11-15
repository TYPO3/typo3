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
import n from"@typo3/core/document-service.js";import{topWindow as i}from"@typo3/core/utility/top-window.js";class o{constructor(t){this.controlElement=null,this.assignedFormField=null,this.updateState=()=>{const e=!this.isSelectionRendered();this.controlElement.classList.toggle("disabled",e),this.controlElement.setAttribute("aria-disabled",e?"true":"false")},this.openFormEditor=e=>{e.preventDefault(),this.isSelectionRendered()&&i.TYPO3?.Backend?.ContentContainer?.setUrl(this.controlElement.href)},n.ready().then(()=>{this.controlElement=document.querySelector(t),this.assignedFormField=document.querySelector('select[data-formengine-input-name="'+this.controlElement.dataset.element+'"]'),this.assignedFormField?.addEventListener("change",this.updateState),this.controlElement.addEventListener("click",this.openFormEditor),this.updateState()})}isSelectionRendered(){return this.assignedFormField?.value===this.controlElement.dataset.persistenceIdentifier}}export{o as default};
