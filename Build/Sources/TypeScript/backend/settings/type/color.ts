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

import { html, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators';
import { BaseElement } from './base';
import '@typo3/backend/color-picker';
import RegularEvent from '@typo3/core/event/regular-event';

export const componentName = 'typo3-backend-settings-type-color';

@customElement(componentName)
export class ColorTypeElement extends BaseElement {

  @property({ type: String }) override value: string;

  protected override firstUpdated(): void {
    const inputElement = this.getInputElement();
    if (inputElement) {
      new RegularEvent('blur', (e: Event): void => {
        this.updateValue((e.target as HTMLInputElement).value);
      }).bindTo(inputElement);
    }
  }

  protected updateValue(value: string) {
    this.value = value;
  }

  protected override render(): TemplateResult {
    return html`
      <typo3-backend-color-picker>
        <input
          type="text"
          id=${this.formid}
          class="form-control"
          ?readonly=${this.readonly}
          .value=${this.value}
          @change=${(e: InputEvent) => this.updateValue((e.target as HTMLInputElement).value)}
        />
      </typo3-backend-color-picker>
    `;
  }

  protected getInputElement(): HTMLInputElement {
    return this.querySelector<HTMLInputElement>('input');
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-settings-type-color': ColorTypeElement;
  }
}
