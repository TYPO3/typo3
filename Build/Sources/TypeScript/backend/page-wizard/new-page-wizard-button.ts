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
import '@typo3/backend/new-record-wizard';
import type { PageWizardConfiguration } from '@typo3/backend/page-wizard/page-wizard-configuration';
import { openPageWizardModal } from '@typo3/backend/page-wizard/helper/wizard-helper';

@customElement('typo3-backend-new-page-wizard-button')
export class NewPageWizardButton extends PseudoButtonLitElement {
  @property({ type: Object }) configuration?: PageWizardConfiguration = null!;
  protected override buttonActivated(): void {
    openPageWizardModal(this.configuration);
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-new-page-wizard-button': NewPageWizardButton;
  }
}
