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
import{property as u,customElement as m}from"lit/decorators.js";import{LitElement as d,html as g}from"lit";import{range as b}from"lit/directives/range.js";import{map as f}from"lit/directives/map.js";import{classMap as l}from"lit/directives/class-map.js";var c=function(i,t,n,a){var o=arguments.length,e=o<3?t:a===null?a=Object.getOwnPropertyDescriptor(t,n):a,r;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(i,t,n,a);else for(var s=i.length-1;s>=0;s--)(r=i[s])&&(e=(o<3?r(e):o>3?r(t,n,e):r(t,n))||e);return o>3&&e&&Object.defineProperty(t,n,e),e};let p=class extends d{constructor(){super(...arguments),this.paging=null}createRenderRoot(){return this}render(){return g`<ul class=pagination><li class=${l({"page-item":!0,disabled:this.paging.currentPage===1})}><button type=button class=page-link data-action=previous ?disabled=${this.paging.currentPage===1}><typo3-backend-icon identifier=actions-view-paging-previous size=small></typo3-backend-icon></button></li>${f(b(1,this.paging.totalPages+1),t=>g`<li class=${l({"page-item":!0,active:this.paging.currentPage===t})}><button type=button class=page-link data-action=page data-page=${t}><span>${t}</span></button></li>`)}<li class=${l({"page-item":!0,disabled:this.paging.currentPage===this.paging.totalPages})}><button type=button class=page-link data-action=next ?disabled=${this.paging.currentPage===this.paging.totalPages}><typo3-backend-icon identifier=actions-view-paging-next size=small></typo3-backend-icon></button></li></ul>`}};c([u({type:Object})],p.prototype,"paging",void 0),p=c([m("typo3-backend-pagination")],p);export{p as PaginationElement};
