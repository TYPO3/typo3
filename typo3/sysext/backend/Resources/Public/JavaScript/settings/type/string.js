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
var __decorate=function(e,t,r,o){var n,l=arguments.length,i=l<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,t,r,o);else for(var p=e.length-1;p>=0;p--)(n=e[p])&&(i=(l<3?n(i):l>3?n(t,r,i):n(t,r))||i);return l>3&&i&&Object.defineProperty(t,r,i),i};import{html}from"lit";import{customElement,property}from"lit/decorators.js";import{BaseElement}from"@typo3/backend/settings/type/base.js";export const componentName="typo3-backend-settings-type-string";let StringTypeElement=class extends BaseElement{render(){return html`
      <input
        type="text"
        id=${this.formid}
        class="form-control"
        ?readonly=${this.readonly}
        .value=${this.value}
        @change=${e=>this.value=e.target.value}
      />
    `}};__decorate([property({type:String})],StringTypeElement.prototype,"value",void 0),StringTypeElement=__decorate([customElement(componentName)],StringTypeElement);export{StringTypeElement};