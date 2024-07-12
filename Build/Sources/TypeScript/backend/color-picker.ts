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
import RegularEvent from '@typo3/core/event/regular-event';

interface ColorPickerSettings {
  swatches?: string[],
  opacity?: boolean
}

/**
 * Module: @typo3/backend/color-picker
 * contains all logic for the color picker used in FormEngine
 * @exports @typo3/backend/color-picker
 */
class ColorPicker {

  /**
   * Initialize the color picker for the given element
   */
  public initialize(element: HTMLInputElement, options: ColorPickerSettings = {}): void {
    if (element.classList.contains('t3js-colorpicker-initialized')) {
      return;
    }

    const alwan = new Alwan(element, {
      position: 'bottom-start',
      format: 'hex',
      opacity: options.opacity,
      preset: false,
      color: element.value,
      swatches: options.swatches,
    });
    element.classList.add('t3js-colorpicker-initialized');

    // On element change, set the formatted value from alwan
    alwan.on('color', (e): void => {
      element.value = e.hex;
      element.dispatchEvent(new Event('blur'));
    });

    // input: react on user input
    // change: react on indirect changes, e.g. a value picker
    ['input', 'change'].forEach((eventName: string): void => {
      new RegularEvent(eventName, (e: Event): void => {
        alwan.setColor((e.target as HTMLInputElement).value);
      }).bindTo(element);
    });
  }
}

export default new ColorPicker();
