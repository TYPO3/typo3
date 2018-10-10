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
define(["require","exports","jquery"],function(e,t,i){"use strict";var l,n;return(n=l||(l={})).fieldContainerSelector=".t3js-formengine-field-group",n.filterTextFieldSelector=".t3js-formengine-multiselect-filter-textfield",n.filterSelectFieldSelector=".t3js-formengine-multiselect-filter-dropdown",function(){function e(e){this.selectElement=null,this.filterText="",this.$availableOptions=null,this.selectElement=e,this.initializeEvents()}return e.prototype.initializeEvents=function(){var e=this,t=this.selectElement.closest(".form-wizards-element");null!==t&&(t.addEventListener("keyup",function(t){t.target.matches(l.filterTextFieldSelector)&&e.filter(t.target.value)}),t.addEventListener("change",function(t){t.target.matches(l.filterSelectFieldSelector)&&e.filter(t.target.value)}))},e.prototype.filter=function(e){var t=this;this.filterText=e,this.$availableOptions||(this.$availableOptions=i(this.selectElement).find("option").clone()),this.selectElement.innerHTML="";var l=new RegExp(e,"i");this.$availableOptions.each(function(i,n){(0===e.length||n.textContent.match(l))&&t.selectElement.appendChild(n)})},e}()});