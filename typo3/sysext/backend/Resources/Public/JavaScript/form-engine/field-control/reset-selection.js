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
import c from"@typo3/core/document-service.js";class r{constructor(t){this.controlElement=null,this.registerClickHandler=n=>{n.preventDefault();const o=this.controlElement.dataset.itemName,l=JSON.parse(this.controlElement.dataset.selectedIndices),e=document.forms.namedItem("editform").querySelector('[name="'+o+'[]"]');e.selectedIndex=-1;for(const s of l)e.options[s].selected=!0},c.ready().then(()=>{this.controlElement=document.querySelector(t),this.controlElement!==null&&this.controlElement.addEventListener("click",this.registerClickHandler)})}}export{r as default};
