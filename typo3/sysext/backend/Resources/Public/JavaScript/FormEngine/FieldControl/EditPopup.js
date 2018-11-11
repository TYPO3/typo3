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
define(["require","exports","jquery"],function(e,t,n){"use strict";return function(e){var t=this;this.controlElement=null,this.assignedFormField=null,this.registerChangeHandler=function(){t.controlElement.classList.toggle("disabled",-1===t.assignedFormField.options.selectedIndex)},this.registerClickHandler=function(e){e.preventDefault();for(var n=[],r=0;r<t.assignedFormField.selectedOptions.length;++r){var l=t.assignedFormField.selectedOptions.item(r);n.push(l.value)}var i=t.controlElement.getAttribute("href")+"&P[currentValue]="+encodeURIComponent(t.assignedFormField.value)+"&P[currentSelectedValues]="+n.join(",");window.open(i,"",t.controlElement.dataset.windowParameters).focus()},n(function(){t.controlElement=document.querySelector(e),t.assignedFormField=document.querySelector('select[data-formengine-input-name="'+t.controlElement.dataset.element+'"]'),-1===t.assignedFormField.options.selectedIndex&&t.controlElement.classList.add("disabled"),t.assignedFormField.addEventListener("change",t.registerChangeHandler),t.controlElement.addEventListener("click",t.registerClickHandler)})}});