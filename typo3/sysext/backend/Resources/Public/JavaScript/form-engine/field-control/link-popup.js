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
import DocumentService from"@typo3/core/document-service.js";import FormEngine from"@typo3/backend/form-engine.js";import Modal from"@typo3/backend/modal.js";class LinkPopup{constructor(e){this.controlElement=null,this.handleControlClick=e=>{e.preventDefault();const t=this.controlElement.dataset.itemName,o=this.controlElement.getAttribute("href")+"&P[currentValue]="+encodeURIComponent(document.forms.namedItem("editform")[t].value)+"&P[currentSelectedValues]="+encodeURIComponent(FormEngine.getFieldElement(t).val());Modal.advanced({type:Modal.types.iframe,content:o,size:Modal.sizes.large})},DocumentService.ready().then(()=>{this.controlElement=document.querySelector(e),this.controlElement.addEventListener("click",this.handleControlClick)})}}export default LinkPopup;