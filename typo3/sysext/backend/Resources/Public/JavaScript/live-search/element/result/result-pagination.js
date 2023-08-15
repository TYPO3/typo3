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
var __decorate=function(e,a,t,i){var n,s=arguments.length,l=s<3?a:null===i?i=Object.getOwnPropertyDescriptor(a,t):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(e,a,t,i);else for(var o=e.length-1;o>=0;o--)(n=e[o])&&(l=(s<3?n(l):s>3?n(a,t,l):n(a,t))||l);return s>3&&l&&Object.defineProperty(a,t,l),l};import{customElement,property}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";import"@typo3/backend/element/icon-element.js";let ResultPagination=class extends LitElement{constructor(){super(...arguments),this.pagination=null}createRenderRoot(){return this}render(){return null===this.pagination||this.pagination.allPageNumbers.length<=1?nothing:html`<nav>
      <ul class="pagination pagination-sm">
        <li class="page-item">
          <typo3-backend-live-search-result-page class="page-link ${!this.pagination.previousPageNumber||this.pagination.previousPageNumber<this.pagination.firstPage?"disabled":""}" page="${this.pagination.previousPageNumber}" perPage="${this.pagination.itemsPerPage}">
            <typo3-backend-icon identifier="actions-arrow-left-alt" size="small"></typo3-backend-icon>
          </typo3-backend-live-search-result-page>
        </li>
        ${this.pagination.allPageNumbers.includes(this.pagination.firstPage)?nothing:html`
          <li class="page-item">
            <typo3-backend-live-search-result-page class="page-link" page="${this.pagination.firstPage}" perPage="${this.pagination.itemsPerPage}">
              ${this.pagination.firstPage}
            </typo3-backend-live-search-result-page>
          </li>`}
        ${this.pagination.hasLessPages?html`<li class="page-item disabled"><span class="page-link disabled">&hellip;</span></li>`:nothing}
        ${this.pagination.allPageNumbers.map((e=>html`
          <li class="page-item">
            <typo3-backend-live-search-result-page page="${e}" perPage="${this.pagination.itemsPerPage}" class="page-link ${this.pagination.currentPage===e?"active":""}">${e}</typo3-backend-live-search-result-page>
          </li>
        `))}
        ${this.pagination.hasMorePages?html`<li class="page-item"><span class="page-link disabled">&hellip;</span></li>`:nothing}
        ${this.pagination.allPageNumbers.includes(this.pagination.lastPage)?nothing:html`
          <li class="page-item">
            <typo3-backend-live-search-result-page class="page-link" page="${this.pagination.lastPage}" perPage="${this.pagination.itemsPerPage}">
              ${this.pagination.lastPage}
            </typo3-backend-live-search-result-page>
          </li>`}
        <li class="page-item">
          <typo3-backend-live-search-result-page class="page-link ${!this.pagination.nextPageNumber||this.pagination.nextPageNumber>this.pagination.lastPage?"disabled":""}" page="${this.pagination.nextPageNumber}" perPage="${this.pagination.itemsPerPage}">
            <typo3-backend-icon identifier="actions-arrow-right-alt" size="small"></typo3-backend-icon>
          </typo3-backend-live-search-result-page>
        </li>
      </ul>
    </nav>`}};__decorate([property({type:Object})],ResultPagination.prototype,"pagination",void 0),ResultPagination=__decorate([customElement("typo3-backend-live-search-result-pagination")],ResultPagination);export{ResultPagination};let ResultPaginationPage=class extends LitElement{connectedCallback(){super.connectedCallback(),this.addEventListener("click",this.dispatchPaginationEvent)}disconnectedCallback(){this.removeEventListener("click",this.dispatchPaginationEvent),super.disconnectedCallback()}createRenderRoot(){return this}render(){return nothing}dispatchPaginationEvent(){this.closest("typo3-backend-live-search").dispatchEvent(new CustomEvent("livesearch:pagination-selected",{detail:{offset:(this.page-1)*this.perPage}}))}};__decorate([property({type:Number})],ResultPaginationPage.prototype,"page",void 0),__decorate([property({type:Number})],ResultPaginationPage.prototype,"perPage",void 0),ResultPaginationPage=__decorate([customElement("typo3-backend-live-search-result-page")],ResultPaginationPage);export{ResultPaginationPage};