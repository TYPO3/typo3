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
import{html as a}from"lit";import{property as u,customElement as s}from"lit/decorators.js";import{BaseElement as y}from"@typo3/backend/settings/type/base.js";var c=function(r,e,n,o){var l=arguments.length,t=l<3?e:o===null?o=Object.getOwnPropertyDescriptor(e,n):o,p;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(r,e,n,o);else for(var i=r.length-1;i>=0;i--)(p=r[i])&&(t=(l<3?p(t):l>3?p(e,n,t):p(e,n))||t);return l>3&&t&&Object.defineProperty(e,n,t),t};const f="typo3-backend-settings-type-int";let m=class extends y{render(){return a`<input type=number id=${this.formid} class=form-control ?readonly=${this.readonly} .value=${this.value} @change=${e=>this.value=parseInt(e.target.value,10)}>`}};c([u({type:Number})],m.prototype,"value",void 0),m=c([s(f)],m);export{m as IntTypeElement,f as componentName};
