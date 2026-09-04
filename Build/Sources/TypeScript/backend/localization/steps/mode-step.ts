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
import { live } from 'lit/directives/live.js';
import { unsafeHTML } from 'lit/directives/unsafe-html.js';
import { Task, TaskStatus } from '@lit/task';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { LocalizationContext } from '@typo3/backend/localization/localization-wizard';
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { WizardStepValueInterface } from '@typo3/backend/wizard/steps/wizard-step-value-interface';
import type { WizardStepSummaryInterface } from '@typo3/backend/wizard/steps/wizard-step-summary-interface';
import type { SummaryItem } from '@typo3/backend/wizard/steps/summary-item-interface';
import localizationWizardLabels from '~labels/backend.wizards.localization';

export type LocalizationMode = {
  key: string;
  label: string;
  description: string;
  iconIdentifier: string;
};

export class ModeStep implements WizardStepInterface, WizardStepValueInterface, WizardStepSummaryInterface {
  readonly key = 'mode';
  readonly title = localizationWizardLabels.get('step.modes.title');
  readonly autoAdvance = true;

  private readonly task: Task<[string, number, number | null, number | null], LocalizationMode[]>;
  private hasDispatchedAutoAdvance: boolean = false;
  private selectedMode: string | null = null;

  constructor(private readonly context: LocalizationContext) {
    this.task = new Task(this.context.wizard, {
      task: async ([recordType, recordUid, targetLanguage, sourceLanguage]: [string, number, number | null, number | null]): Promise<LocalizationMode[]> => {
        if (targetLanguage == null || sourceLanguage == null) {
          return [];
        }

        try {
          const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.wizard_localization_get_modes).withQueryArguments({
            recordType: recordType,
            recordUid: recordUid,
            targetLanguage: targetLanguage,
            sourceLanguage: sourceLanguage,
          }).get();
          const modes: LocalizationMode[] = await response.resolve();

          return modes;
        } catch (error) {
          console.warn('Failed to fetch localization modes:', error);
          return [];
        }
      },
      args: () => [this.context.recordType, this.context.recordUid, this.context.getStoreData('targetLanguage'), this.context.getStoreData('sourceLanguage')],
      autoRun: false
    });
  }

  public isComplete(): boolean {
    return this.getValue() !== null;
  }

  public render(): TemplateResult {
    if (this.task.status === TaskStatus.INITIAL) {
      this.task.run();
    }

    return this.task.render({
      complete: (modes: LocalizationMode[]) => {
        // Initialize from store if not already set
        if (this.getValue() == null) {
          const storedValue = this.context.getStoreData('localizationMode');
          if (storedValue != null) {
            this.setValue(storedValue);
          }
        }

        let shouldAutoAdvance = false;

        // Auto-select first mode if none selected
        if (this.getValue() == null && modes.length > 0) {
          this.setValue(modes[0].key);

          // Only auto-advance if there's only one option
          if (modes.length === 1) {
            shouldAutoAdvance = true;
          }
        }

        // Dispatch auto-advance if needed (only once)
        if (shouldAutoAdvance && !this.hasDispatchedAutoAdvance) {
          this.hasDispatchedAutoAdvance = true;
          this.context.dispatchAutoAdvance();
          return this.context.wizard.renderLoader();
        }

        let content: TemplateResult | typeof nothing = nothing;
        if (modes.length === 0) {
          content = html`
            <div class="text-center">
              <p>${localizationWizardLabels.get('step.modes.none_available')}</p>
            </div>
          `;
        } else {
          content = html`
            <p>${localizationWizardLabels.get('step.modes.description')}</p>
            <div class="form-check-card-container">
              ${modes.map((mode: LocalizationMode) => html`
                <div class="form-check form-check-type-card">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="localizationMode"
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
          `;
        }

        return html`
          <div class="localization-mode-selection">
            <h2 class="h4">${localizationWizardLabels.get('step.modes.headline')}</h2>
            ${content}
          </div>
        `;
      },
      error: (error: unknown) => this.context.wizard.renderError(localizationWizardLabels.get('step.modes.error.message'), error),
      pending: () => this.context.wizard.renderLoader()
    });
  }

  public reset(): void {
    this.setValue(null as any);
    this.context.clearStoreData('localizationMode');
  }

  public getValue(): string | null {
    return this.selectedMode;
  }

  public setValue(value: string): void {
    this.selectedMode = value;
  }

  public beforeAdvance(): void {
    this.context.setStoreData('localizationMode', this.getValue());
  }

  public getSummaryData(): SummaryItem[] {
    const selectedMode = this.context.getStoreData('localizationMode');
    if (!selectedMode || !this.task.value) {
      return [];
    }

    const mode = this.task.value.find((m: LocalizationMode) => m.key === selectedMode);
    if (!mode) {
      return [];
    }

    return [{
      label: localizationWizardLabels.get('step.modes.summary.title'),
      value: html `
        <typo3-backend-icon identifier="${mode.iconIdentifier}" size="small" class="me-1"></typo3-backend-icon>
        ${mode.label}
      `
    }];
  }

}

export default ModeStep;
