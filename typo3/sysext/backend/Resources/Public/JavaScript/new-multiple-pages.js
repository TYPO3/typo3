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
import c from"@typo3/core/document-service.js";import r from"@typo3/core/event/regular-event.js";var t;(function(n){n.containerSelector=".t3js-newmultiplepages-container",n.addMoreFieldsButtonSelector=".t3js-newmultiplepages-createnewfields",n.pageTitleSelector=".t3js-newmultiplepages-page-title",n.doktypeSelector=".t3js-newmultiplepages-select-doktype",n.resetFieldsSelector=".t3js-newmultiplepages-reset-fields",n.templateRow=".t3js-newmultiplepages-newlinetemplate"})(t||(t={}));class a{constructor(){this.lineCounter=5,c.ready().then(()=>{this.initializeEvents()})}initializeEvents(){new r("click",this.createNewFormFields.bind(this)).delegateTo(document,t.addMoreFieldsButtonSelector),new r("change",this.actOnPageTitleChange).delegateTo(document,t.pageTitleSelector),new r("change",this.actOnTypeSelectChange).delegateTo(document,t.doktypeSelector),new r("click",this.resetFieldAttributes).delegateTo(document,t.resetFieldsSelector)}createNewFormFields(){const e=document.querySelector(t.containerSelector),o=document.querySelector(t.templateRow)?.innerHTML||"";if(!(e===null||o==="")){for(let l=0;l<5;l++){const i=this.lineCounter+l+1;e.innerHTML+=o.replace(/\[0\]/g,(this.lineCounter+l).toString()).replace(/\[1\]/g,i.toString())}this.lineCounter+=5}}actOnPageTitleChange(){this.setAttribute("value",this.value)}actOnTypeSelectChange(){for(const l of this.options)l.removeAttribute("selected");const e=this.options[this.selectedIndex],o=document.querySelector(this.dataset.target);e!==null&&o!==null&&(e.setAttribute("selected","selected"),o.innerHTML=e.dataset.icon)}resetFieldAttributes(){document.querySelectorAll(t.containerSelector+" "+t.pageTitleSelector).forEach(e=>{e.removeAttribute("value")}),document.querySelectorAll(t.containerSelector+" "+t.doktypeSelector).forEach(e=>{for(const i of e)i.removeAttribute("selected");const o=e.options[0]?.dataset.icon,l=document.querySelector(e.dataset.target);o&&l!==null&&(l.innerHTML=o)})}}var s=new a;export{s as default};
