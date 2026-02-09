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
import { until } from 'lit/directives/until.js';
import { Task, TaskStatus } from '@lit/task';
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { Wizard } from '@typo3/backend/wizard/wizard';
import type { SubmissionServiceInterface } from '@typo3/backend/wizard/finisher/submission-service-interface';
import type { FinisherResult } from '@typo3/backend/wizard/finisher/finisher-result';
import type { FinisherInterface } from '@typo3/backend/wizard/finisher/finisher-interface';
import type { FinisherConfig } from '@typo3/backend/wizard/finisher/finisher-config';
import wizardLabels from '~labels/backend.wizards.general';


/**
 * Finisher step - executes localization task, shows success message, and provides finisher action
 * This is a terminal step with no way to go back
 */
export class FinisherStep implements WizardStepInterface {
  readonly key = 'finisher';
  readonly title = wizardLabels.get('step.finisher.title');
  readonly autoAdvance = false;

  private finisherInstance: FinisherInterface | null = null;
  private hasError = false;
  private readonly task: Task<[SubmissionServiceInterface], FinisherResult>;

  constructor(private readonly wizard: Wizard, private readonly finisher: SubmissionServiceInterface) {
    this.task = new Task(this.wizard, {
      task: async ([finisher]: [SubmissionServiceInterface]): Promise<FinisherResult> => {
        return finisher.execute();
      },
      args: () => [
        this.finisher
      ] as [SubmissionServiceInterface],
      autoRun: false
    }

    );
  }

  public isComplete(): boolean {
    // Finisher step is complete when the task has finished (success or error)
    // This allows the user to close the wizard even when there's an error
    return this.task.status === TaskStatus.COMPLETE;
  }

  public async beforeAdvance(): Promise<void> {
    // If there was an error, just dismiss the wizard
    if (this.hasError) {
      this.wizard.dismissWizard();
      return;
    }

    // Execute finisher action (redirect, reload, etc.) when user clicks "Finish"
    if (!this.finisherInstance) {
      throw new Error('Finisher instance not loaded');
    }

    // Dismiss the wizard first
    this.wizard.dismissWizard();

    // Execute the finisher action (redirect, reload, etc.)
    await this.finisherInstance.execute();
  }

  public render(): TemplateResult {
    // Run the task if it hasn't been started yet
    if (this.task.status === TaskStatus.INITIAL) {
      this.task.run();
    }

    // Use the task's render method to show loading, error, or success
    return this.task.render({
      pending: () => this.wizard.renderLoader(wizardLabels.get('wizard.status.pending.message')),
      error: (error: unknown) => {
        this.hasError = true;
        return this.wizard.renderError(wizardLabels.get('wizard.status.error.message'), error);
      },
      complete: (result: FinisherResult) => {
        // Check if localization failed
        if (result.success === false) {
          this.hasError = true;
          return this.wizard.renderError(wizardLabels.get('wizard.status.error.message'), result.errors);
        }

        // Load and render the finisher with the result data
        return this.renderFinisher(result.finisher);
      }
    }) as TemplateResult;
  }

  /**
   * Render the finisher (success message)
   */
  private renderFinisher(finisherData: FinisherConfig): TemplateResult {
    if (!this.finisherInstance) {
      // Load finisher instance asynchronously and render its result
      const finisherPromise = this.loadFinisher(finisherData).then(instance => {
        this.finisherInstance = instance;
        return instance.render();
      }).catch(error => {
        console.error('Failed to load finisher:', error);
        this.hasError = true;
        return this.wizard.renderError(wizardLabels.get('wizard.finisher.load_error.message'), error);
      });

      // Show loading while finisher loads
      return html`${until(finisherPromise, this.wizard.renderLoader(wizardLabels.get('wizard.loading_finisher')))}`;
    }

    // Render the finisher instance (it returns a Promise)
    return html`${until(this.finisherInstance.render(), nothing)}`;
  }

  /**
   * Load the finisher implementation from the module
   */
  private async loadFinisher(finisherData: FinisherConfig): Promise<FinisherInterface> {
    if (!finisherData.module) {
      throw new Error('Finisher data does not contain a module path');
    }

    const module = await import(finisherData.module);
    const FinisherClass = module.default as { new(): FinisherInterface };

    if (!FinisherClass) {
      throw new Error(`Finisher module ${finisherData.module} does not export a default class`);
    }

    const finisherInstance = new FinisherClass();
    finisherInstance.setConfig(finisherData);

    return finisherInstance;
  }
}

export default FinisherStep;
