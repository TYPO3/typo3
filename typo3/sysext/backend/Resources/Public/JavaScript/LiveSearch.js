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
 * Global search to deal with everything in the backend that is search-related
 */
define('TYPO3/CMS/Backend/LiveSearch', ['jquery', 'typeaheadjs'], function ($) {

	var containerSelector = '.t3js-topbar-navigation-search';
	var searchFieldSelector = '.t3js-topbar-navigation-search-field';
	var url = TYPO3.settings.ajaxUrls['LiveSearch'] + '&q=';
	var cssPrefix = 'typeahead';

	var initialize = function() {
		var $searchField = $(searchFieldSelector);

		var searchCall = function(query, syncResults, asyncResults) {
			$.ajax({
				url: url + rawurlencode(query.toString()),
				cache: false,
				success: function(results) {
					asyncResults(results);
				}
			});
		};

		$searchField.typeahead({
			hint: false,
			highlight: true,
			limit: 10,
			minLength: 2,
			classNames: {
				wrapper: cssPrefix,
				input: cssPrefix + '-input',
				hint: cssPrefix + '-hint',
				menu: cssPrefix + '-menu dropdown-menu',
				dataset: 'dropdown-list ' + cssPrefix + '-dataset',
				suggestion: cssPrefix + '-suggestion',
				empty: cssPrefix + '-empty',
				open: cssPrefix + '-open',
				cursor: cssPrefix + '-cursor',
				highlight: cssPrefix + '-highlight'
			}
		}, {
			name: 'databaseRecords',
			source: searchCall,
			limit: 1000,	// this needs to be very high, limiter is on PHP side
			display: function() {
				return $searchField.val();
			},
			templates: {
				empty: '<div class="dropdown-info typeahead-search-empty-message">' + TYPO3.LLL.liveSearch.listEmptyText + '</div>'
					+ '<div class="search-list-help-content"><strong>' + TYPO3.LLL.liveSearch.helpTitle + '</strong>'
					+ '<p>' + TYPO3.LLL.liveSearch.helpDescription + '<br>' + TYPO3.LLL.liveSearch.helpDescriptionPages + '</p>'
					+ '</div>'
				,
				suggestion: function(result) {
					return '' +
						'<div data-table-name="' + result.table.name + '" data-table-title="' + result.table.title + '">' +
							'<a class="dropdown-list-link" href="#" data-pageid="' + result.pageId + '" data-target="' + result.editLink + '">' +
								result.iconHTML + ' ' + result.title +
							'</a>' +
						'</div>';
				},
				footer: '' +
						'<div>' +
							'<a href="#" class="btn btn-primary pull-right t3js-live-search-show-all">' +
								TYPO3.LLL.liveSearch.showAllResults +
							'</a>' +
						'</div>'
			}
		}).bind('typeahead:render', function(e) {
			var suggestions = [].slice.call(arguments, 1);
			var lastTable = '';
			$.each(suggestions, function(){
				if (lastTable !== this.table.name) {
					lastTable = this.table.name;
					var $dataSet = $(containerSelector + ' [data-table-name=' + this.table.name + ']');
					$dataSet.first().before('<div class="dropdown-header">' + this.table.title + '</div>');
					$dataSet.last().after('<div class="divider"></div>');
				}
			});
		});

		// set up the events
		$(containerSelector).on('click', '.t3js-live-search-show-all', function() {
			TYPO3.ModuleMenu.App.showModule('web_list', 'id=0&search_levels=4&search_field=' + $searchField.val());
			$searchField.typeahead('close');
		}).on('click', '.typeahead-suggestion a', function() {
			jump($(this).data('target'), 'web_list', 'web', $(this).data('pageid'));
			$searchField.typeahead('close');
		});
		$searchField.on('typeahead:change', function() {
			$searchField.typeahead('close');
		});
	};

	$(document).ready(function() {
		initialize();
	});
});
