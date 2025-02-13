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
import './item/action/action-container';
import { ResultItemInterface } from './item/item';

export const componentName = 'typo3-backend-live-search-result-item-detail-container';

@customElement('typo3-backend-live-search-result-item-detail-container')
export class ResultDetailContainer extends LitElement {
  @property({ type: Object, attribute: false }) resultItem: ResultItemInterface|null = null;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected override render(): TemplateResult | symbol {
    if (this.resultItem === null) {
      return nothing;
    }

    return html`
      <div class="livesearch-detail-preamble">
        <typo3-backend-icon identifier="${this.resultItem.icon.identifier}" overlay="${this.resultItem.icon.overlay}" size="large"></typo3-backend-icon>
        <h3>${this.resultItem.itemTitle}</h3>
        <p class="livesearch-detail-preamble-type">${this.resultItem.typeLabel}</p>
      </div>
      <typo3-backend-live-search-result-item-action-container .resultItem="${this.resultItem}"></typo3-backend-live-search-result-item-action-container>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-live-search-result-item-detail-container': ResultDetailContainer;
  }
}
