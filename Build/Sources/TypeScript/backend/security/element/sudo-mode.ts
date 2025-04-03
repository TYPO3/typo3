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

import { customElement, property, query, state } from 'lit/decorators';
import { html, LitElement, nothing, type PropertyValues, type TemplateResult } from 'lit';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import Viewport from '@typo3/backend/viewport';
import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';
import Modal, { Sizes } from '@typo3/backend/modal';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import type { ReactiveElement } from '@lit/reactive-element';

interface SudoModeResponse {
  message: string;
  redirect?: {
    uri: string,
  }
}

interface Labels {
  cancel: string;
  verify: string;
  password: string;
  verifyWithUserPassword: string;
  verifyWithInstallToolPassword: string;
  accessGranted: string;
  invalidPassword: string;
  userPasswordMode: string;
  installToolPasswordMode: string;
  verificationFailed: string;
  verificationExpired: string;
}

abstract class SudoModeProperties extends LitElement {
  @property({ type: String }) verifyActionUri: string;
  @property({ type: String }) cancelUri: string;
  @property({ type: Boolean, attribute: 'has-fatal-error' }) hasFatalError: boolean;
  @property({ type: Boolean, attribute: 'allow-install-tool-password' }) allowInstallToolPassword: boolean;
  @property({ type: Object }) labels: Labels;
}

export const initiateSudoModeModal = async (properties: Omit<SudoModeProperties, keyof LitElement>): Promise<void> => {
  const isInIframe = window.location !== window.parent.location;
  if (isInIframe) {
    // Create a top-level instance
    topLevelModuleImport('@typo3/backend/security/element/sudo-mode.js');
  }
  const el = top.document.createElement('typo3-backend-security-sudo-mode');
  Object.assign(el, properties);
  top.document.body.append(el);
  return new Promise<void>((resolve, reject) => {
    el.addEventListener('typo3:sudo-mode:verified', () => resolve());
    el.addEventListener('typo3:sudo-mode:finished', () => reject());
  });
};

/**
 * Web Component showing the sudo mode password dialogs. The password verification
 * happens via AJAX, the redirect to the actually requested resources is triggered
 * by this JavaScript component as well - since it is capable of navigating to the
 * `top` frame directly (compared to using `target` in e.g. Fluid HTML).
 */
@customElement('typo3-backend-security-sudo-mode')
export class SudoMode extends SudoModeProperties {

  protected override render() {
    return nothing;
  }

  protected override async firstUpdated() {
    const isInIframe = window.location !== window.parent.location;
    // Launched from /sudo-mode/module route
    if (isInIframe) {
      try {
        await initiateSudoModeModal(this.getPropertyValues());
      } catch {
        // Go back to previous route when the modal is closed without verification
        history.go(-1);
      }
      return;
    }

    Modal.advanced({
      title: this.hasFatalError ? this.labels.verificationFailed : this.labels.verifyWithUserPassword,
      severity: this.hasFatalError ? SeverityEnum.error : SeverityEnum.notice,
      size: Sizes.small,
      additionalCssClasses: ['modal-sudo-mode-verification'],
      buttons: [
        this.hasFatalError ? {
          text: this.labels.cancel,
          active: true,
          btnClass: 'btn-default',
          trigger: () => {
            top.location.href = this.cancelUri;
          },
        } : {
          text: this.labels.verify,
          active: true,
          name: 'verify',
          form: 'verify-sudo-mode',
          btnClass: 'btn-primary',
        }
      ],
      content: html`
        <typo3-backend-security-sudo-mode-form
          .labels=${this.labels}
          .verifyActionUri=${this.verifyActionUri}
          .cancelUri=${this.cancelUri}
          .hasFatalError=${this.hasFatalError}
          .allowInstallToolPassword=${this.allowInstallToolPassword}
          @typo3:sudo-mode:verified=${() => this.dispatchEvent(new Event('typo3:sudo-mode:verified'))}
        ></typo3-backend-security-sudo-mode-form>
      `
    }).addEventListener('typo3-modal-hidden', (): void => {
      this.dispatchEvent(new Event('typo3:sudo-mode:finished'));
      this.remove();
    });
  }

