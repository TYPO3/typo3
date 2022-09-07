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
import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";var Selectors;!function(e){e.editIconSelector=".t3js-toggle",e.colorInputSelector=".t3js-color-input",e.formFieldsSelector=".tstemplate-constanteditor [data-form-update-fragment]"}(Selectors||(Selectors={}));class ConstantEditor{constructor(){DocumentService.ready().then((e=>{const t=e.querySelectorAll(Selectors.colorInputSelector);t.length&&import("@typo3/backend/color-picker.js").then((({default:e})=>{t.forEach((t=>{e.initialize(t),new RegularEvent("blur",this.updateFormFragment).bindTo(t)}))})),this.registerEvents()}))}registerEvents(){new RegularEvent("click",this.changeProperty).delegateTo(document,Selectors.editIconSelector),new RegularEvent("change",this.updateFormFragment).delegateTo(document,Selectors.formFieldsSelector)}updateFormFragment(){const e=this.dataset.formUpdateFragment;let t=document.forms[0].action;-1!==t.indexOf("#")&&(t=t.substring(0,t.indexOf("#"))),document.forms[0].action=t+"#"+e}changeProperty(){const e=this.getAttribute("rel"),t=document.getElementById("defaultTS-"+e),o=document.getElementById("userTS-"+e),r=document.getElementById("check-"+e),n=this.dataset.bsToggle;"edit"===n?(t.style.display="none",o.style.removeProperty("display"),o.querySelectorAll("input").forEach((e=>{e.style.background="#fdf8bd"})),r.removeAttribute("disabled"),r.setAttribute("checked","checked")):"undo"===n&&(o.style.display="none",t.style.removeProperty("display"),r.value="",r.setAttribute("disabled","disabled"))}}export default new ConstantEditor;