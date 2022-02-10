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
import $ from"jquery";var Identifiers;!function(e){e.containerSelector=".t3js-newmultiplepages-container",e.addMoreFieldsButtonSelector=".t3js-newmultiplepages-createnewfields",e.doktypeSelector=".t3js-newmultiplepages-select-doktype",e.templateRow=".t3js-newmultiplepages-newlinetemplate"}(Identifiers||(Identifiers={}));class NewMultiplePages{constructor(){this.lineCounter=5,$(()=>{this.initializeEvents()})}initializeEvents(){$(Identifiers.addMoreFieldsButtonSelector).on("click",()=>{this.createNewFormFields()}),$(document).on("change",Identifiers.doktypeSelector,e=>{this.actOnTypeSelectChange($(e.currentTarget))})}createNewFormFields(){for(let e=0;e<5;e++){const t=this.lineCounter+e+1,i=$(Identifiers.templateRow).html().replace(/\[0\]/g,(this.lineCounter+e).toString()).replace(/\[1\]/g,t.toString());$(i).appendTo(Identifiers.containerSelector)}this.lineCounter+=5}actOnTypeSelectChange(e){const t=e.find(":selected");$(e.data("target")).html(t.data("icon"))}}export default new NewMultiplePages;