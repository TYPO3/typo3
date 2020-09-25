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
define(["require","exports","jquery"],(function(e,t,n){"use strict";var i;return function(e){e.containerSelector=".t3js-newmultiplepages-container",e.addMoreFieldsButtonSelector=".t3js-newmultiplepages-createnewfields",e.doktypeSelector=".t3js-newmultiplepages-select-doktype",e.templateRow=".t3js-newmultiplepages-newlinetemplate"}(i||(i={})),new(function(){function e(){var e=this;this.lineCounter=5,n((function(){e.initializeEvents()}))}return e.prototype.initializeEvents=function(){var e=this;n(i.addMoreFieldsButtonSelector).on("click",(function(){e.createNewFormFields()})),n(document).on("change",i.doktypeSelector,(function(t){e.actOnTypeSelectChange(n(t.currentTarget))}))},e.prototype.createNewFormFields=function(){for(var e=0;e<5;e++){var t=this.lineCounter+e+1,o=n(i.templateRow).html().replace(/\[0\]/g,(this.lineCounter+e).toString()).replace(/\[1\]/g,t.toString());n(o).appendTo(i.containerSelector)}this.lineCounter+=5},e.prototype.actOnTypeSelectChange=function(e){var t=e.find(":selected");n(e.data("target")).html(t.data("icon"))},e}())}));