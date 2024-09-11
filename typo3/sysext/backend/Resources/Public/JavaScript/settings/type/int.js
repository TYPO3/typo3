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
var __decorate=function(e,t,r,o){var n,l=arguments.length,p=l<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)p=Reflect.decorate(e,t,r,o);else for(var a=e.length-1;a>=0;a--)(n=e[a])&&(p=(l<3?n(p):l>3?n(t,r,p):n(t,r))||p);return l>3&&p&&Object.defineProperty(t,r,p),p};import{html}from"lit";import{customElement,property}from"lit/decorators.js";import{BaseElement}from"@typo3/backend/settings/type/base.js";export const componentName="typo3-backend-settings-type-int";let IntTypeElement=class extends BaseElement{render(){return html`
      <input
        type="number"
        id=${this.formid}
        class="form-control"
        ?readonly=${this.readonly}
        .value=${this.value}
        @change=${e=>this.value=parseInt(e.target.value,10)}
      />
    `}};__decorate([property({type:Number})],IntTypeElement.prototype,"value",void 0),IntTypeElement=__decorate([customElement(componentName)],IntTypeElement);export{IntTypeElement};