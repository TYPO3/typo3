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

import type { SubmissionServiceInterface } from '@typo3/backend/wizard/finisher/submission-service-interface';
import type { FinisherResult } from '@typo3/backend/wizard/finisher/finisher-result';
import type { DashboardWizardContext } from '@typo3/dashboard/dashboard-wizard';
import { DashboardAddEvent } from '@typo3/dashboard/dashboard';
import labels from '~labels/dashboard.messages';

export class DashboardWizardSubmissionService implements SubmissionServiceInterface {

  constructor(private readonly context: DashboardWizardContext) {
  }

  async execute(): Promise<FinisherResult> {
    const { preset, title } = this.context.getDataStore();

    return {
      success: true,
      finisher: {
        identifier: 'event',
        module: '@typo3/backend/wizard/finisher/event-finisher.js',
        data: {
          event: new DashboardAddEvent(preset ?? '', title ?? ''),
        },
        labels: {
          successTitle: labels.get('dashboard.wizard.success.title'),
          successDescription: labels.get('dashboard.wizard.success.message'),
        }
      }
    };
  }
}
