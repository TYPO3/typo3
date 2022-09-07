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
import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";var Identifiers;!function(e){e.containerSelector=".t3js-newmultiplepages-container",e.addMoreFieldsButtonSelector=".t3js-newmultiplepages-createnewfields",e.doktypeSelector=".t3js-newmultiplepages-select-doktype",e.templateRow=".t3js-newmultiplepages-newlinetemplate"}(Identifiers||(Identifiers={}));class NewMultiplePages{constructor(){this.lineCounter=5,DocumentService.ready().then((()=>{this.initializeEvents()}))}initializeEvents(){new RegularEvent("click",this.createNewFormFields.bind(this)).delegateTo(document,Identifiers.addMoreFieldsButtonSelector),new RegularEvent("change",this.actOnTypeSelectChange).delegateTo(document,Identifiers.doktypeSelector)}createNewFormFields(){const e=document.querySelector(Identifiers.containerSelector),t=document.querySelector(Identifiers.templateRow)?.innerHTML||"";if(null!==e&&""!==t){for(let n=0;n<5;n++){const i=this.lineCounter+n+1;e.innerHTML+=t.replace(/\[0\]/g,(this.lineCounter+n).toString()).replace(/\[1\]/g,i.toString())}this.lineCounter+=5}}actOnTypeSelectChange(){const e=this.options[this.selectedIndex],t=document.querySelector(this.dataset.target);null!==e&&null!==t&&(t.innerHTML=e.dataset.icon)}}export default new NewMultiplePages;