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

import Alwan from 'alwan';

/**
 * Module: @typo3/backend/color-picker
 * contains all logic for the color picker used in FormEngine
 * @exports @typo3/backend/color-picker
 */
class ColorPicker {

  /**
   * Initialize the color picker for the given element
   */
  public initialize(element: HTMLInputElement): void {
    if (element.parentElement?.classList.contains('t3js-colorpicker-initialized')) {
      return;
    }

    const alwan = new Alwan(element, {
      position: 'bottom-start',
      format: 'hex',
      opacity: false,
      preset: false,
      color: element.value,
      swatches: [], // @todo: finally support color swatches in future
    });
    element.classList.add('t3js-colorpicker-initialized');

    const hiddenElement: HTMLInputElement = element.closest('.t3js-formengine-field-item')?.querySelector('input[type="hidden"]');
    if (!hiddenElement) {
      // Early return in case we do not deal with the usual visibile+hidden field combination
      return;
    }

    // When hidden field is changed (e.g. through a value picker), trigger "paste" on the element
    hiddenElement.addEventListener('change', (e: Event): void => {
      alwan.setColor((e.target as HTMLInputElement).value);
    });

    // On element change, set the formatted value from alwan
    alwan.on('color', (e: Alwan.alwanEvent): void => {
      element.value = e.hex;
      hiddenElement.value = e.hex;
      element.dispatchEvent(new Event('blur'));
    });
  }
}

export default new ColorPicker();
