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
define(["require","exports","jquery"],function(e,t,i){"use strict";var l;!function(e){e.fieldContainerSelector=".t3js-formengine-field-group",e.filterTextFieldSelector=".t3js-formengine-multiselect-filter-textfield",e.filterSelectFieldSelector=".t3js-formengine-multiselect-filter-dropdown"}(l||(l={}));return class{constructor(e){this.selectElement=null,this.filterText="",this.$availableOptions=null,this.selectElement=e,this.initializeEvents()}initializeEvents(){const e=this.selectElement.closest(".form-wizards-element");null!==e&&(e.addEventListener("keyup",e=>{e.target.matches(l.filterTextFieldSelector)&&this.filter(e.target.value)}),e.addEventListener("change",e=>{e.target.matches(l.filterSelectFieldSelector)&&this.filter(e.target.value)}))}filter(e){this.filterText=e,this.$availableOptions||(this.$availableOptions=i(this.selectElement).find("option").clone()),this.selectElement.innerHTML="";const t=new RegExp(e,"i");this.$availableOptions.each((i,l)=>{(0===e.length||l.textContent.match(t))&&this.selectElement.appendChild(l)})}}});