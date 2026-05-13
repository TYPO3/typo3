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
import{property as l,customElement as m}from"lit/decorators.js";import{LitElement as v,html as p,nothing as u}from"lit";import"@typo3/backend/element/icon-element.js";import f from"~labels/core.misc";var r=function(o,i,a,s){var n=arguments.length,e=n<3?i:s===null?s=Object.getOwnPropertyDescriptor(i,a):s,c;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(o,i,a,s);else for(var d=o.length-1;d>=0;d--)(c=o[d])&&(e=(n<3?c(e):n>3?c(i,a,e):c(i,a))||e);return n>3&&e&&Object.defineProperty(i,a,e),e};let t=class extends v{constructor(){super(...arguments),this.language=null}createRenderRoot(){return this}render(){return p`<div class=livesearch-result-item-icon><typo3-backend-icon title=${this.icon.title} identifier=${this.icon.identifier} overlay=${this.icon.overlay} size=small></typo3-backend-icon>${this.language?p`<typo3-backend-icon title=${this.language.title} identifier=${this.language.iconIdentifier} size=small></typo3-backend-icon>`:u}</div><div class=livesearch-result-item-summary><div class=livesearch-result-item-title><div class=livesearch-result-item-title-contentlabel>${this.itemTitle}</div>${this.extraData.inWorkspace?p`<div class=livesearch-result-item-title-indicator><typo3-backend-icon title=${f.get("liveSearch.versionizedRecord")} identifier=actions-dot size=small class=text-warning></typo3-backend-icon></div>`:u}</div><small>${this.extraData.breadcrumb}</small></div>`}};r([l({type:Object,attribute:!1})],t.prototype,"icon",void 0),r([l({type:Object,attribute:!1})],t.prototype,"language",void 0),r([l({type:String,attribute:!1})],t.prototype,"itemTitle",void 0),r([l({type:String,attribute:!1})],t.prototype,"typeLabel",void 0),r([l({type:Object,attribute:!1})],t.prototype,"extraData",void 0),t=r([m("typo3-backend-live-search-result-item-page-provider")],t);var b=t;export{b as default};
