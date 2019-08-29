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
define(["require","exports","jquery"],function(e,t,i){"use strict";var n;return function(e){e.toggleSelector=".t3js-form-field-inputlink-explanation-toggle",e.inputFieldSelector=".t3js-form-field-inputlink-input",e.explanationSelector=".t3js-form-field-inputlink-explanation"}(n||(n={})),function(){function e(e){var t=this;this.element=null,this.container=null,this.explanationField=null,i(function(){t.element=document.querySelector("#"+e),t.container=t.element.closest(".t3js-form-field-inputlink"),t.explanationField=t.container.querySelector(n.explanationSelector),t.toggleVisibility(""===t.explanationField.value),t.registerEventHandler()})}return e.prototype.toggleVisibility=function(e){this.explanationField.classList.toggle("hidden",e),this.element.classList.toggle("hidden",!e);var t=this.container.querySelector(".form-control-clearable button.close");null!==t&&t.classList.toggle("hidden",!e)},e.prototype.registerEventHandler=function(){var e=this;this.container.querySelector(n.toggleSelector).addEventListener("click",function(t){t.preventDefault();var i=!e.explanationField.classList.contains("hidden");e.toggleVisibility(i)}),this.container.querySelector(n.inputFieldSelector).addEventListener("change",function(){var t=!e.explanationField.classList.contains("hidden");t&&e.toggleVisibility(t)})},e}()});