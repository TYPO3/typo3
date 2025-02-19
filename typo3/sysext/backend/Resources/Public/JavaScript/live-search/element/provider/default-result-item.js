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
import{property as s,customElement as f}from"lit/decorators.js";import{LitElement as u,html as d}from"lit";import"@typo3/backend/element/icon-element.js";var l=function(o,i,r,a){var n=arguments.length,e=n<3?i:a===null?a=Object.getOwnPropertyDescriptor(i,r):a,c;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(o,i,r,a);else for(var p=o.length-1;p>=0;p--)(c=o[p])&&(e=(n<3?c(e):n>3?c(i,r,e):c(i,r))||e);return n>3&&e&&Object.defineProperty(i,r,e),e};let t=class extends u{createRenderRoot(){return this}render(){return d`<div class=livesearch-result-item-icon><typo3-backend-icon title=${this.icon.title} identifier=${this.icon.identifier} overlay=${this.icon.overlay} size=small></typo3-backend-icon></div><div class=livesearch-result-item-title>${this.itemTitle}${this.extraData.breadcrumb!==void 0?d`<br><small>${this.extraData.breadcrumb}</small>`:""}</div>`}};l([s({type:Object,attribute:!1})],t.prototype,"icon",void 0),l([s({type:String,attribute:!1})],t.prototype,"itemTitle",void 0),l([s({type:String,attribute:!1})],t.prototype,"typeLabel",void 0),l([s({type:Object,attribute:!1})],t.prototype,"extraData",void 0),t=l([f("typo3-backend-live-search-result-item-default")],t);export{t as DefaultProviderResultItem};
