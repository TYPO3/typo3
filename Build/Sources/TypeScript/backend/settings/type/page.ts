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

import { html, nothing, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators';
import { lll } from '@typo3/core/lit-helper';
import { MessageUtility } from '@typo3/backend/utility/message-utility';
import { BaseElement } from './base';
import { default as Modal } from '@typo3/backend/modal';
import '@typo3/backend/element/icon-element';

export const componentName = 'typo3-backend-settings-type-page';

@customElement(componentName)
export class PageTypeElement extends BaseElement<number> {

  @property({ type: Number }) override value: number;

  protected override render(): TemplateResult {
    /* eslint-disable @stylistic/indent */
    return html`
      <div class="input-grouped">
        <input
          type="number"
          id=${this.formid}
          class="form-control"
          ?readonly=${this.readonly}
          .value=${this.value}
          @change=${(e: InputEvent) => this.value = parseInt((e.target as HTMLInputElement).value, 10)}
        />
        ${this.canUseElementBrowser()
          ? html`
            <button
              type="button"
              class="btn btn-default"
              @click=${() => this.openElementBrowser()}
            >
              <typo3-backend-icon identifier="apps-pagetree-page" size="small"></typo3-backend-icon>
              ${lll('settingseditor.type.page.button') || 'Select page'}
            </button>
          `
          : nothing
        }
      </div>
    `;
  }

  private canUseElementBrowser(): boolean {
    return top.TYPO3.settings?.Wizards?.elementBrowserUrl !== undefined;
  }

  private openElementBrowser() {
    const mode = 'db';
    const params = this.formid + '|||pages';

    const modal = Modal.advanced({
      type: Modal.types.iframe,
      content: top.TYPO3.settings.Wizards.elementBrowserUrl + '&mode=' + mode + '&bparams=' + params,
      size: Modal.sizes.large,
    });
    window.addEventListener('message', this.elementBrowserListener);
    modal.addEventListener('typo3-modal-hide', () => {
      window.removeEventListener('message', this.elementBrowserListener);
    });
  }

  private readonly elementBrowserListener = (e: MessageEvent): void => {
      if (!MessageUtility.verifyOrigin(e.origin)) {
        throw 'Denied message sent by ' + e.origin;
      }

      if (e.data.actionName === 'typo3:elementBrowser:elementAdded') {
        if (typeof e.data.fieldName === 'undefined') {
          throw 'fieldName not defined in message';
        }

        if (typeof e.data.value === 'undefined') {
          throw 'value not defined in message';
        }

        this.value = e.data.value.split('_').pop();
      }
  };
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-settings-type-page': PageTypeElement;
  }
}
