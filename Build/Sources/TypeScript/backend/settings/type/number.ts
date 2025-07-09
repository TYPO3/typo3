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

import { html, nothing, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators';
import { live } from 'lit/directives/live';
import { BaseElement } from './base';

export const componentName = 'typo3-backend-settings-type-number';

@customElement(componentName)
export class NumberTypeElement extends BaseElement<
  number,
  {
    min?: number,
    max?: number,
    step?: number,
  }
> {
  @property({ type: Number }) override value: number;

  protected handleChange(e: InputEvent): void {
    const input = e.target as HTMLInputElement;
    if (input.reportValidity()) {
      this.value = input.valueAsNumber;
    }
  }

  protected override render(): TemplateResult {
    return html`
      <input
        type="number"
        id=${this.formid}
        class="form-control"
        ?readonly=${this.readonly}
        .value=${live(this.value)}
        required
        min=${this.options.min ?? nothing}
        max=${this.options.max ?? nothing}
        step=${this.options.step ?? '0.01'}
        @change=${this.handleChange}
      />
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-settings-type-number': NumberTypeElement;
  }
}
