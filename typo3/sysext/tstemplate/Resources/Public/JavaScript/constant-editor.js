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
import i from"@typo3/core/document-service.js";import l from"@typo3/core/event/regular-event.js";import"@typo3/backend/color-picker.js";var t;(function(o){o.editIconSelector=".t3js-toggle"})(t||(t={}));class d{constructor(){i.ready().then(e=>{e.querySelectorAll("typo3-backend-color-picker").length&&import("@typo3/backend/color-picker.js"),this.registerEvents()})}registerEvents(){new l("click",this.changeProperty).delegateTo(document,t.editIconSelector)}changeProperty(){const e=this.getAttribute("rel"),r=document.getElementById("defaultTS-"+e),n=document.getElementById("userTS-"+e),s=document.getElementById("check-"+e),c=this.dataset.bsToggle;c==="edit"?(r.style.display="none",n.style.removeProperty("display"),s.removeAttribute("disabled")):c==="undo"&&(n.style.display="none",r.style.removeProperty("display"),s.setAttribute("disabled","disabled"))}}var a=new d;export{a as default};
