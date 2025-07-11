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
import{property as c,customElement as v}from"lit/decorators.js";import{LitElement as u,html as p,nothing as m}from"lit";import"@typo3/backend/element/icon-element.js";import{lll as f}from"@typo3/core/lit-helper.js";var l=function(a,i,r,o){var s=arguments.length,e=s<3?i:o===null?o=Object.getOwnPropertyDescriptor(i,r):o,n;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(a,i,r,o);else for(var d=a.length-1;d>=0;d--)(n=a[d])&&(e=(s<3?n(e):s>3?n(i,r,e):n(i,r))||e);return s>3&&e&&Object.defineProperty(i,r,e),e};let t=class extends u{createRenderRoot(){return this}render(){return p`<div class=livesearch-result-item-icon><typo3-backend-icon title=${this.icon.title} identifier=${this.icon.identifier} overlay=${this.icon.overlay} size=small></typo3-backend-icon></div><div class=livesearch-result-item-summary><div class=livesearch-result-item-title><div class=livesearch-result-item-title-contentlabel>${this.itemTitle}</div>${this.extraData.inWorkspace?p`<div class=livesearch-result-item-title-indicator><typo3-backend-icon title=${f("liveSearch.versionizedRecord")} identifier=actions-dot size=small class=text-warning></typo3-backend-icon></div>`:m}</div>${this.extraData.breadcrumb!==void 0?p`<small>${this.extraData.breadcrumb}</small>`:m}</div>`}};l([c({type:Object,attribute:!1})],t.prototype,"icon",void 0),l([c({type:String,attribute:!1})],t.prototype,"itemTitle",void 0),l([c({type:String,attribute:!1})],t.prototype,"typeLabel",void 0),l([c({type:Object,attribute:!1})],t.prototype,"extraData",void 0),t=l([v("typo3-backend-live-search-result-item-default")],t);export{t as DefaultProviderResultItem};
