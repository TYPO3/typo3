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
define(["require","exports","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Backend/FormEngineValidation","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,n,r,i){"use strict";return class{constructor(t){this.element=null,n.ready().then(()=>{this.element=document.getElementById(t),this.registerEventHandler(this.element),e(["../../DateTimePicker"],e=>{e.initialize(this.element)})})}registerEventHandler(e){new i("formengine.dp.change",e=>{r.validateField(e.target),r.markFieldAsChanged(e.target),document.querySelectorAll(".module-docheader-bar .btn").forEach(e=>{e.classList.remove("disabled"),e.disabled=!1})}).bindTo(e)}}}));