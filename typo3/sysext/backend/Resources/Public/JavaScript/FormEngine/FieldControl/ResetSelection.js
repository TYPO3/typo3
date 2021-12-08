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
import DocumentService from"TYPO3/CMS/Core/DocumentService.js";class ResetSelection{constructor(e){this.controlElement=null,this.registerClickHandler=e=>{e.preventDefault();const t=this.controlElement.dataset.itemName,n=JSON.parse(this.controlElement.dataset.selectedIndices),o=document.forms.namedItem("editform").querySelector('[name="'+t+'[]"]');o.selectedIndex=-1;for(let e of n)o.options[e].selected=!0},DocumentService.ready().then(()=>{this.controlElement=document.querySelector(e),null!==this.controlElement&&this.controlElement.addEventListener("click",this.registerClickHandler)})}}export default ResetSelection;