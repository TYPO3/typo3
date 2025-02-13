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
import { KeyTypesEnum } from '@typo3/backend/enum/key-types';

/**
 * Module: @typo3/backend/element/dispatch-modal-button
 *
 * @example
 * <typo3-backend-dispatch-modal-button class="btn btn-default" url="link/to/endpoint" subject="Wizard title" ></typo3-move-record-wizard-button>
 */
@customElement('typo3-backend-dispatch-modal-button')
export class DispatchModalButton extends LitElement {
  static override styles = [css`:host { cursor: pointer; appearance: button; }`];
  @property({ type: String }) url: string;
  @property({ type: String }) subject: string;

  public override connectedCallback(): void {
    super.connectedCallback();

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'button');
    }
    if (!this.hasAttribute('tabindex')) {
      this.setAttribute('tabindex', '0');
    }

    this.addEventListener('click', this.triggerWizard);
    this.addEventListener('keydown', this.triggerWizard)
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();

    this.removeEventListener('click', this.triggerWizard);
    this.removeEventListener('keydown', this.triggerWizard)
  }

  protected override render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private triggerWizard(e: MouseEvent|KeyboardEvent): void {
    if (e instanceof KeyboardEvent) {
      if (e.key === KeyTypesEnum.ENTER || e.key === KeyTypesEnum.SPACE) {
        e.preventDefault();
      }
    } else {
      e.preventDefault();
    }

    this.renderWizard();
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
      type: Modal.types.iframe
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-dispatch-modal-button': DispatchModalButton;
  }
}
