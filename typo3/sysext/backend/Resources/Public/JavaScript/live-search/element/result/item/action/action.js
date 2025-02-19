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
import{property as p,customElement as d}from"lit/decorators.js";import{ifDefined as f}from"lit/directives/if-defined.js";import{LitElement as m,html as u}from"lit";import"@typo3/backend/element/icon-element.js";var a=function(o,e,i,n){var c=arguments.length,t=c<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,i):n,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(o,e,i,n);else for(var s=o.length-1;s>=0;s--)(l=o[s])&&(t=(c<3?l(t):c>3?l(e,i,t):l(e,i))||t);return c>3&&t&&Object.defineProperty(e,i,t),t};let r=class extends m{connectedCallback(){super.connectedCallback(),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0")}createRenderRoot(){return this}render(){return u`<div><div class=livesearch-result-item-icon><typo3-backend-icon identifier=${f(this.resultItemAction.icon.identifier||"actions-arrow-right")} overlay=${this.resultItemAction.icon.overlay} size=small></typo3-backend-icon></div><div class=livesearch-result-item-title>${this.resultItemAction.label}</div></div>`}};a([p({type:Object,attribute:!1})],r.prototype,"resultItem",void 0),a([p({type:Object,attribute:!1})],r.prototype,"resultItemAction",void 0),r=a([d("typo3-backend-live-search-result-item-action")],r);export{r as Action};
