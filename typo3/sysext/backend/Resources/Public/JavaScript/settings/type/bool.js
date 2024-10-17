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
var __decorate=function(e,t,o,r){var c,l=arguments.length,n=l<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,o,r);else for(var p=e.length-1;p>=0;p--)(c=e[p])&&(n=(l<3?c(n):l>3?c(t,o,n):c(t,o))||n);return l>3&&n&&Object.defineProperty(t,o,n),n};import{html}from"lit";import{customElement,property}from"lit/decorators.js";import{BaseElement}from"@typo3/backend/settings/type/base.js";export const componentName="typo3-backend-settings-type-bool";let BoolTypeElement=class extends BaseElement{render(){return html`
      <div class="form-check form-check-type-toggle">
        <input
          type="checkbox"
          id=${this.formid}
          class="form-check-input"
          value="1"
          ?disabled=${this.readonly}
          .checked=${this.value}
          @change=${e=>this.value=e.target.checked}
        />
      </div>
    `}};__decorate([property({type:Boolean,converter:{toAttribute:e=>e?"1":"0",fromAttribute:e=>"1"===e||"true"===e}})],BoolTypeElement.prototype,"value",void 0),BoolTypeElement=__decorate([customElement(componentName)],BoolTypeElement);export{BoolTypeElement};