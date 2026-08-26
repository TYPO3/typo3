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
import { customElement, property, query, state } from 'lit/decorators.js';
import { html, LitElement, type PropertyValues, type TemplateResult } from 'lit';
import type { SubmissionServiceInterface } from '@typo3/backend/wizard/finisher/submission-service-interface';
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { DataStore, Wizard } from '@typo3/backend/wizard/wizard';
import backendLayoutLabels from '~labels/backend.layout';
import wizardLabels from '~labels/backend.wizards.general';
import DoktypeStep from '@typo3/backend/page-wizard/steps/doktype-step';
import { AutoAdvanceEvent } from '@typo3/backend/wizard/events/auto-advance-event';
import PositionStep, { type PositionData } from '@typo3/backend/page-wizard/steps/position-step';
import type { BeforeNextStepEvent } from '@typo3/backend/wizard/events/before-next-step-event';
import { loadDynamicSteps } from '@typo3/backend/wizard/helper/dynamic-steps-loader';
import { PageWizardSubmissionService } from '@typo3/backend/page-wizard/finisher/page-wizard-submission-service';
import type { PageWizardConfiguration } from '@typo3/backend/page-wizard/page-wizard-configuration';

export interface PageWizardDataStore extends DataStore {
  fields?: Record<string, any>;
  doktype?: string;
  position?: PositionData;
}

type PageWizardDataStoreKey = keyof PageWizardDataStore;

export interface PageWizardContext {
  wizard: Wizard<PageWizardDataStore>;
  configuration?: PageWizardConfiguration,
  getStoreData: <T extends PageWizardDataStoreKey>(key: T) => NoInfer<PageWizardDataStore[T]>;
  setStoreData: <T extends PageWizardDataStoreKey>(key: T, value: NoInfer<PageWizardDataStore[T]>) => void;
  clearStoreData: <T extends PageWizardDataStoreKey>(key: T) => void;
  getDataStore: () => Readonly<PageWizardDataStore>;
  dispatchAutoAdvance: () => void;
}

@customElement('typo3-backend-page-wizard')
export class PageWizard extends LitElement {
  @property({ type: Object }) configuration?: PageWizardConfiguration = null;

  @state() steps: WizardStepInterface[] = [];
  @state() submissionService: SubmissionServiceInterface;

  @query('typo3-backend-wizard') wizard!: Wizard<PageWizardDataStore>;

  private fixedSteps: WizardStepInterface[] = [];

  private context: PageWizardContext;

  protected override firstUpdated(_changedProperties: PropertyValues): void {
    super.firstUpdated(_changedProperties);

    this.context = {
      wizard: this.wizard,
      configuration: this.configuration,
      getStoreData: this.wizard.getStoreData.bind(this.wizard),
      setStoreData: this.wizard.setStoreData.bind(this.wizard),
      clearStoreData: this.wizard.clearStoreData.bind(this.wizard),
      getDataStore: this.wizard.getDataStore.bind(this.wizard),
      dispatchAutoAdvance: () => this.wizard.dispatchEvent(new AutoAdvanceEvent())
    };

    this.steps = this.fixedSteps = [
      new PositionStep(this.context),
      new DoktypeStep(this.context),
    ];

    this.submissionService = new PageWizardSubmissionService(this.context);
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override render(): TemplateResult {
    return html `
      <typo3-backend-wizard .steps="${this.steps}"
                            .submissionService="${this.submissionService}"
                            confirm-button-label="${backendLayoutLabels.get('newPage')}"
                            @wizard-before-next-step="${this.loadDynamicStepsAfterDoktype}"
      ></typo3-backend-wizard>
    `;
  }

  protected loadDynamicStepsAfterDoktype(event: BeforeNextStepEvent): void {
    if (event.detail.currentStepKey !== 'doktype') {
      return;
    }

    event.detail.result = loadDynamicSteps('page_wizard', this.context).then(dynamicSteps => {
      this.steps = [
        ...this.fixedSteps,
        ...dynamicSteps
      ];
    }).catch(error => {
      this.wizard.renderError(wizardLabels.get('wizard.status.error.message'), error);
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-page-wizard': PageWizard;
  }
}
