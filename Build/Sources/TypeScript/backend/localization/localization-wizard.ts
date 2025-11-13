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

import { html, LitElement, type TemplateResult } from 'lit';
import { customElement, property, state } from 'lit/decorators';
import { classMap } from 'lit/directives/class-map.js';
import { Task } from '@lit/task';
import { lll } from '@typo3/core/lit-helper';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Modal from '@typo3/backend/modal';
import '@typo3/backend/element/alert-element';
import '@typo3/backend/element/icon-element';
import '@typo3/backend/element/progress-tracker-element';
import '@typo3/backend/element/spinner-element';
import type { ProgressTrackerElement } from '@typo3/backend/element/progress-tracker-element';
import { TargetLanguageStep } from '@typo3/backend/localization/steps/target-language-step';
import { SourceLanguageStep } from '@typo3/backend/localization/steps/source-language-step';
import { ContentRecordSelectionStep } from '@typo3/backend/localization/steps/content-record-selection-step';
import { ModeStep } from '@typo3/backend/localization/steps/mode-step';
import { HandlerSelectionStep } from '@typo3/backend/localization/steps/handler-selection-step';
import { ConfirmStep } from '@typo3/backend/localization/steps/confirm-step';
import { FinisherStep } from '@typo3/backend/localization/steps/finisher-step';

export class AutoAdvanceEvent extends CustomEvent<void> {
  constructor() {
    super('auto-advance', {
      bubbles: true,
      composed: true
    });
  }
}

export interface LocalizationDataStore {
  targetLanguage?: number;
  sourceLanguage?: number;
  selectedRecordUids?: number[];
  localizationMode?: string;
  localizationHandler?: string;
}

type LocalizationDataStoreKey = keyof LocalizationDataStore;

export interface LocalizationContext {
  wizard: LocalizationWizard;
  recordType: string;
  recordUid: number;
  getStoreData: <T extends LocalizationDataStoreKey>(key: T) => NoInfer<LocalizationDataStore[T]>;
  setStoreData: <T extends LocalizationDataStoreKey>(key: T, value: NoInfer<LocalizationDataStore[T]>) => void;
  clearStoreData: <T extends LocalizationDataStoreKey>(key: T) => void;
  getDataStore: () => Readonly<LocalizationDataStore>;
  dispatchAutoAdvance: () => void;
}

export interface LocalizationStepInterface {
  readonly key: string;
  readonly title: string;
  readonly autoAdvance: boolean;
  isComplete(): boolean;
  render(): TemplateResult;
  beforeAdvance?(): void | Promise<void>;
}

export interface LocalizationStepValueInterface {
  getValue(): unknown;
  setValue(value: unknown): void;
  reset(): void;
}

export interface LocalizationStepSummaryInterface {
  getSummary(): TemplateResult;
}

export type LocalizationLanguageRecord = {
  uid: number;
  title: string;
  flagIcon: string;
};

export type RecordInfo = {
  uid: number;
  title: string;
  icon: string;
  type: string;
  typeName: string;
};


@customElement('typo3-backend-localization-wizard')
export class LocalizationWizard extends LitElement {
  @property({ type: String, attribute: 'record-type' }) recordType: string;
  @property({ type: Number, attribute: 'record-uid' }) recordUid: number;
  @property({ type: Number, attribute: 'target-language' }) targetLanguage?: number;

  @state() private currentStepIndex: number = 0;
  @state() private currentStep!: LocalizationStepInterface;
  @state() private dataStore: LocalizationDataStore = {};

