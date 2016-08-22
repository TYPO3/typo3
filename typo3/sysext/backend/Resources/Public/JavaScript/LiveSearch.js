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
 * Module: TYPO3/CMS/Backend/LiveSearch
 * Global search to deal with everything in the backend that is search-related
 * @exports TYPO3/CMS/Backend/LiveSearch
 */
define(['jquery', 'jquery/autocomplete', 'TYPO3/CMS/Backend/jquery.clearable'], function ($) {
	'use strict';

	var containerSelector = '#typo3-cms-backend-backend-toolbaritems-livesearchtoolbaritem';
	var searchFieldSelector = '.t3js-topbar-navigation-search-field';
	var formSelector = '.t3js-topbar-navigation-search';
	var url = TYPO3.settings.ajaxUrls['livesearch'];
	var category = '';

	$(function() {
		$(searchFieldSelector).autocomplete({
			// ajax options
			serviceUrl: url,
			paramName: 'q',
			dataType: 'json',
			minChars: 2,
			groupBy: 'typeLabel',
			containerClass: 'dropdown-list',
			appendTo: containerSelector + ' .dropdown-menu',
			forceFixPosition: false,
			preserveInput: true,
			showNoSuggestionNotice: true,
			triggerSelectOnValidInput: false,
			preventBadQueries: false,
			noSuggestionNotice: '<div class="dropdown-info">' + TYPO3.LLL.liveSearch.listEmptyText + '</div>'
					+ '<div class="search-list-help-content"><strong>' + TYPO3.LLL.liveSearch.helpTitle + '</strong>'
					+ '<p>' + TYPO3.LLL.liveSearch.helpDescription + '<br>' + TYPO3.LLL.liveSearch.helpDescriptionPages + '</p>'
					+ '</div>',
			// put the AJAX results in the right format
			transformResult: function(response) {
				return {
					suggestions: $.map(response, function(dataItem) {
						return { value: dataItem.title, data: dataItem };
					})
				};
			},
			// Format group is currently modified inside autocomplete to be allowed to be configurable
			formatGroup: function(suggestion, value, i) {
				var currentCategory = suggestion.data['typeLabel'];
				if (category === currentCategory) {
					return '';
				}
				category = currentCategory;
				var html = '';
				// add a divider if it's not the first group
				if (i > 0) {
					html = '<div class="divider"></div>';
				}
				return html + '<div class="dropdown-header">' + category + '</div>';
			},
			// Rendering of each item
			formatResult: function(suggestion, value) {
				return '<a class="dropdown-list-link" href="#" data-pageid="' + suggestion.data.pageId + '" data-target="' + suggestion.data.editLink + '">' +
						suggestion.data.iconHTML + ' ' + suggestion.data.title +
					'</a>';
			},
			onSearchComplete: function() {
				$(containerSelector).addClass('open');
			},
			beforeRender: function(container) {
				// Unset height, width and z-index again, should be fixed by the plugin at a later point
				container.attr('style', '').append('<div class="divider"></div><div>' +
					'<a href="#" class="btn btn-primary pull-right t3js-live-search-show-all">' +
						TYPO3.LLL.liveSearch.showAllResults +
					'</a>' +
				'</div>');
				$(containerSelector).addClass('open');
			},
			onHide: function() {
				$(containerSelector).removeClass('open');
			}
		});

		// set up the events
		$(containerSelector).on('click', '.t3js-live-search-show-all', function(evt) {
			evt.preventDefault();
			TYPO3.ModuleMenu.App.showModule('web_list', 'id=0&search_levels=4&search_field=' + encodeURIComponent($(searchFieldSelector).val()));
		});
		$(containerSelector).on('click', '.dropdown-list-link', function(evt) {
			evt.preventDefault();
			jump($(this).data('target'), 'web_list', 'web', $(this).data('pageid'));
		});

		$(searchFieldSelector).clearable({
			onClear: function() {
				$(containerSelector).removeClass('open');
			}
		});

		// Prevent submitting the search form
		$(formSelector).submit(function(evt) {
			evt.preventDefault();
		});
	});

});
