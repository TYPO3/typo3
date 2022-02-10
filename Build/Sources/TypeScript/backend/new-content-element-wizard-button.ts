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

import {customElement, property} from 'lit/decorators';
import {html, LitElement, TemplateResult} from 'lit';
import Modal from '@typo3/backend/modal';
import {SeverityEnum} from '@typo3/backend/enum/severity';
import {NewContentElementWizard} from '@typo3/backend/new-content-element-wizard';

/**
 * Module: @typo3/backend/new-content-element-wizard-button
 *
 * @example
 * <typo3-backend-new-content-element-wizard-button url="link/to/endpoint" title="Wizard title" ></typo3-backend-new-content-element-wizard-button>
 */
@customElement('typo3-backend-new-content-element-wizard-button')
export class NewContentElementWizardButton extends LitElement {
  @property({type: String}) url: string;
  @property({type: String}) title: string;

  private static handleModalContentLoaded(currentModal: HTMLElement): void {
    if (!currentModal || !currentModal.querySelector('.t3-new-content-element-wizard-inner')) {
      // Return in case modal is not defined or we deal with a custom wizard (mod.newContentElementWizard.override)
      return;
    }
    // Initialize the wizard functions
    new NewContentElementWizard(currentModal);
  }

  public constructor() {
    super();
    this.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      this.renderWizard();
    });
  }

  protected render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private renderWizard(): void
  {
    if (!this.url) {
      // Return in case no url is defined
      return;
    }

    Modal.advanced({
      content: this.url,
      title: this.title,
      severity: SeverityEnum.notice,
      size: Modal.sizes.medium,
      type: Modal.types.ajax,
      ajaxCallback: (): void => NewContentElementWizardButton.handleModalContentLoaded(Modal.currentModal[0])
    });
  }
}
