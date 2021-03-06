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
var __createBinding=this&&this.__createBinding||(Object.create?function(t,e,o,r){void 0===r&&(r=o),Object.defineProperty(t,r,{enumerable:!0,get:function(){return e[o]}})}:function(t,e,o,r){void 0===r&&(r=o),t[r]=e[o]}),__setModuleDefault=this&&this.__setModuleDefault||(Object.create?function(t,e){Object.defineProperty(t,"default",{enumerable:!0,value:e})}:function(t,e){t.default=e}),__decorate=this&&this.__decorate||function(t,e,o,r){var i,n=arguments.length,a=n<3?e:null===r?r=Object.getOwnPropertyDescriptor(e,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)a=Reflect.decorate(t,e,o,r);else for(var l=t.length-1;l>=0;l--)(i=t[l])&&(a=(n<3?i(a):n>3?i(e,o,a):i(e,o))||a);return n>3&&a&&Object.defineProperty(e,o,a),a},__importStar=this&&this.__importStar||function(t){if(t&&t.__esModule)return t;var e={};if(null!=t)for(var o in t)"default"!==o&&Object.prototype.hasOwnProperty.call(t,o)&&__createBinding(e,t,o);return __setModuleDefault(e,t),e};define(["require","exports","lit","lit/decorators","../Module"],(function(t,e,o,r,i){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.ModuleRouter=void 0;const n="TYPO3/CMS/Backend/Module/Iframe",a=(t,e)=>!0;let l=class extends o.LitElement{constructor(){super(),this.module="",this.endpoint="",this.addEventListener("typo3-module-load",({target:t,detail:e})=>{const o=t.getAttribute("slot");this.pushState({slotName:o,detail:e})}),this.addEventListener("typo3-module-loaded",({detail:t})=>{this.updateBrowserState(t)}),this.addEventListener("typo3-iframe-load",({detail:t})=>{let e={slotName:n,detail:t};if(e.detail.url.includes(this.stateTrackerUrl+"?state=")){const t=e.detail.url.split("?state=");e=JSON.parse(decodeURIComponent(t[1]||"{}"))}this.slotElement.getAttribute("name")!==e.slotName&&this.slotElement.setAttribute("name",e.slotName),this.markActive(e.slotName,this.slotElement.getAttribute("name")===n?null:e.detail.url,!1),this.updateBrowserState(e.detail),this.parentElement.dispatchEvent(new CustomEvent("typo3-module-load",{bubbles:!0,composed:!0,detail:e.detail}))}),this.addEventListener("typo3-iframe-loaded",({detail:t})=>{this.updateBrowserState(t),this.parentElement.dispatchEvent(new CustomEvent("typo3-module-loaded",{bubbles:!0,composed:!0,detail:t}))})}render(){const t=i.getRecordFromName(this.module).component||n;return o.html`<slot name="${t}"></slot>`}updated(){const t=i.getRecordFromName(this.module).component||n;this.markActive(t,this.endpoint)}async markActive(t,e,o=!0){const r=await this.getModuleElement(t);e&&(o||r.getAttribute("endpoint")!==e)&&r.setAttribute("endpoint",e),r.hasAttribute("active")||r.setAttribute("active","");for(let t=r.previousElementSibling;null!==t;t=t.previousElementSibling)t.removeAttribute("active");for(let t=r.nextElementSibling;null!==t;t=t.nextElementSibling)t.removeAttribute("active")}async getModuleElement(e){let o=this.querySelector(`*[slot="${e}"]`);if(null!==o)return o;try{const r=await new Promise((o,r)=>{t([e],o,r)}).then(__importStar);o=document.createElement(r.componentName)}catch(t){throw console.error({msg:`Error importing ${e} as backend module`,err:t}),t}return o.setAttribute("slot",e),this.appendChild(o),o}async pushState(t){const e=this.stateTrackerUrl+"?state="+encodeURIComponent(JSON.stringify(t));(await this.getModuleElement(n)).setAttribute("endpoint",e)}updateBrowserState(t){const e=new URL(t.url||"",window.location.origin),o=new URLSearchParams(e.search);if(!o.has("token"))return;o.delete("token"),e.search=o.toString();const r=e.toString();window.history.replaceState(t,"",r);const i=t.title||null;i&&(document.title=i)}};l.styles=o.css`
    :host {
      width: 100%;
      min-height: 100%;
      flex: 1 0 auto;
      display: flex;
      flex-direction: row;
    }
    ::slotted(*) {
      min-height: 100%;
      width: 100%;
    }
  `,__decorate([r.property({type:String,hasChanged:a})],l.prototype,"module",void 0),__decorate([r.property({type:String,hasChanged:a})],l.prototype,"endpoint",void 0),__decorate([r.property({type:String,attribute:"state-tracker"})],l.prototype,"stateTrackerUrl",void 0),__decorate([r.query("slot",!0)],l.prototype,"slotElement",void 0),l=__decorate([r.customElement("typo3-backend-module-router")],l),e.ModuleRouter=l}));