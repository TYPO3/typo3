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

import { html, TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators';
import { BaseElement } from './base';

export const componentName = 'typo3-backend-settings-type-bool';

@customElement(componentName)
export class BoolTypeElement extends BaseElement<boolean> {

  @property({
    type: Boolean,
    converter: {
      toAttribute: (value: boolean): string => {
        return value ? '1' : '0';
      },
      fromAttribute: (value: string): boolean => {
        return value === '1' || value === 'true';
      }
    }
  }) override value: boolean;

  protected override render(): TemplateResult {
    return html`
      <div class="form-check form-check-type-toggle">
        <input
          type="checkbox"
          id=${this.formid}
          class="form-check-input"
          value="1"
          ?disabled=${this.readonly}
          .checked=${this.value}
          @change=${(e: InputEvent) => this.value = (e.target as HTMLInputElement).checked}
        />
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-settings-type-bool': BoolTypeElement;
  }
}
