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
import { topWindow } from '@typo3/core/utility/top-window';

/**
 * Opens the form selected in the form content element in the form editor.
 *
 * The link points to the form rendered with the record. Selecting another
 * form saves and reloads the record, so the button is disabled until then.
 * Navigating the content frame lets FormEngine, or the contextual edit
 * sheet, ask about unsaved changes first.
 */
class EditFormControl {
  private controlElement: HTMLAnchorElement | null = null;
  private assignedFormField: HTMLSelectElement | null = null;

  constructor(controlElementId: string) {
    DocumentService.ready().then((): void => {
      this.controlElement = document.querySelector<HTMLAnchorElement>(controlElementId);
      this.assignedFormField = document.querySelector<HTMLSelectElement>(
        'select[data-formengine-input-name="' + this.controlElement.dataset.element + '"]',
      );
      this.assignedFormField?.addEventListener('change', this.updateState);
      this.controlElement.addEventListener('click', this.openFormEditor);
      this.updateState();
    });
  }

  private isSelectionRendered(): boolean {
    return this.assignedFormField?.value === this.controlElement.dataset.persistenceIdentifier;
  }

  private readonly updateState = (): void => {
    const disabled = !this.isSelectionRendered();
    this.controlElement.classList.toggle('disabled', disabled);
    this.controlElement.setAttribute('aria-disabled', disabled ? 'true' : 'false');
  };

  private readonly openFormEditor = (e: Event): void => {
    e.preventDefault();
    if (!this.isSelectionRendered()) {
      return;
    }
    topWindow.TYPO3?.Backend?.ContentContainer?.setUrl(this.controlElement.href);
  };
}

export default EditFormControl;
