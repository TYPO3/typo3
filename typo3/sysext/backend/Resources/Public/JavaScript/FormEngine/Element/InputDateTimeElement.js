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
define(["require","exports","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Backend/FormEngine","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,n,i,r){"use strict";return class{constructor(t){this.element=null,n.ready().then(()=>{this.element=document.getElementById(t),this.registerEventHandler(),e(["../../DateTimePicker"],e=>{e.initialize(this.element)})})}registerEventHandler(){new r("formengine.dp.change",e=>{i.Validation.validate(),i.Validation.markFieldAsChanged(e.detail.element),document.querySelectorAll(".module-docheader-bar .btn").forEach(e=>{e.classList.remove("disabled"),e.disabled=!1})}).bindTo(document)}}}));