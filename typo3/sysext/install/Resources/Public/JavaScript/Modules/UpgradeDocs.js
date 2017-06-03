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
 * Module: TYPO3/CMS/Install/UpgradeDocs
 */
define(['jquery', 'bootstrap', 'chosen'], function ($) {
	'use strict';

	return {
		selectorGridderOpener: '.t3js-upgradeDocs-open',
		selectorMarkReadToken: '#t3js-upgradeDocs-markRead-token',
		selectorUnmarkReadToken: '#t3js-upgradeDocs-unmarkRead-token',
		selectorRestFileItem: '.upgrade_analysis_item_to_filter',
		selectorFulltextSearch: '.gridder-show .t3js-upgradeDocs-fulltext-search',
		selectorChosenField: '.gridder-show .t3js-upgradeDocs-chosen-select',

		chosenField: null,
		fulltextSearchField: null,

		initialize: function() {
			var self = this;

			// Mark a file as read
			$(document).on('click', '.t3js-upgradeDocs-markRead', function(event) {
				self.markRead(event.target);
			});
			$(document).on('click', '.t3js-upgradeDocs-unmarkRead', function(event) {
				self.unmarkRead(event.target);
			});

			// Make jquerys "contains" work case-insensitive
			jQuery.expr[':'].contains = jQuery.expr.createPseudo(function(arg) {
				return function (elem) {
					return jQuery(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
				};
			});

			// Delayed initialize of some stuff on first open
			$(document).on('click', this.selectorGridderOpener, function(event) {
				$('[data-toggle="tooltip"]').tooltip({container: 'body'});
				self.chosenField = $(self.selectorChosenField);
				self.fulltextSearchField = $(self.selectorFulltextSearch);
				self.initializeChosenSelector();
				self.chosenField.on('change', function() {
					self.combinedFilterSearch();
				});
				self.fulltextSearchField.on('keyup', function() {
					self.combinedFilterSearch();
				});
			});
		},

		initializeChosenSelector: function() {
			var self = this;
			var tagString = '';
			$(this.selectorRestFileItem).each(function() {
				tagString += $(this).data('item-tags') + ',';
			});
			var tagArray = this.trimExplodeAndUnique(',', tagString);
			$.each(tagArray, function(i, tag) {
				self.chosenField.append('<option>' + tag + '</option>');
			});
			var config = {
				'.chosen-select': {width: "100%", placeholder_text_multiple: "tags"},
				'.chosen-select-deselect': {allow_single_deselect: true},
				'.chosen-select-no-single': {disable_search_threshold: 10},
				'.chosen-select-no-results': {no_results_text: 'Oops, nothing found!'},
				'.chosen-select-width': {width: "100%"}
			};
			for (var selector in config) {
				$(selector).chosen(config[selector]);
			}
			this.chosenField.trigger('chosen:updated');
		},

		combinedFilterSearch: function() {
			var $items = $('div.item');
			if (this.chosenField.val().length < 1 && this.fulltextSearchField.val().length < 1) {
				$('.panel-version:not(:first) > .panel-collapse').collapse('hide');
				$items.removeClass('hidden searchhit filterhit');
				return false;
			}
			$items.addClass('hidden').removeClass('searchhit filterhit');

			// apply tags
			if (this.chosenField.val().length > 0) {
				$items
					.addClass('hidden')
					.removeClass('filterhit');
				var orTags = [];
				var andTags = [];
				$.each(this.chosenField.val(), function(index, item) {
					var tagFilter = '[data-item-tags*="' + item + '"]';
					if (item.indexOf(':') > 0) {
						orTags.push(tagFilter);
					} else {
						andTags.push(tagFilter);
					}
				});
				var andString = andTags.join('');
				var tags = [];
				if (orTags.length) {
					for (var i = 0; i < orTags.length; i++) {
						tags.push(andString + orTags[i]);
					}
				} else {
					tags.push(andString);
				}
				var tagSelection = tags.join(',');
				$(tagSelection)
					.removeClass('hidden')
					.addClass('searchhit filterhit');
			} else {
				$items
					.addClass('filterhit')
					.removeClass('hidden');
			}
			// apply fulltext search
			var typedQuery = this.fulltextSearchField.val();
			$('div.item.filterhit').each(function() {
				var $item = $(this);
				if ($(':contains(' + typedQuery + ')', $item).length > 0 || $('input[value*="' + typedQuery + '"]', $item).length > 0) {
					$item.removeClass('hidden').addClass('searchhit');
				} else {
					$item.removeClass('searchhit').addClass('hidden');
				}
			});

			$('.searchhit').closest('.panel-collapse').collapse('show');

			//check for empty panels
			$('.panel-version').each(function() {
				if ($(this).find('.searchhit', '.filterhit').length < 1) {
					$(this).find(' > .panel-collapse').collapse('hide');
				}
			});
		},

		markRead: function(element) {
			var $button = $(element).closest('a');
			$button.toggleClass('t3js-upgradeDocs-unmarkRead t3js-upgradeDocs-markRead');
			$button.find('i').toggleClass('fa-check fa-ban');
			$button.closest('.panel').appendTo('.panel-body-read');
			$.ajax({
				method: 'POST',
				url: location.href + '&install[controller]=ajax',
				data: {
					'install': {
						'ignoreFile': $button.data('filepath'),
						'token': $(this.selectorMarkReadToken).text(),
						'action': 'upgradeDocsMarkRead'
					}
				}
			});
		},

		unmarkRead: function(element) {
			var $button = $(element).closest('a');
			var version = $button.closest('.panel').data('item-version');
			$button.toggleClass('t3js-upgradeDocs-markRead t3js-upgradeDocs-unmarkRead');
			$button.find('i').toggleClass('fa-check fa-ban');
			$button.closest('.panel').appendTo('*[data-group-version="' + version + '"] .panel-body');
			$.ajax({
				method: 'POST',
				url: location.href + '&install[controller]=ajax',
				data: {
					'install': {
						'ignoreFile': $button.data('filepath'),
						'token': $(this.selectorUnmarkReadToken).text(),
						action: 'upgradeDocsUnmarkRead'
					}
				}
			});
		},

		trimExplodeAndUnique: function(delimiter, string) {
			var result = [];
			var items = string.split(delimiter);
			for (var i = 0; i < items.length; i++) {
				var item = items[i].trim();
				if (item.length > 0) {
					if ($.inArray(item, result) === -1) {
						result.push(item);
					}
				}
			}
			return result;
		}
	};
});
