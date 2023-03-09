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

import './form-engine/element/suggest/result-container';
import DocumentService from '@typo3/core/document-service';
import FormEngine from '@typo3/backend/form-engine';
import RegularEvent from '@typo3/core/event/regular-event';
import DebounceEvent from '@typo3/core/event/debounce-event';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';

class FormEngineSuggest {
  private readonly element: HTMLInputElement;
  private resultContainer: HTMLElement;
  private currentRequest: AjaxRequest|null = null;

  constructor(element: HTMLInputElement) {
    this.element = element;

    DocumentService.ready().then((): void => {
      this.initialize(element);
      this.registerEvents();
    });
  }

  private initialize(searchField: HTMLElement): void {
    const containerElement: Element = searchField.closest('.t3-form-suggest-container');

    this.resultContainer = document.createElement('typo3-backend-formengine-suggest-result-container');
    this.resultContainer.hidden = true;
    containerElement.append(this.resultContainer);
  }

  private registerEvents(): void {
    new RegularEvent('typo3:formengine:suggest-item-chosen', (e: CustomEvent): void => {
      let insertData: string = '';
      if (this.element.dataset.fieldtype === 'select') {
        insertData = e.detail.element.uid;
      } else {
        insertData = e.detail.element.table + '_' + e.detail.element.uid;
      }
      FormEngine.setSelectOptionFromExternalSource(this.element.dataset.field, insertData, e.detail.element.label, e.detail.element.label);
      FormEngine.Validation.markFieldAsChanged(document.querySelector('input[name="' + this.element.dataset.field + '"]') as HTMLInputElement);
      this.resultContainer.hidden = true;
    }).bindTo(this.resultContainer);

    new RegularEvent('focus', (): void => {
      const results = JSON.parse(this.resultContainer.getAttribute('results'));
      if (results?.length > 0) {
        this.resultContainer.hidden = false;
      }
    }).bindTo(this.element);

    new RegularEvent('blur', (e: FocusEvent): void => {
      if ((e.relatedTarget as HTMLElement)?.tagName.toLowerCase() === 'typo3-backend-formengine-suggest-result-item') {
        // don't to anything if focus switches to a result item
        return;
      }

      this.resultContainer.hidden = true;
    }).bindTo(this.element);

    new DebounceEvent('input', (e: InputEvent): void => {
      if (this.currentRequest instanceof AjaxRequest) {
        this.currentRequest.abort();
      }

      const target = e.target as HTMLInputElement;

      if (target.value.length < parseInt(target.dataset.minchars, 10)) {
        return;
      }

      this.currentRequest = new AjaxRequest(TYPO3.settings.ajaxUrls.record_suggest);
      this.currentRequest.post({
        value: target.value,
        tableName: target.dataset.tablename,
        fieldName: target.dataset.fieldname,
        uid: parseInt(target.dataset.uid, 10),
        pid: parseInt(target.dataset.pid, 10),
        dataStructureIdentifier: target.dataset.datastructureidentifier,
        flexFormSheetName: target.dataset.flexformsheetname,
        flexFormFieldName: target.dataset.flexformfieldname,
        flexFormContainerName: target.dataset.flexformcontainername,
        flexFormContainerFieldName: target.dataset.flexformcontainerfieldname,
      }).then(async (response: AjaxResponse): Promise<void> => {
        const resultSet = await response.raw().text();
        this.resultContainer.setAttribute('results', resultSet);
        this.resultContainer.hidden = false;
      });
    }).bindTo(this.element);

    new RegularEvent('keydown', this.handleKeyDown).bindTo(this.element);
  }

  private handleKeyDown = (e: KeyboardEvent): void => {
    if (e.key === 'ArrowDown') {
      e.preventDefault();

      const results = JSON.parse(this.resultContainer.getAttribute('results'));
      if (results?.length > 0) {
        this.resultContainer.hidden = false;
      }

      // Select first available result item
      const firstSearchResultItem = this.resultContainer.querySelector('typo3-backend-formengine-suggest-result-item') as HTMLElement|null;
      firstSearchResultItem?.focus();

      return;
    }

    if (e.key === 'Escape') {
      e.preventDefault();

      this.resultContainer.hidden = true;
    }
  };
}

export default FormEngineSuggest;
