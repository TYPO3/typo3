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
import { html, LitElement, TemplateResult } from 'lit';
import { range } from 'lit/directives/range';
import { map } from 'lit/directives/map';
import { classMap } from 'lit/directives/class-map';

@customElement('typo3-backend-pagination')
export class PaginationElement extends LitElement {
  @property({ type: Object })
  public paging: Record<string, number> | null = null;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <ul class="pagination">
        <li class=${classMap({ 'page-item': true, disabled: this.paging.currentPage === 1 })}>
          <button type="button" class="page-link" data-action="previous" ?disabled=${this.paging.currentPage === 1}>
            <typo3-backend-icon identifier="actions-view-paging-previous" size="small"></typo3-backend-icon>
          </button>
        </li>
        ${map(range(1, this.paging.totalPages + 1), (page) => html`
          <li class=${classMap({ 'page-item': true, active: this.paging.currentPage === page })}>
            <button type="button" class="page-link" data-action="page" data-page=${page}>
              <span>${page}</span>
            </button>
          </li>
        `)}
        <li class=${classMap({ 'page-item': true, disabled: this.paging.currentPage === this.paging.totalPages })}>
          <button type="button" class="page-link" data-action="next" ?disabled=${this.paging.currentPage === this.paging.totalPages}>
            <typo3-backend-icon identifier="actions-view-paging-next" size="small"></typo3-backend-icon>
          </button>
        </li>
      </ul>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-pagination': PaginationElement;
  }
}
