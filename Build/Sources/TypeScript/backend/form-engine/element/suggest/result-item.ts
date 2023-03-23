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
import '@typo3/backend/element/icon-element';

export interface ResultItemInterface {
  icon: Record<string, string>;
  uid: number;
  table: string;
  label: string;
  path: string;
}

@customElement('typo3-backend-formengine-suggest-result-item')
export class ResultItem extends LitElement {
  @property({ type: Object }) icon: Record<string, string>;
  @property({ type: Number }) uid: number;
  @property({ type: String }) table: string;
  @property({ type: String }) label: string;
  @property({ type: String }) path: string;

  public connectedCallback(): void {
    super.connectedCallback();

    this.addEventListener('blur', this.onBlur);
    this.addEventListener('click', this.onClick);
    this.addEventListener('keyup', this.onKeyUp);
  }

  public disconnectedCallback(): void {
    this.removeEventListener('blur', this.onBlur);
    this.removeEventListener('click', this.onClick);
    this.removeEventListener('keyup', this.onKeyUp);

    super.disconnectedCallback();
  }

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected render(): TemplateResult {
    return html`
      <div class="formengine-suggest-result-item-icon">
        <typo3-backend-icon title="${this.icon.title}" identifier="${this.icon.identifier}" overlay="${this.icon.overlay}" size="small"></typo3-backend-icon>
      </div>
      <div class="formengine-suggest-result-item-label">
        ${this.label} <small>[${this.uid}] ${this.path}</small>
      </div>
    `;
  }

  private onBlur(e: FocusEvent): void {
    let closeResultContainer = true;
    const relatedElement = e.relatedTarget as HTMLElement|null;
    const resultContainer = this.closest('typo3-backend-formengine-suggest-result-container') as HTMLElement;

    if (relatedElement?.tagName.toLowerCase() === 'typo3-backend-formengine-suggest-result-item') {
      closeResultContainer = false;
    }

    if (relatedElement?.matches('input[type="search"]') && resultContainer.contains(relatedElement)) {
      closeResultContainer = false;
    }

    resultContainer.hidden = closeResultContainer;
  }

  private onClick(e: PointerEvent): void {
    e.preventDefault();
    this.dispatchItemChosenEvent(e.currentTarget as Element);
  }

  private onKeyUp(e: KeyboardEvent): void {
    e.preventDefault();

    // Trigger item selection when pressing ENTER or SPACE
    if (['Enter', ' '].includes(e.key)) {
      this.dispatchItemChosenEvent(document.activeElement);
    }
  }

  private dispatchItemChosenEvent(selectedItem: Element): void {
    selectedItem.closest('typo3-backend-formengine-suggest-result-container').dispatchEvent(new CustomEvent('typo3:formengine:suggest-item-chosen', {
      detail: {
        element: selectedItem
      }
    }));
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-formengine-suggest-result-item': ResultItem;
  }
}
