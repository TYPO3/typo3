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
import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";var Selectors;!function(e){e.editIconSelector=".t3js-toggle",e.colorInputSelector=".t3js-color-input"}(Selectors||(Selectors={}));class ConstantEditor{constructor(){DocumentService.ready().then((e=>{const t=e.querySelectorAll(Selectors.colorInputSelector);t.length&&import("@typo3/backend/color-picker.js").then((({default:e})=>{t.forEach((t=>{e.initialize(t)}))})),this.registerEvents()}))}registerEvents(){new RegularEvent("click",this.changeProperty).delegateTo(document,Selectors.editIconSelector)}changeProperty(){const e=this.getAttribute("rel"),t=document.getElementById("defaultTS-"+e),o=document.getElementById("userTS-"+e),r=document.getElementById("check-"+e),l=this.dataset.bsToggle;"edit"===l?(t.style.display="none",o.style.removeProperty("display"),r.removeAttribute("disabled")):"undo"===l&&(o.style.display="none",t.style.removeProperty("display"),r.setAttribute("disabled","disabled"))}}export default new ConstantEditor;