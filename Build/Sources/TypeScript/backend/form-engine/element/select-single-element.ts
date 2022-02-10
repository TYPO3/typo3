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
import DocumentService from '@typo3/core/document-service';
import FormEngine from '@typo3/backend/form-engine';
import OnFieldChangeItem = TYPO3.CMS.Backend.OnFieldChangeItem;

interface SelectSingleElementOptions {
  onChange?: OnFieldChangeItem[];
}

/**
 * Module: @typo3/backend/form-engine/element/select-single-element
 * Logic for SelectSingleElement
 */
class SelectSingleElement {
  public initializeOnReady(selector: string, options: SelectSingleElementOptions): void {
    DocumentService.ready().then(() => {
      this.initialize(selector, options);
    });
  }

  public initialize = (selector: string, options: SelectSingleElementOptions): void => {
    let selectElement: HTMLSelectElement = document.querySelector(selector);
    options = options || {};

    new RegularEvent('change', (e: Event): void => {
      const target = e.target as HTMLSelectElement;
      const groupIconContainer: HTMLElement = target.parentElement.querySelector('.input-group-icon');

      // Update prepended select icon
      if (groupIconContainer !== null) {
        groupIconContainer.innerHTML = (target.options[target.selectedIndex].dataset.icon);
      }

      const selectIcons: HTMLElement = target.closest('.t3js-formengine-field-item').querySelector('.t3js-forms-select-single-icons');
      if (selectIcons !== null) {
        const activeItem = selectIcons.querySelector('.item.active');
        if (activeItem !== null) {
          activeItem.classList.remove('active');
        }

        const selectionIcon = selectIcons.querySelector('[data-select-index="' + target.selectedIndex + '"]');
        if (selectionIcon !== null)  {
          selectionIcon.closest('.item').classList.add('active');
        }
      }
    }).bindTo(selectElement);

    if (options.onChange instanceof Array) {
      // hand `OnFieldChange` processing over to `FormEngine`
      new RegularEvent('change', () => FormEngine.processOnFieldChange(options.onChange)).bindTo(selectElement);
    }

    new RegularEvent('click', (e: Event, target: HTMLAnchorElement): void => {
      const currentActive = target.closest('.t3js-forms-select-single-icons').querySelector('.item.active');
      if (currentActive !== null) {
        currentActive.classList.remove('active');
      }

      selectElement.selectedIndex = parseInt(target.dataset.selectIndex, 10);
      selectElement.dispatchEvent(new Event('change'));
      target.closest('.item').classList.add('active');
    }).delegateTo(selectElement.closest('.form-control-wrap'), '.t3js-forms-select-single-icons .item:not(.active) a');
  }
}

export default new SelectSingleElement();
