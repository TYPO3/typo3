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
import{property as u,customElement as d}from"lit/decorators.js";import{LitElement as h,nothing as m,html as l}from"lit";import"@typo3/backend/live-search/element/result/item/action/action-container.js";var p=function(n,e,r,i){var s=arguments.length,t=s<3?e:i===null?i=Object.getOwnPropertyDescriptor(e,r):i,a;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(n,e,r,i);else for(var c=n.length-1;c>=0;c--)(a=n[c])&&(t=(s<3?a(t):s>3?a(e,r,t):a(e,r))||t);return s>3&&t&&Object.defineProperty(e,r,t),t};const b="typo3-backend-live-search-result-item-detail-container";let o=class extends h{constructor(){super(...arguments),this.resultItem=null}createRenderRoot(){return this}render(){if(this.resultItem===null)return m;const e=Object.entries(this.resultItem.properties??{});return l`<div class=livesearch-detail-preamble>${this.resultItem.thumbnailUrl?l`<div class=livesearch-detail-preamble-thumbnail><img src=${this.resultItem.thumbnailUrl} loading=lazy alt></div>`:l`<typo3-backend-icon identifier=${this.resultItem.icon.identifier} overlay=${this.resultItem.icon.overlay} size=large></typo3-backend-icon>`}<h3>${this.resultItem.itemTitle}</h3><p class=livesearch-detail-preamble-type>${this.resultItem.typeLabel}</p></div>${e.length>0?l`<dl class=livesearch-detail-properties>${e.map(([r,i])=>l`<dt>${r}</dt><dd>${i}</dd>`)}</dl>`:m}<typo3-backend-live-search-result-item-action-container .resultItem=${this.resultItem}></typo3-backend-live-search-result-item-action-container>`}};p([u({type:Object,attribute:!1})],o.prototype,"resultItem",void 0),o=p([d("typo3-backend-live-search-result-item-detail-container")],o);export{o as ResultDetailContainer,b as componentName};
