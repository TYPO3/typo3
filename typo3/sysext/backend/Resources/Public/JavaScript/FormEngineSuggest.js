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
 * Functionality to load suggest functionality
 */
define('TYPO3/CMS/Backend/FormEngineSuggest', ['jquery', 'jquery/autocomplete'], function ($) {
	var initialize = function($searchField) {
		var $containerElement = $searchField.closest('.t3-form-suggest-container');
		var table = $searchField.data('table'),
			field = $searchField.data('field'),
			uid = $searchField.data('uid'),
			pid = $searchField.data('pid'),
			newRecordRow = $searchField.data('recorddata'),
			minimumCharacters = $searchField.data('minchars'),
			url = TYPO3.settings.ajaxUrls['record_suggest'],
			params = {
				'table': table,
				'field': field,
				'uid': uid,
				'pid': pid,
				'newRecordRow': newRecordRow
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
			// put the AJAX results in the right format
			transformResult: function(response) {
				return {
					suggestions: $.map(response, function(dataItem) {
						return { value: dataItem.text, data: dataItem };
					})
				};
			},
			// Rendering of each item
			formatResult: function(suggestion, value) {
				return '<a class="autocomplete-suggestion-link" href="#" data-label="' + suggestion.data.label + '" data-table="' + suggestion.data.table + '" data-uid="' + suggestion.data.uid + '">' +
						suggestion.data.sprite + suggestion.data.text +
					'</a>';
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
			}
		});

		// set up the events
		$containerElement.on('click', '.autocomplete-suggestion-link', function(evt) {
			evt.preventDefault();
			var insertData = '';
			if ($searchField.data('fieldtype') == 'select') {
				insertData = $(this).data('uid');
			} else {
				insertData = $(this).data('table') + '_' + $(this).data('uid');
			}

			var formEl = $searchField.data('fieldname');
			setFormValueFromBrowseWin(formEl, insertData, $(this).data('label'), $(this).data('label'));
			TBE_EDITOR.fieldChanged(table, uid, field, formEl);
		});
	};

	/**
	 * return a function that gets DOM elements that are checked if suggest is already initialized
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
