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

/// <amd-dependency path="TYPO3/CMS/Core/Contrib/jquery.minicolors">
import $ = require('jquery');

/**
 * Module: TYPO3/CMS/Backend/ColorPicker
 * contains all logic for the color picker used in FormEngine
 * @exports TYPO3/CMS/Backend/ColorPicker
 */
class ColorPicker {
  /**
   * The selector for the color picker elements
   */
  private selector: string;

  /**
   * The constructor, set the class properties default values
   */
  constructor() {
    this.selector = '.t3js-color-picker';
  }

  /**
   * Initialize the color picker for the given selector
   */
  public initialize(): void {
    (<any> $(this.selector)).minicolors({
      format: 'hex',
      position: 'bottom left',
      theme: 'bootstrap',
    });
    $(document).on('change', '.t3js-colorpicker-value-trigger', (event: Event): void => {
      const $element: any = $(event.target);
      if ($element.val() !== '') {
        $element.closest('.t3js-formengine-field-item')
          .find('.t3js-color-picker')
          .val($element.val())
          .trigger('paste');
        $element.val('');
      }
    });
  }
}
// Create an instance and return it
export = new ColorPicker();
