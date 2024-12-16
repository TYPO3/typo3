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
import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";import"@typo3/backend/color-picker.js";var Selectors;!function(e){e.editIconSelector=".t3js-toggle"}(Selectors||(Selectors={}));class ConstantEditor{constructor(){DocumentService.ready().then((e=>{e.querySelectorAll("typo3-backend-color-picker").length&&import("@typo3/backend/color-picker.js"),this.registerEvents()}))}registerEvents(){new RegularEvent("click",this.changeProperty).delegateTo(document,Selectors.editIconSelector)}changeProperty(){const e=this.getAttribute("rel"),t=document.getElementById("defaultTS-"+e),o=document.getElementById("userTS-"+e),r=document.getElementById("check-"+e),c=this.dataset.bsToggle;"edit"===c?(t.style.display="none",o.style.removeProperty("display"),r.removeAttribute("disabled")):"undo"===c&&(o.style.display="none",t.style.removeProperty("display"),r.setAttribute("disabled","disabled"))}}export default new ConstantEditor;