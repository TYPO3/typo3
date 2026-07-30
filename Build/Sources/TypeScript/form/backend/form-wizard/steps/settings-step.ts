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
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { WizardStepValueInterface } from '@typo3/backend/wizard/steps/wizard-step-value-interface';
import type { WizardStepSummaryInterface } from '@typo3/backend/wizard/steps/wizard-step-summary-interface';
import type { SummaryItem } from '@typo3/backend/wizard/steps/summary-item-interface';
import formManagerLabels from '~labels/form.form_manager_javascript';
import type { FormWizardContext } from '@typo3/form/backend/form-wizard/form-wizard';

export interface FormSettings {
  formName?: string,
  storageLocation?: string,
}

export class SettingsStep implements WizardStepInterface, WizardStepValueInterface, WizardStepSummaryInterface {
  readonly key = 'settings';
  readonly title = formManagerLabels.get('formManager.newFormWizard.step2.title');
  readonly autoAdvance = true;

  private data: FormSettings = {
    formName: '',
    storageLocation: '',
  };

  constructor(private readonly context: FormWizardContext) {
    this.reset();
  }

  public isComplete(): boolean {
    return this.getValue()?.formName !== '';
  }

  public render(): TemplateResult {
    return html`
      ${this.renderSavePath()}
      ${this.renderFormNameInput()}
    `;
  }

  public reset(): void {
    this.setValue({
      formName: '',
      storageLocation: '',
    });
    this.context.clearStoreData(this.key);
  }

  public getValue(): FormSettings {
    return this.data;
  }

  public setValue(value: FormSettings): void {
    this.data = {
      ...this.data,
      ...value
    };

    this.context.wizard.requestUpdate();
  }

  public beforeAdvance(): void {
    this.context.setStoreData(this.key, this.getValue());
  }

  getSummaryData(): SummaryItem[] {
    const config = this.context.getStoreData(this.key);

    // Resolve storage location value to its human-readable label
    const storageAdapter = this.context.getStoreData('storage');
    const storageLocations = storageAdapter
      ? this.context.formManager.getAccessibleStorageLocationsForAdapter(storageAdapter)
      : [];
    const storageLocationLabel = storageLocations.find(l => l.value === config.storageLocation)?.label
      ?? config.storageLocation;

    return [
      {
        value: config.formName,
        label: formManagerLabels.get('formManager.form_name')
      },
      {
        value: storageLocationLabel,
        label: formManagerLabels.get('formManager.form_storageLocation')
      }
    ];
  }

  private renderSavePath(): TemplateResult | typeof nothing {
    const storageLocations = this.context.formManager.getAccessibleStorageLocationsForAdapter(this.context.getStoreData('storage')) ?? [];

    if (storageLocations.length <= 1) {
      this.setValue({ storageLocation: storageLocations[0]?.value ?? '' });
      return nothing;
    }

    // Auto-select first location if none selected yet
    if (!this.data.storageLocation && storageLocations.length > 0) {
      this.setValue({ storageLocation: storageLocations[0].value });
    }

    return html `
      <div class="form-group">
        <label class="form-label" for="new-form-save-path">${formManagerLabels.get('formManager.form_storageLocation')}</label>
        <div class="form-description">${formManagerLabels.get('formManager.form_storageLocation_description')}</div>
        <select class="new-form-save-path form-select" id="new-form-save-path" data-identifier="newFormSavePath"
          @change=${(e: Event) => this.setValue({ storageLocation: (e.target as HTMLSelectElement).value })}
        >
          ${storageLocations.map(option => html`
            <option
              value=${option.value}
              ?selected=${option.value === this.data.storageLocation}
            >
              ${option.label}
            </option>
        `)}
        </select>
      </div>`;
  }

  private renderFormNameInput(): TemplateResult {
    return html `
      <div class="form-group">
        <label class="form-label" for="new-form-name">${formManagerLabels.get('formManager.form_name')}</label>
        <div class="form-description">${formManagerLabels.get('formManager.form_name_description')}</div>
        <input class="form-control ${!this.isComplete() ? 'has-error' : ''}"
               id="new-form-name"
               data-identifier="newFormName"
               name="newFormName"
               .value=${this.data.formName}
               @input=${(e: Event) => this.setValue({ formName: (e.target as HTMLInputElement).value })}
        />
      </div>`;
  }

}

export default SettingsStep;
