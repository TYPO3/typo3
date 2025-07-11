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
import { html, LitElement, nothing, type TemplateResult } from 'lit';
import '@typo3/backend/element/icon-element';
import { lll } from '@typo3/core/lit-helper';

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
      <div class="livesearch-result-item-summary">
        <div class="livesearch-result-item-title">
          <div class="livesearch-result-item-title-contentlabel">${this.itemTitle}</div>
          ${this.extraData.inWorkspace ? html`<div class="livesearch-result-item-title-indicator"><typo3-backend-icon title="${lll('liveSearch.versionizedRecord')}" identifier="actions-dot" size="small" class="text-warning"></typo3-backend-icon></div>` : nothing}
        </div>
        ${this.extraData.breadcrumb !== undefined ? html`<small>${this.extraData.breadcrumb}</small>` : nothing}
      </div>

    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-live-search-result-item-default': DefaultProviderResultItem;
  }
}
