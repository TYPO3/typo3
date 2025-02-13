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

import { customElement, property } from 'lit/decorators';
import { html, css, LitElement, TemplateResult } from 'lit';
import Modal from '@typo3/backend/modal';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import '@typo3/backend/new-record-wizard';

/**
 * Module: @typo3/backend/new-content-element-wizard-button
 *
 * @example
 * <typo3-backend-new-content-element-wizard-button class="btn btn-default" url="link/to/endpoint" subject="Wizard title" ></typo3-backend-new-content-element-wizard-button>
 */
@customElement('typo3-backend-new-content-element-wizard-button')
export class NewContentElementWizardButton extends LitElement {
  static override styles = [css`:host { cursor: pointer; appearance: button; }`];
  @property({ type: String }) url: string;
  @property({ type: String }) subject: string;

  public constructor() {
    super();
    this.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      this.renderWizard();
    });
    this.addEventListener('keydown', (e: KeyboardEvent): void => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        this.renderWizard();
      }
    });
  }

  public override connectedCallback(): void {
    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'button');
    }
    if (!this.hasAttribute('tabindex')) {
      this.setAttribute('tabindex', '0');
    }
  }

  protected override render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private renderWizard(): void {
    if (!this.url) {
      // Return in case no url is defined
      return;
    }

    Modal.advanced({
      content: this.url,
      title: this.subject,
      severity: SeverityEnum.notice,
      size: Modal.sizes.large,
      type: Modal.types.ajax
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-new-content-element-wizard-button': NewContentElementWizardButton;
  }
}
