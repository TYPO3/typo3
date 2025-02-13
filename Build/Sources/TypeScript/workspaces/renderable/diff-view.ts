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
import { repeat } from 'lit/directives/repeat';
import { unsafeHTML } from 'lit/directives/unsafe-html';

export type Diff = {
  field: string,
  label: string,
  content: string,
  html: string
};

@customElement('typo3-workspaces-diff-view')
export class DiffViewElement extends LitElement {
  @property({ type: Array })
  public diffs: Diff[] = [];

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <div class="diff">
        ${repeat(this.diffs, (diff) => diff.field, (diff) => this.renderDiffItem(diff))}
      </div>
    `;
  }

  protected renderDiffItem(diff: Diff) {
    return html`
      <div class="diff-item">
        <div class="diff-item-title">${diff.label}</div>
        <div class="diff-item-result">${unsafeHTML(diff.content)}</div>
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-workspaces-diff-view': DiffViewElement;
  }
}
