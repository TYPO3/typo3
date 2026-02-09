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

import { type DataStore, type Wizard } from '@typo3/backend/wizard/wizard';
import { customElement, property, query, state } from 'lit/decorators.js';
import { TargetLanguageStep } from '@typo3/backend/localization/steps/target-language-step';
import { SourceLanguageStep } from '@typo3/backend/localization/steps/source-language-step';
import { ContentRecordSelectionStep } from '@typo3/backend/localization/steps/content-record-selection-step';
import { ModeStep } from '@typo3/backend/localization/steps/mode-step';
import { HandlerSelectionStep } from '@typo3/backend/localization/steps/handler-selection-step';
import { Task } from '@lit/task';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Modal from '@typo3/backend/modal';
import { html, LitElement, type PropertyValues, type TemplateResult } from 'lit';
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import { AutoAdvanceEvent } from '@typo3/backend/wizard/events/auto-advance-event';
import { StepSummaryEvent } from '@typo3/backend/wizard/events/step-summary-event';
import { LocalizationSubmissionService } from '@typo3/backend/localization/finisher/localization-submission-service';
import type { SubmissionServiceInterface } from '@typo3/backend/wizard/finisher/submission-service-interface';
import localizationWizardLabels from '~labels/backend.wizards.localization';

export type RecordInfo = {
  uid: number;
  title: string;
  icon: string;
  type: string;
  typeName: string;
};

export interface LocalizationDataStore extends DataStore {
  targetLanguage?: number;
  sourceLanguage?: number;
  selectedRecordUids?: number[];
  localizationMode?: string;
  localizationHandler?: string;
}

type LocalizationDataStoreKey = keyof LocalizationDataStore;

export interface LocalizationContext {
  wizard: Wizard;
  targetLanguage: number;
  recordType: string;
  recordUid: number;
  recordInfo: RecordInfo;
  getStoreData: <T extends LocalizationDataStoreKey>(key: T) => NoInfer<LocalizationDataStore[T]>;
  setStoreData: <T extends LocalizationDataStoreKey>(key: T, value: NoInfer<LocalizationDataStore[T]>) => void;
  clearStoreData: <T extends LocalizationDataStoreKey>(key: T) => void;
  getDataStore: () => Readonly<LocalizationDataStore>;
  dispatchAutoAdvance: () => void;
}

export type LocalizationLanguageRecord = {
  uid: number;
  title: string;
  flagIcon: string;
};

@customElement('typo3-backend-localization-wizard')
export class LocalizationWizard extends LitElement {
  @property({ type: String, attribute: 'record-type' }) recordType: string;
  @property({ type: Number, attribute: 'record-uid' }) recordUid: number;
  @property({ type: Number, attribute: 'target-language' }) targetLanguage?: number;

  @query('typo3-backend-wizard') wizard!: Wizard;

  @state() steps: WizardStepInterface[] = [];
  @state() submissionService: SubmissionServiceInterface;

  protected readonly recordInfoTask = new Task(this, {
    task: async ([recordType, recordUid]: [string, number]): Promise<RecordInfo> => {
      try {
        const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.wizard_localization_get_record).withQueryArguments({
          recordType: recordType,
          recordUid: recordUid,
        }).get();
        const recordInfo: RecordInfo = await response.resolve();

        // Update modal title if we're inside a modal
        if (this.closest('typo3-backend-modal') !== null && Modal.currentModal) {
          Modal.currentModal.modalTitle = `${localizationWizardLabels.get('localization_wizard.modal.title.record_prefix')} ${recordInfo.typeName}, ${recordInfo.title}`;
        }

        return recordInfo;
      } catch (error) {
        console.warn('Failed to fetch record info:', error);
        throw error;
      }
    },
    args: () => [this.recordType, this.recordUid],
  });

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

    const context: LocalizationContext = {
      wizard: this.wizard,
      recordType: this.recordType,
      recordUid: this.recordUid,
      recordInfo: this.recordInfoTask.value,
      targetLanguage: this.targetLanguage,
      getStoreData: this.wizard.getStoreData.bind(this.wizard),
      setStoreData: this.wizard.setStoreData.bind(this.wizard),
      clearStoreData: this.wizard.clearStoreData.bind(this.wizard),
      getDataStore: this.wizard.getDataStore.bind(this.wizard),
      dispatchAutoAdvance: () => this.wizard.dispatchEvent(new AutoAdvanceEvent())
    };
    this.submissionService = new LocalizationSubmissionService(context);

    this.steps = [
      new TargetLanguageStep(context),
      new SourceLanguageStep(context),
      ...(this.recordType === 'pages' ? [new ContentRecordSelectionStep(context)] : []),
      new ModeStep(context),
      new HandlerSelectionStep(context),
    ];
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override render(): TemplateResult {
    return html `
      <typo3-backend-wizard .steps="${this.steps}"
                            .submissionService="${this.submissionService}"
                            confirm-button-label="${localizationWizardLabels.get('localization_wizard.buttons.localize')}"
      ></typo3-backend-wizard>
    `;
  }

  private handleStepSummary(event: StepSummaryEvent) {
    const recordInfo = this.recordInfoTask.value;
    event.detail.summaryData = [
      {
        label: recordInfo.typeName,
        value: html `
          <typo3-backend-icon identifier="${recordInfo.icon}" size="small" class="me-1"></typo3-backend-icon>
          ${recordInfo.title} <code>[${recordInfo.type}:${recordInfo.uid}]</code>`
      },
      ...event.detail.summaryData
    ];
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-localization-wizard': LocalizationWizard;
  }
}
