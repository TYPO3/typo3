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
import BrowserSession from '@typo3/backend/storage/browser-session';
import { ifDefined } from 'lit/directives/if-defined';
import type { InvokeOptionEventData } from '@typo3/backend/toolbar/live-search';

@customElement('typo3-backend-live-search-option-item')
export class SearchOptionItem extends LitElement {
  @property({ type: Boolean }) active: boolean = false;
  @property({ type: String }) optionId: string;
  @property({ type: String }) optionName: string;
  @property({ type: String }) optionLabel: string;

  private parentContainer: HTMLElement;

  public override connectedCallback(): void {
    this.parentContainer = this.closest('typo3-backend-live-search');

    super.connectedCallback();
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <div class="form-check">
        <input type="checkbox" class="form-check-input" name="${this.optionName}[]" value="${this.optionId}" id="${this.optionId}" checked=${ifDefined(this.active ? 'checked' : undefined)} @input="${this.handleInput}">
        <label class="form-check-label" for="${this.optionId}">
          ${this.optionLabel}
        </label>
      </div>
    `;
  }

  private getStorageKey(): string {
    return `livesearch-option-${this.optionName}-${this.optionId}`;
  }

  private handleInput() {
    this.active = !this.active;
    this.parentContainer.dispatchEvent(new CustomEvent<InvokeOptionEventData>('typo3:live-search:option-invoked', {
      detail: {
        active: this.active
      }
    }));

    BrowserSession.set(this.getStorageKey(), this.active ? '1' : '0');
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-live-search-option-item': SearchOptionItem;
  }
}
