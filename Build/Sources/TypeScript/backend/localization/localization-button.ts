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
import { PseudoButtonLitElement } from '@typo3/backend/element/pseudo-button';
import { customElement, property } from 'lit/decorators';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import { lll } from '@typo3/core/lit-helper';
import Modal from '@typo3/backend/modal';

@customElement('typo3-backend-localization-button')
export class LocalizationButton extends PseudoButtonLitElement {
  @property({ type: String, attribute: 'record-type' }) recordType: string;
  @property({ type: Number, attribute: 'record-uid' }) recordUid: number;
  @property({ type: Number, attribute: 'target-language' }) targetLanguage?: number;

  protected override buttonActivated(): void {
    const content = html`
      <typo3-backend-localization-wizard
        record-type="${this.recordType}"
        record-uid="${this.recordUid}"
        target-language="${this.targetLanguage}"
      >
      </typo3-backend-localization-wizard>
    `;

    Modal.advanced({
      title: lll('localization_wizard.modal.title'),
      content: content,
      severity: SeverityEnum.notice,
      size: Modal.sizes.medium,
      staticBackdrop: true,
      buttons: []
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-localization-button': LocalizationButton;
  }
}
