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
import{property as s,customElement as d}from"lit/decorators.js";import{LitElement as f,html as m}from"lit";import"@typo3/backend/element/icon-element.js";var o=function(a,i,r,l){var n=arguments.length,e=n<3?i:l===null?l=Object.getOwnPropertyDescriptor(i,r):l,c;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(a,i,r,l);else for(var p=a.length-1;p>=0;p--)(c=a[p])&&(e=(n<3?c(e):n>3?c(i,r,e):c(i,r))||e);return n>3&&e&&Object.defineProperty(i,r,e),e};let t=class extends f{createRenderRoot(){return this}render(){return m`<div class=livesearch-result-item-icon><typo3-backend-icon title=${this.icon.title} identifier=${this.icon.identifier} overlay=${this.icon.overlay} size=small></typo3-backend-icon><typo3-backend-icon title=${this.extraData.flagIcon.title} identifier=${this.extraData.flagIcon.identifier} size=small></typo3-backend-icon></div><div class=livesearch-result-item-title>${this.itemTitle}<br><small>${this.extraData.breadcrumb}</small></div>`}};o([s({type:Object,attribute:!1})],t.prototype,"icon",void 0),o([s({type:String,attribute:!1})],t.prototype,"itemTitle",void 0),o([s({type:String,attribute:!1})],t.prototype,"typeLabel",void 0),o([s({type:Object,attribute:!1})],t.prototype,"extraData",void 0),t=o([d("typo3-backend-live-search-result-item-page-provider")],t);var v=t;export{v as default};
