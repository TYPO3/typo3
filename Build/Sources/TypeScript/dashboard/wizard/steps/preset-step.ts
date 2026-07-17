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
import { Task, TaskStatus } from '@lit/task';
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { WizardStepValueInterface } from '@typo3/backend/wizard/steps/wizard-step-value-interface';
import type { WizardStepSummaryInterface } from '@typo3/backend/wizard/steps/wizard-step-summary-interface';
import type { WizardStepAfterRenderInterface } from '@typo3/backend/wizard/steps/wizard-step-after-render-interface';
import type { SummaryItem } from '@typo3/backend/wizard/steps/summary-item-interface';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { DashboardWizardContext } from '@typo3/dashboard/dashboard-wizard';
import type { DashboardPresetInterface } from '@typo3/dashboard/dashboard';
import labels from '~labels/dashboard.messages';

export class PresetStep implements WizardStepInterface, WizardStepValueInterface, WizardStepSummaryInterface, WizardStepAfterRenderInterface {
  readonly key = 'preset';
  readonly title = labels.get('dashboard.wizard.preset.title');
  readonly autoAdvance = true;

  private task: Task<[], DashboardPresetInterface[]>;
  private selectedPreset: string | null = null;
  private hasDispatchedAutoAdvance: boolean = false;

  constructor(private readonly context: DashboardWizardContext) {
    this.initPresetsTask();
  }

  public isComplete(): boolean {
    return this.getValue() !== null;
  }

  public render(): TemplateResult {
    if (this.task.status === TaskStatus.INITIAL) {
      this.task.run();
    }

    return this.task.render({
      complete: (presets: DashboardPresetInterface[]) => {
        let shouldAutoAdvance = false;

        // Initialize from store, otherwise preselect the first preset
        if (this.getValue() === null) {
          const storedValue = this.context.getStoreData(this.key);
          if (storedValue != null) {
            this.setValue(storedValue);
          } else if (presets.length > 0) {
            this.setValue(presets[0].identifier);
            shouldAutoAdvance = presets.length === 1;
          }
        }

        if (shouldAutoAdvance && !this.hasDispatchedAutoAdvance) {
          this.hasDispatchedAutoAdvance = true;
          this.context.dispatchAutoAdvance();
          return this.context.wizard.renderLoader();
        }

        return html`
          <div class="dashboard-wizard-preset">
            <h2 class="h4">${labels.get('dashboard.wizard.preset.headline')}</h2>
            <div class="form-check-card-container">
              ${presets.map((preset: DashboardPresetInterface) => html`
                <div class="form-check form-check-type-card">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="preset"
                    id="preset-${preset.identifier}"
                    value="${preset.identifier}"
                    .checked="${live(this.getValue() === preset.identifier)}"
                    @change="${() => this.setValue(preset.identifier)}"
                  >
                  <label class="form-check-label" for="preset-${preset.identifier}">
                    <span class="form-check-label-header form-check-label-header-inherit">
                      <typo3-backend-icon identifier="${preset.icon}" size="small"></typo3-backend-icon>
                      ${preset.title}
                    </span>
                    <span class="form-check-label-body">${preset.description}</span>
                  </label>
                </div>
              `)}
            </div>
          </div>
        `;
      },
      error: (error: unknown) => this.context.wizard.renderError(labels.get('dashboard.wizard.preset.loadError'), error),
      pending: () => this.context.wizard.renderLoader()
    });
  }

  public reset(): void {
    this.selectedPreset = null;
    this.context.clearStoreData(this.key);
    this.initPresetsTask();
  }

  public getValue(): string {
    return this.selectedPreset;
  }

  public setValue(value: string): void {
    this.selectedPreset = value;
    this.context.wizard.requestUpdate();
  }

  public beforeAdvance(): void {
    this.context.setStoreData(this.key, this.getValue());
  }

  public getSummaryData(): SummaryItem[] {
    const selectedIdentifier = this.context.getStoreData(this.key);
    if (!selectedIdentifier || !this.task.value) {
      return [];
    }

    const selectedPreset = this.task.value.find((preset: DashboardPresetInterface) => preset.identifier === selectedIdentifier);
    if (!selectedPreset) {
      return [];
    }

    return [{
      label: this.title,
      value: html`
        <typo3-backend-icon identifier="${selectedPreset.icon}" size="small" class="me-1"></typo3-backend-icon>
        ${selectedPreset.title}
      `
    }];
  }

  public async afterRender(): Promise<void> {
    // Presets load asynchronously; signal readiness so the wizard moves focus
    // (onto the selected preset) only once they are rendered.
    try {
      await this.task.taskComplete;
    } catch {
      // ignore — the error state is rendered by the step
    }
  }

  private initPresetsTask(): void {
    this.task = new Task(this.context.wizard, {
      task: async (): Promise<DashboardPresetInterface[]> => {
        const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.dashboard_presets_get).get({ cache: 'no-cache' });
        const data = await response.resolve();
        return Object.values(data as Record<string, DashboardPresetInterface>).filter((preset: DashboardPresetInterface) => preset.showInWizard);
      },
      autoRun: false
    });
  }
}

export default PresetStep;
