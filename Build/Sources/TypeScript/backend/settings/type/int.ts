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

export const componentName = 'typo3-backend-settings-type-int';

@customElement(componentName)
export class IntTypeElement extends BaseElement<
  number,
  {
    min?: number,
    max?: number,
    step?: number,
  }
> {

  @property({ type: Number }) override value: number;

  protected handleChange(e: InputEvent): void {
    const input = e.target as HTMLInputElement|HTMLSelectElement;
    if (input.reportValidity()) {
      if (input instanceof HTMLInputElement) {
        this.value = input.valueAsNumber;
      } else {
        this.value = parseInt(input.value, 10);
      }
    }
  }

  protected renderEnum(): TemplateResult {
    return html`
      <select
        id=${this.formid}
        class="form-select"
        ?readonly=${this.readonly}
        .value=${live(this.value)}
        @change=${this.handleChange}
      >
        ${Object.entries(this.enum).map(([value, label]) => html`
          <option ?selected=${this.value.toString() === value} value=${value}>${label}${this.debug ? html` [${value}]` : nothing}</option>
        `)}
      </select>
    `;
  }

  protected override render(): TemplateResult {
    if (typeof this.enum === 'object') {
      return this.renderEnum();
    }
    return html`
      <input
        type="number"
        id=${this.formid}
        class="form-control"
        ?readonly=${this.readonly}
        .value=${live(String(this.value))}
        required
        min=${this.options.min ?? nothing}
        max=${this.options.max ?? nothing}
        step=${this.options.step ?? nothing}
        @change=${this.handleChange}
      />
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-settings-type-int': IntTypeElement;
  }
}
