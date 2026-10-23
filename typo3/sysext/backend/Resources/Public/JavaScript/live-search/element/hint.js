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
import{property as f,customElement as h}from"lit/decorators.js";import{LitElement as s,nothing as u,html as a}from"lit";import{markdown as d}from"@typo3/core/directive/markdown.js";import"@typo3/backend/element/icon-element.js";var m=function(r,e,n,i){var o=arguments.length,t=o<3?e:i===null?i=Object.getOwnPropertyDescriptor(e,n):i,c;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(r,e,n,i);else for(var p=r.length-1;p>=0;p--)(c=r[p])&&(t=(o<3?c(t):o>3?c(e,n,t):c(e,n))||t);return o>3&&t&&Object.defineProperty(e,n,t),t};let l=class extends s{constructor(){super(...arguments),this.hint=""}createRenderRoot(){return this}render(){return this.hint===""?u:a`<typo3-backend-icon identifier=actions-lightbulb-on size=small></typo3-backend-icon>${d(this.hint,"minimal")}`}};m([f({type:String})],l.prototype,"hint",void 0),l=m([h("typo3-backend-live-search-hint")],l);export{l as Hint};
