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

import Severity from './severity';
import { customElement, property } from 'lit/decorators';
import { html, LitElement, nothing, TemplateResult } from 'lit';

/**
 * Module: @typo3/install/module/flash-message
 */
@customElement('typo3-install-flashmessage')
export class FlashMessage extends LitElement {
  @property({ type: Number }) severity: number;
  @property({ type: String }) subject: string;
  @property({ type: String }) content: string;

  public static create(severity: number, subject: string, content: string = ''): FlashMessage {
    const message = document.createElement('typo3-install-flashmessage');
    message.severity = severity;
    message.subject = subject;

    if (content) {
      message.content = content;
    }

    return message;
  }

  protected createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  protected render(): TemplateResult {
    let content: TemplateResult | symbol = nothing;
    if (this.content) {
      content = html`<p class="messageText">${this.content}</p>`;
    }
    return html`
      <div class="t3js-message typo3-message alert alert-${Severity.getCssClass(this.severity)}">
        <h4>${this.subject}</h4>
        ${content}
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-install-flashmessage': FlashMessage;
  }
}
