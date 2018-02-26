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
define(["require","exports","jquery"],function(a,b,c){"use strict";var d;!function(a){a.containerSelector=".t3js-newmultiplepages-container",a.addMoreFieldsButtonSelector=".t3js-newmultiplepages-createnewfields",a.doktypeSelector=".t3js-newmultiplepages-select-doktype",a.templateRow=".t3js-newmultiplepages-newlinetemplate"}(d||(d={}));var e=function(){function a(){var a=this;this.lineCounter=5,c(function(){a.initializeEvents()})}return a.prototype.initializeEvents=function(){var a=this;c(d.addMoreFieldsButtonSelector).on("click",function(){a.createNewFormFields()}),c(document).on("change",d.doktypeSelector,function(b){a.actOnTypeSelectChange(c(b.currentTarget))})},a.prototype.createNewFormFields=function(){for(var a=0;a<5;a++){var b=this.lineCounter+a+1,e=c(d.templateRow).html().replace(/\[0\]/g,(this.lineCounter+a).toString()).replace(/\[1\]/g,b.toString());c(e).appendTo(d.containerSelector)}this.lineCounter+=5},a.prototype.actOnTypeSelectChange=function(a){var b=a.find(":selected"),d=c(a.data("target"));d.html(b.data("icon"))},a}();return new e});