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

import { customElement, property } from 'lit/decorators.js';
import { PseudoButtonLitElement } from '@typo3/backend/element/pseudo-button';
import Modal from '@typo3/backend/modal';
import { html } from 'lit';
import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';

/**
 * Module: @typo3/backend/element/qrcode-modal-button
 *
 * @example
 * <typo3-qrcode-modal-button content="https://example.com" show-url></typo3-qrcode-modal-button>
 */
@customElement('typo3-qrcode-modal-button')
export class QrCodeModalButton extends PseudoButtonLitElement {
  @property({ type: String, attribute: 'modal-title' }) modalTitle: string;
  @property({ type: String }) content: string;
  @property({ type: Boolean, attribute: 'show-url' }) showUrl: boolean = false;

  protected override buttonActivated(): void {
    this.modalOpen();
  }

  protected async loadModuleFrameAgnostic(module: string): Promise<any> {
    const isInIframe = window.location !== window.parent.location;
    if (isInIframe) {
      await topLevelModuleImport(module);
    } else {
      await import(module);
    }
  }

  protected async modalOpen(): Promise<void> {
    // Import elements manually to ensure they work across iframe boundaries
    await this.loadModuleFrameAgnostic('@typo3/backend/element/qrcode-element.js');
    Modal.advanced({
      title: this.modalTitle || 'QR Code',
      size: Modal.sizes.small,
      content: html`
        <div class="text-center">
          <typo3-qrcode
            class="text-start"
            content="${this.content}"
            size="large"
            show-download
            ?show-url="${this.showUrl}"
          ></typo3-qrcode>
        </div>
      `,
      buttons: [
        {
          text: TYPO3.lang['button.close'] || 'Close',
          name: 'close',
          trigger: function (event, modal) {
            modal.hideModal();
          },
        },
      ],
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-qrcode-modal-button': QrCodeModalButton;
  }
}
