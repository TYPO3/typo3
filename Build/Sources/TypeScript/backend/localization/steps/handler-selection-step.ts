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
import type { LocalizationContext, LocalizationStepInterface, LocalizationStepValueInterface, LocalizationStepSummaryInterface } from '../localization-wizard';

export type LocalizationHandler = {
  identifier: string;
  label: string;
  description: string;
  iconIdentifier: string;
};

export class HandlerSelectionStep implements LocalizationStepInterface, LocalizationStepValueInterface, LocalizationStepSummaryInterface {
  public readonly key = 'handler';
  public readonly title = lll('step.handler_selection.title');
  public readonly autoAdvance = true;

  private readonly task: Task<[string, number, number | null, number | null, string | null], LocalizationHandler[]>;
  private hasDispatchedAutoAdvance: boolean = false;
  private selectedHandler: string | null = null;

  constructor(private readonly context: LocalizationContext) {
    this.task = new Task(this.context.wizard, {
      task: async ([recordType, recordUid, sourceLanguage, targetLanguage, mode]: [string, number, number | null, number | null, string | null]): Promise<LocalizationHandler[]> => {
        if (sourceLanguage == null || targetLanguage == null || mode == null) {
          return [];
        }

        try {
          const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.wizard_localization_get_handlers)
            .withQueryArguments({
              recordType: recordType,
              recordUid: recordUid,
              sourceLanguage: sourceLanguage,
              targetLanguage: targetLanguage,
              mode: mode,
            })
            .get();
          const handlers: LocalizationHandler[] = await response.resolve();

          return handlers;
        } catch (error) {
          console.warn('Failed to fetch handlers:', error);
          return [];
        }
      },
      args: () => [this.context.recordType, this.context.recordUid, this.context.getStoreData('sourceLanguage'), this.context.getStoreData('targetLanguage'), this.context.getStoreData('localizationMode')],
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
      complete: (handlers: LocalizationHandler[]) => {
        // Initialize from store if not already set
        if (this.getValue() == null) {
          const storedValue = this.context.getStoreData('localizationHandler');
          if (storedValue != null) {
            this.setValue(storedValue);
          }
        }

        let shouldAutoAdvance = false;

        // Handle auto-selection for handler selection step
        if (this.getValue() == null && handlers.length > 0) {
          this.setValue(handlers[0].identifier);

          // Only auto-advance if there's only one option
          if (handlers.length === 1) {
            shouldAutoAdvance = true;
          }
        }

        // Dispatch auto-advance if needed (only once)
        if (shouldAutoAdvance && !this.hasDispatchedAutoAdvance) {
          this.hasDispatchedAutoAdvance = true;
          this.context.dispatchAutoAdvance();
          return this.context.wizard.renderLoader('localization_wizard.loading');
        }

        if (handlers.length === 0) {
          return html`
            <div class="alert alert-warning">
              <p>${lll('step.handler_selection.no_handlers')}</p>
            </div>
          `;
        }

        return html`
          <div class="localization-handler-selection">
            <h2 class="h4">${lll('step.handler_selection.headline')}</h2>
            <p>${lll('step.handler_selection.description')}</p>
            <div class="form-check-card-container">
              ${handlers.map((handler: LocalizationHandler) => this.renderHandlerOption(handler))}
            </div>
          </div>
        `;
      },
      error: (error: unknown) => this.context.wizard.renderError(
        'localization_wizard.step.error.title',
        'step.handler_selection.error',
        error
      ),
      pending: () => this.context.wizard.renderLoader('localization_wizard.loading')
    });
  }

  public reset(): void {
    this.setValue(null as any);
    this.context.clearStoreData('localizationHandler');
  }

  public getValue(): string | null {
    return this.selectedHandler;
  }

  public setValue(value: string): void {
    this.selectedHandler = value;
  }

  public beforeAdvance(): void {
    this.context.setStoreData('localizationHandler', this.getValue());
  }

  public getSummary(): TemplateResult {
    const selectedHandler = this.context.getStoreData('localizationHandler');
    if (selectedHandler == null || !this.task.value) {
      return html`${nothing}`;
    }

    const handler = this.task.value.find((h: LocalizationHandler) => h.identifier === selectedHandler);
    if (!handler) {
      return html`${nothing}`;
    }

    return html`
      <tr>
        <th class="col-fieldname">
          ${lll('step.handler_selection.summary_label')}
        </th>
        <td class="col-word-break">
          <typo3-backend-icon identifier="${handler.iconIdentifier}" size="small" class="me-1"></typo3-backend-icon>
          ${handler.label}
        </td>
      </tr>
    `;
  }

  private renderHandlerOption(handler: LocalizationHandler): TemplateResult {
    const isSelected = this.getValue() === handler.identifier;

    return html`
      <div class="form-check form-check-type-card">
        <input
          class="form-check-input"
          type="radio"
          name="localization-handler"
          id="handler-${handler.identifier}"
          value=${handler.identifier}
          .checked=${live(isSelected)}
          @change=${() => this.setValue(handler.identifier)}
        />
        <label class="form-check-label" for="handler-${handler.identifier}">
          <span class="form-check-label-header">
            <typo3-backend-icon identifier="${handler.iconIdentifier}" size="medium"></typo3-backend-icon>
            ${handler.label}
          </span>
          <span class="form-check-label-body">
            ${handler.description}
          </span>
        </label>
      </div>
    `;
  }

}
