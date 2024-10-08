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
import { css, html, LitElement, TemplateResult } from 'lit';
import Modal from '@typo3/backend/modal';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import DeferredAction from '@typo3/backend/action-button/deferred-action';
import AjaxDataHandler from '@typo3/backend/ajax-data-handler';
import ResponseInterface from '@typo3/backend/ajax-data-handler/response-interface';
import Viewport from '@typo3/backend/viewport';

/**
 * Module: @typo3/sys-note/delete-button
 *
 * @example
 * <typo3-sysnote-delete-button uid="42" return-url="">
 *   ...
 * </typo3-sysnote-delete-button>
 */
@customElement('typo3-sysnote-delete-button')
export class DeleteButton extends LitElement {
  static styles = [css`:host { cursor: pointer; appearance: button; }`];
  @property({ type: Number }) uid: number;
  @property({ type: String, attribute: 'return-url' }) returnUrl: string;
  @property({ type: String, attribute: 'modal-title' }) modalTitle: string;
  @property({ type: String, attribute: 'modal-content' }) modalContent: string;
  @property({ type: String, attribute: 'modal-button-ok' }) okButtonLabel: string;
  @property({ type: String, attribute: 'modal-button-cancel' }) cancelButtonLabel: string;

  public connectedCallback(): void {
    super.connectedCallback();

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'button');
    }
    if (!this.hasAttribute('tabindex')) {
      this.setAttribute('tabindex', '0');
    }

    this.addEventListener('click', this.showConfirmationModal);
  }

  public disconnectedCallback() {
    super.disconnectedCallback();

    this.removeEventListener('click', this.showConfirmationModal);
  }

  protected render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private showConfirmationModal(): void {
    Modal.advanced({
      content: this.modalContent,
      title: this.modalTitle,
      severity: SeverityEnum.warning,
      size: Modal.sizes.small,
      buttons: [
        {
          text: this.cancelButtonLabel || 'Close',
          btnClass: 'btn-default',
          trigger: function(): void {
            Modal.dismiss();
          },
        }, {
          text: this.okButtonLabel || 'OK',
          btnClass: 'btn-warning',
          action: new DeferredAction(async (): Promise<void> => {
            await this.deleteRecord();
          }),
        },
      ]
    });
  }

  private async deleteRecord(): Promise<ResponseInterface> {
    const processing = AjaxDataHandler.process(`cmd[sys_note][${this.uid}][delete]=1`);
    processing.then((): void => {
      Viewport.ContentContainer.setUrl(this.returnUrl);
    });

    return processing;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-sysnote-delete-button': DeleteButton;
  }
}
