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
  private readonly controlElement: HTMLElement = null;

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

  constructor(controlElementId: string) {
    this.controlElement = document.getElementById(controlElementId) as HTMLElement;
    this.initializeSlider();
  }

  /**
   * Initialize all slider elements
   */
  private initializeSlider(): void {
    const $slider = $(this.controlElement);
    $slider.slider({
      formatter: this.renderTooltipValue,
    });
    $slider.on('change', ValueSlider.updateValue);
  }

  /**
   * @param {string} value
   * @returns {string}
   */
  private renderTooltipValue = (value: string): string => {
    let renderedValue;
    const $slider = $(this.controlElement);
    const data = $slider.data();
    switch (data.sliderValueType) {
      case 'double':
        renderedValue = parseFloat(value).toFixed(2);
        break;
      case 'int':
      default:
        renderedValue = parseInt(value, 10).toString();
    }

    return renderedValue;
  }
}

export = ValueSlider;
