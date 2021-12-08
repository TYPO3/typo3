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

import $ from 'jquery';

/**
 * Module: TYPO3/CMS/Info/TranslationStatus
 */
class TranslationStatus {
  constructor() {
    this.registerEvents();
  }

  private registerEvents(): void {
    $('input[type="checkbox"][data-lang]').on('change', this.toggleNewButton);
  }

  /**
   * @param {JQueryEventObject} e
   */
  private toggleNewButton(e: JQueryEventObject): void {
    const $me = $(e.currentTarget);
    const languageId = parseInt($me.data('lang'), 10);
    const $newButton = $('.t3js-language-new-' + languageId);
    const $selected = $('input[type="checkbox"][data-lang="' + languageId + '"]:checked');

    const additionalArguments: string[] = [];
    $selected.each((index: number, element: Element): void => {
      additionalArguments.push('cmd[pages][' + (<HTMLInputElement>element).dataset.uid + '][localize]=' + languageId);
    });
    const updatedHref = $newButton.data('editUrl') + '&' + additionalArguments.join('&');
    $newButton.attr('href', updatedHref);
    $newButton.toggleClass('disabled', $selected.length === 0);
  }
}

export default new TranslationStatus();
