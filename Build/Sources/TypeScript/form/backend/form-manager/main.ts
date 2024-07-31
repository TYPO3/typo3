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

import DocumentService from '@typo3/core/document-service';
import '@typo3/backend/input/clearable';

/**
 * Module: @typo3/form/backend/form-manager/main
 * JavaScript for form manager
 * @exports @typo3/form/backend/form-manager/main
 */
class FormManager {
  private clearableElements: NodeListOf<HTMLInputElement> = null;

  constructor() {
    DocumentService.ready().then((): void => {
      this.clearableElements = document.querySelectorAll('.t3js-clearable') as NodeListOf<HTMLInputElement>;
      this.initializeClearableElements();
    });
  }

  private initializeClearableElements(): void {
    this.clearableElements.forEach(
      (clearableField) => clearableField.clearable()
    );
  }
}

export default new FormManager();
