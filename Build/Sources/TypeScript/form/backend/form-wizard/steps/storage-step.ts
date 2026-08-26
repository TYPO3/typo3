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

import { html, type TemplateResult } from 'lit';
import { live } from 'lit/directives/live.js';
import { unsafeHTML } from 'lit/directives/unsafe-html.js';
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { WizardStepValueInterface } from '@typo3/backend/wizard/steps/wizard-step-value-interface';
import type { WizardStepSummaryInterface } from '@typo3/backend/wizard/steps/wizard-step-summary-interface';
import type { SummaryItem } from '@typo3/backend/wizard/steps/summary-item-interface';
import formManagerLabels from '~labels/form.form_manager_javascript';
import type { FormWizardContext } from '@typo3/form/backend/form-wizard/form-wizard';
import type { StorageAdapter } from '@typo3/form/backend/form-manager';

export class StorageStep implements WizardStepInterface, WizardStepValueInterface, WizardStepSummaryInterface {
  readonly key = 'storage';
  readonly title = formManagerLabels.get('formManager.newFormWizard.step.storages.progressLabel');
  readonly autoAdvance = true;

  private hasDispatchedAutoAdvance: boolean = false;

  private selectedStorage: StorageAdapter | null = null;

  constructor(private readonly context: FormWizardContext) {
  }

  public isComplete(): boolean {
    return this.getValue() !== null;
  }

  public render(): TemplateResult {
    const storageAdapters = this.context.formManager.getAccessibleStorageAdapters();

    let shouldAutoAdvance = false;

    // Auto-select first storage adapter if none selected
    if (this.getValue() == null && storageAdapters.length > 0) {
      this.setValue(storageAdapters[0]);

      // Only auto-advance if there's only one option
      if (storageAdapters.length === 1) {
        shouldAutoAdvance = true;
      }
    }

    // Dispatch auto-advance if needed (only once)
    if (shouldAutoAdvance && !this.hasDispatchedAutoAdvance) {
      this.hasDispatchedAutoAdvance = true;
      this.context.dispatchAutoAdvance();
      return this.context.wizard.renderLoader();
    }
    return html`
      <h2 class="h4">${formManagerLabels.get('formManager.newFormWizard.step.storages.title')}</h2>
      <p>${formManagerLabels.get('formManager.newFormWizard.step.storages.description')}</p>
      <div class="form-storage-selection">
        <div class="form-check-card-container">
          ${storageAdapters.map((storage) => html`
            <div class="form-check form-check-type-card">
              <input
                class="form-check-input"
                type="radio"
                name="${this.key}"
                id="mode-${storage.typeIdentifier}"
                value=${storage.typeIdentifier}
                .checked=${live(this.getValue()?.typeIdentifier === storage.typeIdentifier)}
                @change=${() => this.setValue(storage)}
              >
              <label class="form-check-label" for="mode-${storage.typeIdentifier}">
                <span class="form-check-label-header">
                  <typo3-backend-icon identifier="${storage.iconIdentifier}" size="medium"></typo3-backend-icon>
                  ${storage.label}
                </span>
                <span class="form-check-label-body">
                  ${unsafeHTML(storage.description)}
                </span>
              </label>
            </div>
          `)}
        </div>
      </div>
    `;
  }

  public reset(): void {
    this.setValue(null as any);
    this.context.clearStoreData(this.key);
  }

  public getValue(): StorageAdapter | null {
    return this.selectedStorage;
  }

  public setValue(value: StorageAdapter): void {
    this.selectedStorage = value;
    this.context.wizard.requestUpdate();
  }

  public beforeAdvance(): void {
    this.context.setStoreData(this.key, this.getValue());
  }

  public getSummaryData(): SummaryItem[] {
    const selectedStorage = this.context.getStoreData(this.key);
    if (!selectedStorage) {
      return [];
    }

    return [{
      label: formManagerLabels.get('formManager.newFormWizard.step.storages.summary.title'),
      value: html `
        <typo3-backend-icon identifier="${selectedStorage.iconIdentifier}" size="small" class="me-1"></typo3-backend-icon>
        ${selectedStorage.label}
      `
    }];
  }
}

export default StorageStep;
