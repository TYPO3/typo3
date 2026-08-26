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
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { WizardStepValueInterface } from '@typo3/backend/wizard/steps/wizard-step-value-interface';
import type { WizardStepSummaryInterface } from '@typo3/backend/wizard/steps/wizard-step-summary-interface';
import type { SummaryItem } from '@typo3/backend/wizard/steps/summary-item-interface';
import labels from '~labels/backend.wizards.page';
import corePagesLabels from '~labels/core.db.pages';
import coreLabels from '~labels/core.core';
import type { PageWizardContext } from '@typo3/backend/page-wizard/page-wizard';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { SeverityEnum } from '@typo3/backend/enum/severity';

export type Doktype = {
  value: string;
  label: string;
  description: string|null
  icon: string;
};

export type DoktypeGroup = {
  label: string;
  items: Doktype[];
};

export class DoktypeStep implements WizardStepInterface, WizardStepValueInterface, WizardStepSummaryInterface {
  readonly key = 'doktype';
  readonly title = corePagesLabels.get('doktype');
  readonly autoAdvance = true;
  private task: Task<[], Doktype[]>;

  private selectedDoktype: string | null = null;
  private searchTerm: string = '';
  private hasDispatchedAutoAdvance: boolean = false;

  constructor(private readonly context: PageWizardContext) {
    this.initDoktypesTask();
  }

  public isComplete(): boolean {
    return this.getValue() !== null;
  }

  public render(): TemplateResult {
    if (this.task.status === TaskStatus.INITIAL) {
      this.task.run();
    }

    return this.task.render({
      complete: (doktypes: Doktype[]) => {
        let shouldAutoAdvance = false;
        let warning: TemplateResult | typeof nothing = nothing;

        // Initialize from store if not already set
        if (this.getValue() === null) {
          const storedValue = this.context.getStoreData(this.key);
          const predefinedDoktype = this.context?.configuration?.doktype ?? null;
          const selectableDoktypes = doktypes.filter(doktype => doktype.value !== '--div--');
          if (storedValue != null) {
            this.setValue(storedValue);
          } else if (predefinedDoktype !== null) {
            // select predefined doktype; auto-advance
            if (selectableDoktypes.some(doktype => doktype.value === (predefinedDoktype))) {
              this.setValue(predefinedDoktype);
              shouldAutoAdvance = true;
            } else {
              // Preselected doktype is invalid: show a warning and require the user to select a different doktype
              warning = html `
                <typo3-backend-alert
                  severity="${SeverityEnum.warning}"
                  heading="${labels.get('step.doktype.not_allowed_doktype.title')}"
                  message="${labels.get('step.doktype.not_allowed_doktype.message')}"
                  show-icon
                ></typo3-backend-alert>`;
            }
          } else if (selectableDoktypes.length > 0) {
            // Preselect first doktype; auto-advance if it's the only one
            const [firstDoktype] = selectableDoktypes;
            this.setValue(firstDoktype.value);
            shouldAutoAdvance = selectableDoktypes.length === 1;
          }
        }

        if (shouldAutoAdvance && !this.hasDispatchedAutoAdvance) {
          this.hasDispatchedAutoAdvance = true;
          this.context.dispatchAutoAdvance();
          return this.context.wizard.renderLoader();
        }

        const filteredDoktypes = doktypes
          .filter((doktype) => doktype.value === '--div--' || doktype.label.toLowerCase().includes(this.searchTerm.toLowerCase()));

        const groups: DoktypeGroup[] = [];
        let currentGroup: DoktypeGroup = { label: '', items: [] };

        filteredDoktypes.forEach((doktype) => {
          if (doktype.value === '--div--') {
            if (currentGroup.items.length > 0) {
              groups.push(currentGroup);
            }
            currentGroup = { label: doktype.label, items: [] };
          } else {
            currentGroup.items.push(doktype);
          }
        });

        if (currentGroup.items.length > 0) {
          groups.push(currentGroup);
        }

        // Filter out empty groups
        const finalGroups = groups.filter(group => group.items.length > 0);

        return html`
          ${warning}
          <div class="doktype-selection">
            <h2 class="h4">${labels.get('step.doktype.headline')}</h2>
            <div class="doktype-selection__search">
              <div class="input-group mb-4">
                <label for="doktype-search" class="visually-hidden">
                  ${coreLabels.get('labels.label.searchString')}
                </label>
                <input
                  type="search"
                  autocomplete="off"
                  id="doktype-search"
                  class="form-control form-control-sm search-input"
                  value="${this.searchTerm}"
                  placeholder="${coreLabels.get('tree.searchTermInfo')}"
                  @input=${(event: InputEvent) => this.handleSearch(event)}
                >
              </div>
            </div>
            ${finalGroups.length == 0 ? html`<div role="alert" class="alert alert-info">${labels.get('step.doktype.filter.noResults')}</div>` : nothing}
            <div class="form-check-card-container">
              ${finalGroups.map((group) => html`
                ${group.label ? html`<h3 class="form-check-card-container-headline">${group.label}</h3>` : nothing}
                ${group.items.map((doktype) => html`
                  <div class="form-check form-check-type-card">
                    <input
                      class="form-check-input"
                      type="radio"
                      name="doktype"
                      id="mode-${doktype.value}"
                      value=${doktype.value}
                      .checked=${live(this.getValue() === doktype.value)}
                      @change=${() => this.setValue(doktype.value)}
                    >
                    <label class="form-check-label" for="mode-${doktype.value}">
                      <span class="form-check-label-header form-check-label-header-inherit">
                        <typo3-backend-icon identifier="${doktype.icon}" size="small"></typo3-backend-icon>
                        ${doktype.label}
                      </span>
                    </label>
                  </div>
                `)}
              `)}
            </div>
          </div>
        `;
      },
      error: (error: unknown) => this.context.wizard.renderError(labels.get('step.doktype.load_error'), error),
      pending: () => this.context.wizard.renderLoader()
    });
  }

  public reset(): void {
    this.setValue(null as any);
    this.context.clearStoreData(this.key);
    this.initDoktypesTask();
  }

  public getValue(): string {
    return this.selectedDoktype;
  }

  public setValue(value: string): void {
    this.selectedDoktype = value;
    this.context.wizard.requestUpdate();
  }

  public beforeAdvance(): void {
    this.context.setStoreData(this.key, this.getValue());
  }

  public getSummaryData(): SummaryItem[] {
    const selectedDoktypeKey = this.context.getStoreData(this.key);
    if (!selectedDoktypeKey || !this.task.value) {
      return [];
    }

    const selectedDoktype = this.task.value.find((doktype) => doktype.value === selectedDoktypeKey);
    if (!selectedDoktype) {
      return [];
    }

    return [{
      label: this.title,
      value: html `
        <typo3-backend-icon identifier="${selectedDoktype.icon}" size="small" class="me-1"></typo3-backend-icon>
        ${selectedDoktype.label}
      `
    }];
  }

  private handleSearch(event: InputEvent): void {
    this.searchTerm = (event.target as HTMLInputElement).value;
    this.context.wizard.requestUpdate();
  }

  private initDoktypesTask(): void {
    this.task = new Task(this.context.wizard, {
      task: async (): Promise<Doktype[]> => {
        const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.wizard_page_get_doktypes)
          .withQueryArguments({ data: this.context.getDataStore() })
          .get();

        return await response.resolve();
      },
      autoRun: false
    });
  }
}

export default DoktypeStep;
