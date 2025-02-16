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
import { html, LitElement, nothing, type TemplateResult } from 'lit';
import { repeat } from 'lit/directives/repeat';
import { unsafeHTML } from 'lit/directives/unsafe-html';
import type { Diff } from './diff-view';

type History = {
  differences: string | Diff[];
  datetime: string;
  user: string;
  user_avatar: string;
}

@customElement('typo3-workspaces-history-view')
export class HistoryViewElement extends LitElement {
  @property({ type: Array })
  public historyItems: History[] = [];

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <div>
        ${repeat(this.historyItems, (historyItem) => historyItem.datetime, (historyItem) => this.renderHistoryItem(historyItem))}
      </div>
    `;
  }

  protected renderHistoryItem(historyItem: History): TemplateResult|typeof nothing {
    if (typeof historyItem.differences === 'object' && historyItem.differences.length === 0) {
      return nothing;
    }

    return html`
      <div class="media">
        <div class="media-left text-center">
          <div>
            ${unsafeHTML(historyItem.user_avatar)}
          </div>
          ${historyItem.user}
        </div>
        <div class="media-body">
          <div class="panel panel-default">
            ${typeof historyItem.differences === 'object' ? html`
          <div>
            <div class="diff">
              ${repeat(historyItem.differences, (diff) => diff, (diff) => html`
              <div class="diff-item">
                <div class="diff-item-title">
                  ${diff.label}
                </div>
                <div class="diff-item-result diff-item-result-inline">
                  ${unsafeHTML(diff.html)}
                </div>
              </div>
            `)}
            </div>
          </div>
        ` : html`
          <div class="panel-body">
            ${historyItem.differences}
          </div>
        `}
        <div class="panel-footer">
          <span class="badge badge-info">
            ${historyItem.datetime}
          </span>
            </div>
          </div>
        </div>
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-workspaces-history-view': HistoryViewElement;
  }
}

