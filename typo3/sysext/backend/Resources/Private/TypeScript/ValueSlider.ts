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

import * as $ from 'jquery';
import 'twbs/bootstrap-slider';

interface UpdatedValue {
  oldValue: number;
  newValue: number;
}

interface ValueSliderUpdateEvent extends JQueryEventObject {
  value: UpdatedValue;
}

class ValueSlider {
  private id: string; // internally set by the renderTooltipValue callback
  private selector: string = '[data-slider-id]';

  /**
   * Update value of slider element
   *
   * @param {ValueSliderUpdateEvent} e
   */
  private static updateValue(e: ValueSliderUpdateEvent): void {
    const $slider = $(e.currentTarget);
    const $foreignField = $('[data-formengine-input-name="' + $slider.data('sliderItemName') + '"]');
    const sliderCallbackParams = $slider.data('sliderCallbackParams');

    $foreignField.val(e.value.newValue);
    TBE_EDITOR.fieldChanged.apply(TBE_EDITOR, sliderCallbackParams);
  }

  constructor() {
    this.initializeSlider();
  }

  /**
   * Initialize all slider elements
   */
  private initializeSlider(): void {
    const $sliders = $(this.selector);
    if ($sliders.length > 0) {
      $sliders.slider({
        formatter: this.renderTooltipValue,
      });
      $sliders.on('change', ValueSlider.updateValue);
    }
  }

  /**
   * @param {string} value
   * @returns {string | number}
   */
  private renderTooltipValue(value: string): any {
    let renderedValue;
    const $slider = $('[data-slider-id="' + this.id + '"]');
    const data = $slider.data();
    switch (data.sliderValueType) {
      case 'double':
        renderedValue = parseFloat(value).toFixed(2);
        break;
      case 'int':
      default:
        renderedValue = parseInt(value, 10);
    }

    return renderedValue;
  }
}

export = new ValueSlider();
