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
import { customElement, query, state } from 'lit/decorators.js';
import { html, LitElement, type PropertyValues, type TemplateResult } from 'lit';
import type { SubmissionServiceInterface } from '@typo3/backend/wizard/finisher/submission-service-interface';
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { DataStore, Wizard } from '@typo3/backend/wizard/wizard';
import { AutoAdvanceEvent } from '@typo3/backend/wizard/events/auto-advance-event';
import PresetStep from '@typo3/dashboard/wizard/steps/preset-step';
import TitleStep from '@typo3/dashboard/wizard/steps/title-step';
import { DashboardWizardSubmissionService } from '@typo3/dashboard/wizard/finisher/dashboard-wizard-submission-service';

export interface DashboardWizardDataStore extends DataStore {
  preset?: string;
  title?: string;
}

type DashboardWizardDataStoreKey = keyof DashboardWizardDataStore;

export interface DashboardWizardContext {
  wizard: Wizard;
  getStoreData: <T extends DashboardWizardDataStoreKey>(key: T) => NoInfer<DashboardWizardDataStore[T]>;
  setStoreData: <T extends DashboardWizardDataStoreKey>(key: T, value: NoInfer<DashboardWizardDataStore[T]>) => void;
  clearStoreData: <T extends DashboardWizardDataStoreKey>(key: T) => void;
  getDataStore: () => Readonly<DashboardWizardDataStore>;
  dispatchAutoAdvance: () => void;
}

@customElement('typo3-dashboard-wizard')
export class DashboardWizard extends LitElement {
  @state() steps: WizardStepInterface[] = [];
  @state() submissionService: SubmissionServiceInterface;

  @query('typo3-backend-wizard') wizard!: Wizard;

  private context: DashboardWizardContext;

  protected override firstUpdated(_changedProperties: PropertyValues): void {
    super.firstUpdated(_changedProperties);

    this.context = {
      wizard: this.wizard,
      getStoreData: this.wizard.getStoreData.bind(this.wizard),
      setStoreData: this.wizard.setStoreData.bind(this.wizard),
      clearStoreData: this.wizard.clearStoreData.bind(this.wizard),
      getDataStore: this.wizard.getDataStore.bind(this.wizard),
      dispatchAutoAdvance: () => this.wizard.dispatchEvent(new AutoAdvanceEvent())
    };

    this.steps = [
      new PresetStep(this.context),
      new TitleStep(this.context),
    ];

    this.submissionService = new DashboardWizardSubmissionService(this.context);
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <typo3-backend-wizard .steps="${this.steps}"
                            .submissionService="${this.submissionService}"
                            skip-summary
      ></typo3-backend-wizard>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-dashboard-wizard': DashboardWizard;
  }
}
