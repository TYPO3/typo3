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

import RegularEvent from '@typo3/core/event/regular-event';
import Icons from '@typo3/backend/icons';

/**
 * Module: @typo3/info/translation-status
 */
class TranslationStatus {
  constructor() {
    this.registerEvents();
  }

  private registerEvents(): void {
    new RegularEvent('click', this.toggleNewButton).delegateTo(document, 'input[type="checkbox"][data-lang]');
  }

  private async toggleNewButton(this: HTMLInputElement): Promise<void> {
    const relatedCreationButton = document.querySelector(`.t3js-language-new[data-lang="${this.dataset.lang}"]`) as HTMLAnchorElement;
    const relatedCreationButtonIcon = relatedCreationButton.querySelector('.t3js-icon') as HTMLSpanElement;
    const selectedButtons = document.querySelectorAll(`input[type="checkbox"][data-lang="${this.dataset.lang}"]:checked`);
    const actionUrl = new URL(location.origin + relatedCreationButton.dataset.editUrl);
    selectedButtons.forEach((element: HTMLInputElement): void => {
      actionUrl.searchParams.set(`cmd[pages][${element.dataset.uid}][localize]`, this.dataset.lang);
    });

    const disableCreationButton = selectedButtons.length === 0;

    relatedCreationButton.href = actionUrl.toString();
    relatedCreationButton.classList.toggle('disabled', disableCreationButton);

    const newIcon = await Icons.getIcon(relatedCreationButtonIcon.dataset.identifier, Icons.sizes.small, null, disableCreationButton ? Icons.states.disabled : Icons.states.default);
    relatedCreationButtonIcon.replaceWith(document.createRange().createContextualFragment(newIcon));
  }
}

export default new TranslationStatus();