  private readonly recordInfoTask = new Task(this, {
    task: async ([recordType, recordUid]: [string, number]): Promise<RecordInfo> => {
      try {
        const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.wizard_localization_get_record).withQueryArguments({
          recordType: recordType,
          recordUid: recordUid,
        }).get();
        const recordInfo: RecordInfo = await response.resolve();

        // Update modal title if we're inside a modal
        if (this.closest('typo3-backend-modal') !== null && Modal.currentModal) {
          Modal.currentModal.modalTitle = `${lll('localization_wizard.modal.title.record_prefix')} ${recordInfo.typeName}, ${recordInfo.title}`;
        }

        return recordInfo;
      } catch (error) {
        console.warn('Failed to fetch record info:', error);
        throw error;
      }
    },
    args: () => [this.recordType, this.recordUid],
  });

  private progressTracker: ProgressTrackerElement | null = null;
  private builtSteps!: LocalizationStepInterface[];

  override connectedCallback(): void {
    super.connectedCallback();
    this.addEventListener('auto-advance', this.handleAutoAdvance);
    this.builtSteps = this.buildSteps();
    this.currentStep = this.builtSteps[this.currentStepIndex];
    this.progressTracker = document.createElement('typo3-backend-progress-tracker');
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.removeEventListener('auto-advance', this.handleAutoAdvance);
    this.progressTracker = null;
  }

  public getStepSummaries(): TemplateResult[] {
    const summaries: TemplateResult[] = [];
    for (const step of this.builtSteps) {
      if (this.hasSummary(step)) {
        const summary = step.getSummary();
        summaries.push(summary);
      }
    }

    return summaries;
  }

  public getStoreData<T extends LocalizationDataStoreKey>(key: T): NoInfer<LocalizationDataStore[T]> {
    return this.dataStore[key] as LocalizationDataStore[T];
  }

  public setStoreData<T extends LocalizationDataStoreKey>(key: T, value: NoInfer<LocalizationDataStore[T]>): void {
    this.dataStore = { ...this.dataStore, [key]: value };
  }

  public getDataStore(): LocalizationDataStore {
    return this.dataStore;
  }

  public getRecordInfo(): RecordInfo | undefined {
    return this.recordInfoTask.value;
  }

  public clearStoreData<T extends LocalizationDataStoreKey>(key: T): void {
    const newDataStore = { ...this.dataStore };
    delete newDataStore[key];
    this.dataStore = newDataStore;
  }

  public tryAutoAdvance(): void {
    const currentStep = this.getCurrentStep();
    if (currentStep.autoAdvance && currentStep.isComplete()) {
      this.goToNextStep();
    }
  }

  public dismissWizard(): void {
    Modal.dismiss();
  }

  public renderLoader(label: string = 'localization_wizard.loading'): TemplateResult {
    return html`
      <div class="localization-wizard-loader">
        <typo3-backend-spinner size="large"></typo3-backend-spinner>
        <p>${lll(label)}</p>
      </div>
    `;
  }

  public renderError(heading: string, message: string, error?: unknown): TemplateResult {
    // Translate the base message if it's a translation key
    const translatedMessage = message.includes('.') ? lll(message) : message;

    // Build the error details separately (don't translate these)
    let errorDetails = '';
    if (error instanceof Error) {
      errorDetails = error.message;
    } else if (Array.isArray(error)) {
      errorDetails = error.join('\n');
    } else if (error && typeof error === 'string') {
      errorDetails = error;
    }

    // Combine message and error details
    const fullMessage = errorDetails
      ? `${translatedMessage}\n\n${errorDetails}`
      : translatedMessage;

    return html`
      <div class="localization-wizard-error">
        <typo3-backend-alert
          severity="2"
          heading="${lll(heading)}"
          message="${fullMessage}"
          show-icon
        ></typo3-backend-alert>
      </div>
    `;
  }

  public override render(): TemplateResult {
    return html`
      <div class="localization-wizard">
        <div class="localization-wizard-progress">${this.renderProgressTracker()}</div>
        <div class="localization-wizard-content" aria-live="polite">${this.currentStep.render()}</div>
        <div class="localization-wizard-actions">${this.renderWizardButtons()}</div>
      </div>
    `;
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  private hasSummary(step: LocalizationStepInterface): step is LocalizationStepInterface & LocalizationStepSummaryInterface {
    return 'getSummary' in step;
  }

  private hasValue(step: LocalizationStepInterface): step is LocalizationStepInterface & LocalizationStepValueInterface {
    return 'getValue' in step && 'setValue' in step && 'reset' in step;
  }

  private readonly handleAutoAdvance = (): void => {
    this.tryAutoAdvance();
  };

  private buildSteps(): LocalizationStepInterface[] {
    const stepConfigs: LocalizationStepInterface[] = [];
    const context: LocalizationContext = {
      wizard: this,
      recordType: this.recordType,
      recordUid: this.recordUid,
      getStoreData: this.getStoreData.bind(this),
      setStoreData: this.setStoreData.bind(this),
      clearStoreData: this.clearStoreData.bind(this),
      getDataStore: this.getDataStore.bind(this),
      dispatchAutoAdvance: () => this.dispatchEvent(new AutoAdvanceEvent())
    };

    stepConfigs.push(new TargetLanguageStep(context));
    stepConfigs.push(new SourceLanguageStep(context));

    if (this.recordType === 'pages') {
      stepConfigs.push(new ContentRecordSelectionStep(context));
    }

    stepConfigs.push(new ModeStep(context));
    stepConfigs.push(new HandlerSelectionStep(context));
    stepConfigs.push(new ConfirmStep(context));
    stepConfigs.push(new FinisherStep(context));

    return stepConfigs;
  }

  private renderWizardButtons(): TemplateResult {
    return html`
      ${this.renderPreviousButton()}
      ${this.renderNextButton()}
    `;
  }

  private renderPreviousButton(): TemplateResult {
    const isFirstStep = this.currentStepIndex === 0;
    const isLastStep = this.currentStepIndex === this.builtSteps.length - 1;
    const isDisabled = isFirstStep || isLastStep;

    return html`
      <button type="button" class="${classMap({ 'btn': true, 'btn-secondary': true, 'disabled': isDisabled })}"
        @click="${this.handlePrevious}"
      >
        <typo3-backend-icon identifier="actions-arrow-start-alt" size="small" bidi></typo3-backend-icon>
        ${lll('localization_wizard.buttons.previous')}
      </button>
    `;
  }

  private renderNextButton(): TemplateResult {
    const isComplete = this.currentStep.isComplete();

    let buttonLabel: string;
    if (this.currentStep.key === 'finisher') {
      buttonLabel = 'localization_wizard.buttons.finish';
    } else if (this.currentStep.key === 'confirm') {
      buttonLabel = 'localization_wizard.buttons.localize';
    } else {
      buttonLabel = 'localization_wizard.buttons.next';
    }

    return html`
      <button type="button" class="${classMap({ 'btn': true, 'btn-primary': true, 'disabled': !isComplete })}"
        @click="${this.handleNext}"
      >
        ${lll(buttonLabel)}
        <typo3-backend-icon identifier="actions-arrow-end-alt" bidi size="small"></typo3-backend-icon>
      </button>
    `;
  }

  private getCurrentStep(): LocalizationStepInterface {
    return this.currentStep;
  }

  /**
   * Navigate to a specific step
   * When moving forward, executes beforeAdvance hook and validates all intermediate steps are complete
   * When moving backward, resets all steps after the target step
   */
  private async gotoStep(targetIndex: number): Promise<void> {
    if (targetIndex < 0 || targetIndex >= this.builtSteps.length || targetIndex === this.currentStepIndex) {
      return;
    }

    if (targetIndex > this.currentStepIndex) {
      // Moving forward: validate and execute beforeAdvance for each intermediate step
      for (let i = this.currentStepIndex; i < targetIndex; i++) {
        const step = this.builtSteps[i];

        if (!step.isComplete()) {
          // Stop at first incomplete step and update to it if different from current
          if (i !== this.currentStepIndex) {
            this.currentStepIndex = i;
            this.currentStep = this.builtSteps[i];
            await this.updateComplete;
          }
          return;
        }

        // Execute beforeAdvance hook (may be async)
        if (step.beforeAdvance) {
          await step.beforeAdvance();
        }
      }
    } else {
      // Moving backward: reset all steps after the target
      for (let i = this.currentStepIndex; i > targetIndex; i--) {
        const step = this.builtSteps[i];
        if (this.hasValue(step)) {
          step.reset();
        }
      }
    }

    // Update step state
    this.currentStepIndex = targetIndex;
    this.currentStep = this.builtSteps[targetIndex];

    await this.updateComplete;
  }

  private goToNextStep(): void {
    if (this.currentStepIndex < this.builtSteps.length - 1) {
      this.gotoStep(this.currentStepIndex + 1);
    }
  }

  private goToPreviousStep(): void {
    if (this.currentStepIndex > 0) {
      this.gotoStep(this.currentStepIndex - 1);
    }
  }

  private getProgressSteps(): Array<{key: string, title: string}> {
    return this.builtSteps.map(step => ({
      key: step.key,
      title: step.title
    }));
  }

  private getCurrentStepIndex(): number {
    return this.currentStepIndex;
  }

  private renderProgressTracker(): TemplateResult {
    const steps = this.getProgressSteps();
    const currentStepIndex = this.getCurrentStepIndex();
    const stages = steps.map(step => step.title);
    const activeStage = currentStepIndex + 1;

    if (this.progressTracker) {
      this.progressTracker.stages = stages;
      this.progressTracker.activeStage = activeStage;
    }

    return html`${this.progressTracker}`;
  }

  private handlePrevious(): void {
    const isFirstStep = this.currentStepIndex === 0;
    const isLastStep = this.currentStepIndex === this.builtSteps.length - 1;
    if (isFirstStep || isLastStep) {
      return;
    }
    this.goToPreviousStep();
  }

  private async handleNext(): Promise<void> {
    if (!this.currentStep.isComplete()) {
      return;
    }

    // If we're on the last step, execute its beforeAdvance hook directly
    const isLastStep = this.currentStepIndex === this.builtSteps.length - 1;
    if (isLastStep) {
      if (this.currentStep.beforeAdvance) {
        await this.currentStep.beforeAdvance();
      }
      return;
    }

    this.goToNextStep();
  }

}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-localization-wizard': LocalizationWizard;
  }
}
