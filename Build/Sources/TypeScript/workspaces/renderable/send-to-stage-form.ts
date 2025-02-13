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
import { html, LitElement, nothing, TemplateResult } from 'lit';

export type SendToStageResultData = {
  sendMailTo?: SendToStageRecipient[],
  additional?: {
    type: string,
    value: string
  },
  comments: {
    type: string,
    value: string
  }
};

export type SendToStageRecipient = {
  name: string,
  value: string,
  checked: boolean,
  disabled: boolean,
  label: string
};

@customElement('typo3-workspaces-send-to-stage-form')
export class SendToStageFormElement extends LitElement {
  @property({ type: Object })
  public data: SendToStageResultData | null = null;

  @property({ type: Object })
  public TYPO3lang: typeof TYPO3.lang | null = null;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <form>
        ${this.data.sendMailTo !== undefined && this.data.sendMailTo.length > 0 ? html`
          <label class="form-label">${this.TYPO3lang['window.sendToNextStageWindow.itemsWillBeSentTo']}</label>
          ${this.renderRecipientCheckboxes()}
        ` : nothing}
        ${this.data.additional !== undefined ? html`
          <div class="form-group">
            <label for="additional" class="form-label">
              ${this.TYPO3lang['window.sendToNextStageWindow.additionalRecipients']}
            </label>
            <textarea class="form-control" name="additional" id="additional">${this.data.additional.value}</textarea>
            <div class="form-text">
              ${this.TYPO3lang['window.sendToNextStageWindow.additionalRecipients.hint']}
            </div>
          </div>
        ` : nothing}
        <div class="form-group">
          <label for="comments" class="form-label">
            ${this.TYPO3lang['window.sendToNextStageWindow.comments']}
          </label>
          <textarea class="form-control" name="comments" id="comments">${this.data.comments.value}</textarea>
        </div>
      </form>
    `;
  }

  private renderRecipientCheckboxes(): TemplateResult[] {
    const renderResult: TemplateResult[] = [];

    this.data.sendMailTo?.forEach((recipient: SendToStageRecipient) => {
      renderResult.push(html`
        <div class="form-check">
          <input
            type="checkbox"
            name="recipients"
            class="form-check-input t3js-workspace-recipient"
            id=${recipient.name}
            value=${recipient.value}
            ?checked=${recipient.checked}
            ?disabled=${recipient.disabled}
            />
          <label class="form-check-label" for=${recipient.name}>
            ${recipient.label}
          </label>
        </div>
      `);
    });

    return renderResult;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-workspaces-send-to-stage-form': SendToStageFormElement;
  }
}
