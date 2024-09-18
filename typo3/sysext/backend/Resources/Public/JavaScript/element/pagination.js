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
var __decorate=function(t,e,a,i){var n,o=arguments.length,r=o<3?e:null===i?i=Object.getOwnPropertyDescriptor(e,a):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)r=Reflect.decorate(t,e,a,i);else for(var s=t.length-1;s>=0;s--)(n=t[s])&&(r=(o<3?n(r):o>3?n(e,a,r):n(e,a))||r);return o>3&&r&&Object.defineProperty(e,a,r),r};import{customElement,property}from"lit/decorators.js";import{html,LitElement}from"lit";import{range}from"lit/directives/range.js";import{map}from"lit/directives/map.js";import{classMap}from"lit/directives/class-map.js";let PaginationElement=class extends LitElement{constructor(){super(...arguments),this.paging=null}createRenderRoot(){return this}render(){return html`
      <ul class="pagination">
        <li class=${classMap({"page-item":!0,disabled:1===this.paging.currentPage})}>
          <button type="button" class="page-link" data-action="previous" ?disabled=${1===this.paging.currentPage}>
            <typo3-backend-icon identifier="actions-view-paging-previous" size="small"></typo3-backend-icon>
          </button>
        </li>
        ${map(range(1,this.paging.totalPages+1),(t=>html`
          <li class=${classMap({"page-item":!0,active:this.paging.currentPage===t})}>
            <button type="button" class="page-link" data-action="page" data-page=${t}>
              <span>${t}</span>
            </button>
          </li>
        `))}
        <li class=${classMap({"page-item":!0,disabled:this.paging.currentPage===this.paging.totalPages})}>
          <button type="button" class="page-link" data-action="next" ?disabled=${this.paging.currentPage===this.paging.totalPages}>
            <typo3-backend-icon identifier="aactions-view-paging-next" size="small"></typo3-backend-icon>
          </button>
        </li>
      </ul>
    `}};__decorate([property({type:Object})],PaginationElement.prototype,"paging",void 0),PaginationElement=__decorate([customElement("typo3-backend-pagination")],PaginationElement);export{PaginationElement};