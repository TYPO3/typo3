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
import {customElement, property, query} from 'lit/decorators';
import {html, LitElement, TemplateResult} from 'lit';
import {lll} from '@typo3/core/lit-helper';
import './item/item-container';
import './result-detail-container';
import {ResultItemInterface} from './item/item';
import {ItemContainer} from './item/item-container';
import {ResultDetailContainer} from './result-detail-container';

export const componentName = 'typo3-backend-live-search-result-container';

@customElement(componentName)
export class ResultContainer extends LitElement {
  @property({type: Object}) results: ResultItemInterface[]|null = null;
  @property({type: Boolean, attribute: false}) loading: boolean = false;

  @query('typo3-backend-live-search-result-item-container') itemContainer: ItemContainer;
  @query('typo3-backend-live-search-result-item-detail-container') resultDetailContainer: ResultDetailContainer;

  public connectedCallback() {
    super.connectedCallback();

    this.addEventListener('livesearch:request-actions', (e: CustomEvent): void => {
      this.resultDetailContainer.resultItem = e.detail.resultItem;
    })
    this.addEventListener('livesearch:invoke-action', (e: CustomEvent): void => {
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
        TYPO3.Backend.ContentContainer.setUrl(action.url);
      }
      this.dispatchEvent(new CustomEvent('live-search:item-chosen', {
        detail: { resultItem }
      }));
    })
  }

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected render(): TemplateResult {
    if (this.loading) {
      return html`<div class="d-flex flex-fill justify-content-center mt-2"><typo3-backend-spinner size="large"></typo3-backend-spinner></div>`;
    }

    if (this.results === null) {
      return html``;
    }

    if (this.results.length === 0) {
      return html`<div class="alert alert-info">${lll('liveSearch_listEmptyText')}</div>`;
    }

    return html`
      <typo3-backend-live-search-result-item-container .results="${this.results}"></typo3-backend-live-search-result-item-container>
      <typo3-backend-live-search-result-item-detail-container></typo3-backend-live-search-result-item-detail-container>
    `;
  }
}
