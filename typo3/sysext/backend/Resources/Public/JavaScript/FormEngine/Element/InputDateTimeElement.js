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
var __createBinding=this&&this.__createBinding||(Object.create?function(e,t,n,r){void 0===r&&(r=n),Object.defineProperty(e,r,{enumerable:!0,get:function(){return t[n]}})}:function(e,t,n,r){void 0===r&&(r=n),e[r]=t[n]}),__setModuleDefault=this&&this.__setModuleDefault||(Object.create?function(e,t){Object.defineProperty(e,"default",{enumerable:!0,value:t})}:function(e,t){e.default=t}),__importStar=this&&this.__importStar||function(e){if(e&&e.__esModule)return e;var t={};if(null!=e)for(var n in e)"default"!==n&&Object.prototype.hasOwnProperty.call(e,n)&&__createBinding(t,e,n);return __setModuleDefault(t,e),t};define(["require","exports","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Backend/FormEngineValidation","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,n,r,i){"use strict";return class{constructor(t){this.element=null,n.ready().then(()=>{this.element=document.getElementById(t),this.registerEventHandler(this.element),new Promise((t,n)=>{e(["../../DateTimePicker"],t,n)}).then(__importStar).then(({default:e})=>{e.initialize(this.element)})})}registerEventHandler(e){new i("formengine.dp.change",e=>{r.validateField(e.target),r.markFieldAsChanged(e.target),document.querySelectorAll(".module-docheader-bar .btn").forEach(e=>{e.classList.remove("disabled"),e.disabled=!1})}).bindTo(e)}}}));