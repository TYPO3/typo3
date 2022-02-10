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

import {render, html, TemplateResult, LitElement} from 'lit';
import {customElement, property} from 'lit/decorators';
import Modal from '@typo3/backend/modal';

enum Selectors {
  modalBody = '.t3js-modal-body'
}

@customElement('typo3-mfa-totp-url-info-button')
class MfaTotpUrlButton extends LitElement {
  @property({type: String}) url: string;
  @property({type: String}) title: string;
  @property({type: String}) description: string;
  @property({type: String}) ok: string;

  public constructor() {
    super();
    this.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      this.showTotpAuthUrlModal();
    });
  }

  protected render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private showTotpAuthUrlModal(): void {
    Modal.advanced({
      title: this.title,
      content: '', // Empty content, will be filled by the callback
      buttons: [
        {
          trigger: (): void => Modal.dismiss(),
          text: this.ok || 'OK',
          active: true,
          btnClass: 'btn-default',
          name: 'ok'
        }
      ],
      callback: (currentModal: HTMLCollection): void => {
        render(
          html`
            <p>${this.description}</p>
            <pre>${this.url}</pre>
          `,
          currentModal[0].querySelector(Selectors.modalBody) as HTMLElement
        );
      }
    });
  }
}
