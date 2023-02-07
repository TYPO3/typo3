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

import FormEngine from '@typo3/backend/form-engine';
import FormEngineValidation from '@typo3/backend/form-engine-validation';
import RegularEvent from '@typo3/core/event/regular-event';

export abstract class AbstractSortableSelectItems {

  /**
   * Moves currently selected options from a select field to the very top,
   * can be multiple entries as well
   *
   * @param {HTMLSelectElement} fieldElement
   */
  private static moveOptionToTop(fieldElement: HTMLSelectElement): void {
    Array.from(fieldElement.querySelectorAll(':checked')).reverse().forEach((optionEl: HTMLOptionElement): void => {
      fieldElement.insertBefore(optionEl, fieldElement.firstElementChild);
    });
  }

  /**
   * Moves currently selected options from a select field as the very last entries
   *
   * @param {HTMLSelectElement} fieldElement
   */
  private static moveOptionToBottom(fieldElement: HTMLSelectElement): void {
    fieldElement.querySelectorAll(':checked').forEach((optionEl: HTMLOptionElement): void => {
      fieldElement.insertBefore(optionEl, null);
    });
  }

  /**
   * Moves currently selected options from a select field up by one position,
   * can be multiple entries as well
   *
   * @param {HTMLSelectElement} fieldElement
   */
  private static moveOptionUp(fieldElement: HTMLSelectElement): void {
    const allChildren = Array.from(fieldElement.children);
    const selectedOptions = Array.from(fieldElement.querySelectorAll(':checked'));
    for (const optionEl of selectedOptions) {
      if (allChildren.indexOf(optionEl) === 0 && optionEl.previousElementSibling === null) {
        break;
      }

      fieldElement.insertBefore(optionEl, optionEl.previousElementSibling);
    }
  }

  /**
   * Moves currently selected options from a select field up by one position,
   * can be multiple entries as well
   *
   * @param {HTMLSelectElement} fieldElement
   */
  private static moveOptionDown(fieldElement: HTMLSelectElement): void {
    const allChildren = Array.from(fieldElement.children).reverse();
    const selectedOptions = Array.from(fieldElement.querySelectorAll(':checked')).reverse();
    for (const optionEl of selectedOptions) {
      if (allChildren.indexOf(optionEl) === 0 && optionEl.nextElementSibling === null) {
        break;
      }

      fieldElement.insertBefore(optionEl, optionEl.nextElementSibling.nextElementSibling);
    }
  }

  /**
   * Removes currently selected options from a select field
   *
   * @param {HTMLSelectElement} fieldElement
   * @param {HTMLSelectElement} availableFieldElement
   */
  private static removeOption(fieldElement: HTMLSelectElement, availableFieldElement: HTMLSelectElement): void {
    const previousSelectIndex = fieldElement.selectedIndex;
    fieldElement.querySelectorAll(':checked').forEach((option: HTMLOptionElement): void => {
      const originalOption = <HTMLOptionElement>availableFieldElement.querySelector('option[value="' + option.value + '"]');
      if (originalOption !== null) {
        originalOption.classList.remove('hidden');
        originalOption.disabled = false;
        FormEngine.enableOptGroup(originalOption);
      }
      fieldElement.removeChild(option);
    });

    // set the next selected option to the previous sibling removed
    fieldElement.selectedIndex = previousSelectIndex > 0 ? (previousSelectIndex - 1) : 0;
  }

  /**
   * @param {HTMLSelectElement} fieldElement
   */
  protected registerSortableEventHandler = (fieldElement: HTMLSelectElement): void => {
    this.registerKeyboardEventHandler(fieldElement);

    const aside = fieldElement.closest('.form-wizards-wrap').querySelector('.form-wizards-items-aside');
    if (aside === null) {
      return;
    }

    aside.addEventListener('click', (e: Event): void => {
      let target: HTMLAnchorElement;

      if ((target = (<Element>e.target).closest('.t3js-btn-option')) === null) {
        if ((<Element>e.target).matches('.t3js-btn-option')) {
          target = <HTMLAnchorElement>e.target;
        }

        return;
      }

      e.preventDefault();

      const relatedFieldName = target.dataset.fieldname;
      const relatedField = FormEngine.getFieldElement(relatedFieldName).get(0) as HTMLSelectElement;
      const relatedAvailableValuesField = FormEngine.getFieldElement(relatedFieldName,'_avail').get(0) as HTMLSelectElement;

      if (target.classList.contains('t3js-btn-moveoption-top')) {
        AbstractSortableSelectItems.moveOptionToTop(fieldElement);
      } else if (target.classList.contains('t3js-btn-moveoption-up')) {
        AbstractSortableSelectItems.moveOptionUp(fieldElement);
      } else if (target.classList.contains('t3js-btn-moveoption-down')) {
        AbstractSortableSelectItems.moveOptionDown(fieldElement);
      } else if (target.classList.contains('t3js-btn-moveoption-bottom')) {
        AbstractSortableSelectItems.moveOptionToBottom(fieldElement);
      } else if (target.classList.contains('t3js-btn-removeoption')) {
        AbstractSortableSelectItems.removeOption(
          fieldElement,
          relatedAvailableValuesField,
        );
      }

      FormEngine.updateHiddenFieldValueFromSelect(fieldElement, relatedField);
      FormEngine.legacyFieldChangedCb();
      FormEngineValidation.markFieldAsChanged(relatedAvailableValuesField);
      FormEngineValidation.validateField(relatedAvailableValuesField);
    });
  }

  /**
   * @param {HTMLSelectElement} fieldElement
   */
  private registerKeyboardEventHandler = (fieldElement: HTMLSelectElement): void => {
    const relatedFieldName = fieldElement.dataset.formengineInputName;
    const relatedField = FormEngine.getFieldElement(relatedFieldName).get(0) as HTMLSelectElement;
    const relatedAvailableValuesField = FormEngine.getFieldElement(relatedFieldName,'_avail').get(0) as HTMLSelectElement;

    new RegularEvent('keydown', (e: KeyboardEvent): void => {
      if (e.code === 'Delete' || e.code === 'Backspace') {
        e.preventDefault();
        AbstractSortableSelectItems.removeOption(
          fieldElement,
          relatedAvailableValuesField,
        );
      }
      if (e.code === 'ArrowUp' && e.altKey) {
        e.preventDefault();
        AbstractSortableSelectItems.moveOptionUp(fieldElement);
      }
      if (e.code === 'ArrowDown' && e.altKey) {
        e.preventDefault();
        AbstractSortableSelectItems.moveOptionDown(fieldElement);
      }
      if (e.code === 'ArrowUp' && e.altKey && e.shiftKey) {
        e.preventDefault();
        AbstractSortableSelectItems.moveOptionToTop(fieldElement);
      }
      if (e.code === 'ArrowDown' && e.altKey && e.shiftKey) {
        e.preventDefault();
        AbstractSortableSelectItems.moveOptionToBottom(fieldElement);
      }
      if (e.defaultPrevented) {
        FormEngine.updateHiddenFieldValueFromSelect(fieldElement, relatedField);
        FormEngine.legacyFieldChangedCb();
        FormEngineValidation.markFieldAsChanged(relatedAvailableValuesField);
        FormEngineValidation.validateField(relatedAvailableValuesField);
      }
    }).bindTo(fieldElement);
  }

}
