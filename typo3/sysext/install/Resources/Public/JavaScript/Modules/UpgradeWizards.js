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
 * Module: TYPO3/CMS/Install/UpgradeWizards
 */
define([
	'jquery',
	'TYPO3/CMS/Install/FlashMessage',
	'TYPO3/CMS/Install/ProgressBar',
	'TYPO3/CMS/Install/InfoBox',
	'TYPO3/CMS/Install/Severity'
],
function($, FlashMessage, ProgressBar, InfoBox, Severity) {
	'use strict';

	return {
		selectorGridderOpener: '.t3js-upgradeWizards-open',
		selectorMarkUndoneToken: '#t3js-upgradeWizards-markUndone-token',
		selectorOutputWizardsContainer: '.t3js-upgradeWizards-wizards-output',
		selectorOutputDoneContainer: '.t3js-upgradeWizards-done-output',
		selectorWizardsBlockingAddsTemplate: '.t3js-upgradeWizards-blocking-adds-template',
		selectorWizardsBlockingAddsRows: '.t3js-upgradeWizards-blocking-adds-rows',
		selectorWizardsBlockingAddsExecute: '.t3js-upgradeWizards-blocking-adds-execute',
		selectorWizardsBlockingCharsetTemplate: '.t3js-upgradeWizards-blocking-charset-template',
		selectorWizardsBlockingCharsetFix: '.t3js-upgradeWizards-blocking-charset-fix',
		selectorWizardsDoneBodyTemplate: '.t3js-upgradeWizards-done-body-template',
		selectorWizardsDoneRows: '.t3js-upgradeWizards-done-rows',
		selectorWizardsDoneRowTemplate: '.t3js-upgradeWizards-done-row-template table tbody',
		selectorWizardsDoneRowMarkUndone: '.t3js-upgradeWizards-done-markUndone',
		selectorWizardsDoneRowTitle: '.t3js-upgradeWizards-done-title',
		selectorWizardsListTemplate: '.gridder-show .t3js-upgradeWizards-list-template',
		selectorWizardsListRows: '.t3js-upgradeWizards-list-rows',
		selectorWizardsListRowTemplate: '.gridder-show .t3js-upgradeWizards-list-row-template',
		selectorWizardsListRowTitle: '.t3js-upgradeWizards-list-row-title',
		selectorWizardsListRowExplanation: '.t3js-upgradeWizards-list-row-explanation',
		selectorWizardsListRowExecute: '.t3js-upgradeWizards-list-row-execute',
		selectorWizardsInputToken: '#t3js-upgradeWizards-input-token',
		selectorWizardsInputTemplate: '.gridder-show .t3js-upgradeWizards-input',
		selectorWizardsInputTitle: '.t3js-upgradeWizards-input-title',
		selectorWizardsInputHtml: '.t3js-upgradeWizards-input-html',
		selectorWizardsInputPerform: '.t3js-upgradeWizards-input-perform',
		selectorWizardsExecuteToken: '#t3js-upgradeWizards-execute-token',

		loadingMessage: ProgressBar.render(Severity.loading, 'Loading...', ''),

		initialize: function() {
			var self = this;

			// Load main content on first open
			$(document).on('click', this.selectorGridderOpener, function(event) {
				var $element = $(event.target).closest(self.selectorGridderOpener);
				if (!$element.data('upgradeWizardInitialized')) {
					$element.data('upgradeWizardInitialized', true);
					self.silentUpgrades();
					self.doneUpgrades();
				}
			});

			// Mark a done wizard undone
			$(document).on('click', this.selectorWizardsDoneRowMarkUndone, function(event) {
				var identifier = $(event.target).data('identifier');
				self.markUndone(identifier);
			});

			// Execute "fix default mysql connection db charset" blocking wizard
			$(document).on('click', this.selectorWizardsBlockingCharsetFix, function(event) {
				self.blockingUpgradesDatabaseCharsetFix();
			});

			// Execute "add required fields + tables" blocking wizard
			$(document).on('click', this.selectorWizardsBlockingAddsExecute, function(event) {
				self.blockingUpgradesDatabaseAddsExecute();
			});

			// Get user input of a single upgrade wizard
			$(document).on('click', this.selectorWizardsListRowExecute, function(event) {
				var identifier = $(event.target).data('identifier');
				self.wizardInput(identifier);
			});

			// Execute one upgrade wizard
			$(document).on('click', this.selectorWizardsInputPerform, function(event) {
				var identifier = $(event.target).data('identifier');
				self.wizardExecute(identifier);
			});
		},

		silentUpgrades: function() {
			var self = this;
			var url = location.href + '&install[controller]=ajax&install[action]=upgradeWizardsSilentUpgrades';
			if (location.hash) {
				url = url.replace(location.hash, "");
			}
			var $outputContainer = $(this.selectorOutputWizardsContainer);
			$outputContainer.empty().html(self.loadingMessage);
			$.ajax({
				url: url,
				cache: false,
				success: function(data) {
					$outputContainer.empty();
					if (data.success === true && Array.isArray(data.status)) {
						if (data.status.length > 0) {
							data.status.forEach((function (element) {
								var message = InfoBox.render(element.severity, element.title, element.message);
								$outputContainer.append(message);
							}));
						}
						self.blockingUpgradesDatabaseCharsetTest();
					}
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					$outputContainer.empty().html(message);
				}
			});
		},

		blockingUpgradesDatabaseCharsetTest: function() {
			var self = this;
			var url = location.href + '&install[controller]=ajax&install[action]=upgradeWizardsBlockingDatabaseCharsetTest';
			if (location.hash) {
				url = url.replace(location.hash, "");
			}
			var $outputContainer = $(this.selectorOutputWizardsContainer);
			var charsetTemplate = $(this.selectorWizardsBlockingCharsetTemplate).html();
			$outputContainer.append().html(self.loadingMessage);
			$.ajax({
				url: url,
				cache: false,
				success: function(data) {
					self.removeLoadingMessage($outputContainer);
					if (data.success === true) {
						if (data.needsUpdate === true) {
							$outputContainer.append($(charsetTemplate).clone());
						} else {
							self.blockingUpgradesDatabaseAdds();
						}
					}
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					self.removeLoadingMessage($outputContainer);
					$outputContainer.append(message);
				}
			});
		},

		blockingUpgradesDatabaseCharsetFix: function() {
			var self = this;
			var url = location.href + '&install[controller]=ajax&install[action]=upgradeWizardsBlockingDatabaseCharsetFix';
			if (location.hash) {
				url = url.replace(location.hash, "");
			}
			var $outputContainer = $(this.selectorOutputWizardsContainer);
			$outputContainer.append().html(self.loadingMessage);
			$.ajax({
				url: url,
				cache: false,
				success: function(data) {
					self.removeLoadingMessage($outputContainer);
					if (data.success === true) {
						if (Array.isArray(data.status) && data.status.length > 0) {
							data.status.forEach(function(element) {
								var message = InfoBox.render(element.severity, element.title, element.message);
								$outputContainer.append(message);
							});
						}
					}
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					self.removeLoadingMessage($outputContainer);
					$outputContainer.append(message);
				}
			});
		},

		blockingUpgradesDatabaseAdds: function() {
			var self = this;
			var url = location.href + '&install[controller]=ajax&install[action]=upgradeWizardsBlockingDatabaseAdds';
			if (location.hash) {
				url = url.replace(location.hash, "");
			}
			var $outputContainer = $(this.selectorOutputWizardsContainer);
			var breakingAddsTemplate = $(this.selectorWizardsBlockingAddsTemplate).html();
			$outputContainer.append().html(self.loadingMessage);
			$.ajax({
				url: url,
				cache: false,
				success: function(data) {
					self.removeLoadingMessage($outputContainer);
					if (data.success === true) {
						if (data.needsUpdate === true) {
							var adds = $(breakingAddsTemplate).clone();
							if (typeof(data.adds.tables) === 'object') {
								data.adds.tables.forEach(function(element) {
									adds.find(self.selectorWizardsBlockingAddsRows).append('Table: ' + element.table + '<br>');
								});
							}
							if (typeof(data.adds.columns) === 'object') {
								data.adds.columns.forEach(function(element) {
									adds.find(self.selectorWizardsBlockingAddsRows).append('Table: ' + element.table + ', Field: ' + element.field + '<br>');
								});
							}
							if (typeof(data.adds.indexes) === 'object') {
								data.adds.indexes.forEach(function(element) {
									adds.find(self.selectorWizardsBlockingAddsRows).append('Table: ' + element.table + ', Index: ' + element.index + '<br>');
								});
							}
							$outputContainer.append(adds);
						} else {
							self.wizardsList();
						}
					}
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					self.removeLoadingMessage($outputContainer);
					$outputContainer.append(message);
				}
			});
		},

		blockingUpgradesDatabaseAddsExecute: function() {
			var self = this;
			var url = location.href + '&install[controller]=ajax&install[action]=upgradeWizardsBlockingDatabaseExecute';
			if (location.hash) {
				url = url.replace(location.hash, "");
			}
			var $outputContainer = $(this.selectorOutputWizardsContainer);
			$outputContainer.empty().html(self.loadingMessage);
			$.ajax({
				url: url,
				cache: false,
				success: function(data) {
					self.removeLoadingMessage($outputContainer);
					if (data.success === true) {
						if (Array.isArray(data.status) && data.status.length > 0) {
							data.status.forEach(function(element) {
								var message = InfoBox.render(element.severity, element.title, element.message);
								$outputContainer.append(message);
							});
							self.wizardsList();
						}
					}
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					self.removeLoadingMessage($outputContainer);
					$outputContainer.append(message);
				}
			});
		},

		wizardsList: function() {
			var self = this;
			var url = location.href + '&install[controller]=ajax&install[action]=upgradeWizardsList';
			if (location.hash) {
				url = url.replace(location.hash, "");
			}
			var listTemplate = $(this.selectorWizardsListTemplate);
			var wizardTemplate = $(this.selectorWizardsListRowTemplate);
			var $outputContainer = $(this.selectorOutputWizardsContainer);
			$outputContainer.append(self.loadingMessage);
			$.ajax({
				url: url,
				cache: false,
				success: function(data) {
					self.removeLoadingMessage($outputContainer);
					var list = $(listTemplate).clone();
					list.removeClass('t3js-upgradeWizards-list-template');
					if (data.success === true) {
						var numberOfWizardsTodo = 0;
						var numberOfWizards = 0;
						if (Array.isArray(data.wizards) && data.wizards.length > 0) {
							numberOfWizards = data.wizards.length;
							data.wizards.forEach(function(element) {
								if (element.shouldRenderWizard === true) {
									var aRow = $(wizardTemplate).clone();
									numberOfWizardsTodo = numberOfWizardsTodo +1;
									aRow.removeClass('t3js-upgradeWizards-list-row-template');
									aRow.find(self.selectorWizardsListRowTitle).empty().text(element.title);
									aRow.find(self.selectorWizardsListRowExplanation).empty().html(element.explanation);
									aRow.find(self.selectorWizardsListRowExecute).data('identifier', element.identifier);
									list.find(self.selectorWizardsListRows).append(aRow);
								}
							});
							list.find(self.selectorWizardsListRows + ' hr:last').remove();
						}
						var percent = 100;
						if (numberOfWizardsTodo > 0) {
							percent = ((numberOfWizards - numberOfWizardsTodo) / data.wizards.length) * 100;
						}
						list.find('.progress-bar')
							.css('width', percent + '%')
							.attr('aria-valuenow', percent)
							.find('span')
							.text(parseInt(percent) + '%');
						$outputContainer.append(list);
					}
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					self.removeLoadingMessage($outputContainer);
					$outputContainer.append(message);
				}
			});
		},

		wizardInput: function(identifier) {
			var self = this;
			var url = location.href + '&install[controller]=ajax';
			var postData = {
				'install': {
					'action': 'upgradeWizardsInput',
					'token': $(this.selectorWizardsInputToken).text(),
					'identifier': identifier
				}
			};
			var $outputContainer = $(this.selectorOutputWizardsContainer);
			var inputTemplate = $(this.selectorWizardsInputTemplate);
			$outputContainer.empty().html(this.loadingMessage);
			$.ajax({
				method: 'POST',
				data: postData,
				url: url,
				cache: false,
				success: function(data) {
					$outputContainer.empty();
					var input = $(inputTemplate).clone();
					input.removeClass('t3js-upgradeWizards-input');
					if (data.success === true) {
						if (Array.isArray(data.status)) {
							data.status.forEach(function(element) {
								var message = FlashMessage.render(element.severity, element.title, element.message);
								$outputContainer.append(message);
							});
						}
						if (data.userInput.wizardHtml.length > 0) {
							input.find(self.selectorWizardsInputHtml).html(data.userInput.wizardHtml);
						}
						input.find(self.selectorWizardsInputTitle).text(data.userInput.title);
						input.find(self.selectorWizardsInputPerform).data('identifier', data.userInput.identifier);
					}
					$outputContainer.append(input);
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					$outputContainer.empty().html(message);
				}
			});
		},

		wizardExecute: function(identifier) {
			var self = this;
			var url = location.href + '&install[controller]=ajax';
			var postData = {
				'install[action]': 'upgradeWizardsExecute',
				'install[token]': $(this.selectorWizardsExecuteToken).text(),
				'install[identifier]': identifier
			};
			$($('.gridder-show ' + this.selectorOutputWizardsContainer + ' form').serializeArray()).each(function() {
				postData[this.name] = this.value;
			});
			var $outputContainer = $(this.selectorOutputWizardsContainer);
			var $outputDoneContainer = $(this.selectorOutputDoneContainer);
			$outputContainer.empty().html(this.loadingMessage);
			$.ajax({
				method: 'POST',
				data: postData,
				url: url,
				cache: false,
				success: function(data) {
					$outputContainer.empty();
					if (data.success === true) {
						if (Array.isArray(data.status)) {
							data.status.forEach(function(element) {
								var message = InfoBox.render(element.severity, element.title, element.message);
								$outputContainer.append(message);
							});
						}
						self.wizardsList();
						$outputDoneContainer.empty();
						self.doneUpgrades();
					}
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					$outputContainer.empty().html(message);
				}
			});
		},

		doneUpgrades: function() {
			var self = this;
			var url = location.href + '&install[controller]=ajax&install[action]=upgradeWizardsDoneUpgrades';
			if (location.hash) {
				url = url.replace(location.hash, "");
			}
			var $outputContainer = $(this.selectorOutputDoneContainer);
			var rowTemplate = $(this.selectorWizardsDoneRowTemplate).html();
			var bodyTemplate = $(this.selectorWizardsDoneBodyTemplate).html();
			$outputContainer.append(this.loadingMessage);
			$.ajax({
				url: url,
				cache: false,
				success: function(data) {
					self.removeLoadingMessage($outputContainer);
					if (data.success === true) {
						if (Array.isArray(data.status) && data.status.length > 0) {
							data.status.forEach(function(element) {
								var message = InfoBox.render(element.severity, element.title, element.message);
								$outputContainer.append(message);
							});
						}
						var body = $(bodyTemplate).clone();
						var hasBodyContent = false;
						var $wizardsDoneContainer = body.find(self.selectorWizardsDoneRows);
						if (Array.isArray(data.wizardsDone) && data.wizardsDone.length > 0) {
							data.wizardsDone.forEach(function(element) {
								hasBodyContent = true;
								var aRow = $(rowTemplate).clone();
								aRow.find(self.selectorWizardsDoneRowMarkUndone).data('identifier', element.identifier);
								aRow.find(self.selectorWizardsDoneRowTitle).text(element.title);
								$wizardsDoneContainer.append(aRow);
							});
						}
						if (Array.isArray(data.rowUpdatersDone) && data.rowUpdatersDone.length > 0) {
							data.rowUpdatersDone.forEach(function(element) {
								hasBodyContent = true;
								var aRow = $(rowTemplate).clone();
								aRow.find(self.selectorWizardsDoneRowMarkUndone).data('identifier', element.identifier);
								aRow.find(self.selectorWizardsDoneRowTitle).html(element.title);
								$wizardsDoneContainer.append(aRow);
							});
						}
						if (hasBodyContent === true) {
							$outputContainer.append(body);
						}
					}
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					self.removeLoadingMessage($outputContainer);
					$outputContainer.append(message);
				}
			});
		},

		markUndone: function(identifier) {
			var self = this;
			var url = location.href + '&install[controller]=ajax';
			var postData = {
				'install': {
					'action': 'upgradeWizardsMarkUndone',
					'token': $(this.selectorMarkUndoneToken).text(),
					'identifier': identifier
				}
			};
			var $outputContainer = $(this.selectorOutputDoneContainer);
			$outputContainer.empty().html(this.loadingMessage);
			$.ajax({
				method: 'POST',
				data: postData,
				url: url,
				cache: false,
				success: function(data) {
					$outputContainer.empty();
					if (data.success === true && Array.isArray(data.status)) {
						data.status.forEach(function(element) {
							var message = InfoBox.render(element.severity, element.title, element.message);
							$outputContainer.append(message);
							self.doneUpgrades();
							self.blockingUpgradesDatabaseCharsetTest();
						});
					}
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					$outputContainer.empty().html(message);
				}
			});
		},

		removeLoadingMessage: function($container) {
			$container.find('.alert-loading').remove();
		}
	};
});
