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
define(["require","exports","jquery"],function(e,n,t){"use strict";return function(){return function(e){var n=this;this.controlElement=null,this.assignedFormField=null,this.registerChangeHandler=function(){n.controlElement.classList.toggle("disabled",-1===n.assignedFormField.options.selectedIndex)},this.registerClickHandler=function(e){e.preventDefault();for(var t=[],r=0;r<n.assignedFormField.selectedOptions.length;++r){var l=n.assignedFormField.selectedOptions.item(r);t.push(l.value)}var i=n.controlElement.getAttribute("href")+"&P[currentValue]="+encodeURIComponent(n.assignedFormField.value)+"&P[currentSelectedValues]="+t.join(",");window.open(i,"",n.controlElement.dataset.windowParameters).focus()},t(function(){n.controlElement=document.querySelector(e),n.assignedFormField=document.querySelector('select[data-formengine-input-name="'+n.controlElement.dataset.element+'"]'),-1===n.assignedFormField.options.selectedIndex&&n.controlElement.classList.add("disabled"),n.assignedFormField.addEventListener("change",n.registerChangeHandler),n.controlElement.addEventListener("click",n.registerClickHandler)})}}()});