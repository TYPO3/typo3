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
var __decorate=function(e,t,o,r){var l,a=arguments.length,n=a<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,o,r);else for(var p=e.length-1;p>=0;p--)(l=e[p])&&(n=(a<3?l(n):a>3?l(t,o,n):l(t,o))||n);return a>3&&n&&Object.defineProperty(t,o,n),n};import{html}from"lit";import{customElement,property}from"lit/decorators.js";import{BaseElement}from"@typo3/backend/settings/type/base.js";import Alwan from"alwan";export const componentName="typo3-backend-settings-type-color";let ColorTypeElement=class extends BaseElement{constructor(){super(...arguments),this.alwan=null}firstUpdated(){this.alwan=new Alwan(this.querySelector("input"),{position:"bottom-start",format:"hex",opacity:!1,preset:!1,color:this.value}),this.alwan.on("color",(e=>{this.value=e.hex}))}updateValue(e){this.value=e,this.alwan?.setColor(e)}render(){return html`
      <input
        type="text"
        id=${this.formid}
        class="form-control"
        .value=${this.value}
        @change=${e=>this.updateValue(e.target.value)}
      />
    `}};__decorate([property({type:String})],ColorTypeElement.prototype,"value",void 0),ColorTypeElement=__decorate([customElement(componentName)],ColorTypeElement);export{ColorTypeElement};