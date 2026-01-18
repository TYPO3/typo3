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
import { Task, TaskStatus } from '@lit/task';
import { lll } from '@typo3/core/lit-helper';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { LocalizationContext, LocalizationStepInterface, LocalizationStepValueInterface, LocalizationStepSummaryInterface, LocalizationLanguageRecord } from '@typo3/backend/localization/localization-wizard';

export class TargetLanguageStep implements LocalizationStepInterface, LocalizationStepValueInterface, LocalizationStepSummaryInterface {
  readonly key = 'targetLanguage';
  readonly title = lll('step.target_language.title');
  readonly autoAdvance = true;

  private readonly task: Task<[string, number], LocalizationLanguageRecord[]>;
  private hasDispatchedAutoAdvance: boolean = false;
  private selectedLanguage: number | null = null;

  constructor(private readonly context: LocalizationContext) {
    this.task = new Task(this.context.wizard, {
      task: async ([recordType, recordUid]: [string, number]): Promise<LocalizationLanguageRecord[]> => {
        try {
          const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.wizard_localization_get_targets).withQueryArguments({
            recordType: recordType,
            recordUid: recordUid,
          }).get();
          let targetLanguages: LocalizationLanguageRecord[] = await response.resolve();

          // Filter to only the predefined target language if one exists via property
          const predefinedTargetLanguage = this.context.wizard.targetLanguage;
          if (predefinedTargetLanguage != null) {
            targetLanguages = targetLanguages.filter(lang => lang.uid === predefinedTargetLanguage);
          }

          return targetLanguages;
        } catch (error) {
          console.warn('Failed to fetch target languages:', error);
          return [];
        }
      },
      args: () => [this.context.recordType, this.context.recordUid],
      autoRun: false
    });
  }

  public isComplete(): boolean {
    return this.getValue() != null;
  }

  public render(): TemplateResult {
    if (this.task.status === TaskStatus.INITIAL) {
      this.task.run();
    }

    return this.task.render({
      complete: (languages: LocalizationLanguageRecord[]) => {
        // Initialize from store if not already set
        if (this.getValue() == null) {
          const storedValue = this.context.getStoreData('targetLanguage');
          if (storedValue != null) {
            this.setValue(storedValue);
          }
        }

        let shouldAutoAdvance = false;

        // Handle auto-selection and auto-advance if no selection exists yet
        if (this.getValue() == null) {
          // Check if target language is predefined via property
          const predefinedTargetLanguage = this.context.wizard.targetLanguage;

          if (predefinedTargetLanguage != null) {
            // If target language is predefined, set it locally and auto-advance
            this.setValue(predefinedTargetLanguage);
            shouldAutoAdvance = true;
          } else if (languages.length > 0) {
            // No predefined language - auto-select first available
            this.setValue(languages[0].uid);

            // Auto-advance if there's only one option
            if (languages.length === 1) {
              shouldAutoAdvance = true;
            }
          }

          // Dispatch auto-advance if needed (only once)
          if (shouldAutoAdvance && !this.hasDispatchedAutoAdvance) {
            this.hasDispatchedAutoAdvance = true;
            this.context.dispatchAutoAdvance();
            return this.context.wizard.renderLoader('localization_wizard.loading');
          }
        }

        if (languages.length === 0) {
          return html`
            <div class="localization-language-selection">
              <h2 class="h4">${lll('step.target_language.headline')}</h2>
              <div class="text-center">
                <p>${lll('step.target_language.none_available')}</p>
              </div>
            </div>
          `;
        }

        return html`
          <div class="localization-language-selection">
            <h2 class="h4">${lll('step.target_language.headline')}</h2>
            <p>${lll('step.target_language.description')}</p>
            <div class="form-check-card-container">
              ${languages.map((language: LocalizationLanguageRecord) => html`
                <div class="form-check form-check-type-card">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="targetLanguage"
                    id="target-lang-${language.uid}"
                    value=${language.uid}
                    .checked=${live(this.getValue() === language.uid)}
                    @change=${() => this.setValue(language.uid)}
                  >
                  <label class="form-check-label" for="target-lang-${language.uid}">
                    <span class="form-check-label-header form-check-label-header-inherit">
                      <typo3-backend-icon identifier="${language.flagIcon}" size="small"></typo3-backend-icon>
                      ${language.title}
                    </span>
                  </label>
                </div>
              `)}
            </div>
          </div>
        `;
      },
      error: (error: unknown) => this.context.wizard.renderError(
        'localization_wizard.step.error.title',
        'localization_wizard.step.target_language.error.message',
        error
      ),
      pending: () => this.context.wizard.renderLoader('localization_wizard.loading')
    });
  }

  public reset(): void {
    this.setValue(null as any);
    this.context.clearStoreData('targetLanguage');
  }

  public getValue(): number | null {
    return this.selectedLanguage;
  }

  public setValue(value: number): void {
    this.selectedLanguage = value;
  }

  public beforeAdvance(): void {
    this.context.setStoreData('targetLanguage', this.getValue());
  }

  public getSummary(): TemplateResult {
    const selectedTargetLanguage = this.context.getStoreData('targetLanguage');
    if (selectedTargetLanguage == null || !this.task.value) {
      return html`${nothing}`;
    }

    const selectedLanguage = this.task.value.find((lang: LocalizationLanguageRecord) => lang.uid === selectedTargetLanguage);
    if (!selectedLanguage) {
      return html`${nothing}`;
    }

    return html`
      <tr>
        <th class="col-fieldname">
          ${lll('step.target_language.summary.title')}
        </th>
        <td class="col-word-break">
          <typo3-backend-icon identifier="${selectedLanguage.flagIcon}" size="small" class="me-1"></typo3-backend-icon>
          ${selectedLanguage.title}
        </td>
      </tr>
    `;
  }
}

export default TargetLanguageStep;
