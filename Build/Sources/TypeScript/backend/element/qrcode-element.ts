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

import { customElement, property, state } from 'lit/decorators.js';
import { html, LitElement, nothing, type TemplateResult } from 'lit';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import '@typo3/backend/element/spinner-element';
import '@typo3/backend/element/icon-element';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { unsafeHTML } from 'lit/directives/unsafe-html';

enum QrCodeSize {
  small = 64,
  medium = 128,
  large = 256,
  mega = 512,
}

/**
 * Module: @typo3/backend/element/qrcode-element
 *
 * @example
 * <typo3-qrcode
 *   content="https://example.com/"
 *   size="large"
 *   show-download="true">
 * </typo3-qrcode>
 *
 * @internal this is subject to change
 */
@customElement('typo3-qrcode')
export class QrCodeElement extends LitElement {
  @property({ type: String, reflect: true }) content: string = '';
  @property({ type: Boolean, reflect: true, attribute: 'show-download' }) showDownload: boolean = false;
  @property({ type: String, reflect: true }) size: QrCodeSize = QrCodeSize.small;

  @state() private qrcodePreview: TemplateResult = html`
    <typo3-backend-spinner size="large"></typo3-backend-spinner>`;

  public override connectedCallback() {
    super.connectedCallback();
    this.loadQrCode();
  }

  protected override render(): TemplateResult | symbol {
    return html`
      <div class="preview">${this.qrcodePreview}</div>
      ${this.getControls()}
    `;
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  private getControls(): TemplateResult {
    if (!this.showDownload) {
      return html`${nothing}`;
    }

    const pngLabel = TYPO3.lang['qrcode.format.png'] || 'PNG';
    const svgLabel = TYPO3.lang['qrcode.format.svg'] || 'SVG';
    const formatLabel = TYPO3.lang['qrcode.format'] || 'Format';
    const sizeLabel = TYPO3.lang['qrcode.size'] || 'Size';
    const downloadLabel = TYPO3.lang['qrcode.download'] || 'Download';

    return html`
      <form name="qrcode-download" method="POST" action="${TYPO3.settings.ajaxUrls.qrcode_download}">
        <div class="form-row">
          <div class="form-group">
            <input name="content" type="hidden" value="${this.content}">
            <label class="form-label">${formatLabel}</label>
            <select name="format" class="form-select">
              <option value="svg">${svgLabel}</option>
              <option value="png">${pngLabel}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">${sizeLabel}</label>
            <select name="size" class="form-select">
              ${this.getSizeOptions()}
            </select>
          </div>
          <div class="form-group">
            <button type="submit" class="btn btn-default">${downloadLabel}</button>
          </div>
        </div>
      </form>`;
  }

  private getSizeOptions() {
    return Object.entries(QrCodeSize)
      .filter(([, value]) => typeof value === 'number')
      .map(([name, size]) => html`
        <option value="${name}">${size}x${size}</option>
      `);
  }

  private async loadQrCode(): Promise<void> {
    // Return a 404 image to indicate that an empty content can't be rendered as QR Code
    if (this.content === '') {
      this.qrcodePreview = html`<typo3-backend-icon identifier="default-not-found" size="small"></typo3-backend-icon>`;
      return;
    }

    await new AjaxRequest(TYPO3.settings.ajaxUrls.qrcode_generator).withQueryArguments({
      content: this.content,
      size: this.size
    }).get({ cache: 'no-cache' }).then(async (response: AjaxResponse): Promise<void> => {
      this.qrcodePreview = html`${unsafeHTML(await response.resolve())}`;
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-qrcode': QrCodeElement;
  }
}
