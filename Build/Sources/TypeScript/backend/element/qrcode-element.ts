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
import '@typo3/backend/copy-to-clipboard';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { unsafeHTML } from 'lit/directives/unsafe-html.js';

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
 *   show-download
 *   show-url>
 * </typo3-qrcode>
 *
 * @internal this is subject to change
 */
@customElement('typo3-qrcode')
export class QrCodeElement extends LitElement {
  @property({ type: String, reflect: true }) content: string = '';
  @property({ type: Boolean, reflect: true, attribute: 'show-download' }) showDownload: boolean = false;
  @property({ type: Boolean, reflect: true, attribute: 'show-url' }) showUrl: boolean = false;
  @property({ type: String, reflect: true }) size: QrCodeSize = QrCodeSize.small;

  @state() private qrcodePreview: TemplateResult = html`
    <typo3-backend-spinner size="large"></typo3-backend-spinner>`;
  @state() private urlSectionVisible: boolean = false;

  public constructor() {
    super();
    document.addEventListener('copy-to-clipboard-success', this.showCopySuccess.bind(this));
  }

  public override connectedCallback() {
    super.connectedCallback();
    this.loadQrCode();
  }

  protected override render(): TemplateResult | symbol {
    return html`
      <div class="preview">${this.qrcodePreview}</div>
      ${this.getUrlToggle()}
      ${this.getUrlSection()}
      ${this.getControls()}
    `;
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  private getUrlToggle(): TemplateResult | symbol {
    if (!this.showUrl) {
      return nothing;
    }

    const showUrlLabel = TYPO3.lang['qrcode.showUrl'] || 'Show URL';
    return html`
      <div class="url-info-icon">
        <button type="button" class="btn btn-default btn-sm" title="${showUrlLabel}" @click=${this.toggleUrlSection}>
          <typo3-backend-icon identifier="actions-eye-link" size="small"></typo3-backend-icon>
        </button>
      </div>
    `;
  }

  private getUrlSection(): TemplateResult | symbol {
    if (!this.showUrl || !this.urlSectionVisible) {
      return nothing;
    }
    const urlLabel = TYPO3.lang['qrcode.url'] || 'URL';
    const copyUrlLabel = TYPO3.lang['qrcode.copyUrl'] || 'Copy URL';
    return html`
      <div class="form-group url-info-section">
        <label class="form-label">${urlLabel}</label>
        <div class="input-group">
          <input type="text" class="form-control" readonly .value="${this.content}">
          <typo3-copy-to-clipboard text="${this.content}" class="btn btn-default" silent>
            <typo3-backend-icon identifier="actions-clipboard" size="small"></typo3-backend-icon>
            ${copyUrlLabel}
          </typo3-copy-to-clipboard>
        </div>
      </div>
    `;
  }

  private toggleUrlSection(): void {
    this.urlSectionVisible = !this.urlSectionVisible;
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
            <button type="submit" class="btn btn-primary">${downloadLabel}</button>
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

  private showCopySuccess(): void {
    const urlInfoSection = this.querySelector('.url-info-section');
    if (urlInfoSection !== null) {
      urlInfoSection.classList.add('copy-success');
      setTimeout(() => urlInfoSection.classList.remove('copy-success'), 500);
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-qrcode': QrCodeElement;
  }
}
