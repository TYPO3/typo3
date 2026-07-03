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

import { html, LitElement, nothing, type PropertyValues, type TemplateResult } from 'lit';
import { customElement, property, state } from 'lit/decorators.js';
import { classMap } from 'lit/directives/class-map.js';
import { createRef, ref, type Ref } from 'lit/directives/ref.js';
import { KeyTypesEnum } from '@typo3/backend/enum/key-types';
import Modal from '@typo3/backend/modal';
import '@typo3/backend/element/alert-element';
import '@typo3/backend/element/icon-element';
import '@typo3/backend/element/progress-tracker-element';
import '@typo3/backend/element/spinner-element';
import type { ProgressTrackerElement } from '@typo3/backend/element/progress-tracker-element';
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { WizardStepValueInterface } from '@typo3/backend/wizard/steps/wizard-step-value-interface';
import type { WizardStepAfterRenderInterface } from '@typo3/backend/wizard/steps/wizard-step-after-render-interface';
import type {
  WizardStepSummaryInterface
} from '@typo3/backend/wizard/steps/wizard-step-summary-interface';
import ConfirmStep from '@typo3/backend/wizard/steps/confirm-step';
import FinisherStep from '@typo3/backend/wizard/steps/finisher-step';
import type { SubmissionServiceInterface } from '@typo3/backend/wizard/finisher/submission-service-interface';
import type { SummaryItem } from '@typo3/backend/wizard/steps/summary-item-interface';
import wizardLabels from '~labels/backend.wizards.general';
import { BeforeNextStepEvent } from '@typo3/backend/wizard/events/before-next-step-event';

export type DataStore = object;

type DataStoreKey = keyof DataStore;

@customElement('typo3-backend-wizard')
export class Wizard extends LitElement {
  @property({ type: Array, attribute: false }) steps: WizardStepInterface[] = [];
  @property({ type: String, attribute: 'confirm-button-label' }) confirmButtonLabel: string;
  @property({ type: Object, attribute: false }) submissionService: SubmissionServiceInterface;

  @state() protected currentStepIndex: number = 0;
  @state() protected currentStep!: WizardStepInterface;
  @state() protected dataStore: DataStore = {};
  @state() protected allSteps: WizardStepInterface[] = [];

  private progressTracker: ProgressTrackerElement | null = null;
  private readonly primaryButtonRef: Ref<HTMLButtonElement> = createRef();
  private focusPendingStep: WizardStepInterface | null = null;

  override connectedCallback(): void {
    super.connectedCallback();
    this.addEventListener('auto-advance', this.handleAutoAdvance);
    this.progressTracker = document.createElement('typo3-backend-progress-tracker');
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.removeEventListener('auto-advance', this.handleAutoAdvance);
    this.progressTracker = null;
  }

  public getStepSummaries(): SummaryItem[] {
    const summaries: SummaryItem[] = [];
    for (const step of this.allSteps) {
      if (this.hasSummary(step)) {
        const summaryData = step.getSummaryData();
        summaries.push(...summaryData);
      }
    }

    return summaries;
  }

  public getStoreData<T extends DataStoreKey>(key: T): NoInfer<DataStore[T]> {
    return this.dataStore[key] ?? null;
  }

  public setStoreData<T extends DataStoreKey>(key: T, value: NoInfer<DataStore[T]>): void {
    this.dataStore = { ...this.dataStore, [key]: value };
  }

  public getDataStore(): DataStore {
    return this.dataStore;
  }

