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
var __decorate=this&&this.__decorate||function(e,t,r,o){var n,c=arguments.length,i=c<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,t,r,o);else for(var s=e.length-1;s>=0;s--)(n=e[s])&&(i=(c<3?n(i):c>3?n(t,r,i):n(t,r))||i);return c>3&&i&&Object.defineProperty(t,r,i),i};define(["require","exports","lit","lit/decorators","TYPO3/CMS/Backend/FormEngine"],(function(e,t,r,o,n){"use strict";var c;Object.defineProperty(t,"__esModule",{value:!0}),function(e){e.ask="ask",e.enforce="enforce"}(c||(c={}));const i={fromAttribute:e=>document.querySelectorAll(e)};let s=class extends r.LitElement{constructor(){super(...arguments),this.mode=c.ask,this.requestFormEngineUpdate=()=>{const e=this.mode===c.ask;n.requestFormEngineUpdate(e)}}connectedCallback(){super.connectedCallback();for(let e of this.fields)e.addEventListener("change",this.requestFormEngineUpdate)}disconnectedCallback(){super.disconnectedCallback();for(let e of this.fields)e.removeEventListener("change",this.requestFormEngineUpdate)}};__decorate([(0,o.property)({type:String,attribute:"mode"})],s.prototype,"mode",void 0),__decorate([(0,o.property)({attribute:"field",converter:i})],s.prototype,"fields",void 0),s=__decorate([(0,o.customElement)("typo3-formengine-updater")],s)}));