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

interface SelectSingleElementOptions {
  [key: string]: any;
}

/**
 * Module: TYPO3/CMS/Backend/FormEngine/Element/SelectSingleElement
 * Logic for SelectSingleElement
 */
class SelectSingleElement {
  public initialize = (selector: string, options: SelectSingleElementOptions): void => {
    let $selectElement: JQuery = $(selector);
    let $groupIconContainer: JQuery = $selectElement.prev('.input-group-icon');
    options = options || {};

    $selectElement.on('change', (e: JQueryEventObject): void => {
      let $me: JQuery = $(e.target);

      // Update prepended select icon
      $groupIconContainer.html($selectElement.find(':selected').data('icon'));

      let $selectIcons: JQuery = $me.closest('.t3js-formengine-field-item').find('.t3js-forms-select-single-icons');
      $selectIcons.find('.item.active').removeClass('active');
      $selectIcons.find('[data-select-index="' + $me.prop('selectedIndex') + '"]').closest('.item').addClass('active');
    });

    // Append optionally passed additional "change" event callback
    if (typeof options.onChange === 'function') {
      $selectElement.on('change', options.onChange);
    }

    // Append optionally passed additional "focus" event callback
    if (typeof options.onFocus === 'function') {
      $selectElement.on('focus', options.onFocus);
    }

    $selectElement.closest('.form-control-wrap').find('.t3js-forms-select-single-icons a').on('click', (e: JQueryEventObject): boolean => {
      let $me: JQuery = $(e.target);
      let $selectIcon: JQuery = $me.closest('[data-select-index]');

      $me.closest('.t3js-forms-select-single-icons').find('.item.active').removeClass('active');
      $selectElement
        .prop('selectedIndex', $selectIcon.data('selectIndex'))
        .trigger('change');
      $selectIcon.closest('.item').addClass('active');

      return false;
    });
  }
}

export = new SelectSingleElement();
