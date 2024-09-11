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

export const componentName = 'typo3-backend-settings-type-string';

@customElement(componentName)
export class StringTypeElement extends BaseElement<string> {

  @property({ type: String }) value: string;

  protected render(): TemplateResult {
    return html`
      <input
        type="text"
        id=${this.formid}
        class="form-control"
        ?readonly=${this.readonly}
        .value=${this.value}
        @change=${(e: InputEvent) => this.value = (e.target as HTMLInputElement).value}
      />
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-settings-type-string': StringTypeElement;
  }
}
