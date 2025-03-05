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
var __decorate=function(e,t,r,o){var n,l=arguments.length,m=l<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)m=Reflect.decorate(e,t,r,o);else for(var p=e.length-1;p>=0;p--)(n=e[p])&&(m=(l<3?n(m):l>3?n(t,r,m):n(t,r))||m);return l>3&&m&&Object.defineProperty(t,r,m),m};import{html}from"lit";import{customElement,property}from"lit/decorators.js";import{BaseElement}from"@typo3/backend/settings/type/base.js";export const componentName="typo3-backend-settings-type-number";let NumberTypeElement=class extends BaseElement{render(){return html`<input type="number" id="${this.formid}" class="form-control" step="0.01" ?readonly="${this.readonly}" .value="${this.value}" @change="${e=>this.value=parseFloat(e.target.value)}">`}};__decorate([property({type:Number})],NumberTypeElement.prototype,"value",void 0),NumberTypeElement=__decorate([customElement(componentName)],NumberTypeElement);export{NumberTypeElement};