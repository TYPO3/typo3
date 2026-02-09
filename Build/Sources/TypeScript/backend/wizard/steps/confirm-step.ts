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
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { Wizard } from '@typo3/backend/wizard/wizard';
import { StepSummaryEvent } from '@typo3/backend/wizard/events/step-summary-event';
import type { SummaryItem } from '@typo3/backend/wizard/steps/summary-item-interface';
import wizardLabels from '~labels/backend.wizards.general';

export class ConfirmStep implements WizardStepInterface {
  readonly key = 'confirm';
  readonly title = wizardLabels.get('step.confirmation.title');
  readonly autoAdvance = false;

  constructor(private readonly wizard: Wizard) {}

  public isComplete(): boolean {
    return true; // Always completable once we reach this step
  }

  public render(): TemplateResult {
    const event = new StepSummaryEvent(this.wizard.getStepSummaries());
    this.wizard.dispatchEvent(event);

    const summaryData = event.detail.summaryData;

    return html`
      <div class="localization-confirmation">
        <h2 class="h4">${wizardLabels.get('step.confirmation.headline')}</h2>
        <p>${wizardLabels.get('step.confirmation.description')}</p>
        <div class="table-fit table-fit-wrap">
          <table class="table table-striped">
            <tbody>
             ${summaryData.map((summaryItem: SummaryItem) => html`
               <tr>
                 <th class="col-fieldname">
                   ${summaryItem.label}
                 </th>
                 <td class="col-word-break">
                   ${summaryItem.value}
                 </td>
               </tr>
             `)}
            </tbody>
          </table>
        </div>
      </div>
    `;
  }
}

export default ConfirmStep;
