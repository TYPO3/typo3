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
var __decorate=function(t,e,o,r){var i,n=arguments.length,a=n<3?e:null===r?r=Object.getOwnPropertyDescriptor(e,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)a=Reflect.decorate(t,e,o,r);else for(var l=t.length-1;l>=0;l--)(i=t[l])&&(a=(n<3?i(a):n>3?i(e,o,a):i(e,o))||a);return n>3&&a&&Object.defineProperty(e,o,a),a};import{html,css,LitElement}from"lit";import{customElement,property,query}from"lit/decorators.js";import{getRecordFromName}from"@typo3/backend/module.js";const IFRAME_COMPONENT="@typo3/backend/module/iframe",alwaysUpdate=(t,e)=>!0;let ModuleRouter=class extends LitElement{constructor(){super(),this.module="",this.endpoint="",this.addEventListener("typo3-module-load",({target:t,detail:e})=>{const o=t.getAttribute("slot");this.pushState({slotName:o,detail:e})}),this.addEventListener("typo3-module-loaded",({detail:t})=>{this.updateBrowserState(t)}),this.addEventListener("typo3-iframe-load",({detail:t})=>{let e={slotName:IFRAME_COMPONENT,detail:t};if(e.detail.url.includes(this.stateTrackerUrl+"?state=")){const t=e.detail.url.split("?state=");e=JSON.parse(decodeURIComponent(t[1]||"{}"))}this.slotElement.getAttribute("name")!==e.slotName&&this.slotElement.setAttribute("name",e.slotName),this.markActive(e.slotName,this.slotElement.getAttribute("name")===IFRAME_COMPONENT?null:e.detail.url,!1),this.updateBrowserState(e.detail),this.parentElement.dispatchEvent(new CustomEvent("typo3-module-load",{bubbles:!0,composed:!0,detail:e.detail}))}),this.addEventListener("typo3-iframe-loaded",({detail:t})=>{this.updateBrowserState(t),this.parentElement.dispatchEvent(new CustomEvent("typo3-module-loaded",{bubbles:!0,composed:!0,detail:t}))})}render(){const t=getRecordFromName(this.module).component||IFRAME_COMPONENT;return html`<slot name="${t}"></slot>`}updated(){const t=getRecordFromName(this.module).component||IFRAME_COMPONENT;this.markActive(t,this.endpoint)}async markActive(t,e,o=!0){const r=await this.getModuleElement(t);e&&(o||r.getAttribute("endpoint")!==e)&&r.setAttribute("endpoint",e),r.hasAttribute("active")||r.setAttribute("active","");for(let t=r.previousElementSibling;null!==t;t=t.previousElementSibling)t.removeAttribute("active");for(let t=r.nextElementSibling;null!==t;t=t.nextElementSibling)t.removeAttribute("active")}async getModuleElement(t){let e=this.querySelector(`*[slot="${t}"]`);if(null!==e)return e;try{const o=await import(t+".js");e=document.createElement(o.componentName)}catch(e){throw console.error({msg:`Error importing ${t} as backend module`,err:e}),e}return e.setAttribute("slot",t),this.appendChild(e),e}async pushState(t){const e=this.stateTrackerUrl+"?state="+encodeURIComponent(JSON.stringify(t));(await this.getModuleElement(IFRAME_COMPONENT)).setAttribute("endpoint",e)}updateBrowserState(t){const e=new URL(t.url||"",window.location.origin),o=new URLSearchParams(e.search),r="title"in t?t.title:"";if(null!==r){const t=[this.sitename];""!==r&&t.unshift(r),this.sitenameFirst&&t.reverse(),document.title=t.join(" · ")}if(!o.has("token"))return;o.delete("token"),e.search=o.toString();const i=e.toString();window.history.replaceState(t,"",i)}};ModuleRouter.styles=css`
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
  `,__decorate([property({type:String,hasChanged:alwaysUpdate})],ModuleRouter.prototype,"module",void 0),__decorate([property({type:String,hasChanged:alwaysUpdate})],ModuleRouter.prototype,"endpoint",void 0),__decorate([property({type:String,attribute:"state-tracker"})],ModuleRouter.prototype,"stateTrackerUrl",void 0),__decorate([property({type:String,attribute:"sitename"})],ModuleRouter.prototype,"sitename",void 0),__decorate([property({type:Boolean,attribute:"sitename-first"})],ModuleRouter.prototype,"sitenameFirst",void 0),__decorate([query("slot",!0)],ModuleRouter.prototype,"slotElement",void 0),ModuleRouter=__decorate([customElement("typo3-backend-module-router")],ModuleRouter);export{ModuleRouter};