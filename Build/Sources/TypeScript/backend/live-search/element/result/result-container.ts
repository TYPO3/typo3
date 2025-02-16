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

import LiveSearchConfigurator from '@typo3/backend/live-search/live-search-configurator';
import Viewport from '@typo3/backend/viewport';
import { customElement, property, query } from 'lit/decorators';
import { html, LitElement, nothing, type TemplateResult } from 'lit';
import { lll } from '@typo3/core/lit-helper';
import { type ItemContainer } from './item/item-container';
import { type ResultDetailContainer } from './result-detail-container';
import type { ResultItemActionInterface, ResultItemInterface } from './item/item';
import type { ChooseItemEventData } from '@typo3/backend/toolbar/live-search';

export interface InvokeActionEventData {
  resultItem: ResultItemInterface,
  action: ResultItemActionInterface|null
}

export interface RequestActionsEventData {
  resultItem: ResultItemInterface,
}

export const componentName = 'typo3-backend-live-search-result-container';

@customElement('typo3-backend-live-search-result-container')
export class ResultContainer extends LitElement {
  @property({ type: Object }) results: ResultItemInterface[] | null = null;
  @property({ type: Boolean, attribute: false }) loading: boolean = false;

  @query('typo3-backend-live-search-result-item-container') itemContainer: ItemContainer;
  @query('typo3-backend-live-search-result-item-detail-container') resultDetailContainer: ResultDetailContainer;

  public override connectedCallback(): void {
    super.connectedCallback();

    this.addEventListener('livesearch:request-actions', this.onActionsRequested);
    this.addEventListener('livesearch:invoke-action', this.onActionInvoked);
  }

  public override disconnectedCallback(): void {
    this.removeEventListener('livesearch:request-actions', this.onActionsRequested);
    this.removeEventListener('livesearch:invoke-action', this.onActionInvoked);

    super.disconnectedCallback();
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected override render(): TemplateResult | symbol {
    if (this.loading) {
      return html`<div class="d-flex flex-fill align-items-center justify-content-center"><typo3-backend-spinner size="large"></typo3-backend-spinner></div>`;
    }

    if (this.results === null) {
      return nothing;
    }

    if (this.results.length === 0) {
      return html`<div class="alert alert-info">${lll('liveSearch_listEmptyText')}</div>`;
    }

    return html`
      <typo3-backend-live-search-result-item-container .results="${this.results}"></typo3-backend-live-search-result-item-container>
      <typo3-backend-live-search-result-item-detail-container></typo3-backend-live-search-result-item-detail-container>
    `;
  }

  private onActionsRequested(e: CustomEvent<RequestActionsEventData>): void {
    this.resultDetailContainer.resultItem = e.detail.resultItem;
  }

  private onActionInvoked(e: CustomEvent<InvokeActionEventData>): void {
    const invokeHandlers = LiveSearchConfigurator.getInvokeHandlers();
    const resultItem = e.detail.resultItem;
    const action = e.detail.action;

    if (action === undefined) {
      return;
    }

    if (typeof invokeHandlers[resultItem.provider + '_' + action.identifier] === 'function') {
      invokeHandlers[resultItem.provider + '_' + action.identifier](resultItem, action);
    } else {
      // Default handler to open the URL
      Viewport.ContentContainer.setUrl(action.url);
    }
    this.dispatchEvent(new CustomEvent<ChooseItemEventData>('live-search:item-chosen', {
      detail: { resultItem }
    }));
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-live-search-result-container': ResultContainer;
  }
}
