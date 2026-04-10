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
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { topWindow } from '@typo3/core/utility/top-window';
import labels from '~labels/extensionmanager.messages';
import { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';

export interface ToggleResponse {
  success: boolean;
  extensionKey?: string;
  dependencies?: Record<string, Array<{ code: number; message: string }>>;
  skipDependencyUri?: string;
  error?: string;
}

@customElement('typo3-extensionmanager-dependency-check')
class DependencyCheckElement extends LitElement {
  @property({ type: String, attribute: 'extension-key' }) extensionKey: string = '';
  @property({ type: Object }) dependencies: Record<string, Array<{ code: number; message: string }>> = {};

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    const warningMessage = labels.render<'dependencyCheck.unresolvedDependencies.message', TemplateResult>('dependencyCheck.unresolvedDependencies.message', {
      p: (chunks: unknown[]) => html`<p>${chunks}</p>`,
      ul: (chunks: unknown[]) => html`<ul>${chunks}</ul>`,
      li: (chunks: unknown[]) => html`<li>${chunks}</li>`,
      strong: (chunks: unknown[]) => html`<strong>${chunks}</strong>`,
    });

    return html`
      <p>${labels.get('dependencyCheck.headline', [this.extensionKey])}</p>
      <ul>${Object.entries(this.dependencies).map(([key, messages]) => this.renderDependency(key, messages))}</ul>
      ${warningMessage ? html`
        <div class="callout callout-warning mt-3">
          <div class="callout-content">
            <div class="callout-title">${labels.get('dependencyCheck.unresolvedDependencies.title')}</div>
            <div class="callout-body">${warningMessage}</div>
          </div>
        </div>
      ` : nothing}
      <div class="form-check mt-3">
        <input type="checkbox" name="unlockDependencyIgnoreButton" id="unlockDependencyIgnoreButton" class="form-check-input">
        <label class="form-check-label" for="unlockDependencyIgnoreButton">
          ${labels.get('label.resolveDependenciesEnableButton')}
        </label>
      </div>
      <p class="mt-3 mb-0">
        ${labels.get('extensionList.toggleStateConfirmation.reloadMessage')}
      </p>
    `;
  }

  private renderDependency(key: string, messages: Array<{ code: number; message: string }>): TemplateResult | TemplateResult[] {
    if (key === this.extensionKey) {
      return messages.map(msg => html`<li>${msg.message}</li>`);
    }
    return html`
      <li>
        <strong>${labels.get('dependencyCheck.requiredExtension', [key])}</strong>
        <ul>${messages.map(msg => html`<li>${msg.message}</li>`)}</ul>
      </li>
    `;
  }
}

/**
 * Shows a modal with unresolved dependency errors and an option to proceed
 * by skipping dependency checks.
 */
export function showDependencyModal(
  extensionKey: string,
  dependencies: Record<string, Array<{ code: number; message: string }>>,
  skipDependencyUri: string,
  onSuccess?: () => void,
): HTMLElement {
  const content: DependencyCheckElement = document.createElement('typo3-extensionmanager-dependency-check');
  content.extensionKey = extensionKey;
  content.dependencies = dependencies;

  const modal = Modal.confirm(
    labels.get('dependencyCheck.modalTitle.activate', [extensionKey]),
    content,
    Severity.warning,
    [
      {
        text: labels.get('button.cancel'),
        active: true,
        btnClass: 'btn-default',
        trigger: (): void => {
          Modal.dismiss();
        },
      },
      {
        text: labels.get('dependencyCheck.unresolvedDependencies.proceed'),
        btnClass: 'btn-danger disabled t3js-dependencies',
        trigger: (e: Event): void => {
          if (!(e.currentTarget as HTMLElement).classList.contains('disabled')) {
            Modal.dismiss();
            submitSkipDependency(extensionKey, skipDependencyUri, onSuccess);
          }
        },
      },
    ],
  );

  modal.addEventListener('typo3-modal-shown', (): void => {
    const actionButton = modal.querySelector('.t3js-dependencies');
    modal.querySelector('input[name="unlockDependencyIgnoreButton"]')?.addEventListener('change', (e: Event): void => {
      if ((e.currentTarget as HTMLInputElement).checked) {
        actionButton?.classList.remove('disabled');
      } else {
        actionButton?.classList.add('disabled');
      }
    });
  });

  return modal;
}

function submitSkipDependency(extensionKey: string, url: string, onSuccess?: () => void): void {
  const progressBar: ProgressBarElement = document.createElement('typo3-backend-progress-bar');
  document.body.appendChild(progressBar);
  progressBar.start();

  new AjaxRequest(url).post({}).then(
    async (response: AjaxResponse): Promise<void> => {
      const data: ToggleResponse = await response.resolve();
      if (data.success) {
        if (onSuccess) {
          onSuccess();
        } else {
          topWindow.location.reload();
        }
        return;
      }
      progressBar.done();
      if (data.dependencies && data.skipDependencyUri) {
        showDependencyModal(extensionKey, data.dependencies, data.skipDependencyUri, onSuccess);
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

declare global {
  interface HTMLElementTagNameMap {
    'typo3-extensionmanager-dependency-check': DependencyCheckElement;
  }
}
