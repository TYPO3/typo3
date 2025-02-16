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

export interface ResultItemInterface {
  provider: string;
  actions: ResultItemActionInterface[];
  icon: Record<string, string>;
  itemTitle: string;
  typeLabel: string;
  extraData: { [key: string]: any }
}

export interface ResultItemActionInterface {
  identifier: string;
  icon: Record<string, string>;
  label: string;
  url: string;
}

@customElement('typo3-backend-live-search-result-item')
export class Item extends LitElement {
  @property({ type: Object, attribute: false }) resultItem: ResultItemInterface;

  public override connectedCallback(): void {
    super.connectedCallback();

    if (!this.hasAttribute('tabindex')) {
      this.setAttribute('tabindex', '0');
    }

    this.addEventListener('focus', this.onFocus);
  }

  public override disconnectedCallback(): void {
    this.removeEventListener('focus', this.onFocus);

    super.disconnectedCallback();
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected override render(): TemplateResult {
    return html`<div class="livesearch-expand-action" @click="${(e: Event): void => { e.stopPropagation(); this.focus(); }}"><typo3-backend-icon identifier="actions-chevron-right" size="small"></typo3-backend-icon></div>`;
  }

  private onFocus(e: Event) {
    const target = e.target as HTMLElement;
    target.parentElement.querySelector('.active')?.classList.remove('active');
    target.classList.add('active');
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-live-search-result-item': Item;
  }
}
