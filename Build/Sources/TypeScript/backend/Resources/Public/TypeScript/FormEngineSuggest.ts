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
import 'TYPO3/CMS/Core/Contrib/jquery.autocomplete';
import FormEngine = require('TYPO3/CMS/Backend/FormEngine');

// data structure returned by SuggestWizardDefaultReceiver::queryTable()
interface SuggestEntry {
  table: string;
  uid: number;

  label: string;
  // The HTML content for the suggest option
  text: string;
  // The record path
  path: string;

  style: string;
  class: string;
  // the icon
  sprite: string;
}

interface TransformedSuggestEntry {
  value: string;
  data: SuggestEntry;
}

class FormEngineSuggest {
  constructor(element: HTMLElement) {
    $((): void => {
      this.initialize(element);
    });
  }

  private initialize(searchField: HTMLElement): void {
    const containerElement: Element = searchField.closest('.t3-form-suggest-container');
    const tableName: string = searchField.dataset.tablename;
    const fieldName: string = searchField.dataset.fieldname;
    const formEl: string = searchField.dataset.field;
    const uid: number = parseInt(searchField.dataset.uid, 10);
    const pid: number = parseInt(searchField.dataset.pid, 10);
    const dataStructureIdentifier: string = searchField.dataset.datastructureidentifier;
    const flexFormSheetName: string = searchField.dataset.flexformsheetname;
    const flexFormFieldName: string = searchField.dataset.flexformfieldname;
    const flexFormContainerName: string = searchField.dataset.flexformcontainername;
    const flexFormContainerFieldName: string = searchField.dataset.flexformcontainerfieldname;
    const minimumCharacters: number = parseInt(searchField.dataset.minchars, 10);
    const url: string = TYPO3.settings.ajaxUrls.record_suggest;
    const params = {
      tableName,
      fieldName,
      uid,
      pid,
      dataStructureIdentifier,
      flexFormSheetName,
      flexFormFieldName,
      flexFormContainerName,
      flexFormContainerFieldName,
    };

    function insertValue(element: HTMLElement): void {
      let insertData: string = '';
      if (searchField.dataset.fieldtype === 'select') {
        insertData = element.dataset.uid;
      } else {
        insertData = element.dataset.table + '_' + element.dataset.uid;
      }
      FormEngine.setSelectOptionFromExternalSource(formEl, insertData, element.dataset.label, element.dataset.label);
      FormEngine.Validation.markFieldAsChanged($(document.querySelector('input[name="' + formEl + '"]')));
    }

    $(searchField).autocomplete({
      // ajax options
      serviceUrl: url,
      params: params,
      type: 'POST',
      paramName: 'value',
      dataType: 'json',
      minChars: minimumCharacters,
      groupBy: 'typeLabel',
      containerClass: 'autocomplete-results',
      appendTo: containerElement,
      forceFixPosition: false,
      preserveInput: true,
      showNoSuggestionNotice: true,
      noSuggestionNotice: '<div class="autocomplete-info">No results</div>',
      minLength: minimumCharacters,
      preventBadQueries: false,
      // put the AJAX results in the right format
      transformResult: (response: Array<SuggestEntry>): {suggestions: Array<TransformedSuggestEntry>} => {
        return {
          suggestions: response.map((dataItem: SuggestEntry): {value: string, data: SuggestEntry} => {
            return {value: dataItem.text, data: dataItem};
          }),
        };
      },
      // Rendering of each item
      formatResult: (suggestion: {data: SuggestEntry}): string => {
        return $('<div>').append(
          $('<a class="autocomplete-suggestion-link" href="#">' +
            suggestion.data.sprite + suggestion.data.text +
            '</a></div>').attr({
            'data-label': suggestion.data.label,
            'data-table': suggestion.data.table,
            'data-uid': suggestion.data.uid,
          })).html();
      },
      onSearchComplete: function(): void {
        containerElement.classList.add('open');
      },
      beforeRender: function(container: JQuery): void {
        // Unset height, width and z-index again, should be fixed by the plugin at a later point
        container.attr('style', '');
        containerElement.classList.add('open');
      },
      onHide: function(): void {
        containerElement.classList.remove('open');
      },
      onSelect: function(): void {
        insertValue(<HTMLElement>(containerElement.querySelector('.autocomplete-selected a')));
      },
    });
  }
}

export = FormEngineSuggest;
