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
 * Module: @typo3/install/module/info-box
 */
@customElement('typo3-install-infobox')
export class InfoBox extends LitElement {
  @property({ type: Number }) severity: number;
  @property({ type: String }) subject: string;
  @property({ type: String }) content: string;

  public static create(severity: number, subject: string, content: string = ''): InfoBox {
    const isInIframe = window.location !== window.parent.location;
    const doc = isInIframe ? window.parent.document : document;
    const infobox = doc.createElement('typo3-install-infobox');
    infobox.severity = severity;
    infobox.subject = subject;

    if (content) {
      infobox.content = content;
    }

    return infobox;
  }

  protected createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  protected render(): TemplateResult {
    let content: TemplateResult | symbol = nothing;
    if (this.content) {
      content = html`<div class="callout-body">${this.content}</div>`;
    }
    return html`
      <div class="t3js-infobox callout callout-sm callout-${Severity.getCssClass(this.severity)}">
        <div class="callout-content">
          <div class="callout-title">${this.subject}</div>
          ${content}
        </div>
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-install-infobox': InfoBox;
  }
}
