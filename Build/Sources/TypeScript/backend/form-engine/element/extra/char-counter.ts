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

import { customElement, property, state } from 'lit/decorators';
import { html, LitElement, TemplateResult } from 'lit';
import { lll } from '@typo3/core/lit-helper';

@customElement('typo3-backend-formengine-char-counter')
export class CharCounter extends LitElement {
  @property() target: string;
  @state() private remainingCharacters: number = 0;

  private targetElement: HTMLInputElement|HTMLTextAreaElement = null;
  private readonly threshold = 15;

  public connectedCallback(): void {
    super.connectedCallback();
    this.registerCallbacks();

    this.hidden = true;
  }

  public disconnectedCallback() {
    super.disconnectedCallback();
    this.removeCallbacks();
  }

  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected updated(changedProperties: Map<string, any>) {
    if (changedProperties.has('target')) {
      this.removeCallbacks();
      this.targetElement = document.querySelector(this.target);
      this.registerCallbacks();
    }
  }

  protected render(): TemplateResult {
    return html`
      <span class="form-hint form-hint--${this.determineCounterClass()}">
        ${lll(('FormEngine.remainingCharacters')).replace('{0}', this.remainingCharacters.toString(10))}
      </span>
    `;
  }

  private registerCallbacks(): void {
    if (this.targetElement === null) {
      return;
    }
    this.targetElement.addEventListener('input', this.onInput);
    this.targetElement.addEventListener('focus', this.onFocus);
    this.targetElement.addEventListener('blur', this.onBlur);
  }

  private removeCallbacks(): void {
    if (this.targetElement === null) {
      return;
    }
    this.targetElement.removeEventListener('input', this.onInput);
    this.targetElement.removeEventListener('focus', this.onFocus);
    this.targetElement.removeEventListener('blur', this.onBlur);
  }

  private readonly onInput = (e: InputEvent): void => {
    this.determineRemainingCharacters(e.target as HTMLInputElement|HTMLTextAreaElement);
  }

  private readonly onFocus = (e: FocusEvent): void => {
    this.determineRemainingCharacters(e.target as HTMLInputElement|HTMLTextAreaElement);
    this.hidden = false;
  }

  private readonly onBlur = (): void => {
    this.hidden = true;
  }

  private determineRemainingCharacters(field: HTMLInputElement|HTMLTextAreaElement): void {
    const fieldText = field.value;
    const currentFieldLength = fieldText.length;
    const numberOfLineBreaks = (fieldText.match(/\n/g) || []).length;

    this.remainingCharacters = this.targetElement.maxLength - currentFieldLength - numberOfLineBreaks;
  }

  private determineCounterClass(): string {
    if (this.remainingCharacters < this.threshold) {
      return 'danger';
    }
    if (this.remainingCharacters < this.threshold * 2) {
      return 'warning';
    }
    return 'info';
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-formengine-char-counter': CharCounter;
  }
}
