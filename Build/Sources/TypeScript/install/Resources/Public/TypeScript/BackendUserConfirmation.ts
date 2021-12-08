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

import DocumentService from 'TYPO3/CMS/Core/DocumentService';

class BackendUserConfirmation {

  constructor() {
    DocumentService.ready().then((): void => this.addFocusToFormInput());
  }

  private addFocusToFormInput(): void {
    const confirmationPasswordField: HTMLElement = document.getElementById('confirmationPassword');
    if (confirmationPasswordField !== null) {
      confirmationPasswordField.focus();
    }
  }

}

export default new BackendUserConfirmation();
