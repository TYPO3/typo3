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
 * Module: TYPO3/CMS/Install/FolderStructure
 */
define(['jquery',
	'TYPO3/CMS/Install/FlashMessage',
	'TYPO3/CMS/Install/ProgressBar',
	'TYPO3/CMS/Install/InfoBox',
	'TYPO3/CMS/Install/Severity',
	'bootstrap'
], function($, FlashMessage, ProgressBar, InfoBox, Severity) {
	'use strict';

	return {
		selectorGridderBadge: '.t3js-folderStructure-badge',
		selectorFixTrigger: '.t3js-folderStructure-errors-fix',
		selectorOutputContainer: '.t3js-folderStructure-output',
		selectorErrorContainer: '.t3js-folderStructure-errors',
		selectorErrorList: '.t3js-folderStructure-errors-list',
		selectorErrorFixTrigger: '.t3js-folderStructure-errors-fix',
		selectorOkContainer: '.t3js-folderStructure-ok',
		selectorOkList: '.t3js-folderStructure-ok-list',

		initialize: function() {
			var self = this;

			// Get status on initialize to have the badge and content ready
			self.getStatus();

			$(document).on('click', this.selectorErrorFixTrigger, function(e) {
				e.preventDefault();
				self.fix();
			});
		},

		getStatus: function() {
			var self = this;
			var url = location.href + '&install[controller]=ajax&install[action]=folderStructureGetStatus';
			if (location.hash) {
				url = url.replace(location.hash, "");
			}
			var $outputContainer = $(this.selectorOutputContainer);
			var $errorContainer = $(this.selectorErrorContainer);
			var $errorBadge = $(this.selectorGridderBadge);
			$errorBadge.text('').hide();
			var $errorList = $(this.selectorErrorList);
			var $okContainer = $(this.selectorOkContainer);
			var $okList = $(this.selectorOkList);
			var message = ProgressBar.render(Severity.loading, 'Loading...', '');
			$outputContainer.append(message);
			$.ajax({
				url: url,
				cache: false,
				success: function(data) {
					self.removeLoadingMessage($outputContainer);
					if (data.success === true && Array.isArray(data.errorStatus)) {
						var errorCount = 0;
						if (data.errorStatus.length > 0) {
							$errorContainer.show();
							$errorList.empty();
							data.errorStatus.forEach((function(element) {
								errorCount += 1;
								$errorBadge.text(errorCount).show();
								var message = InfoBox.render(element.severity, element.title, element.message);
								$errorList.append(message);
							}));
						} else {
							$errorContainer.hide();
						}
					}
					if (data.success === true && Array.isArray(data.okStatus)) {
						if (data.okStatus.length > 0) {
							$okContainer.show();
							$okList.empty();
							data.okStatus.forEach((function(element) {
								var message = InfoBox.render(element.severity, element.title, element.message);
								$okList.append(message);
							}));
						} else {
							$okList.hide();
						}
					}
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					$outputContainer.append(message);
				}
			});
		},

		fix: function() {
			var self = this;
			var url = location.href + '&install[controller]=ajax&install[action]=folderStructureFix';
			if (location.hash) {
				url = url.replace(location.hash, "");
			}
			var $outputContainer = $(this.selectorOutputContainer);
			var message = ProgressBar.render(Severity.loading, 'Loading...', '');
			$outputContainer.empty().html(message);
			$.ajax({
				url: url,
				cache: false,
				success: function(data) {
					self.removeLoadingMessage($outputContainer);
					if (data.success === true && Array.isArray(data.fixedStatus)) {
						if (data.fixedStatus.length > 0) {
							data.fixedStatus.forEach(function(element) {
								var message = InfoBox.render(element.severity, element.title, element.message);
								$outputContainer.append(message);
							});
						} else {
							var message = InfoBox.render(Severity.warning, 'Nothing fixed', '');
							$outputContainer.append(message);
						}
						self.getStatus();
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
