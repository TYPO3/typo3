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
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import formManagerLabels from '~labels/form.form_manager_javascript';
import type { FormWizardContext } from '@typo3/form/backend/form-wizard/form-wizard';

export class CreateFormSubmissionService implements SubmissionServiceInterface {

  constructor(private readonly context: FormWizardContext) {
  }

  async execute(): Promise<FinisherResult> {
    const dataStore = this.context.getDataStore();
    const selectedTemplate = dataStore.template;
    if (!selectedTemplate) {
      throw new Error('No form template selected');
    }
    const requestUrl = this.context.formManager.getAjaxEndpoint('create');
    const response = await new AjaxRequest(requestUrl)
      .post({
        formName: dataStore.settings.formName,
        templatePath: selectedTemplate.templatePath,
        prototypeName: selectedTemplate.prototypeIdentifier,
        storage: dataStore.storage.typeIdentifier,
        storageLocation: dataStore.settings.storageLocation
      });
    const data: {status: string, url?: string, message?: ''} = await response.resolve();

    if (data?.status === 'success' ) {
      return {
        success: true,
        finisher: {
          identifier: 'redirect',
          module: '@typo3/backend/wizard/finisher/redirect-finisher.js',
          data: {
            url: data.url,
          },
          labels: {
            successTitle: formManagerLabels.get('formManager.finisher.redirect.success.title'),
            successDescription: formManagerLabels.get('formManager.finisher.redirect.success.description'),
          }
        }
      };
    }
    return {
      success: false,
      errors: [data?.message]
    };
  }
}
