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
import { customElement, property } from 'lit/decorators.js';
import { ifDefined } from 'lit/directives/if-defined.js';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import Modal from '@typo3/backend/modal';
import labels from '~labels/backend.wizards.localization';

@customElement('typo3-backend-localization-button')
export class LocalizationButton extends PseudoButtonLitElement {
  @property({ type: String, attribute: 'record-type' }) recordType: string;
  @property({ type: Number, attribute: 'record-uid' }) recordUid: number;
  @property({ type: Number, attribute: 'target-language' }) targetLanguage?: number;
  @property({ type: String, attribute: 'return-url' }) returnUrl?: string;

  protected override buttonActivated(): void {
    const content = html`
      <typo3-backend-localization-wizard
        record-type="${this.recordType}"
        record-uid="${this.recordUid}"
        target-language="${this.targetLanguage}"
        return-url="${ifDefined(this.returnUrl)}"
      >
      </typo3-backend-localization-wizard>
    `;

    Modal.advanced({
      title: labels.get('localization_wizard.modal.title'),
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
