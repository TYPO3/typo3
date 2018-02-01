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

/**
 * Module: TYPO3/CMS/Backend/FormEngineSuggest
 * Functionality to load suggest functionality
 */
define(['jquery', 'jquery/autocomplete'], function($) {
  var initialize = function($searchField) {
    var $containerElement = $searchField.closest('.t3-form-suggest-container');
    var tableName = $searchField.data('tablename'),
      fieldName = $searchField.data('fieldname'),
      formEl = $searchField.data('field'),
      uid = $searchField.data('uid'),
      pid = $searchField.data('pid'),
      dataStructureIdentifier = $searchField.data('datastructureidentifier'),
      flexFormSheetName = $searchField.data('flexformsheetname'),
      flexFormFieldName = $searchField.data('flexformfieldname'),
      flexFormContainerName = $searchField.data('flexformcontainername'),
      flexFormContainerFieldName = $searchField.data('flexformcontainerfieldname'),
      minimumCharacters = $searchField.data('minchars'),
      url = TYPO3.settings.ajaxUrls['record_suggest'],
      params = {
        'tableName': tableName,
        'fieldName': fieldName,
        'uid': uid,
        'pid': pid,
        'dataStructureIdentifier': dataStructureIdentifier,
        'flexFormSheetName': flexFormSheetName,
        'flexFormFieldName': flexFormFieldName,
        'flexFormContainerName': flexFormContainerName,
        'flexFormContainerFieldName': flexFormContainerFieldName
      },
      insertValue = function(element) {
        var insertData = '';
        if ($searchField.data('fieldtype') === 'select') {
          insertData = $(element).data('uid');
        } else {
          insertData = $(element).data('table') + '_' + $(element).data('uid');
        }
        var labelEl = $('<div>').html($(element).data('label'));
        var label = labelEl.text();
        var title = labelEl.find('span').attr('title') || label;
        setFormValueFromBrowseWin(formEl, insertData, label, title);
        TBE_EDITOR.fieldChanged(tableName, uid, fieldName, formEl);
      };

    $searchField.autocomplete({
      // ajax options
      serviceUrl: url,
      params: params,
      type: 'POST',
      paramName: 'value',
      dataType: 'json',
      minChars: minimumCharacters,
      groupBy: 'typeLabel',
      containerClass: 'autocomplete-results',
      appendTo: $containerElement,
      forceFixPosition: false,
      preserveInput: true,
      showNoSuggestionNotice: true,
      noSuggestionNotice: '<div class="autocomplete-info">No results</div>',
      minLength: minimumCharacters,
      preventBadQueries: false,
      // put the AJAX results in the right format
      transformResult: function(response) {
        return {
          suggestions: $.map(response, function(dataItem) {
            return {value: dataItem.text, data: dataItem};
          })
        };
      },
      // Rendering of each item
      formatResult: function(suggestion, value) {
        return $('<div>').append(
          $('<a class="autocomplete-suggestion-link" href="#">' +
            suggestion.data.sprite + suggestion.data.text +
            '</a></div>').attr({
            'data-label': suggestion.data.label,
            'data-table': suggestion.data.table,
            'data-uid': suggestion.data.uid
          })).html();
      },
      onSearchComplete: function() {
        $containerElement.addClass('open');
      },
      beforeRender: function(container) {
        // Unset height, width and z-index again, should be fixed by the plugin at a later point
        container.attr('style', '');
        $containerElement.addClass('open');
      },
      onHide: function() {
        $containerElement.removeClass('open');
      },
      onSelect: function() {
        insertValue($containerElement.find('.autocomplete-selected a')[0]);
      }
    });

    // set up the events
    $containerElement.on('click', '.autocomplete-suggestion-link', function(evt) {
      evt.preventDefault();
    });
  };

  /**
   * Return a function that gets DOM elements that are checked if suggest is already initialized
   * @exports TYPO3/CMS/Backend/FormEngineSuggest
   */
  return function(selectorElements) {
    $(selectorElements).each(function(key, el) {
      if (!$(el).data('t3-suggest-initialized')) {
        initialize($(el));
        $(el).data('t3-suggest-initialized', true);
      }
    });
  };
});
