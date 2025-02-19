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
import{html as f}from"lit";import{property as i,customElement as s}from"lit/decorators.js";import{BaseElement as y}from"@typo3/backend/settings/type/base.js";var a=function(o,e,r,n){var l=arguments.length,t=l<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,r):n,p;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(o,e,r,n);else for(var u=o.length-1;u>=0;u--)(p=o[u])&&(t=(l<3?p(t):l>3?p(e,r,t):p(e,r))||t);return l>3&&t&&Object.defineProperty(e,r,t),t};const c="typo3-backend-settings-type-number";let m=class extends y{render(){return f`<input type=number id=${this.formid} class=form-control step=0.01 ?readonly=${this.readonly} .value=${this.value} @change=${e=>this.value=parseFloat(e.target.value)}>`}};a([i({type:Number})],m.prototype,"value",void 0),m=a([s(c)],m);export{m as NumberTypeElement,c as componentName};
