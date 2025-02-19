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
import n from"@typo3/core/document-service.js";import s from"@typo3/backend/form-engine.js";class i{constructor(t){this.controlElement=null,this.registerClickHandler=r=>{r.preventDefault();const o=this.controlElement.dataset.element,l=JSON.parse(this.controlElement.dataset.clipboardItems);for(const e of l)s.setSelectOptionFromExternalSource(o,e.value,e.title,e.title)},n.ready().then(()=>{this.controlElement=document.querySelector(t),this.controlElement.addEventListener("click",this.registerClickHandler)})}}export{i as default};
