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
import { lll } from '@typo3/core/lit-helper';
import type { LocalizationContext, LocalizationStepInterface } from '@typo3/backend/localization/localization-wizard';

export class ConfirmStep implements LocalizationStepInterface {
  readonly key = 'confirm';
  readonly title = lll('step.confirmation.title');
  readonly autoAdvance = false;

  constructor(private readonly context: LocalizationContext) {}

  public isComplete(): boolean {
    return true; // Always completable once we reach this step
  }

  public render(): TemplateResult {
    // Collect summaries from all completed steps
    const summaries = this.context.wizard.getStepSummaries();
    const recordInfo = this.context.wizard.getRecordInfo();

    return html`
      <div class="localization-confirmation">
        <h2 class="h4">${lll('step.confirmation.headline')}</h2>
        <p>${lll('step.confirmation.description')}</p>
        <div class="table-fit table-fit-wrap">
          <table class="table table-striped">
            <tbody>
              ${recordInfo ? html`
                <tr>
                  <th class="col-fieldname">
                    ${recordInfo.typeName}
                  </th>
                  <td class="col-word-break">
                    <typo3-backend-icon identifier="${recordInfo.icon}" size="small" class="me-1"></typo3-backend-icon>
                    ${recordInfo.title} <code>[${recordInfo.type}:${recordInfo.uid}]</code>
                  </td>
                </tr>
              ` : ''}
              ${summaries}
            </tbody>
          </table>
        </div>
      </div>
    `;
  }
}

export default ConfirmStep;