  public clearStoreData<T extends DataStoreKey>(key: T): void {
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

  public renderLoader(message?: string): TemplateResult {
    return html`
      <div class="wizard-loader">
        <typo3-backend-spinner size="large"></typo3-backend-spinner>
        <p>${message ?? wizardLabels.get('wizard.loading')}</p>
      </div>
    `;
  }

  public renderError(message: string, error?: unknown, heading?: string): TemplateResult {

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
      ? `${message}\n\n${errorDetails}`
      : message;

    return html`
      <div class="wizard-error">
        <typo3-backend-alert
          severity="2"
          heading="${heading ?? wizardLabels.get('wizard.step.error.title')}"
          message="${fullMessage}"
          show-icon
        ></typo3-backend-alert>
      </div>
    `;
  }

  public override render(): TemplateResult {
    return html`
      <div class="wizard">
        <div class="wizard-progress">${this.renderProgressTracker()}</div>
        <div class="wizard-content" aria-live="polite" @keydown=${this.handleContentKeydown}>${this.currentStep?.render()}</div>
        <div class="wizard-actions">${this.renderWizardButtons()}</div>
      </div>
    `;
  }

  public goToNextStep(): void {
    if (this.currentStepIndex < this.allSteps.length - 1) {
      this.goToStep(this.currentStepIndex + 1);
    }
  }

  public goToPreviousStep(): void {
    if (this.currentStepIndex > 0) {
      this.goToStep(this.currentStepIndex - 1);
    }
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override async updated(_changedProperties: PropertyValues) {
    super.updated(_changedProperties);

    // init wizard if steps and submissionService are passed
    if (
      ( _changedProperties.has('steps') || _changedProperties.has('submissionService') ) &&
      this.steps?.length &&
      this.submissionService
    ) {
      this.allSteps = [
        ...this.steps,
        new ConfirmStep(this),
        new FinisherStep(this, this.submissionService),
      ];

      this.progressTracker.requestUpdate();
      if (!this.currentStep) {
        this.currentStep = this.allSteps[this.currentStepIndex];
      }
    }

    if (this.currentStep && _changedProperties.has('currentStep')) {
      // Optional per-step readiness hook (e.g. reinitialising FormEngine).
      if (this.hasAfterRender(this.currentStep)) {
        await this.currentStep.afterRender();
        this.requestUpdate();
      }
      this.focusPendingStep = this.currentStep;
    }

    await this.moveFocusIntoStep();
  }

  /**
   * Move focus into the current step's content once present. Async steps (e.g.
   * @lit/task) re-render the wizard when their data settles, so retry on every
   * update until a focus target is found — no per-step focus wiring required.
   */
  private async moveFocusIntoStep(): Promise<void> {
    const step = this.focusPendingStep;
    if (!step || step !== this.currentStep) {
      this.focusPendingStep = null; // no request, or navigated on since
      return;
    }
    await this.updateComplete;
    if (step === this.currentStep && this.focusStepContent()) {
      this.focusPendingStep = null;
    }
  }

  /**
   * Focus the selected radio, otherwise the first form control. Returns whether
   * focus settled on the step's own content; the primary-button fall back
   * returns `false` so async content can still claim focus once it renders.
   */
  private focusStepContent(): boolean {
    const content = this.renderRoot.querySelector('.wizard-content');
    if (content) {
      // Don't steal focus if the step already has a focus.
      if (content.contains(this.ownerDocument.activeElement)) {
        return true;
      }

      const target = this.findFocusTarget(content);
      if (target) {
        // autofocus makes an opening modal <dialog> pick this over the close
        // button; focus() covers later step transitions.
        target.setAttribute('autofocus', '');
        target.focus();
        return true;
      }
    }

    // No focusable content (yet): fall back to the primary button, but not if
    // focus already rests on an action button (don't steal it on re-renders).
    const actions = this.renderRoot.querySelector('.wizard-actions');
    if (!actions?.contains(this.ownerDocument.activeElement)) {
      this.primaryButtonRef.value?.focus();
    }

    return false;
  }

  /**
   * Explicit autofocus wins, then the selected radio, then the first control
   * that is enabled and visible. Shadow roots are not pierced — custom
   * elements take part by declaring autofocus or tabindex="0" on their host.
   */
  private findFocusTarget(content: Element): HTMLElement | null {
    const firstOperable = (selector: string): HTMLElement | null => {
      for (const element of content.querySelectorAll<HTMLElement>(selector)) {
        if (!element.matches(':disabled') && element.checkVisibility()) {
          return element;
        }
      }
      return null;
    };

    return firstOperable('[autofocus]')
      ?? firstOperable('input[type="radio"]:checked')
      ?? firstOperable('input:not([type="hidden"]):not([type="radio"]):not([readonly]), select, button, textarea:not([readonly]), [contenteditable]:not([contenteditable="false"]), [tabindex]:not([tabindex="-1"])');
  }

  private hasAfterRender(step: WizardStepInterface): step is WizardStepInterface & WizardStepAfterRenderInterface {
    return step && typeof (step as any).afterRender === 'function';
  }

  private hasSummary(step: WizardStepInterface): step is WizardStepInterface & WizardStepSummaryInterface {
    return 'getSummaryData' in step;
  }

  private hasValue(step: WizardStepInterface): step is WizardStepInterface & WizardStepValueInterface {
    return 'getValue' in step && 'setValue' in step && 'reset' in step;
  }

  private readonly handleAutoAdvance = (): void => {
    this.tryAutoAdvance();
  };

  /**
   * Enter inside a bare input advances the wizard, emulating a form submit.
   * Controls with their own Enter behaviour are left alone: inputs inside a
   * <form>, search inputs, checkboxes and non-input controls.
   */
  private readonly handleContentKeydown = (event: KeyboardEvent): void => {
    if (event.key !== KeyTypesEnum.ENTER) {
      return;
    }
    const target = event.target;
    if (!(target instanceof HTMLInputElement) || target.form || target.type === 'search' || target.type === 'checkbox') {
      return;
    }
    event.preventDefault();
    void this.handleNext();
  };

  private renderWizardButtons(): TemplateResult {
    return html`
      ${this.renderPreviousButton()}
      ${this.renderNextButtons()}
    `;
  }

  private renderPreviousButton(): TemplateResult {
    const isFirstStep = this.currentStepIndex === 0;
    const isLastStep = this.currentStepIndex === this.allSteps.length - 1;
    const isDisabled = isFirstStep || isLastStep;

    return html`
      <button type="button" class="${classMap({ 'btn': true, 'btn-secondary': true, 'disabled': isDisabled })}"
        @click="${this.handlePrevious}"
      >
        <typo3-backend-icon identifier="actions-arrow-start-alt" size="small" bidi></typo3-backend-icon>
        ${wizardLabels.get('wizard.buttons.previous')}
      </button>
    `;
  }

  private renderNextButtons(): TemplateResult {
    const isComplete = this.currentStep?.isComplete();
    let buttonLabel: string;
    let resetButtonHtml: TemplateResult | typeof nothing = nothing;

    if (this.currentStep?.key === 'finisher') {
      buttonLabel = wizardLabels.get('wizard.buttons.finish');

      const finisherStep: FinisherStep = this.currentStep as FinisherStep;
      if (finisherStep?.resetButtonTitle) {
        resetButtonHtml = html`
          <button type="button" class="${classMap({ 'btn': true, 'btn-secondary': true })}"
            @click="${this.handleRestart}"
          >
            <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
            ${finisherStep?.resetButtonTitle}
          </button>
         `;
      }
    } else if (this.currentStep?.key === 'confirm') {
      buttonLabel = this.confirmButtonLabel;
    } else {
      buttonLabel = wizardLabels.get('wizard.buttons.next');
    }

    return html`
      <div>
        ${resetButtonHtml}
        <button type="button" ${ref(this.primaryButtonRef)} class="${classMap({ 'btn': true, 'btn-primary': true, 'disabled': !isComplete })}"
          @click="${this.handleNext}"
        >
          ${buttonLabel}
          <typo3-backend-icon identifier="actions-arrow-end-alt" bidi size="small"></typo3-backend-icon>
        </button>
      </div>
    `;
  }

  private getCurrentStep(): WizardStepInterface {
    return this.currentStep;
  }

  /**
   * Navigate to a specific step
   * When moving forward, executes beforeAdvance hook and validates all intermediate steps are complete
   * When moving backward, resets all steps after the target step
   */
  private async goToStep(targetIndex: number): Promise<void> {
    if (targetIndex < 0 || targetIndex >= this.allSteps.length || targetIndex === this.currentStepIndex) {
      return;
    }

    if (targetIndex > this.currentStepIndex) {
      // Moving forward: validate and execute beforeAdvance for each intermediate step
      for (let i = this.currentStepIndex; i < targetIndex; i++) {
        const step = this.allSteps[i];

        if (!step.isComplete()) {
          // Stop at first incomplete step and update to it if different from current
          if (i !== this.currentStepIndex) {
            this.currentStepIndex = i;
            this.currentStep = this.allSteps[i];

            await this.updateComplete;
          }
          return;
        }

        // Execute beforeAdvance hook (may be async)
        if (step.beforeAdvance) {
          await step.beforeAdvance();
        }
      }
      const event = new BeforeNextStepEvent(this.currentStep.key, this.currentStepIndex);
      this.dispatchEvent(event);
      // If result was attached by a listener wait for result (allows dynamic steps reload)
      if (event.detail?.result) {
        const result = event.detail.result;

        if (typeof result === 'object' && typeof (result as Promise<any>).then === 'function') {
          await result;
        }

        await this.updateComplete;
      }
    } else {
      // Moving backward: reset all steps after the target
      for (let i = this.currentStepIndex; i > targetIndex; i--) {
        const step = this.allSteps[i];
        if (this.hasValue(step)) {
          step.reset();
        }
      }
    }

    // Update step state
    this.currentStepIndex = targetIndex;
    this.currentStep = this.allSteps[targetIndex];

    await this.updateComplete;
  }

  private getProgressSteps(): Array<{key: string, title: string}> {
    return this.allSteps.map(step => ({
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
    const isLastStep = this.currentStepIndex === this.allSteps.length - 1;
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
    const isLastStep = this.currentStepIndex === this.allSteps.length - 1;
    if (isLastStep) {
      if (this.currentStep.beforeAdvance) {
        await this.currentStep.beforeAdvance();
      }
      return;
    }

    this.goToNextStep();
  }

  private handleRestart(): void {
    this.dataStore = {};
    this.goToStep(0).then(() => this.updateComplete);
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-wizard': Wizard;
  }
}
