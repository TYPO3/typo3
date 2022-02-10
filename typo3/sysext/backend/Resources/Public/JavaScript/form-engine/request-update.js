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
var UpdateMode,__decorate=function(e,t,o,r){var n,d=arguments.length,c=d<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)c=Reflect.decorate(e,t,o,r);else for(var a=e.length-1;a>=0;a--)(n=e[a])&&(c=(d<3?n(c):d>3?n(t,o,c):n(t,o))||c);return d>3&&c&&Object.defineProperty(t,o,c),c};import{LitElement}from"lit";import{customElement,property}from"lit/decorators.js";import FormEngine from"@typo3/backend/form-engine.js";!function(e){e.ask="ask",e.enforce="enforce"}(UpdateMode||(UpdateMode={}));const selectorConverter={fromAttribute:e=>document.querySelectorAll(e)};let RequestUpdate=class extends LitElement{constructor(){super(...arguments),this.mode=UpdateMode.ask,this.requestFormEngineUpdate=()=>{const e=this.mode===UpdateMode.ask;FormEngine.requestFormEngineUpdate(e)}}connectedCallback(){super.connectedCallback();for(let e of this.fields)e.addEventListener("change",this.requestFormEngineUpdate)}disconnectedCallback(){super.disconnectedCallback();for(let e of this.fields)e.removeEventListener("change",this.requestFormEngineUpdate)}};__decorate([property({type:String,attribute:"mode"})],RequestUpdate.prototype,"mode",void 0),__decorate([property({attribute:"field",converter:selectorConverter})],RequestUpdate.prototype,"fields",void 0),RequestUpdate=__decorate([customElement("typo3-formengine-updater")],RequestUpdate);