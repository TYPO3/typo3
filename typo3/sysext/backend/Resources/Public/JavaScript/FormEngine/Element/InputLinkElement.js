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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery"],(function(e,t,i){"use strict";var l;i=__importDefault(i),function(e){e.toggleSelector=".t3js-form-field-inputlink-explanation-toggle",e.inputFieldSelector=".t3js-form-field-inputlink-input",e.explanationSelector=".t3js-form-field-inputlink-explanation",e.iconSelector=".t3js-form-field-inputlink-icon"}(l||(l={}));return class{constructor(e){this.element=null,this.container=null,this.toggleSelector=null,this.explanationField=null,this.icon=null,i.default(()=>{this.element=document.getElementById(e),this.container=this.element.closest(".t3js-form-field-inputlink"),this.toggleSelector=this.container.querySelector(l.toggleSelector),this.explanationField=this.container.querySelector(l.explanationSelector),this.icon=this.container.querySelector(l.iconSelector),this.toggleVisibility(""===this.explanationField.value),this.registerEventHandler()})}toggleVisibility(e){this.explanationField.classList.toggle("hidden",e),this.element.classList.toggle("hidden",!e);const t=this.container.querySelector(".form-control-clearable button.close");null!==t&&t.classList.toggle("hidden",!e)}registerEventHandler(){this.toggleSelector.addEventListener("click",e=>{e.preventDefault();const t=!this.explanationField.classList.contains("hidden");this.toggleVisibility(t)}),this.container.querySelector(l.inputFieldSelector).addEventListener("change",()=>{const e=!this.explanationField.classList.contains("hidden");e&&this.toggleVisibility(e),this.disableToggle(),this.clearIcon()})}disableToggle(){this.toggleSelector.classList.add("disabled"),this.toggleSelector.setAttribute("disabled","disabled")}clearIcon(){this.icon.innerHTML=""}}}));