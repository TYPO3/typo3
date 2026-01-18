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

export class SourceLanguageStep implements LocalizationStepInterface, LocalizationStepValueInterface, LocalizationStepSummaryInterface {
  readonly key = 'sourceLanguage';
  readonly title = lll('step.source_language.title');
  readonly autoAdvance = true;

  private readonly task: Task<[string, number, number | null], LocalizationLanguageRecord[]>;
  private hasDispatchedAutoAdvance: boolean = false;
  private selectedLanguage: number | null = null;

  constructor(private readonly context: LocalizationContext) {
    this.task = new Task(this.context.wizard, {
      task: async ([recordType, recordUid, targetLanguageId]: [string, number, number | null]): Promise<LocalizationLanguageRecord[]> => {
        if (targetLanguageId == null) {
          return [];
        }

        try {
          const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.wizard_localization_get_sources).withQueryArguments({
            recordType: recordType,
            recordUid: recordUid,
            targetLanguage: targetLanguageId,
          }).get();
          const sourceLanguages: LocalizationLanguageRecord[] = await response.resolve();

          return sourceLanguages;
        } catch (error) {
          console.warn('Failed to fetch source languages:', error);
          return [];
        }
      },
      args: () => [this.context.recordType, this.context.recordUid, this.context.getStoreData('targetLanguage')],
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
          const storedValue = this.context.getStoreData('sourceLanguage');
          if (storedValue != null) {
            this.setValue(storedValue);
          }
        }

        let shouldAutoAdvance = false;

        // Auto-select preferred option if none selected
        if (this.getValue() == null && languages.length > 0) {
          // Prefer UID 0 (default language) if available, otherwise take first available
          const preferredLanguage = languages.find(lang => lang.uid === 0) || languages[0];
          this.setValue(preferredLanguage.uid);

          // Only auto-advance if there's only one option
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

        if (languages.length === 0) {
          return html`
            <div class="localization-language-selection">
              <h2 class="h4">${lll('step.source_language.headline')}</h2>
              <div class="text-center">
                <p>${lll('step.source_language.none_available')}</p>
              </div>
            </div>
          `;
        }

        return html`
          <div class="localization-language-selection">
            <h2 class="h4">${lll('step.source_language.headline')}</h2>
            <p>${lll('step.source_language.description')}</p>
            <div class="form-check-card-container">
              ${languages.map((language: LocalizationLanguageRecord) => html`
                <div class="form-check form-check-type-card">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="sourceLanguage"
                    id="source-lang-${language.uid}"
                    value=${language.uid}
                    .checked=${live(this.getValue() === language.uid)}
                    @change=${() => this.setValue(language.uid)}
                  >
                  <label class="form-check-label" for="source-lang-${language.uid}">
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
        'localization_wizard.step.source_language.error.message',
        error
      ),
      pending: () => this.context.wizard.renderLoader('localization_wizard.loading')
    });
  }

  public reset(): void {
    this.setValue(null as any);
    this.context.clearStoreData('sourceLanguage');
  }

  public getValue(): number | null {
    return this.selectedLanguage;
  }

  public setValue(value: number): void {
    this.selectedLanguage = value;
  }

  public beforeAdvance(): void {
    this.context.setStoreData('sourceLanguage', this.getValue());
  }

  public getSummary(): TemplateResult {
    const selectedSourceLanguage = this.context.getStoreData('sourceLanguage');
    if (selectedSourceLanguage == null || !this.task.value) {
      return html`${nothing}`;
    }

    const selectedLanguage = this.task.value.find((lang: LocalizationLanguageRecord) => lang.uid === selectedSourceLanguage);
    if (!selectedLanguage) {
      return html`${nothing}`;
    }

    return html`
      <tr>
        <th class="col-fieldname">
          ${lll('step.source_language.summary.title')}
        </th>
        <td class="col-word-break">
          <typo3-backend-icon identifier="${selectedLanguage.flagIcon}" size="small" class="me-1"></typo3-backend-icon>
          ${selectedLanguage.title}
        </td>
      </tr>
    `;
  }

}

export default SourceLanguageStep;
