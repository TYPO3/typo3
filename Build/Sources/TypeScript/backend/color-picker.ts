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
import 'jquery/minicolors';

/**
 * Module: @typo3/backend/color-picker
 * contains all logic for the color picker used in FormEngine
 * @exports @typo3/backend/color-picker
 */
class ColorPicker {

  /**
   * Initialize the color picker for the given element
   */
  public initialize(element?: HTMLInputElement): void {

    if (typeof element === 'undefined') {
      // @deprecated since v12, will be removed in v13.
      console.warn('Initializing all color pickers globally has been marked as deprecated. Please pass a specific element to ColorPicker.initialize().');
      document.querySelectorAll('.t3js-color-picker').forEach((colorPicker: HTMLInputElement): void => {
        this.initialize(colorPicker);
      });
      return;
    }

    if (!(element instanceof HTMLInputElement) || element.parentElement?.classList.contains('minicolors')) {
      return;
    }

    // Initialize color picker
    ($(element) as any).minicolors({
      format: 'hex',
      position: 'bottom left',
      theme: 'bootstrap',
    });

    const hiddenElement: HTMLInputElement = element.closest('.t3js-formengine-field-item')?.querySelector('input[type="hidden"]');
    if (!hiddenElement) {
      // Early return in case we do not deal with the usual visibile+hidden field combination
      return;
    }

    // When hidden field is changed (e.g. through a value picker), trigger "paste" on the element
    hiddenElement.addEventListener('change', (): JQuery => $(element).trigger('paste'));

    // On element change, set the formatted value from minicolors
    element.addEventListener('blur', (e: Event): void => {
      e.stopImmediatePropagation();

      const target = e.target as HTMLInputElement;

      hiddenElement.value = target.value;
      if (target.value === '') {
        $(target).trigger('paste');
      }

      target.dispatchEvent(new Event('formengine.cp.change'));
    });
  }
}

export default new ColorPicker();
