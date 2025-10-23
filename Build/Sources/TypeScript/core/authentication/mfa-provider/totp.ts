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

import { html } from 'lit';
import { customElement, property } from 'lit/decorators';
import { PseudoButtonLitElement } from '@typo3/backend/element/pseudo-button';
import Modal from '@typo3/backend/modal';

@customElement('typo3-mfa-totp-url-info-button')
export class MfaTotpUrlButton extends PseudoButtonLitElement {
  @property({ type: String, attribute: 'data-url' }) modalUrl: string;
  @property({ type: String, attribute: 'data-title' }) modalTitle: string;
  @property({ type: String, attribute: 'data-description' }) modalDescription: string;
  @property({ type: String, attribute: 'data-button-ok' }) buttonOk: string;

  protected override buttonActivated(): void {
    this.showTotpAuthUrlModal();
  }

  private showTotpAuthUrlModal(): void {
    Modal.advanced({
      title: this.modalTitle,
      content: html`
        <p>${this.modalDescription}</p>
        <pre>${this.modalUrl}</pre>
      `,
      buttons: [
        {
          trigger: (): void => Modal.dismiss(),
          text: this.buttonOk || 'OK',
          active: true,
          btnClass: 'btn-default',
          name: 'ok'
        }
      ]
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-mfa-totp-url-info-button': MfaTotpUrlButton;
  }
}
