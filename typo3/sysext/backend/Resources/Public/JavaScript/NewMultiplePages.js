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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery"],(function(e,t,n){"use strict";var l;n=__importDefault(n),function(e){e.containerSelector=".t3js-newmultiplepages-container",e.addMoreFieldsButtonSelector=".t3js-newmultiplepages-createnewfields",e.doktypeSelector=".t3js-newmultiplepages-select-doktype",e.templateRow=".t3js-newmultiplepages-newlinetemplate"}(l||(l={}));return new class{constructor(){this.lineCounter=5,(0,n.default)(()=>{this.initializeEvents()})}initializeEvents(){(0,n.default)(l.addMoreFieldsButtonSelector).on("click",()=>{this.createNewFormFields()}),(0,n.default)(document).on("change",l.doktypeSelector,e=>{this.actOnTypeSelectChange((0,n.default)(e.currentTarget))})}createNewFormFields(){for(let e=0;e<5;e++){const t=this.lineCounter+e+1,i=(0,n.default)(l.templateRow).html().replace(/\[0\]/g,(this.lineCounter+e).toString()).replace(/\[1\]/g,t.toString());(0,n.default)(i).appendTo(l.containerSelector)}this.lineCounter+=5}actOnTypeSelectChange(e){const t=e.find(":selected");(0,n.default)(e.data("target")).html(t.data("icon"))}}}));