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

import { html, LitElement, nothing, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators.js';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import Severity from '@typo3/backend/severity';
import BrowserSession from '@typo3/backend/storage/browser-session';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { topWindow } from '@typo3/core/utility/top-window';
import { showDependencyModal, type ToggleResponse } from './dependency-check';
import '@typo3/backend/element/icon-element';
import { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';
import labels from '~labels/extensionmanager.messages';

interface CheckResponse {
  installed: boolean;
  hasDependencyErrors: boolean;
  dependencies?: Record<string, Array<{ code: number; message: string }>>;
  skipDependencyUri?: string;
  error?: string;
}

/**
 * Module: @typo3/extensionmanager/extension-toggle-button
 *
 * Web component that renders an activate/deactivate button for extensions.
 * First performs a preflight dependency check, then shows a confirmation modal.
 * Only toggles the extension state after user confirmation.
 * If unresolved dependencies are detected, a modal is shown with the option
 * to proceed or cancel. After reload, focus is restored to the button.
 *
 * @example
 * <typo3-extension-toggle-button check-url="/typo3/check..." url="/typo3/toggle..." extension-key="my_ext" installed label="Deactivate">
 * </typo3-extension-toggle-button>
 */
@customElement('typo3-extension-toggle-button')
export class ExtensionToggleButton extends LitElement {
  private static readonly focusSessionKey: string = 'extensionManager.focusExtension';

  @property({ type: String, attribute: 'check-url' }) checkUrl: string;
  @property({ type: String }) url: string;
  @property({ type: String, attribute: 'extension-key' }) extensionKey: string;
  @property({ type: Boolean }) installed: boolean = false;
  @property({ type: String }) label: string = '';
  @property({ type: Boolean, attribute: 'show-label' }) showLabel: boolean = false;

  public override connectedCallback(): void {
    super.connectedCallback();

    const focusKey = BrowserSession.get(ExtensionToggleButton.focusSessionKey);
    if (focusKey === this.extensionKey) {
      BrowserSession.unset(ExtensionToggleButton.focusSessionKey);
      this.updateComplete.then(() => {
        this.querySelector('button')?.focus();
      });
    }
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult | symbol {
    if (!this.url) {
      return nothing;
    }

    const iconIdentifier = 'actions-system-extension-' + (this.installed ? 'uninstall' : 'install');
    return html`
      <button type="button" class="btn btn-default" title="${this.label}" @click="${this.handleClick}">
        <typo3-backend-icon identifier="${iconIdentifier}" size="small"></typo3-backend-icon>
        ${this.showLabel ? html` ${this.label}` : nothing}
      </button>
    `;
  }

  private handleClick(e: Event): void {
    e.preventDefault();

    const progressBar: ProgressBarElement = document.createElement('typo3-backend-progress-bar');
    document.body.appendChild(progressBar);
    progressBar.start();

    const title = this.installed
      ? labels.get('dependencyCheck.modalTitle.deactivate', [this.extensionKey])
      : labels.get('dependencyCheck.modalTitle.activate', [this.extensionKey]);

    const onToggleSuccess = (): void => {
      BrowserSession.set(ExtensionToggleButton.focusSessionKey, this.extensionKey);
      topWindow.location.reload();
    };

    new AjaxRequest(this.checkUrl).post({}).then(
      async (response: AjaxResponse): Promise<void> => {
        const data: CheckResponse = await response.resolve();
        progressBar.done();

        if (data.error) {
          Notification.error(
            labels.get('extensionList.dependenciesResolveInstallError.title'),
            data.error,
          );
          return;
        }

        if (data.hasDependencyErrors && data.dependencies && data.skipDependencyUri) {
          const modal = showDependencyModal(this.extensionKey, data.dependencies, data.skipDependencyUri, onToggleSuccess);
          this.focusButtonOnModalHidden(modal);
          return;
        }

        const confirmLabel = this.installed
          ? labels.get('extensionList.deactivate')
          : labels.get('extensionList.activate');
        const modal = Modal.confirm(title, labels.get('extensionList.toggleStateConfirmation.reloadMessage'), Severity.warning, [
          {
            text: labels.get('button.cancel'),
            active: true,
            btnClass: 'btn-default',
            trigger: (): void => {
              Modal.dismiss();
            },
          },
          {
            text: confirmLabel,
            btnClass: 'btn-warning',
            trigger: (): void => {
              Modal.dismiss();
              this.performToggle(onToggleSuccess);
            },
          },
        ]);
        this.focusButtonOnModalHidden(modal);
      },
      (): void => {
        progressBar.done();
        Notification.error(
          labels.get('extensionList.dependenciesResolveInstallError.title'),
          labels.get('extensionList.dependenciesResolveInstallError.message'),
        );
      },
    );
  }

  private performToggle(onSuccess: () => void): void {
    const progressBar: ProgressBarElement = document.createElement('typo3-backend-progress-bar');
    document.body.appendChild(progressBar);
    progressBar.start();

    new AjaxRequest(this.url).post({}).then(
      async (response: AjaxResponse): Promise<void> => {
        const data: ToggleResponse = await response.resolve();
        if (data.success) {
          onSuccess();
          return;
        }
        progressBar.done();
        if (data.dependencies && data.skipDependencyUri) {
          const modal = showDependencyModal(this.extensionKey, data.dependencies, data.skipDependencyUri, onSuccess);
          this.focusButtonOnModalHidden(modal);
        } else if (data.error) {
          Notification.error(
            labels.get('extensionList.dependenciesResolveInstallError.title'),
            data.error,
          );
        }
      },
      (): void => {
        progressBar.done();
        Notification.error(
          labels.get('extensionList.dependenciesResolveInstallError.title'),
          labels.get('extensionList.dependenciesResolveInstallError.message'),
        );
      },
    );
  }

  private focusButtonOnModalHidden(modal: HTMLElement): void {
    modal.addEventListener('typo3-modal-hidden', (): void => {
      this.querySelector('button')?.focus();
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-extension-toggle-button': ExtensionToggleButton;
  }
}
