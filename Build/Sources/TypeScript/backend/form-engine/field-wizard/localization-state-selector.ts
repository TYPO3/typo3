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
import RegularEvent from '@typo3/core/event/regular-event';

enum States {
  CUSTOM = 'custom',
}

class LocalizationStateSelector {
  constructor(fieldName: string) {
    DocumentService.ready().then((): void => {
      this.registerEventHandler(fieldName);
    });
  }

  /**
   * @param {string} fieldName
   */
  private registerEventHandler(fieldName: string): void {
    new RegularEvent('change', (e: Event): void => {
      const target = e.target as HTMLInputElement;
      const input: HTMLInputElement = target.closest('.t3js-formengine-field-item')?.querySelector('[data-formengine-input-name]');
      if (!input) {
        return;
      }

      const lastState = input.dataset.lastL10nState || false;
      const currentState = target.value;

      if (lastState && currentState === lastState) {
        return;
      }

      if (currentState === States.CUSTOM) {
        if (lastState) {
          target.dataset.originalLanguageValue = input.value;
        }
        input.disabled = false;
      } else {
        if (lastState === States.CUSTOM) {
          (target.closest('.t3js-l10n-state-container')
            .querySelector('.t3js-l10n-state-custom') as HTMLElement)
            .dataset.originalLanguageValue = input.value;
        }
        input.disabled = true;
      }

      input.value = target.dataset.originalLanguageValue;
      input.dispatchEvent(new Event('change'));
      input.dataset.lastL10nState = target.value;
    }).delegateTo(document, '.t3js-l10n-state-container input[type="radio"][name="' + fieldName + '"]');
  }
}

export default LocalizationStateSelector;
