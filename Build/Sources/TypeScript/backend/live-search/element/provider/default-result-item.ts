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
import { html, LitElement, type TemplateResult } from 'lit';
import '@typo3/backend/element/icon-element';

@customElement('typo3-backend-live-search-result-item-default')
export class DefaultProviderResultItem extends LitElement {
  @property({ type: Object, attribute: false }) icon: Record<string, string>;
  @property({ type: String, attribute: false }) itemTitle: string;
  @property({ type: String, attribute: false }) typeLabel: string;
  @property({ type: Object, attribute: false }) extraData: { [key: string]: any };

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <div class="livesearch-result-item-icon">
        <typo3-backend-icon title="${this.icon.title}" identifier="${this.icon.identifier}" overlay="${this.icon.overlay}" size="small"></typo3-backend-icon>
      </div>
      <div class="livesearch-result-item-title">
        ${this.itemTitle}${this.extraData.breadcrumb !== undefined ? html`<br><small>${this.extraData.breadcrumb}</small>` : ''}
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-live-search-result-item-default': DefaultProviderResultItem;
  }
}
