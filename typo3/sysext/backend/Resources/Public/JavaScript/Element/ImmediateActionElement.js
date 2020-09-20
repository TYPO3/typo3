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
var __createBinding=this&&this.__createBinding||(Object.create?function(e,t,n,r){void 0===r&&(r=n),Object.defineProperty(e,r,{enumerable:!0,get:function(){return t[n]}})}:function(e,t,n,r){void 0===r&&(r=n),e[r]=t[n]}),__setModuleDefault=this&&this.__setModuleDefault||(Object.create?function(e,t){Object.defineProperty(e,"default",{enumerable:!0,value:t})}:function(e,t){e.default=t}),__importStar=this&&this.__importStar||function(e){if(e&&e.__esModule)return e;var t={};if(null!=e)for(var n in e)"default"!==n&&Object.prototype.hasOwnProperty.call(e,n)&&__createBinding(t,e,n);return __setModuleDefault(t,e),t};define(["require","exports","TYPO3/CMS/Core/Contrib/document-register-element-polyfill"],(function(e,t){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.ImmediateActionElement=void 0;class n extends HTMLElement{static async getDelegate(t){switch(t){case"TYPO3.ModuleMenu.App.refreshMenu":const n=await new Promise((t,n)=>{e(["TYPO3/CMS/Backend/ModuleMenu"],t,n)}).then(__importStar);return n.App.refreshMenu.bind(n);case"TYPO3.Backend.Topbar.refresh":const r=await new Promise((t,n)=>{e(["TYPO3/CMS/Backend/Viewport"],t,n)}).then(__importStar);return r.Topbar.refresh.bind(r.Topbar);default:throw Error('Unknown action "'+t+'"')}}static get observedAttributes(){return["action"]}attributeChangedCallback(e,t,n){"action"===e&&(this.action=n)}connectedCallback(){if(!this.action)throw new Error("Missing mandatory action attribute");n.getDelegate(this.action).then(e=>e.apply(null,[]))}}t.ImmediateActionElement=n,window.customElements.define("typo3-immediate-action",n)}));