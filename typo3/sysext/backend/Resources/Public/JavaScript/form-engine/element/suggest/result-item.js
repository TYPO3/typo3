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
import{html as u}from"lit";import{property as l,customElement as m}from"lit/decorators.js";import{PseudoButtonLitElement as d}from"@typo3/backend/element/pseudo-button.js";import"@typo3/backend/element/icon-element.js";var s=function(c,e,t,n){var r=arguments.length,o=r<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,t):n,a;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(c,e,t,n);else for(var p=c.length-1;p>=0;p--)(a=c[p])&&(o=(r<3?a(o):r>3?a(e,t,o):a(e,t))||o);return r>3&&o&&Object.defineProperty(e,t,o),o};let i=class extends d{constructor(){super(),this.addEventListener("blur",this.onBlur)}buttonActivated(e){this.dispatchItemChosenEvent(e.currentTarget)}createRenderRoot(){return this}render(){return u`<div class=formengine-suggest-result-item-icon><typo3-backend-icon title=${this.icon.title} identifier=${this.icon.identifier} overlay=${this.icon.overlay} size=small></typo3-backend-icon></div><div class=formengine-suggest-result-item-label>${this.label} <small>[${this.uid}] ${this.path}</small></div>`}onBlur(e){let t=!0;const n=e.relatedTarget,r=this.closest("typo3-backend-formengine-suggest-result-container");n?.tagName.toLowerCase()==="typo3-backend-formengine-suggest-result-item"&&(t=!1),n?.matches('input[type="search"]')&&r.contains(n)&&(t=!1),r.hidden=t}dispatchItemChosenEvent(e){e.closest("typo3-backend-formengine-suggest-result-container").dispatchEvent(new CustomEvent("typo3:formengine:suggest-item-chosen",{detail:{element:e}}))}};s([l({type:Object})],i.prototype,"icon",void 0),s([l({type:Number})],i.prototype,"uid",void 0),s([l({type:String})],i.prototype,"table",void 0),s([l({type:String})],i.prototype,"label",void 0),s([l({type:String})],i.prototype,"path",void 0),i=s([m("typo3-backend-formengine-suggest-result-item")],i);export{i as ResultItem};
