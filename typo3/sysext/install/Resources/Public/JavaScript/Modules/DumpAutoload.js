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
 * Module: TYPO3/CMS/Install/DumpAutoload
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
		selectorDumpTrigger: '.t3js-dumpAutoload-dump',
		selectorOutputContainer: '.t3js-dumpAutoload-output',

		initialize: function() {
			var self = this;
			$(document).on('click', this.selectorDumpTrigger, function(e) {
				e.preventDefault();
				self.dump();
			});
		},

		dump: function() {
			var $outputContainer = $(this.selectorOutputContainer);
			var message = ProgressBar.render(Severity.loading, 'Loading...', '');
			$outputContainer.empty().html(message);
			$.ajax({
				url: Router.getUrl('dumpAutoload'),
				cache: false,
				success: function(data) {
					if (data.success === true && Array.isArray(data.status)) {
						if (data.status.length > 0) {
							$outputContainer.empty();
							data.status.forEach((function(element) {
								var message = InfoBox.render(element.severity, element.title, element.message);
								$outputContainer.append(message);
							}));
						}
					} else {
						var message = InfoBox.render(Severity.error, 'Something went wrong', '');
						$outputContainer.empty().html(message);
					}
				},
				error: function(xhr) {
					Router.handleAjaxError(xhr);
				}
			});
		}
	};
});
