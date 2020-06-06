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

import DocumentService = require('TYPO3/CMS/Core/DocumentService');
import FormEngine = require('TYPO3/CMS/Backend/FormEngine');

/**
 * Handles the "Add record" field control that renders a new FormEngine instance
 */
class AddRecord {
  private controlElement: HTMLElement = null;

  constructor(controlElementId: string) {
    DocumentService.ready().then((): void => {
      this.controlElement = <HTMLElement>document.querySelector(controlElementId);
      this.controlElement.addEventListener('click', this.registerClickHandler);
    });
  }

  /**
   * @param {Event} e
   */
  private registerClickHandler = (e: Event): void => {
    e.preventDefault();

    FormEngine.preventFollowLinkIfNotSaved(this.controlElement.getAttribute('href'));
  }
}

export = AddRecord;
