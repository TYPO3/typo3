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
 * Module: TYPO3/CMS/Install/TcaIntegrityChecker
 */
define([
	'jquery',
	'TYPO3/CMS/Install/FlashMessage',
	'TYPO3/CMS/Install/ProgressBar',
	'TYPO3/CMS/Install/InfoBox',
	'TYPO3/CMS/Install/Severity'
], function($, FlashMessage, ProgressBar, InfoBox, Severity) {
	'use strict';

	return {
		selectorCheckTrigger: '.t3js-tcaMigrationsCheck-check',
		selectorOutputContainer: '.t3js-tcaMigrationsCheck-output',

		initialize: function() {
			var self = this;
			$(document).on('click', this.selectorCheckTrigger, function(e) {
				e.preventDefault();
				self.check();
			});
		},

		check: function() {
			var self = this;
			var url = location.href + '&install[controller]=ajax&install[action]=tcaMigrationsCheck';
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
					if (data.success === true && Array.isArray(data.status)) {
						if (data.status.length > 0) {
							var message = InfoBox.render(
								Severity.warning,
								'TCA migrations need to be applied',
								'Check the following list and apply needed changes.'
							);
							$outputContainer.empty();
							$outputContainer.append(message);
							data.status.forEach(function(element) {
								var message = InfoBox.render(element.severity, element.title, element.message);
								$outputContainer.append(message);
							});
						} else {
							var message = InfoBox.render(Severity.ok, 'No TCA migrations need to be applied', 'Your TCA looks good.');
							$outputContainer.empty().html(message);
						}
					}
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', 'Use "Check for broken extensions"');
					$outputContainer.empty().html(message);
				}
			});
		}
	};
});
