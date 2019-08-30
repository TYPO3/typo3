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
define(["require","exports","jquery"],function(e,t,i){"use strict";var n;!function(e){e.toggleSelector=".t3js-form-field-inputlink-explanation-toggle",e.inputFieldSelector=".t3js-form-field-inputlink-input",e.explanationSelector=".t3js-form-field-inputlink-explanation"}(n||(n={}));return class{constructor(e){this.element=null,this.container=null,this.explanationField=null,i(()=>{this.element=document.querySelector("#"+e),this.container=this.element.closest(".t3js-form-field-inputlink"),this.explanationField=this.container.querySelector(n.explanationSelector),this.toggleVisibility(""===this.explanationField.value),this.registerEventHandler()})}toggleVisibility(e){this.explanationField.classList.toggle("hidden",e),this.element.classList.toggle("hidden",!e);const t=this.container.querySelector(".form-control-clearable button.close");null!==t&&t.classList.toggle("hidden",!e)}registerEventHandler(){this.container.querySelector(n.toggleSelector).addEventListener("click",e=>{e.preventDefault();const t=!this.explanationField.classList.contains("hidden");this.toggleVisibility(t)}),this.container.querySelector(n.inputFieldSelector).addEventListener("change",()=>{const e=!this.explanationField.classList.contains("hidden");e&&this.toggleVisibility(e)})}}});