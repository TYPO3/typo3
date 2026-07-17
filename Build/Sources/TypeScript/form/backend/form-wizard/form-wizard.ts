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
import '@typo3/backend/wizard/wizard';
import { customElement, property, query, state } from 'lit/decorators.js';
import SettingsStep, { type FormSettings } from '@typo3/form/backend/form-wizard/steps/settings-step';
import { html, LitElement, type PropertyValues, type TemplateResult } from 'lit';
import type { SubmissionServiceInterface } from '@typo3/backend/wizard/finisher/submission-service-interface';
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { DataStore, Wizard } from '@typo3/backend/wizard/wizard';
import formManagerLabels from '~labels/form.form_manager_javascript';
import { StepSummaryEvent } from '@typo3/backend/wizard/events/step-summary-event';
import {
  DuplicateFormSubmissionService
} from '@typo3/form/backend/form-wizard/finisher/duplicate-form-submission-service';
import ModeStep, { type MODE } from '@typo3/form/backend/form-wizard/steps/mode-step';
import { CreateFormSubmissionService } from '@typo3/form/backend/form-wizard/finisher/create-form-submission-service';
import { FormManager, type StorageAdapter } from '@typo3/form/backend/form-manager';
import { AutoAdvanceEvent } from '@typo3/backend/wizard/events/auto-advance-event';
import { StorageStep } from '@typo3/form/backend/form-wizard/steps/storage-step';

export interface FormWizardDataStore extends DataStore {
  mode?: MODE;
  storage?: StorageAdapter;
  settings?: FormSettings;
}

type FormWizardDataStoreKey = keyof FormWizardDataStore;

export interface FormWizardContext {
  wizard: Wizard;
  formManager: FormManager;
  getStoreData: <T extends FormWizardDataStoreKey>(key: T) => NoInfer<FormWizardDataStore[T]>;
  setStoreData: <T extends FormWizardDataStoreKey>(key: T, value: NoInfer<FormWizardDataStore[T]>) => void;
  clearStoreData: <T extends FormWizardDataStoreKey>(key: T) => void;
  getDataStore: () => Readonly<FormWizardDataStore>;
  dispatchAutoAdvance: () => void;
}

@customElement('typo3-backend-form-wizard')
export class FormWizard extends LitElement {
  @state() steps: WizardStepInterface[] = [];
  @state() submissionService: SubmissionServiceInterface;
  @state() errorMessage: string = null;

  @property({ type: FormManager, attribute: false }) formManager: FormManager;
  @property({ type: Object, attribute: false }) duplicateForm?: {name: string, persistenceIdentifier: string} = null;

  @query('typo3-backend-wizard') wizard!: Wizard;

  override connectedCallback() {
    super.connectedCallback();
    this.addEventListener(StepSummaryEvent.eventName, this.handleStepSummary);
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.removeEventListener(StepSummaryEvent.eventName, this.handleStepSummary);
  }

  protected override firstUpdated(_changedProperties: PropertyValues) {
    super.firstUpdated(_changedProperties);

    if (this.formManager.getAccessibleStorageAdapters().length < 1) {
      this.errorMessage = formManagerLabels.get('formManager.newFormWizard.step1.noStorages');
      return;
    }

    const context: FormWizardContext = {
      wizard: this.wizard,
      formManager: this.formManager,
      getStoreData: this.wizard.getStoreData.bind(this.wizard),
      setStoreData: this.wizard.setStoreData.bind(this.wizard),
      clearStoreData: this.wizard.clearStoreData.bind(this.wizard),
      getDataStore: this.wizard.getDataStore.bind(this.wizard),
      dispatchAutoAdvance: () => this.wizard.dispatchEvent(new AutoAdvanceEvent())
    };

    if (this.duplicateForm != null) {
      this.steps = [new StorageStep(context), new SettingsStep(context)];
      this.submissionService = new DuplicateFormSubmissionService(context, this.duplicateForm.persistenceIdentifier);
    } else {
      this.steps = [new ModeStep(context), new StorageStep(context), new SettingsStep(context)];
      this.submissionService = new CreateFormSubmissionService(context);
    }
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override render(): TemplateResult {
    if (this.errorMessage) {
      return this.wizard.renderError(this.errorMessage);
    }

    return html `
      <typo3-backend-wizard .steps="${this.steps}"
                            .submissionService="${this.submissionService}"
                            confirm-button-label="${formManagerLabels.get('formManager.newFormWizard.step1.title')}"
                            skip-summary
      ></typo3-backend-wizard>
    `;
  }

  private handleStepSummary(event: StepSummaryEvent) {
    if (this.duplicateForm === null) {
      return;
    }

    event.detail.summaryData = [
      {
        label: formManagerLabels.get('formManager.form_copied'),
        value: html `
          <typo3-backend-icon identifier="content-form" size="small" class="me-1"></typo3-backend-icon>
          ${this.duplicateForm.name}`
      },
      ...event.detail.summaryData
    ];
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-form-wizard': FormWizard;
  }
}
