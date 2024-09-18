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

import { customElement, property } from 'lit/decorators';
import { html, LitElement, nothing, TemplateResult } from 'lit';
import '@typo3/backend/element/icon-element';

export type Pagination = {
  itemsPerPage: number,
  currentPage: number,
  firstPage: number,
  lastPage: number,
  allPageNumbers: number[],
  previousPageNumber: number|null,
  nextPageNumber: number|null,
  hasMorePages: boolean,
  hasLessPages: boolean,
};

@customElement('typo3-backend-live-search-result-pagination')
export class ResultPagination extends LitElement {
  @property({ type: Object }) pagination: Pagination|null = null;

  protected createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected render(): TemplateResult | symbol {
    if (this.pagination === null || this.pagination.allPageNumbers.length <= 1) {
      return nothing;
    }

    return html`<nav>
      <ul class="pagination">
        <li class="page-item">
          <typo3-backend-live-search-result-page class="page-link ${!this.pagination.previousPageNumber || this.pagination.previousPageNumber < this.pagination.firstPage ? 'disabled' : ''}" page="${this.pagination.previousPageNumber}" perPage="${this.pagination.itemsPerPage}">
            <typo3-backend-icon identifier="actions-view-paging-previous" size="small"></typo3-backend-icon>
          </typo3-backend-live-search-result-page>
        </li>
        ${!this.pagination.allPageNumbers.includes(this.pagination.firstPage) ? html`
          <li class="page-item">
            <typo3-backend-live-search-result-page class="page-link" page="${this.pagination.firstPage}" perPage="${this.pagination.itemsPerPage}">
              ${this.pagination.firstPage}
            </typo3-backend-live-search-result-page>
          </li>` : nothing}
        ${this.pagination.hasLessPages ? html`<li class="page-item disabled"><span class="page-link disabled">&hellip;</span></li>` : nothing}
        ${this.pagination.allPageNumbers.map((page: number) => html`
          <li class="page-item">
            <typo3-backend-live-search-result-page page="${page}" perPage="${this.pagination.itemsPerPage}" class="page-link ${this.pagination.currentPage === page ? 'active' : ''}">${page}</typo3-backend-live-search-result-page>
          </li>
        `)}
        ${this.pagination.hasMorePages ? html`<li class="page-item"><span class="page-link disabled">&hellip;</span></li>` : nothing}
        ${!this.pagination.allPageNumbers.includes(this.pagination.lastPage) ? html`
          <li class="page-item">
            <typo3-backend-live-search-result-page class="page-link" page="${this.pagination.lastPage}" perPage="${this.pagination.itemsPerPage}">
              ${this.pagination.lastPage}
            </typo3-backend-live-search-result-page>
          </li>` : nothing}
        <li class="page-item">
          <typo3-backend-live-search-result-page class="page-link ${!this.pagination.nextPageNumber || this.pagination.nextPageNumber > this.pagination.lastPage ? 'disabled' : ''}" page="${this.pagination.nextPageNumber}" perPage="${this.pagination.itemsPerPage}">
            <typo3-backend-icon identifier="actions-view-paging-next" size="small"></typo3-backend-icon>
          </typo3-backend-live-search-result-page>
        </li>
      </ul>
    </nav>`;
  }
}

@customElement('typo3-backend-live-search-result-page')
export class ResultPaginationPage extends LitElement {
  @property({ type: Number }) page: number;
  @property({ type: Number }) perPage: number;

  public connectedCallback() {
    super.connectedCallback();

    this.addEventListener('click', this.dispatchPaginationEvent);
  }

  public disconnectedCallback() {
    this.removeEventListener('click', this.dispatchPaginationEvent);

    super.disconnectedCallback();
  }

  protected createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected render(): symbol {
    return nothing;
  }

  private dispatchPaginationEvent(): void {
    const liveSearchContainer = this.closest('typo3-backend-live-search');
    liveSearchContainer.dispatchEvent(new CustomEvent('livesearch:pagination-selected', {
      detail: {
        offset: (this.page - 1) * this.perPage,
      }
    }));
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-live-search-result-pagination': ResultPagination;
    'typo3-backend-live-search-result-page': ResultPaginationPage;
  }
}
