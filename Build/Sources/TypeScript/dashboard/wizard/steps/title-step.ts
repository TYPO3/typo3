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
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { WizardStepValueInterface } from '@typo3/backend/wizard/steps/wizard-step-value-interface';
import type { WizardStepSummaryInterface } from '@typo3/backend/wizard/steps/wizard-step-summary-interface';
import type { SummaryItem } from '@typo3/backend/wizard/steps/summary-item-interface';
import type { DashboardWizardContext } from '@typo3/dashboard/dashboard-wizard';
import labels from '~labels/dashboard.messages';

export class TitleStep implements WizardStepInterface, WizardStepValueInterface, WizardStepSummaryInterface {
  readonly key = 'title';
  readonly title = labels.get('dashboard.wizard.title.title');
  readonly autoAdvance = false;

  private titleValue: string = '';

  constructor(private readonly context: DashboardWizardContext) {
  }

  public isComplete(): boolean {
    return this.getValue().trim() !== '';
  }

  public render(): TemplateResult {
    if (this.titleValue === '') {
      this.titleValue = this.context.getStoreData(this.key) ?? '';
    }

    return html`
      <div class="dashboard-wizard-title">
        <div class="form-group">
          <label class="form-label" for="dashboard-wizard-title-input">${labels.get('dashboard.title')}</label>
          <input
            type="text"
            id="dashboard-wizard-title-input"
            class="form-control"
            required="required"
            .value="${live(this.titleValue)}"
            @input="${(event: InputEvent) => this.setValue((event.target as HTMLInputElement).value)}"
          >
        </div>
      </div>
    `;
  }

  public reset(): void {
    this.titleValue = '';
    this.context.clearStoreData(this.key);
  }

  public getValue(): string {
    return this.titleValue;
  }

  public setValue(value: string): void {
    this.titleValue = value;
    this.context.wizard.requestUpdate();
  }

  public beforeAdvance(): void {
    this.context.setStoreData(this.key, this.getValue());
  }

  public getSummaryData(): SummaryItem[] {
    const title = this.context.getStoreData(this.key);
    if (!title) {
      return [];
    }

    return [{
      label: this.title,
      value: title
    }];
  }
}

export default TitleStep;
