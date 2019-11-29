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
define(["require","exports","jquery"],(function(e,t,n){"use strict";var i;!function(e){e.containerSelector=".t3js-newmultiplepages-container",e.addMoreFieldsButtonSelector=".t3js-newmultiplepages-createnewfields",e.doktypeSelector=".t3js-newmultiplepages-select-doktype",e.templateRow=".t3js-newmultiplepages-newlinetemplate"}(i||(i={}));return new class{constructor(){this.lineCounter=5,n(()=>{this.initializeEvents()})}initializeEvents(){n(i.addMoreFieldsButtonSelector).on("click",()=>{this.createNewFormFields()}),n(document).on("change",i.doktypeSelector,e=>{this.actOnTypeSelectChange(n(e.currentTarget))})}createNewFormFields(){for(let e=0;e<5;e++){const t=this.lineCounter+e+1,l=n(i.templateRow).html().replace(/\[0\]/g,(this.lineCounter+e).toString()).replace(/\[1\]/g,t.toString());n(l).appendTo(i.containerSelector)}this.lineCounter+=5}actOnTypeSelectChange(e){const t=e.find(":selected");n(e.data("target")).html(t.data("icon"))}}}));