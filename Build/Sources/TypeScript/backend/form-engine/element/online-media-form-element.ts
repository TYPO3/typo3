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
import { LitElement, TemplateResult, html } from 'lit';

/**
 * Module: @typo3/backend/form-engine/element/online-media-form-element
 */
@customElement('typo3-backend-formengine-online-media-form')
export class OnlineMediaFormElement extends LitElement {
  @property({ type: String }) placeholder: string;
  @property({ type: String, attribute: 'help-text' }) allowedExtensionsHelpText: string;
  @property({ type: String, attribute: 'extensions' }) allowedExtensions: string;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <form @submit="${ this.dispatchSubmitEvent }">
        <div class="form-control-wrap">
          <input type="text" class="form-control" name="online-media-url" placeholder="${this.placeholder}" required>
          <div class="form-text">
            ${this.allowedExtensionsHelpText}<br>
            <ul class="badge-list">
            ${this.allowedExtensions.split(',').map((ext: string) => html`
              <li><span class="badge badge-success">${ext.trim().toUpperCase()}</span></li>
            `)}
            </ul>
          </div>
        </div>
      </form>
    `;
  }

  private dispatchSubmitEvent(e: SubmitEvent): void {
    e.preventDefault();

    const formData = new FormData(e.target as HTMLFormElement);
    const submittedData = Object.fromEntries(formData);

    this.dispatchEvent(new CustomEvent('typo3:formengine:online-media-added', {
      detail: submittedData
    }));
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-formengine-online-media-form': OnlineMediaFormElement;
  }
}
