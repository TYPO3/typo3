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
var __decorate=function(e,t,o,r){var l,n=arguments.length,p=n<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)p=Reflect.decorate(e,t,o,r);else for(var a=e.length-1;a>=0;a--)(l=e[a])&&(p=(n<3?l(p):n>3?l(t,o,p):l(t,o))||p);return n>3&&p&&Object.defineProperty(t,o,p),p};import{html}from"lit";import{customElement,property}from"lit/decorators.js";import{BaseElement}from"@typo3/backend/settings/type/base.js";import"@typo3/backend/color-picker.js";import RegularEvent from"@typo3/core/event/regular-event.js";export const componentName="typo3-backend-settings-type-color";let ColorTypeElement=class extends BaseElement{firstUpdated(){const e=this.getInputElement();e&&new RegularEvent("blur",(e=>{this.updateValue(e.target.value)})).bindTo(e)}updateValue(e){this.value=e}render(){return html`
      <typo3-backend-color-picker>
        <input
          type="text"
          id=${this.formid}
          class="form-control"
          ?readonly=${this.readonly}
          .value=${this.value}
          @change=${e=>this.updateValue(e.target.value)}
        />
      </typo3-backend-color-picker>
    `}getInputElement(){return this.querySelector("input")}};__decorate([property({type:String})],ColorTypeElement.prototype,"value",void 0),ColorTypeElement=__decorate([customElement(componentName)],ColorTypeElement);export{ColorTypeElement};