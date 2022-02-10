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

import ThrottleEvent from '@typo3/core/event/throttle-event';

class ValueSlider {
  private readonly controlElement: HTMLInputElement = null;

  /**
   * Update value of slider element
   *
   * @param {HTMLInputElement} element
   */
  private static updateValue(element: HTMLInputElement): void {
    const foreignField = document.querySelector(`[data-formengine-input-name="${element.dataset.sliderItemName}"]`) as HTMLInputElement;

    foreignField.value = element.value;
    foreignField.dispatchEvent(new Event('change', {bubbles: true, cancelable: true}));
  }

  /**
   * @param {HTMLInputElement} element
   */
  private static updateTooltipValue(element: HTMLInputElement): void {
    let renderedValue;
    const value = element.value;
    switch (element.dataset.sliderValueType) {
      case 'double':
        renderedValue = parseFloat(value).toFixed(2);
        break;
      case 'int':
      default:
        renderedValue = parseInt(value, 10);
    }

    element.title = renderedValue.toString();
  }

  constructor(controlElementId: string) {
    this.controlElement = document.getElementById(controlElementId) as HTMLInputElement;
    new ThrottleEvent('input', this.handleRangeChange, 25).bindTo(this.controlElement);
  }

  private handleRangeChange = (e: Event): void => {
    const target = e.target as HTMLInputElement;
    ValueSlider.updateValue(target);
    ValueSlider.updateTooltipValue(target);
  }
}

export default ValueSlider;
