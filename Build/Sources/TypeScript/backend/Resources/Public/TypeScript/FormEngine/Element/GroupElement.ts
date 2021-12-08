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

import {AbstractSortableSelectItems} from './AbstractSortableSelectItems';
import DocumentService from 'TYPO3/CMS/Core/DocumentService';
import FormEngineSuggest from '../../FormEngineSuggest';

class GroupElement extends AbstractSortableSelectItems {
  private element: HTMLSelectElement = null;

  constructor(elementId: string) {
    super();

    DocumentService.ready().then((): void => {
      this.element = <HTMLSelectElement>document.getElementById(elementId);
      this.registerEventHandler();
      this.registerSuggest();
    });
  }

  private registerEventHandler(): void {
    this.registerSortableEventHandler(this.element);
  }

  private registerSuggest(): void {
    let suggestContainer;
    if ((suggestContainer = <HTMLElement>this.element.closest('.t3js-formengine-field-item').querySelector('.t3-form-suggest')) !== null) {
      // tslint:disable-next-line:no-unused-expression
      new FormEngineSuggest(suggestContainer);
    }
  }
}

export default GroupElement;
