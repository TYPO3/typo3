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

export enum MODE {
  Blank = 'blank',
  Predefined = 'predefined',
}

export type FormMode = {
  key: MODE;
  label: string;
  description: string;
  iconIdentifier: string;
};

export class ModeStep implements WizardStepInterface, WizardStepValueInterface, WizardStepSummaryInterface {
  readonly key = 'mode';
  readonly title = formManagerLabels.get('formManager.newFormWizard.step1.progressLabel');
  readonly autoAdvance = true;

  private selectedMode: MODE | null = null;

  private readonly modes: FormMode[] = [
    {
      key: MODE.Blank,
      label: formManagerLabels.get('formManager.blankForm.label'),
      description: formManagerLabels.get('formManager.blankForm.description'),
      iconIdentifier: 'apps-pagetree-page-default',
    },
    {
      key: MODE.Predefined,
      label: formManagerLabels.get('formManager.predefinedForm.label'),
      description: formManagerLabels.get('formManager.predefinedForm.description'),
      iconIdentifier: 'form-page',
    }
  ];

  constructor(private readonly context: FormWizardContext) {
  }

  public isComplete(): boolean {
    return this.getValue() !== null;
  }

  public render(): TemplateResult {
    // Auto-select first mode if none selected
    if (this.getValue() == null && this.modes.length > 0) {
      this.setValue(this.modes[0].key);
    }

    return html`
      <div class="form-mode-selection">
        <div class="form-check-card-container">
          ${this.modes.map((mode: FormMode) => html`
            <div class="form-check form-check-type-card">
              <input
                class="form-check-input"
                type="radio"
                name="${this.key}"
                id="mode-${mode.key}"
                value=${mode.key}
                .checked=${live(this.getValue() === mode.key)}
                @change=${() => this.setValue(mode.key)}
              >
              <label class="form-check-label" for="mode-${mode.key}">
                <span class="form-check-label-header">
                  <typo3-backend-icon identifier="${mode.iconIdentifier}" size="medium"></typo3-backend-icon>
                  ${mode.label}
                </span>
                <span class="form-check-label-body">
                  ${unsafeHTML(mode.description)}
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

  public getValue(): MODE | null {
    return this.selectedMode;
  }

  public setValue(value: MODE): void {
    this.selectedMode = value;
    this.context.wizard.requestUpdate();
  }

  public beforeAdvance(): void {
    this.context.setStoreData(this.key, this.getValue()!);
  }

  public getSummaryData(): SummaryItem[] {
    const selectedMode = this.context.getStoreData(this.key);
    if (!selectedMode) {
      return [];
    }

    const mode = this.modes.find((m: FormMode) => m.key === selectedMode);
    if (!mode) {
      return [];
    }

    return [{
      label: formManagerLabels.get('formManager.newFormWizard.step.modes.summary.title'),
      value: html `
        <typo3-backend-icon identifier="${mode.iconIdentifier}" size="small" class="me-1"></typo3-backend-icon>
        ${mode.label}
      `
    }];
  }
}

export default ModeStep;
