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
 * Module: TYPO3/CMS/Install/ClearTable
 */
define([
	'jquery',
	'TYPO3/CMS/Install/Router',
	'TYPO3/CMS/Install/FlashMessage',
	'TYPO3/CMS/Install/ProgressBar',
	'TYPO3/CMS/Install/InfoBox',
	'TYPO3/CMS/Install/Severity'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity) {
	'use strict';

	return {
		selectorGridderOpener: 't3js-clearTables-open',
		selectorClearToken: '#t3js-clearTables-clear-token',
		selectorClearTrigger: '.t3js-clearTables-clear',
		selectorStatsTrigger: '.t3js-clearTables-stats',
		selectorOutputContainer: '.t3js-clearTables-output',
		selectorStatContainer: 't3js-clearTables-stat-container',
		selectorStatTemplate: '.t3js-clearTables-stat-template',
		selectorStatDescription: '.t3js-clearTables-stat-description',
		selectorStatRows: '.t3js-clearTables-stat-rows',
		selectorStatName: '.t3js-clearTables-stat-name',
		selectorStatLastRuler: '.t3js-clearTables-stat-lastRuler',

		initialize: function() {
			var self = this;

			// Load stats on first open
			$(document).on('cardlayout:card-opened', function(event, $card) {
				if ($card.hasClass(self.selectorGridderOpener) && !$card.data('isInitialized')) {
					$card.data('isInitialized', true);
					self.getStats();
				}
			});

			$(document).on('click', this.selectorStatsTrigger, function(e) {
				e.preventDefault();
				$(self.selectorOutputContainer).empty();
				self.getStats();
			});

			$(document).on('click', this.selectorClearTrigger, function(e) {
				var table = $(e.target).closest(self.selectorClearTrigger).data('table');
				e.preventDefault();
				self.clear(table);
			});
		},

		getStats: function() {
			var self = this;
			var $outputContainer = $(this.selectorOutputContainer);
			var $statContainer = $('.' + this.selectorStatContainer);
			$statContainer.empty();
			var $statTemplate = $(this.selectorStatTemplate);
			var message = ProgressBar.render(Severity.loading, 'Loading...', '');
			$outputContainer.append(message);
			$.ajax({
				url: Router.getUrl('clearTablesStats'),
				cache: false,
				success: function (data) {
					if (data.success === true) {
						$outputContainer.find('.alert-loading').remove();
						if (Array.isArray(data.stats) && data.stats.length > 0) {
							data.stats.forEach(function(element) {
								if (element.rowCount > 0) {
									var $aStat = $statTemplate.clone();
									$aStat.find(self.selectorStatDescription).text(element.description);
									$aStat.find(self.selectorStatName).text(element.name);
									$aStat.find(self.selectorStatRows).text(element.rowCount);
									$aStat.find(self.selectorClearTrigger).data('table', element.name);
									$statContainer.append($aStat);
								}
							});
							$statContainer.find(self.selectorStatLastRuler + ':last').remove();
						}
					} else {
						var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
						$outputContainer.append(message);
					}
				},
				error: function(xhr) {
					Router.handleAjaxError(xhr);
				}
			});
		},

		clear: function(table) {
			var $outputContainer = $(this.selectorOutputContainer);
			var message = ProgressBar.render(Severity.loading, 'Loading...', '');
			$outputContainer.empty().append(message);
			$.ajax({
				url: Router.getUrl(),
				method: 'POST',
				context: this,
				data: {
					'install': {
						'action': 'clearTablesClear',
						'token': $(this.selectorClearToken).text(),
						'table': table
					}
				},
				cache: false,
				success: function(data) {
					$outputContainer.empty();
					if (data.success === true && Array.isArray(data.status)) {
						data.status.forEach(function(element) {
							var message = InfoBox.render(element.severity, element.title, element.message);
							$outputContainer.append(message);
						});
					} else {
						var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
						$outputContainer.append(message);
					}
					this.getStats();
				},
				error: function(xhr) {
					Router.handleAjaxError(xhr);
				}
			});
		}
	};
});