  protected getPropertyValues(): Omit<this, keyof LitElement> {
    const properties = {} as Omit<this, keyof LitElement>;
    const ctor = this.constructor as typeof ReactiveElement;
    for (const key of ctor.elementProperties.keys() as IterableIterator<keyof Omit<this, keyof LitElement>>) {
      properties[key] = this[key];
    }
    return properties;
  }
}

@customElement('typo3-backend-security-sudo-mode-form')
export class SudoModeForm extends SudoModeProperties {
  @state() useInstallToolPassword = false;
  @state() errorMessage: string = null;
  @query('#password') passwordElement: HTMLInputElement;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    if (this.hasFatalError) {
      return html`
        <div>
          <div class="alert alert-danger">${this.labels.verificationExpired}</div>
        </div>
      `;
    }
    return html`
      <div>
        ${this.errorMessage ? html`
          <div class="alert alert-danger" id="invalid-password">${this.labels[this.errorMessage as keyof Labels] || this.errorMessage}</div>
        ` : nothing}
        <form method="post" class="form" id="verify-sudo-mode" spellcheck="false" @submit=${(evt: SubmitEvent) => this.verifyPassword(evt)}>
          ${this.useInstallToolPassword ? nothing : html`
            <input hidden aria-hidden="true" type="text" autocomplete="username" value=${TYPO3.configuration.username}>
          `}
          <div class="form-group">
            <label class="form-label" for="password">${this.labels.password}</label>
            <input required="required" class="form-control" id="password" type="password" name="password"
                   autocomplete=${this.useInstallToolPassword ? 'section-install current-password' : 'current-password'}>
          </div>
        </form>
        ${!this.allowInstallToolPassword ? nothing : html`
          <div class="text-end">
            <a href="#" @click=${(evt: MouseEvent) => this.toggleUseInstallToolPassword(evt)}>
              ${this.useInstallToolPassword ? this.labels.userPasswordMode : this.labels.installToolPasswordMode}
            </a>
          </div>
        `}
      </div>
    `;
  }

  protected override updated(changedProperties: PropertyValues): void {
    if (changedProperties.has('useInstallToolPassword')) {
      this.closest('typo3-backend-modal').modalTitle = this.getModalTitle();
    }
  }

  protected override firstUpdated(_changedProperties: PropertyValues): void {
    super.firstUpdated(_changedProperties);
    this.passwordElement?.focus();
  }

  protected getModalTitle() {
    if (this.hasFatalError) {
      return this.labels.verificationFailed;
    }
    if (this.useInstallToolPassword) {
      return this.labels.verifyWithInstallToolPassword;
    }
    return this.labels.verifyWithUserPassword;
  }

  private async verifyPassword(evt: SubmitEvent): Promise<void> {
    evt.preventDefault();
    this.errorMessage = null;
    try {
      const response = await new AjaxRequest(this.verifyActionUri).post({
        password: this.passwordElement.value,
        useInstallToolPassword: this.useInstallToolPassword ? 1 : 0
      });

      const responseData: SudoModeResponse = await response.resolve('application/json');
      this.dispatchEvent(new Event('typo3:sudo-mode:verified'));
      this.closest('typo3-backend-modal').hideModal();
      if (responseData.redirect) {
        Viewport.ContentContainer.setUrl(responseData.redirect.uri);
      }
    } catch (e: unknown) {
      if (e instanceof AjaxResponse) {
        const response = await e.resolve('application/json');
        this.errorMessage = response.message;
      } else {
        throw e;
      }
    }
  }

  private toggleUseInstallToolPassword(evt: MouseEvent): void {
    evt.preventDefault();
    this.useInstallToolPassword = !this.useInstallToolPassword;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-security-sudo-mode': SudoMode;
    'typo3-backend-security-sudo-mode-form': SudoModeForm;
  }
}
