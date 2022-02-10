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

import {AbstractSortableSelectItems} from './abstract-sortable-select-items';
import DocumentService from '@typo3/core/document-service';
import FormEngine from '@typo3/backend/form-engine';
import SelectBoxFilter from './extra/select-box-filter';

class SelectMultipleSideBySideElement extends AbstractSortableSelectItems {
  private selectedOptionsElement: HTMLSelectElement = null;
  private availableOptionsElement: HTMLSelectElement = null;

  constructor(selectedOptionsElementId: string, availableOptionsElementId: string) {
    super();

    DocumentService.ready().then((document: Document): void => {
      this.selectedOptionsElement = <HTMLSelectElement>document.getElementById(selectedOptionsElementId);
      this.availableOptionsElement = <HTMLSelectElement>document.getElementById(availableOptionsElementId);
      this.registerEventHandler();
    });
  }

  private registerEventHandler(): void {
    this.registerSortableEventHandler(this.selectedOptionsElement);

    this.availableOptionsElement.addEventListener('click', (e: Event): void => {
      const el = <HTMLSelectElement>e.currentTarget;
      const fieldName = el.dataset.relatedfieldname;
      if (fieldName) {
        const exclusiveValues = el.dataset.exclusivevalues;
        const selectedOptions = el.querySelectorAll('option:checked'); // Yep, :checked finds selected options
        if (selectedOptions.length > 0) {
          selectedOptions.forEach((optionElement: HTMLOptionElement): void => {
            FormEngine.setSelectOptionFromExternalSource(
              fieldName,
              optionElement.value,
              optionElement.textContent,
              optionElement.getAttribute('title'),
              exclusiveValues,
              optionElement,
            );
          });
        }
      }
    });

    // tslint:disable-next-line:no-unused-expression
    new SelectBoxFilter(this.availableOptionsElement);
  }
}

export default SelectMultipleSideBySideElement;
