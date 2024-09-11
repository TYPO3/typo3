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
import { live } from 'lit/directives/live.js';

export const componentName = 'typo3-backend-settings-type-stringlist';

@customElement(componentName)
export class StringlistTypeElement extends BaseElement<string[]> {

  @property({ type: Array }) value: string[];

  protected updateValue(value: string, index: number) {
    const copy = [...this.value];
    copy[index] = value;
    this.value = copy;
  }

  protected addValue(index: number, value: string = '') {
    this.value = this.value.toSpliced(index + 1, 0, value);
  }

  protected removeValue(index: number) {
    this.value = this.value.toSpliced(index, 1);
  }

  protected renderItem(value: string, index: number): TemplateResult {
    return html`
      <tr>
        <td width="99%">
          <input
            id=${`${this.formid}${index > 0 ? '-' + index : ''}`}
            type="text"
            class="form-control"
            ?readonly=${this.readonly}
            .value=${live(value)}
            @change=${(e: InputEvent) => this.updateValue((e.target as HTMLInputElement).value, index)}
          />
        </td>
        <td>
          <div class="btn-group" role="group">
            <button class="btn btn-default" type="button" ?disabled=${this.readonly} @click=${() => this.addValue(index)}>
              <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
            </button>
            <button class="btn btn-default" type="button" ?disabled=${this.readonly} @click=${() => this.removeValue(index)}>
              <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
            </button>
          </div>
        </td>
      </tr>
    `;
  }

  protected render(): TemplateResult {
    const value = this.value || [];
    return html`
      <div class="form-control-wrap">
        <div class="table-fit">
          <table class="table table-hover">
            <tbody>
              ${value.map((v, i) => this.renderItem(v, i))}
            </tbody>
          </table>
        </div>
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-settings-type-stringlist': StringlistTypeElement;
  }
}
