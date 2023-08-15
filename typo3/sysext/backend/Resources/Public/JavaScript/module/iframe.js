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
var __decorate=function(e,t,o,r){var n,i=arguments.length,l=i<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(e,t,o,r);else for(var a=e.length-1;a>=0;a--)(n=e[a])&&(l=(i<3?n(l):i>3?n(t,o,l):n(t,o))||l);return i>3&&l&&Object.defineProperty(t,o,l),l};import{html,LitElement,nothing}from"lit";import{customElement,property,query}from"lit/decorators.js";import{lll}from"@typo3/core/lit-helper.js";export const componentName="typo3-iframe-module";let IframeModuleElement=class extends LitElement{constructor(){super(...arguments),this.endpoint=""}attributeChangedCallback(e,t,o){super.attributeChangedCallback(e,t,o),"endpoint"===e&&o===t&&this.iframe.setAttribute("src",o)}connectedCallback(){super.connectedCallback(),this.endpoint&&this.dispatch("typo3-iframe-load",{url:this.endpoint,title:null})}createRenderRoot(){return this}render(){return this.endpoint?html`
      <iframe
        src="${this.endpoint}"
        name="list_frame"
        id="typo3-contentIframe"
        class="scaffold-content-module-iframe t3js-scaffold-content-module-iframe"
        title="${lll("iframe.listFrame")}"
        scrolling="no"
        @load="${this._loaded}"
      ></iframe>
    `:nothing}registerPagehideHandler(e){try{e.contentWindow.addEventListener("pagehide",(t=>this._pagehide(t,e)),{once:!0})}catch(e){throw console.error("Failed to access contentWindow of module iframe – using a foreign origin?"),e}}retrieveModuleStateFromIFrame(e){try{return{url:e.contentWindow.location.href,title:e.contentDocument.title,module:e.contentDocument.body.querySelector(".module[data-module-name]")?.getAttribute("data-module-name")}}catch(e){return console.error("Failed to access contentWindow of module iframe – using a foreign origin?"),{url:this.endpoint,title:null}}}_loaded({target:e}){const t=e;this.registerPagehideHandler(t);const o=this.retrieveModuleStateFromIFrame(t);this.dispatch("typo3-iframe-loaded",o)}_pagehide(e,t){new Promise((e=>window.setTimeout(e,0))).then((()=>{null!==t.contentWindow&&this.dispatch("typo3-iframe-load",{url:t.contentWindow.location.href,title:null})}))}dispatch(e,t){this.dispatchEvent(new CustomEvent(e,{detail:t,bubbles:!0,composed:!0}))}};__decorate([property({type:String})],IframeModuleElement.prototype,"endpoint",void 0),__decorate([query("iframe",!0)],IframeModuleElement.prototype,"iframe",void 0),IframeModuleElement=__decorate([customElement("typo3-iframe-module")],IframeModuleElement);export{IframeModuleElement};