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
import FormEngine from '@typo3/backend/form-engine';

/**
 * Client-side logic to toggle required state of "shortcut" based on "shortcut_mode".
 *
 * @internal
 */
class ShortcutValidation {
  constructor() {
    DocumentService.ready().then((): void => {
      this.run();
    });
  }

  private run(): void {
    const shortcut = document.querySelector<HTMLInputElement>('[name$="[shortcut]"]');
    const shortcut_mode = document.querySelector<HTMLInputElement>('[name$="[shortcut_mode]"]');
    const form = shortcut?.closest<HTMLFormElement>('form');

    if (!shortcut || !shortcut_mode || !form) {
      return;
    }

    shortcut_mode.addEventListener('change', () => {
      this.apply(shortcut, form, shortcut_mode);
    });
  }

  private async apply(shortcut: HTMLInputElement, form: HTMLFormElement, shortcut_mode: HTMLInputElement): Promise<void> {
    const isRequired = parseInt(shortcut_mode.value, 10) === 0;
    const rules = JSON.parse(shortcut.dataset.formengineValidationRules || '[]');
    const existingRequiredIndex = rules.findIndex((r: any) => r.type === 'required');

    if (isRequired && existingRequiredIndex === -1) {
      rules.push({ type: 'required' });
    } else if (!isRequired && existingRequiredIndex !== -1) {
      rules.splice(existingRequiredIndex, 1);
    }

    shortcut.dataset.formengineValidationRules = JSON.stringify(rules);
    FormEngine.reinitialize();
    FormEngine.Validation.initializeInputFields();
    FormEngine.Validation.validate(form);
  }
}

export default new ShortcutValidation();
