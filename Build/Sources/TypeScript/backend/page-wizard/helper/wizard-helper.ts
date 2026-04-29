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

import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';
import { html } from 'lit';
import Modal, { Size } from '@typo3/backend/modal';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import backendLayoutLabels from '~labels/backend.layout';
import type { PageWizardConfiguration } from '@typo3/backend/page-wizard/page-wizard-configuration';

export const openPageWizardModal = async(configuration?: PageWizardConfiguration) => {
  await topLevelModuleImport('@typo3/backend/page-wizard/page-wizard.js');

  Modal.advanced({
    title: backendLayoutLabels.get('newPage'),
    content:  html`<typo3-backend-page-wizard .configuration="${configuration}"></typo3-backend-page-wizard>`,
    severity: SeverityEnum.notice,
    size: {
      width: Size.medium,
      height: Size.large
    },
    staticBackdrop: true,
    buttons: []
  });
};


